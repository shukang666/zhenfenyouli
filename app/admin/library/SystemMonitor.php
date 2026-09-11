<?php

namespace app\admin\library;

/**
 * 服务器运行状态采集（PC 后台「安全加固 - 服务器监控」用）
 *
 * 全部走 PHP 内置能力，不依赖任何扩展、不装任何软件、不需要 root：
 *   CPU 负载   ：sys_getloadavg()（内核 syscall，不受 open_basedir 影响）
 *   内存       ：/proc/meminfo，读不到时回落到 shell `free -k`，Windows 开发机用 wmic
 *   硬盘容量   ：disk_total_space() / disk_free_space()（statvfs syscall，同样不受 open_basedir 影响）
 *   运行时长   ：/proc/uptime
 *   系统信息   ：php_uname() / PHP 常量 / php_sapi_name()
 *
 * 读取 /proc 属于文件操作，会被 PHP 的 open_basedir 拦截（宝塔默认开着「防跨站攻击」时
 * 只放行站点目录，/proc 不在其中），此时对应指标标记为「不可用」，页面照常渲染不报错。
 * 想让它显示出来，可在宝塔站点设置里关掉「防跨站攻击(open_basedir)」，或把 /proc 加进
 * open_basedir 白名单。
 */
class SystemMonitor
{
    /** 使用率分级阈值（百分比） */
    const LEVEL_WARNING = 70;
    const LEVEL_DANGER  = 90;

    /**
     * 采集一次全量快照，直接给模板 / JSON 接口用
     */
    public static function snapshot()
    {
        $uptime = self::uptime();

        return [
            'time'          => date('Y-m-d H:i:s'),
            'uptime'        => $uptime,
            'uptime_text'   => self::uptimeText($uptime),
            'cpu'           => self::cpu(),
            'memory'        => self::memory(),
            'disk'          => self::disk(),
            'system'        => self::systemInfo(),
        ];
    }

    /* ==================== CPU ==================== */

    /**
     * CPU：1/5/15 分钟平均负载 + 按核心数换算的使用率
     * 单核跑满时负载为 1.0，所以「使用率 ≈ 1 分钟负载 ÷ 核心数」
     */
    public static function cpu()
    {
        $cores = self::cpuCores();

        $load = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
        if (!is_array($load) || count($load) < 3) {
            return [
                'available' => false,
                'cores'     => $cores,
                'load1'     => null,
                'load5'     => null,
                'load15'    => null,
                'percent'    => null,
                'text'       => '不可用',
                'level'      => 'unknown',
                'level_text' => '不可用',
            ];
        }

        $percent = $cores > 0 ? round((float)$load[0] / $cores * 100, 1) : null;
        if ($percent !== null && $percent > 100) {
            $percent = 100.0;
        }

        return [
            'available' => true,
            'cores'     => $cores,
            'load1'     => round((float)$load[0], 2),
            'load5'     => round((float)$load[1], 2),
            'load15'    => round((float)$load[2], 2),
            'percent'    => $percent,
            'text'       => $percent === null ? '不可用' : $percent . '%',
            'level'      => self::level($percent),
            'level_text' => self::levelText($percent),
        ];
    }

    /**
     * 逻辑核心数：Linux 数 /proc/cpuinfo 的 processor 行；Windows 用环境变量
     */
    private static function cpuCores()
    {
        static $cores = null;
        if ($cores !== null) {
            return $cores;
        }

        $cores = 0;
        $raw   = @file_get_contents('/proc/cpuinfo');
        if (is_string($raw) && $raw !== '') {
            $cores = substr_count(strtolower($raw), 'processor');
        }
        if ($cores <= 0) {
            $cores = (int)getenv('NUMBER_OF_PROCESSORS');
        }

        return $cores > 0 ? $cores : 1;
    }

    /* ==================== 内存 ==================== */

