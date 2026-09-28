<?php
/**
 * 商城订单：按发货 Excel 批量填物流并改为待收货
 *
 * --file=path/to.xlsx   必填，相对项目根或绝对路径
 * --dry-run             只预览（默认）
 * --apply               事务写库
 * --express-map=申通线下:申通快递  可重复，覆盖默认映射
 */
declare(strict_types=1);

$projectRoot = dirname(__DIR__, 4);
chdir($projectRoot);

require $projectRoot . '/vendor/autoload.php';

$app = new \think\App();
$app->initialize();

use PhpOffice\PhpSpreadsheet\IOFactory;
use think\facade\Db;

$opts = getopt('', ['file:', 'dry-run', 'apply', 'express-map::']);
$file = $opts['file'] ?? '';
if ($file === '') {
    fwrite(STDERR, "用法: php bulk_ship_from_excel.php --file=public/xxx.xlsx [--dry-run|--apply]\n");
    exit(1);
}
if ($file[0] !== '/' && !preg_match('#^[A-Za-z]:#', $file)) {
    $file = $projectRoot . '/' . ltrim(str_replace('\\', '/', $file), '/');
}
if (!is_file($file)) {
    fwrite(STDERR, "文件不存在: {$file}\n");
    exit(1);
}

$apply = isset($opts['apply']);
$dryRun = !$apply;

$expressMap = [
    '申通线下' => '申通快递',
    '申通' => '申通快递',
];
if (!empty($opts['express-map'])) {
    $pairs = is_array($opts['express-map']) ? $opts['express-map'] : [$opts['express-map']];
    foreach ($pairs as $pair) {
        $parts = explode(':', $pair, 2);
        if (count($parts) === 2) {
            $expressMap[trim($parts[0])] = trim($parts[1]);
        }
    }
}

$headerMap = [];
$rows = [];
$sheet = IOFactory::load($file)->getActiveSheet();
$rowIndex = 0;
foreach ($sheet->getRowIterator() as $row) {
    $rowIndex++;
    $cells = [];
    foreach ($row->getCellIterator() as $cell) {
        $cells[] = trim((string)$cell->getValue());
    }
    if ($headerMap === []) {
        foreach ($cells as $i => $title) {
            if ($title !== '') {
                $headerMap[$title] = $i;
            }
        }
        continue;
    }
    $phone = cellByHeader($cells, $headerMap, ['手机/电话', '手机', '电话', '手机号']);
    if ($phone === '') {
        continue;
    }
    $rows[] = [
        'line' => $rowIndex,
        'phone' => preg_replace('/\D/', '', $phone),
        'express_raw' => cellByHeader($cells, $headerMap, ['快递公司', '物流公司']),
        'express_sn' => cellByHeader($cells, $headerMap, ['快递单号', '物流单号', '运单号']),
        'name' => cellByHeader($cells, $headerMap, ['收货人', '收件人']),
        'goods' => cellByHeader($cells, $headerMap, ['商品名称', '商品']),
    ];
}

function cellByHeader(array $cells, array $headerMap, array $names): string
{
    foreach ($names as $n) {
        if (isset($headerMap[$n]) && isset($cells[$headerMap[$n]])) {
            return trim($cells[$headerMap[$n]]);
        }
    }
    return '';
}

if (!$rows) {
    fwrite(STDERR, "未解析到有效数据行\n");
    exit(1);
}

$expressNames = Db::name('shop_express')->column('name', 'name');

$pending = Db::name('shop_order')
    ->alias('a')
    ->leftJoin('shop_order_detail d', 'd.order_num = a.order_num')
    ->where('a.delete_time', 0)
    ->whereIn('a.status', [2, 14])
    ->field('a.id,a.order_num,a.phone,a.status,a.express_sn,d.goods_name')
    ->select()
    ->toArray();

$byPhone = [];
foreach ($pending as $o) {
    $p = preg_replace('/\D/', '', (string)$o['phone']);
    if ($p === '') {
        continue;
    }
    $byPhone[$p][] = $o;
}

function pickOrder(array $list, array $excelRow): ?array
{
    if (count($list) === 1) {
        return $list[0];
    }
    $keywords = ['华月', '松鼠', '月饼', '480'];
    foreach ($keywords as $kw) {
        if ($excelRow['goods'] !== '' && mb_strpos($excelRow['goods'], $kw) === false) {
            continue;
        }
        foreach ($list as $o) {
            $gn = (string)($o['goods_name'] ?? '');
            if ($kw === '480' && $excelRow['goods'] !== '' && mb_strpos($excelRow['goods'], '480') !== false && mb_strpos($gn, '480') !== false) {
                return $o;
            }
            if ($kw !== '480' && mb_strpos($gn, $kw) !== false) {
                return $o;
            }
        }
    }
    return $list[0];
}

$plan = [];
$skipped = [];
$usedIds = [];

foreach ($rows as $r) {
    if ($r['express_sn'] === '') {
        $skipped[] = "行{$r['line']} 无快递单号";
        continue;
    }
    $list = $byPhone[$r['phone']] ?? [];
    if (!$list) {
        $skipped[] = "行{$r['line']} 无待发货订单 phone={$r['phone']} {$r['name']}";
        continue;
    }
    $order = pickOrder($list, $r);
    if (isset($usedIds[$order['id']])) {
        $skipped[] = "行{$r['line']} 订单 id={$order['id']} 已被本表上一行占用";
        continue;
    }
    if (!empty($order['express_sn'])) {
        $skipped[] = "行{$r['line']} id={$order['id']} 已有单号 {$order['express_sn']}";
        $usedIds[$order['id']] = true;
        continue;
    }
    $expressName = $expressMap[$r['express_raw']] ?? $r['express_raw'];
    if ($expressName !== '' && !isset($expressNames[$expressName])) {
        $skipped[] = "行{$r['line']} 快递公司「{$expressName}」不在 cmf_shop_express";
        continue;
    }
    $plan[] = ['excel' => $r, 'order' => $order, 'express_name' => $expressName];
    $usedIds[$order['id']] = true;
}

echo "Excel 有效行: " . count($rows) . " | 待发货订单: " . count($pending) . " | 计划更新: " . count($plan) . "\n\n";
foreach ($plan as $p) {
    $e = $p['excel'];
    $o = $p['order'];
    echo sprintf(
        "行%d | %s | %s | id=%d | %s | %s | %s\n",
        $e['line'],
        $e['phone'],
        $e['name'],
        $o['id'],
        mb_substr((string)$o['goods_name'], 0, 28),
        $p['express_name'],
        $e['express_sn']
    );
}
if ($skipped) {
    echo "\n跳过/异常:\n" . implode("\n", $skipped) . "\n";
}

if ($dryRun) {
    echo "\n[dry-run] 确认后加 --apply 写库\n";
    exit(0);
}

if (!$plan) {
    echo "\n无可更新项\n";
    exit(0);
}

$now = time();
Db::startTrans();
try {
    foreach ($plan as $p) {
        Db::name('shop_order')->where('id', $p['order']['id'])->update([
            'express_name' => $p['express_name'],
            'express_sn' => $p['excel']['express_sn'],
            'send_time' => $now,
            'status' => 4,
        ]);
    }
    Db::commit();
    echo "\n已更新 " . count($plan) . " 条订单为待收货(status=4)\n";
} catch (\Throwable $e) {
    Db::rollback();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
