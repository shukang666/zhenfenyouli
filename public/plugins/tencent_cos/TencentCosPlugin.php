<?php
namespace plugins\tencent_cos;

use cmf\lib\Plugin;

class TencentCosPlugin extends Plugin
{
    public $info = [
        'name'        => 'TencentCos',
        'title'       => '腾讯云 COS',
        'description' => '静态资源读写腾讯云对象存储（键为 xcxkf052/...，无 upload 前缀）',
        'status'      => 1,
        'author'      => 'xiayuxuan',
        'version'     => '1.0.0',
    ];

    public $hasAdmin = 0;

    public function install()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }
        if (empty($storageOption['storages']['Local'])) {
            $storageOption['storages']['Local'] = ['name' => '本地'];
        }
        $storageOption['storages']['TencentCos'] = [
            'name'   => '腾讯云 COS',
            'driver' => '\\plugins\\tencent_cos\\lib\\TencentCos',
        ];
        cmf_set_option('storage', $storageOption);

        return true;
    }

    public function uninstall()
    {
        $storageOption = cmf_get_option('storage');
        if (empty($storageOption)) {
            $storageOption = [];
        }
        unset($storageOption['storages']['TencentCos']);
        if (!empty($storageOption['type']) && $storageOption['type'] === 'TencentCos') {
            $storageOption['type'] = 'Local';
        }
        cmf_set_option('storage', $storageOption);

        return true;
    }
}