    /**
     * 内存占用率
     * 返回的 total / used / free 单位都是字节
     */
    public static function memory()
    {
        list($total, $available) = self::rawMemory();

        if ($total <= 0 || $available <= 0) {
            return self::unavailable('memory');
        }

        $used    = $total - $available;
        $percent = round($used / $total * 100, 1);

        return [
            'available'  => true,
            'total'      => $total,
            'used'       => $used,
            'free'       => $available,
            'total_text' => self::fmtBytes($total),
            'used_text'  => self::fmtBytes($used),
            'free_text'  => self::fmtBytes($available),
            'percent'    => $percent,
            'text'       => $percent . '%',
            'level'      => self::level($percent),
            'level_text' => self::levelText($percent),
        ];
    }

    /**
     * 读原始内存数据（字节）：[总量, 可用量]
     * 优先级：/proc/meminfo →（Windows）wmic /（Linux）free -k，全失败返回 [0, 0]
     */
    private static function rawMemory()
    {
        // 1) Linux /proc/meminfo（open_basedir 未放行 /proc 时读不到，落到下面的兜底）
        $raw = @file_get_contents('/proc/meminfo');
        if (is_string($raw) && $raw !== '') {
            list($total, $free) = self::parseMeminfo($raw);
            if ($total > 0 && $free > 0) {
                return [$total, $free];
            }
        }

        // 2) Windows 开发机：wmic（新系统已移除 wmic，拿不到就按不可用处理）
        if (PHP_OS_FAMILY === 'Windows') {
            $out = self::runCommand('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /value 2>nul');
            if ($out !== ''
                && preg_match('/TotalVisibleMemorySize=(\d+)/', $out, $t)
                && preg_match('/FreePhysicalMemory=(\d+)/', $out, $f)
            ) {
                return [(int)$t[1] * 1024, (int)$f[1] * 1024];
            }

            return [0, 0];
        }

        // 3) Linux free 命令（shell_exec 被宝塔默认禁用时会拿不到）
        list($total, $free) = self::parseFreeOutput(self::runCommand('free -k 2>/dev/null'));

        return $total > 0 ? [$total, $free > 0 ? $free : 0] : [0, 0];
    }

    /**
     * 解析 /proc/meminfo，返回 [总量, 可用量]（字节）
     */
    private static function parseMeminfo($raw)
    {
        $total = self::meminfoValue($raw, 'MemTotal');
        $free  = self::meminfoValue($raw, 'MemAvailable');
        if ($free <= 0) {
            // 老内核没有 MemAvailable，用「空闲 + Buffers + Cached」近似
            $free = self::meminfoValue($raw, 'MemFree')
                + self::meminfoValue($raw, 'Buffers')
                + self::meminfoValue($raw, 'Cached');
        }

        return [$total, $free];
    }

    /**
     * 解析 `free -k` 的 Mem 行，返回 [总量, 可用量]（字节）
     * 新版 procps 表头带 available 列（末尾即可用内存）；
     * 老版没有这一列，用 free + buffers + cached 近似
     */
    private static function parseFreeOutput($out)
    {
        if (!is_string($out) || $out === '' || !preg_match('/^Mem:\s+(.+)$/m', $out, $m)) {
            return [0, 0];
        }

        $cols  = preg_split('/\s+/', trim($m[1]));
        $total = (int)$cols[0] * 1024;
        $free  = 0;

        if (preg_match('/\bavailable\b/i', $out) && count($cols) >= 6) {
            $free = (int)$cols[5] * 1024;
        } else {
            $free = (int)$cols[2] * 1024;                                   // free
            $free += isset($cols[4]) ? (int)$cols[4] * 1024 : 0;            // buffers
            $free += isset($cols[5]) ? (int)$cols[5] * 1024 : 0;            // cached
        }

        return [$total, $free];
    }

    /**
     * 从 /proc/meminfo 文本里取一项（值以 kB 计），返回字节数
     */
    private static function meminfoValue($raw, $key)
    {
        if (preg_match('/^' . preg_quote($key, '/') . ':\s*(\d+)\s*kB/mi', $raw, $m)) {
            return (int)$m[1] * 1024;
        }
        return 0;
    }

    /* ==================== 硬盘 ==================== */

    /**
     * 站点所在磁盘的容量 / 占用
     */
    public static function disk()
    {
        $path  = self::rootPath();
        $total = @disk_total_space($path);
        $free  = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return array_merge(self::unavailable('disk'), ['path' => $path]);
        }

        $used    = $total - $free;
        $percent = round($used / $total * 100, 1);

