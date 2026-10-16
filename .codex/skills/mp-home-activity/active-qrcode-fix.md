---
name: active-qrcode-fix
description: 修复活动小程序码生成问题。当用户反馈活动没有小程序码、小程序码不显示、或需要重新生成活动小程序码时使用。涉及活动管理后台的二维码生成逻辑和阿里云OSS上传。
---

# 活动小程序码修复

## 问题背景

活动管理后台在创建/编辑活动时会自动生成小程序码并上传到阿里云OSS。由于代码bug，同一天创建的活动会共享同一个OSS文件路径，导致后一个活动覆盖前一个的小程序码。

## 根因分析

**文件**: `app/admin/controller/ActiveController.php`

**问题代码**:
```php
// 第147行（新建活动）
$qr_code = CommonModel::setShareImage($scene, 'active', 'dz1111', $share_url);

// 第154行（编辑活动）
$params['qr_code'] = CommonModel::setShareImage($scene, 'active', 'dz1111', $share_url);
```

**根因**: `$image_name` 参数固定为 `'active'`，不包含活动ID。在 `CommonModel::setShareImage` 中生成的文件路径为：
```php
$ali_file_path = $dz . '/' . $dir . '/' . date('Ymd') . '/' . $image_name;
// 结果: dz1111/userShare/20260908/active
```

同一天的所有活动都会写入同一个OSS文件，后创建的覆盖先创建的。

## 修复方案

### 1. 代码修复（已完成）

将 `$image_name` 改为包含活动ID的唯一值：

```php
// 新建活动
$qr_code = CommonModel::setShareImage($scene, 'active_'.$result['id'], 'dz1111', $share_url);

// 编辑活动
$params['qr_code'] = CommonModel::setShareImage($scene, 'active_'.$params['id'], 'dz1111', $share_url);
```

### 2. 已有问题数据的修复脚本

当发现已有活动的小程序码有问题时，使用以下脚本重新生成：

```php
<?php
/**
 * 修复活动小程序码 - 重新生成指定活动的小程序码
 * 用法: php fix_active_qrcode.php [活动ID1] [活动ID2] ...
 */

// 加载 OSS SDK
$ossBaseDir = __DIR__ . '/vendor/aliyuncs/oss-sdk-php/src/OSS/';
require_once $ossBaseDir . 'Core/OssException.php';
require_once $ossBaseDir . 'Core/OssUtil.php';
require_once $ossBaseDir . 'Core/MimeTypes.php';
require_once $ossBaseDir . 'Http/RequestCore.php';
require_once $ossBaseDir . 'Http/ResponseCore.php';
require_once $ossBaseDir . 'Http/RequestCore_Exception.php';
require_once $ossBaseDir . 'Model/XmlConfig.php';
require_once $ossBaseDir . 'Result/Result.php';
require_once $ossBaseDir . 'OssClient.php';
// ... 其他依赖文件

// 配置
$appid = 'wxb3b18fa0721e9d9c';
$secret = 'a2a01bc4517e77adf7a4613807779be6';
$ossConfig = [
    'accessKey' => '从数据库 cmf_plugin 表 Oss 插件配置获取',
    'secretKey' => '从数据库 cmf_plugin 表 Oss 插件配置获取',
    'upload_endpoint' => 'http://oss-cn-beijing.aliyuncs.com',
    'domain' => 'oss.ausite.cn',
    'bucket' => 'twjbdzoss',
    'dir' => 'xcxkf052',
];

// 数据库配置
$dbConfig = [
    'hostname' => '124.223.56.252',
    'database' => 'xcxkf052_aubye_c',
    'username' => 'xcxkf052_aubye_c',
    'password' => 'acacQQndx12ADXMm',
    'prefix'   => 'cmf_',
];

// 小程序页面路径
$shareUrl = 'pages/low_carbon/lsxf/details-new';

// 获取 access_token
$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$response = curl_exec($ch);
curl_close($ch);
$data = json_decode($response, true);
$accessToken = $data['access_token'];

// 为每个活动生成小程序码
foreach ($activeIds as $activeId) {
    $imageName = 'active_' . $activeId;
    $localFile = "upload/userShare/" . date('Ymd') . "/{$imageName}.png";
    $aliFilePath = $ossConfig['dir'] . "/userShare/" . date('Ymd') . "/{$imageName}.png";
    
    // 调用微信 API 生成小程序码
    $apiUrl = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
    $params = [
        'scene' => (string)$activeId,
        'page' => $shareUrl,
        'check_path' => false,
        'env_version' => 'release',
        'width' => 430,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $qrcodeData = curl_exec($ch);
    curl_close($ch);
    
    // 保存到本地
    file_put_contents($localFile, $qrcodeData);
    
    // 上传到 OSS
    $ossClient = new \OSS\OssClient($ossConfig['accessKey'], $ossConfig['secretKey'], $ossConfig['upload_endpoint']);
    $ossClient->uploadFile($ossConfig['bucket'], $aliFilePath, $localFile);
    
    // 更新数据库
    $pdo = new PDO("mysql:host={$dbConfig['hostname']};dbname={$dbConfig['database']}", $dbConfig['username'], $dbConfig['password']);
    $stmt = $pdo->prepare("UPDATE {$dbConfig['prefix']}active SET qr_code = ? WHERE id = ?");
    $stmt->execute([$aliFilePath, $activeId]);
    
    unlink($localFile); // 删除本地临时文件
}
```

## 关键配置说明

| 配置项 | 说明 | 获取方式 |
|--------|------|----------|
| accessKey | 阿里云AccessKey | `cmf_plugin` 表的 Oss 插件配置 |
| secretKey | 阿里云SecretKey | `cmf_plugin` 表的 Oss 插件配置 |
| upload_endpoint | 上传用 endpoint | 固定: `http://oss-cn-beijing.aliyuncs.com` |
| domain | 访问用域名 | 固定: `oss.ausite.cn` |
| bucket | OSS Bucket | 固定: `twjbdzoss` |
| dir | 存储目录 | 固定: `xcxkf052` |

## 验证方法

修复后验证小程序码是否可访问：
```bash
curl -I "https://oss.ausite.cn/xcxkf052/userShare/YYYYMMDD/active_{活动ID}.png"
```

正常返回应包含：
- Status: 200
- Content-Type: image/png
- Size: 90000+ bytes（正常小程序码大小）

## 相关文件

- 后台控制器: `app/admin/controller/ActiveController.php`
- 小程序码生成: `api/wxapp/model\CommonModel.php` → `setShareImage()`
- OSS 上传插件: `public/plugins/oss/lib/Oss.php`
- 活动列表模板: `public/themes/admin_fengiy/admin/active/index.html`
