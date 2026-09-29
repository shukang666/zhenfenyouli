<?php

namespace plugins\tencent_cos\lib;

class TencentCos
{
    /** @var array */
    private $config;

    /** @var \plugins\tencent_cos\TencentCosPlugin|null */
    private $plugin;

    public function __construct($config)
    {
        $pluginClass = cmf_get_plugin_class('TencentCos');
        $this->plugin = new $pluginClass();
        $this->config = is_array($config) && !empty($config) ? $config : $this->plugin->getConfig();
    }

    public function upload($file, $filePath = '', $fileType = 'image', $param = null)
    {
        $key = self::normalizeObjectKey($file);
        $localPath = $filePath;
        if ($localPath === '' || !is_file($localPath)) {
            $localPath = WEB_ROOT . 'upload/' . ltrim(str_replace('\\', '/', (string)$file), '/');
        }
        if (!is_file($localPath)) {
            throw new \RuntimeException('本地文件不存在: ' . $localPath);
        }

        $cosCfg = $this->toCosLibConfig();
        $this->putObject($cosCfg, $key, $localPath);

        if (!empty($this->config['delete_local_after_upload']) && (string)$this->config['delete_local_after_upload'] !== '0') {
            @unlink($localPath);
        }

        $url = $this->buildObjectUrl($key);
        return [
            'preview_url' => $url,
            'url'         => $url,
        ];
    }

    public function getImageUrl($file, $style = '')
    {
        return $this->getUrl($file, $style);
    }

    public function getPreviewUrl($file, $style = '')
    {
        return $this->getUrl($file, $style);
    }

    public function getUrl($file, $style = '')
    {
        if ($file === null || $file === '') {
            return '';
        }
        $file = (string)$file;
        if (preg_match('#^https?://#i', $file)) {
            return $file;
        }
        return $this->buildObjectUrl(self::normalizeObjectKey($file));
    }

    public function getFileDownloadUrl($file, $expires = 3600)
    {
        return $this->getUrl($file);
    }

    public function getDomain()
    {
        $base = rtrim((string)($this->config['public_base_url'] ?? ''), '/');
        if ($base === '') {
            $bucket = (string)($this->config['bucket'] ?? '');
            $region = (string)($this->config['region'] ?? 'ap-shanghai');
            $base = 'https://' . $bucket . '.cos.' . $region . '.myqcloud.com';
        }
        $host = parse_url($base, PHP_URL_HOST);

        return $host ?: '';
    }

    public function getFilePath($url)
    {
        if (!is_string($url) || $url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            return self::normalizeObjectKey($url);
        }
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === '') {
            return '';
        }

        return self::normalizeObjectKey(ltrim($path, '/'));
    }

    private function buildObjectUrl($key)
    {
        $this->loadCosUploadLib();
        $cosCfg = $this->toCosLibConfig();

        return cos_object_url($cosCfg, $key);
    }

    private function putObject(array $cosCfg, $key, $localPath)
    {
        $autoload = CMF_ROOT . 'vendor/autoload.php';
        if (is_file($autoload)) {
            require_once $autoload;
            if (class_exists('Qcloud\\Cos\\Client')) {
                $client = new \Qcloud\Cos\Client([
                    'region'      => $cosCfg['region'],
                    'schema'      => 'https',
                    'credentials' => [
                        'secretId'  => $cosCfg['secret_id'],
                        'secretKey' => $cosCfg['secret_key'],
                    ],
                ]);
                $fp = fopen($localPath, 'rb');
                try {
                    $client->upload($cosCfg['bucket'], $key, $fp);
                } finally {
                    if (is_resource($fp)) {
                        fclose($fp);
                    }
                }

                return;
            }
        }

        $this->loadCosUploadLib();
        cos_put_object_file($cosCfg, $key, $localPath);
    }

    private function toCosLibConfig()
    {
        return [
            'secret_id'       => trim((string)($this->config['secret_id'] ?? '')),
            'secret_key'      => trim((string)($this->config['secret_key'] ?? '')),
            'region'          => trim((string)($this->config['region'] ?? 'ap-shanghai')),
            'bucket'          => trim((string)($this->config['bucket'] ?? '')),
            'public_base_url' => rtrim((string)($this->config['public_base_url'] ?? ''), '/'),
            'curl_ssl_verify' => !empty($this->config['curl_ssl_verify']) && (string)$this->config['curl_ssl_verify'] !== '0',
        ];
    }

    private function loadCosUploadLib()
    {
        static $loaded = false;
        if (!$loaded) {
            require_once CMF_ROOT . 'data/cos_upload_lib.php';
            $loaded = true;
        }
    }

    public static function normalizeObjectKey($path)
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }
        if (preg_match('#^https?://#i', $path)) {
            $path = preg_replace('#^https?://[^/]+/#', '', $path);
        }
        $path = preg_replace('/\?.*$/', '', $path);
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (strpos($path, 'upload/') === 0) {
            $path = substr($path, 7);
        }

        return $path;
    }
}
