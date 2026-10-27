<?php

namespace api\wxapp\service;

/**
 * 小区名称排序：MySQL GBK 码表近似拼音序，分页需再带 id 保证稳定
 */
class CommunitySortHelper
{
    public static function orderRawByNamePinyin(string $nameColumn = 'c.name', string $idColumn = 'c.id'): string
    {
        return 'CONVERT(' . $nameColumn . ' USING gbk) ASC, ' . $idColumn . ' ASC';
    }
}
