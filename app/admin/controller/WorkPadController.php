<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * 工作Pad管理 - 软件下载
 * 软件包通过服务器上传到指定目录，后台只提供下载能力
 */
class WorkPadController extends AdminBaseController
{
    /**
     * 软件下载页面
     */
    public function index()
    {
        // 软件包存放目录（相对于 public/）
        $softDir = CMF_ROOT . DS . 'public' . DS . 'workpad' . DS;

        $files = [];
        if (is_dir($softDir)) {
            $items = scandir($softDir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $filePath = $softDir . $item;
                if (!is_file($filePath)) {
                    continue;
                }
                // 跳过 .htaccess / html 等隐藏安全文件
                if (strpos($item, '.') === 0) {
                    continue;
                }
                $files[] = [
                    'name'       => $item,
                    'size'       => $this->formatSize(filesize($filePath)),
                    'size_bytes' => filesize($filePath),
                    'time'       => date('Y-m-d H:i', filemtime($filePath)),
                ];
            }
            // 按上传时间倒序
            usort($files, function ($a, $b) {
                return $b['size_bytes'] - $a['size_bytes'];
            });
        }

        $this->assign('files', $files);
        $this->assign('dir_exists', is_dir($softDir));
        $this->assign('soft_dir', 'public/workpad/');
        return $this->fetch();
    }

    /**
     * 下载接口（登录后才能访问）
     */
    public function download()
    {
        // 必须登录（AdminBaseController 已保证）
        $filename = $this->request->param('file', '');
        $filename = basename($filename); // 安全过滤，防止路径穿越

        if (empty($filename)) {
            $this->error('文件参数错误');
        }

        $filePath = CMF_ROOT . DS . 'public' . DS . 'workpad' . DS . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            $this->error('文件不存在：' . htmlspecialchars($filename));
        }

        // 输出文件
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        ob_clean();
        flush();
        readfile($filePath);
        exit;
    }

    /**
     * 格式化文件大小
     */
    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
