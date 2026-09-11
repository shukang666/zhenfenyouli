<?php

namespace app\admin\library;

/**
 * IP 归属地解析
 *
 * 不依赖任何需要申请 key 的商业服务（原腾讯地图 WebService IP 定位已移除），
 * 改为「免费公开接口多级回落 + 内置中英映射」：
 *   1. https://api.ip.sb/geoip/{ip}    Cloudflare 线路，国内可访问，返回国家/省/市 + 经纬度
 *   2. https://ipwho.is/{ip}           免费无需 key，返回国家/省/市 + 经纬度
 *   3. http://ip-api.com/json/{ip}     免费 45 次/分钟，返回国家/省/市 + 经纬度
 *   三个接口任意一个成功即返回；全部失败返回 null（调用方按「未解析」处理，不影响封锁动作）。
 *
 * 接口返回的地名都是英文，这里用内置的 ISO 3166-1 国家代码表翻成中文国家名，
 * 中国 IP 再用省级英文名表翻成中文省名，最终拼成「中国 山东 济南」这样的归属地。
 *
 * 只在「新增封锁」和「重新解析归属地」时调用，结果会写回 cmf_security_block_ip，
 * 属于永久缓存，不会重复请求，所以不必担心接口频率限制。
 */
class IpLocation
{
    /** 单个接口超时（秒），三个接口都超时最多等 9 秒 */
    const TIMEOUT = 3;

    /** 归属地字段最终长度上限（cmf_security_block_ip.region 是 varchar(120)） */
    const REGION_MAX_LEN = 120;

    /**
     * 免费接口列表，按顺序尝试
     * key 用于 parseGeo() 里区分字段名
     */
    const APIS = [
        'ipsb'    => 'https://api.ip.sb/geoip/{ip}',
        'ipwhois' => 'https://ipwho.is/{ip}',
        'ipapi'   => 'http://ip-api.com/json/{ip}?lang=zh-CN&fields=status,country,countryCode,regionName,city,lat,lon',
    ];

    /**
     * 查询 IP 归属地
     *
     * @param  string     $ip 单个 IPv4 / IPv6
     * @return array|null     ['region' => 中文归属地, 'lng' => 经度, 'lat' => 纬度]；解析不出来返回 null
     */
    public static function lookup($ip)
    {
        $ip = trim((string)$ip);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        // 内网 / 保留地址不需要查，也没有意义（不打点，但给个可读的归属地）
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['region' => '内网/保留地址', 'lng' => 0.0, 'lat' => 0.0];
        }

        foreach (self::APIS as $type => $tpl) {
            $body = self::httpGet(str_replace('{ip}', urlencode($ip), $tpl));
            if ($body === '') {
                continue;
            }
            $json = json_decode($body, true);
            if (!is_array($json)) {
                continue;
            }
            $geo = self::parseGeo($type, $json);
            if ($geo !== null) {
                return $geo;
            }
        }