        return [
            'available'  => true,
            'path'       => $path,
            'total'      => $total,
            'used'       => $used,
            'free'       => $free,
            'total_text' => self::fmtBytes($total),
            'used_text'  => self::fmtBytes($used),
            'free_text'  => self::fmtBytes($free),
            'percent'    => $percent,
            'text'       => $percent . '%',
            'level'      => self::level($percent),
            'level_text' => self::levelText($percent),
        ];
    }

    /* ==================== 运行时长 / 系统信息 ==================== */

    /**
     * 系统已运行秒数（取不到返回 null）
     */
    public static function uptime()
    {
        $raw = @file_get_contents('/proc/uptime');
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
        $sec = (float)strtok(trim($raw), ' ');
        return $sec > 0 ? (int)$sec : null;
    }

    /**
     * 运行时长文案
     */
    public static function uptimeText($seconds)
    {
        if ($seconds === null || $seconds <= 0) {
            return '不可用';
        }
        $days  = (int)floor($seconds / 86400);
        $hours = (int)floor(($seconds % 86400) / 3600);
        $mins  = (int)floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' 天';
        }
        $parts[] = $hours . ' 小时';
        $parts[] = $mins . ' 分';

        return implode(' ', $parts);
    }

    /**
     * 服务器软硬件信息
     */
    public static function systemInfo()
    {
        return [
            'os'        => php_uname('s') . ' ' . php_uname('r'),
            'host'      => php_uname('n'),
            'arch'      => php_uname('m'),
            'software'  => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '命令行',
            'php'       => PHP_VERSION,
            'sapi'      => php_sapi_name(),
            'root'      => self::rootPath(),
            'mem_limit' => (string)ini_get('memory_limit'),
            'mem_used'  => self::fmtBytes(memory_get_usage(true)),
            'max_exec'  => (string)ini_get('max_execution_time'),
        ];
    }

    /* ==================== 工具 ==================== */

    /**
     * 站点根目录（用于算磁盘容量）
     */
    private static function rootPath()
    {
        if (defined('CMF_ROOT')) {
            return rtrim(CMF_ROOT, '/\\');
        }
        return rtrim(dirname(__DIR__, 3), '/\\');
    }

    /**
     * 使用率分级：ok / warning / danger / unknown
     */
    private static function level($percent)
    {
        if ($percent === null) {
            return 'unknown';
        }
        if ($percent >= self::LEVEL_DANGER) {
            return 'danger';
        }
        if ($percent >= self::LEVEL_WARNING) {
            return 'warning';
        }
        return 'ok';
    }

    /**
     * 分级文案
     */
    private static function levelText($percent)
    {
        switch (self::level($percent)) {
            case 'danger':
                return '危急';
            case 'warning':
                return '偏高';
            case 'ok':
                return '正常';
            default:
                return '不可用';
        }
    }

    /**
     * 指标不可用时的统一结构
     */
    private static function unavailable($type)
    {
        $base = [
            'available'  => false,
            'percent'    => null,
            'text'       => '不可用',
            'level'      => 'unknown',
            'level_text' => '不可用',
        ];

        if ($type === 'memory') {
            return $base + ['total_text' => '-', 'used_text' => '-', 'free_text' => '-'];
        }

        return $base + ['total_text' => '-', 'used_text' => '-', 'free_text' => '-', 'path' => ''];
    }

    /**
     * 字节数格式化
     */
    public static function fmtBytes($bytes, $precision = 1)
    {
        $bytes = (float)$bytes;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i     = (int)floor(log($bytes, 1024));
        $i     = max(0, min(count($units) - 1, $i));

        return round($bytes / pow(1024, $i), $precision) . ' ' . $units[$i];
    }

    /**
     * 执行一条 shell 命令（shell_exec 不可用时返回空串，绝不让页面出错）
     */
    private static function runCommand($cmd)
    {
        if (!function_exists('shell_exec')) {
            return '';
        }
        $disabled = (string)ini_get('disable_functions');
        if ($disabled !== '' && stripos($disabled, 'shell_exec') !== false) {
            return '';
        }

        $out = @shell_exec($cmd);
        return is_string($out) ? $out : '';
    }
}
