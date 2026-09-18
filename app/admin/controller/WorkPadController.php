<?php

namespace app\admin\controller;

use think\Controller;

/**
 * 工作Pad - 软件下载（公开接口，登录页使用）
 * 软件包由运维上传至 public/workpad/ 目录
 */
class WorkPadController extends Controller
{
    /**
     * 软件下载接口（无需登录）
     * 路由：/admin/work_pad/download/file/xxx.apk
     */
    public function download()
    {
        $filename = $this->request->param('file', '');
        $filename = basename($filename); // 安全过滤，防止路径穿越

        if (empty($filename)) {
            abort(404, '文件参数错误');
        }

        $filePath = CMF_ROOT . '/public/workpad/' . $filename;

        if (!file_exists($filePath) || !is_file($filePath)) {
            abort(404, '文件不存在');
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
}