        return null;
    }

    /**
     * 解析各接口的返回结构
     */
    private static function parseGeo($type, array $json)
    {
        $country = '';
        $code    = '';
        $region  = '';
        $city    = '';
        $lng     = 0.0;
        $lat     = 0.0;

        switch ($type) {
            case 'ipsb':
                // {"region":"Shandong","city":"Jinan","latitude":34.77,"longitude":113.72,"country":"China","country_code":"CN"}
                if (empty($json['country']) && empty($json['country_code'])) {
                    return null;
                }
                $country = isset($json['country']) ? $json['country'] : '';
                $code    = isset($json['country_code']) ? $json['country_code'] : '';
                $region  = isset($json['region']) ? $json['region'] : '';
                $city    = isset($json['city']) ? $json['city'] : '';
                $lng     = isset($json['longitude']) ? (float)$json['longitude'] : 0.0;
                $lat     = isset($json['latitude']) ? (float)$json['latitude'] : 0.0;
                break;

            case 'ipwhois':
                // {"success":true,"country":"China","country_code":"CN","region":"Jiangsu Sheng","city":"Nanjing","latitude":32.06,"longitude":118.77}
                if (empty($json['success'])) {
                    return null;
                }
                $country = isset($json['country']) ? $json['country'] : '';
                $code    = isset($json['country_code']) ? $json['country_code'] : '';
                $region  = isset($json['region']) ? $json['region'] : '';
                $city    = isset($json['city']) ? $json['city'] : '';
                $lng     = isset($json['longitude']) ? (float)$json['longitude'] : 0.0;
                $lat     = isset($json['latitude']) ? (float)$json['latitude'] : 0.0;
                break;

            case 'ipapi':
                // {"status":"success","country":"中国","countryCode":"CN","regionName":"山东省","city":"济南市","lat":36.67,"lon":116.99}
                if (empty($json['status']) || $json['status'] !== 'success') {
                    return null;
                }
                $country = isset($json['country']) ? $json['country'] : '';
                $code    = isset($json['countryCode']) ? $json['countryCode'] : '';
                $region  = isset($json['regionName']) ? $json['regionName'] : '';
                $city    = isset($json['city']) ? $json['city'] : '';
                $lng     = isset($json['lon']) ? (float)$json['lon'] : 0.0;
                $lat     = isset($json['lat']) ? (float)$json['lat'] : 0.0;
                break;

            default:
                return null;
        }

        $nation  = self::nationCn($code, $country);
        $province = self::provinceCn($code, $region);
        $city     = trim((string)$city);

        $parts = [];
        if ($nation !== '') {
            $parts[] = $nation;
        }
        if ($province !== '') {
            $parts[] = $province;
        }
        // 省市同名（如「北京市 北京市」）只留一个
        if ($city !== '' && $city !== $province) {
            $parts[] = $city;
        }

        return [
            'region' => mb_substr(implode(' ', $parts), 0, self::REGION_MAX_LEN, 'utf-8'),
            'lng'    => $lng,
            'lat'    => $lat,
        ];
    }

    /**
     * 国家名：优先用国家代码查中文，查不到就用接口返回的原文
     */
    private static function nationCn($code, $fallback)
    {
        $code = strtoupper(trim((string)$code));
        if ($code !== '' && isset(self::COUNTRY_CN[$code])) {
            return self::COUNTRY_CN[$code];
        }
        return trim((string)$fallback);
    }

    /**
     * 省名：只有中国 IP 才翻译（接口给的 "Jiangsu Sheng" / "Shandong" 这类写作）
     * 其他国家保留原文，避免把 "California" 之类翻错
     */
    private static function provinceCn($code, $name)
    {
        $name = trim((string)$name);
        if ($name === '') {
            return '';
        }
        if (strtoupper(trim((string)$code)) !== 'CN') {
            return $name;
        }

        // 已经是中文（ip-api 会直接返回「山东省」）就不用再翻了
        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $name)) {
            return $name;
        }

        $key = strtolower($name);
        $key = str_replace(['-', '_', '.'], ' ', $key);
        $key = preg_replace('/\s+/', ' ', $key);
        // 去掉 "Jiangsu Sheng" / "Guangdong Province" / "Guangxi Zhuang Autonomous Region" 这类后缀
        $key = preg_replace('/\s+(sheng|shi|province|municipality|autonomous region|zhuang autonomous region|hui autonomous region|uighur autonomous region|uygur autonomous region|special administrative region|sar)$/', '', $key);
        $key = trim($key);

        return isset(self::CN_REGION_CN[$key]) ? self::CN_REGION_CN[$key] : $name;
    }

    /**
     * 发一个 GET 请求，失败统一返回空字符串
     */
    private static function httpGet($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
                CURLOPT_FOLLOWLOCATION => true,
                // 部分服务器没装 CA 证书包，关掉校验保证接口可用（只是取地理位置，风险可控）
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; ThinkCMF-Security/1.0)',
            ]);
            $body = curl_exec($ch);
            curl_close($ch);
            return is_string($body) ? $body : '';
        }

        $ctx  = stream_context_create(['http' => ['timeout' => self::TIMEOUT, 'method' => 'GET']]);
        $body = @file_get_contents($url, false, $ctx);
        return is_string($body) ? $body : '';
    }

    /**
     * ISO 3166-1 alpha-2 国家代码 → 中文名
     */
    const COUNTRY_CN = [
        'AD' => '安道尔', 'AE' => '阿联酋', 'AF' => '阿富汗', 'AG' => '安提瓜和巴布达', 'AI' => '安圭拉',
        'AL' => '阿尔巴尼亚', 'AM' => '亚美尼亚', 'AO' => '安哥拉', 'AQ' => '南极洲', 'AR' => '阿根廷',
        'AS' => '美属萨摩亚', 'AT' => '奥地利', 'AU' => '澳大利亚', 'AW' => '阿鲁巴', 'AX' => '奥兰群岛',
        'AZ' => '阿塞拜疆', 'BA' => '波黑', 'BB' => '巴巴多斯', 'BD' => '孟加拉国', 'BE' => '比利时',
        'BF' => '布基纳法索', 'BG' => '保加利亚', 'BH' => '巴林', 'BI' => '布隆迪', 'BJ' => '贝宁',
        'BL' => '圣巴泰勒米', 'BM' => '百慕大', 'BN' => '文莱', 'BO' => '玻利维亚', 'BQ' => '荷兰加勒比区',
        'BR' => '巴西', 'BS' => '巴哈马', 'BT' => '不丹', 'BV' => '布韦岛', 'BW' => '博茨瓦纳',
        'BY' => '白俄罗斯', 'BZ' => '伯利兹', 'CA' => '加拿大', 'CC' => '科科斯群岛', 'CD' => '刚果(金)',
        'CF' => '中非', 'CG' => '刚果(布)', 'CH' => '瑞士', 'CI' => '科特迪瓦', 'CK' => '库克群岛',
        'CL' => '智利', 'CM' => '喀麦隆', 'CN' => '中国', 'CO' => '哥伦比亚', 'CR' => '哥斯达黎加',
        'CU' => '古巴', 'CV' => '佛得角', 'CW' => '库拉索', 'CX' => '圣诞岛', 'CY' => '塞浦路斯',
        'CZ' => '捷克', 'DE' => '德国', 'DJ' => '吉布提', 'DK' => '丹麦', 'DM' => '多米尼克',
        'DO' => '多米尼加', 'DZ' => '阿尔及利亚', 'EC' => '厄瓜多尔', 'EE' => '爱沙尼亚', 'EG' => '埃及',
        'EH' => '西撒哈拉', 'ER' => '厄立特里亚', 'ES' => '西班牙', 'ET' => '埃塞俄比亚', 'FI' => '芬兰',
        'FJ' => '斐济', 'FK' => '马尔维纳斯群岛', 'FM' => '密克罗尼西亚', 'FO' => '法罗群岛', 'FR' => '法国',
        'GA' => '加蓬', 'GB' => '英国', 'GD' => '格林纳达', 'GE' => '格鲁吉亚', 'GF' => '法属圭亚那',
        'GG' => '根西岛', 'GH' => '加纳', 'GI' => '直布罗陀', 'GL' => '格陵兰', 'GM' => '冈比亚',
        'GN' => '几内亚', 'GP' => '瓜德罗普', 'GQ' => '赤道几内亚', 'GR' => '希腊', 'GS' => '南乔治亚和南桑威奇群岛',
        'GT' => '危地马拉', 'GU' => '关岛', 'GW' => '几内亚比绍', 'GY' => '圭亚那', 'HK' => '中国香港',
        'HM' => '赫德岛和麦克唐纳群岛', 'HN' => '洪都拉斯', 'HR' => '克罗地亚', 'HT' => '海地', 'HU' => '匈牙利',
        'ID' => '印度尼西亚', 'IE' => '爱尔兰', 'IL' => '以色列', 'IM' => '马恩岛', 'IN' => '印度',
        'IO' => '英属印度洋领地', 'IQ' => '伊拉克', 'IR' => '伊朗', 'IS' => '冰岛', 'IT' => '意大利',
        'JE' => '泽西岛', 'JM' => '牙买加', 'JO' => '约旦', 'JP' => '日本', 'KE' => '肯尼亚',
        'KG' => '吉尔吉斯斯坦', 'KH' => '柬埔寨', 'KI' => '基里巴斯', 'KM' => '科摩罗', 'KN' => '圣基茨和尼维斯',
        'KP' => '朝鲜', 'KR' => '韩国', 'KW' => '科威特', 'KY' => '开曼群岛', 'KZ' => '哈萨克斯坦',
        'LA' => '老挝', 'LB' => '黎巴嫩', 'LC' => '圣卢西亚', 'LI' => '列支敦士登', 'LK' => '斯里兰卡',
        'LR' => '利比里亚', 'LS' => '莱索托', 'LT' => '立陶宛', 'LU' => '卢森堡', 'LV' => '拉脱维亚',
        'LY' => '利比亚', 'MA' => '摩洛哥', 'MC' => '摩纳哥', 'MD' => '摩尔多瓦', 'ME' => '黑山',
        'MF' => '法属圣马丁', 'MG' => '马达加斯加', 'MH' => '马绍尔群岛', 'MK' => '北马其顿', 'ML' => '马里',
        'MM' => '缅甸', 'MN' => '蒙古', 'MO' => '中国澳门', 'MP' => '北马里亚纳群岛', 'MQ' => '马提尼克',
        'MR' => '毛里塔尼亚', 'MS' => '蒙特塞拉特', 'MT' => '马耳他', 'MU' => '毛里求斯', 'MV' => '马尔代夫',
        'MW' => '马拉维', 'MX' => '墨西哥', 'MY' => '马来西亚', 'MZ' => '莫桑比克', 'NA' => '纳米比亚',
        'NC' => '新喀里多尼亚', 'NE' => '尼日尔', 'NF' => '诺福克岛', 'NG' => '尼日利亚', 'NI' => '尼加拉瓜',
        'NL' => '荷兰', 'NO' => '挪威', 'NP' => '尼泊尔', 'NR' => '瑙鲁', 'NU' => '纽埃',
        'NZ' => '新西兰', 'OM' => '阿曼', 'PA' => '巴拿马', 'PE' => '秘鲁', 'PF' => '法属波利尼西亚',
        'PG' => '巴布亚新几内亚', 'PH' => '菲律宾', 'PK' => '巴基斯坦', 'PL' => '波兰', 'PM' => '圣皮埃尔和密克隆',
        'PN' => '皮特凯恩群岛', 'PR' => '波多黎各', 'PS' => '巴勒斯坦', 'PT' => '葡萄牙', 'PW' => '帕劳',
        'PY' => '巴拉圭', 'QA' => '卡塔尔', 'RE' => '留尼汪', 'RO' => '罗马尼亚', 'RS' => '塞尔维亚',
        'RU' => '俄罗斯', 'RW' => '卢旺达', 'SA' => '沙特阿拉伯', 'SB' => '所罗门群岛', 'SC' => '塞舌尔',
        'SD' => '苏丹', 'SE' => '瑞典', 'SG' => '新加坡', 'SH' => '圣赫勒拿', 'SI' => '斯洛文尼亚',
        'SJ' => '斯瓦尔巴和扬马延', 'SK' => '斯洛伐克', 'SL' => '塞拉利昂', 'SM' => '圣马力诺', 'SN' => '塞内加尔',
        'SO' => '索马里', 'SR' => '苏里南', 'SS' => '南苏丹', 'ST' => '圣多美和普林西比', 'SV' => '萨尔瓦多',
        'SX' => '荷属圣马丁', 'SY' => '叙利亚', 'SZ' => '斯威士兰', 'TC' => '特克斯和凯科斯群岛', 'TD' => '乍得',
        'TF' => '法属南方领地', 'TG' => '多哥', 'TH' => '泰国', 'TJ' => '塔吉克斯坦', 'TK' => '托克劳',
        'TL' => '东帝汶', 'TM' => '土库曼斯坦', 'TN' => '突尼斯', 'TO' => '汤加', 'TR' => '土耳其',
        'TT' => '特立尼达和多巴哥', 'TV' => '图瓦卢', 'TW' => '中国台湾', 'TZ' => '坦桑尼亚', 'UA' => '乌克兰',
        'UG' => '乌干达', 'UM' => '美国本土外小岛屿', 'US' => '美国', 'UY' => '乌拉圭', 'UZ' => '乌兹别克斯坦',
        'VA' => '梵蒂冈', 'VC' => '圣文森特和格林纳丁斯', 'VE' => '委内瑞拉', 'VG' => '英属维尔京群岛',
        'VI' => '美属维尔京群岛', 'VN' => '越南', 'VU' => '瓦努阿图', 'WF' => '瓦利斯和富图纳', 'WS' => '萨摩亚',
        'YE' => '也门', 'YT' => '马约特', 'ZA' => '南非', 'ZM' => '赞比亚', 'ZW' => '津巴布韦',
    ];

    /**
     * 中国省级行政区英文名（小写，已去后缀）→ 中文
     * 覆盖 ip.sb 的 "Shandong" 与 ipwho.is 的 "Jiangsu Sheng" 两种常见写法
     */
    const CN_REGION_CN = [
        'beijing'                 => '北京',
        'tianjin'                 => '天津',
        'hebei'                   => '河北',
        'shanxi'                  => '山西',
        'inner mongolia'          => '内蒙古',
        'nei mongol'              => '内蒙古',
        'neimenggu'               => '内蒙古',
        'liaoning'                => '辽宁',
        'jilin'                   => '吉林',
        'heilongjiang'            => '黑龙江',
        'shanghai'                => '上海',
        'jiangsu'                 => '江苏',
        'zhejiang'                => '浙江',
        'anhui'                   => '安徽',
        'fujian'                  => '福建',
        'jiangxi'                 => '江西',
        'shandong'                => '山东',
        'henan'                   => '河南',
        'hubei'                   => '湖北',
        'hunan'                   => '湖南',
        'guangdong'               => '广东',
        'guangxi'                 => '广西',
        'hainan'                  => '海南',
        'chongqing'               => '重庆',
        'sichuan'                 => '四川',
        'guizhou'                 => '贵州',
        'yunnan'                  => '云南',
        'tibet'                   => '西藏',
        'xizang'                  => '西藏',
        'shaanxi'                 => '陕西',
        'gansu'                   => '甘肃',
        'qinghai'                 => '青海',
        'ningxia'                 => '宁夏',
        'xinjiang'                => '新疆',
        'hong kong'               => '香港',
        'hongkong'                => '香港',
        'macau'                   => '澳门',
        'macao'                   => '澳门',
        'taiwan'                  => '台湾',
        'guangxi zhuang'          => '广西',
        'xinjiang uygur'          => '新疆',
        'ningxia hui'             => '宁夏',
        // 少量常见写法兜底
        'guangzhou'               => '广东',
        'shenzhen'                => '广东',
        'hangzhou'                => '浙江',
        'chengdu'                 => '四川',
        'wuhan'                   => '湖北',
        'xian'                    => '陕西',
        "xi'an"                   => '陕西',
    ];
}
