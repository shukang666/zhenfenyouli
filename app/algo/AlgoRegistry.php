<?php

namespace app\algo;

/**
 * 算法注册中心
 *
 * 项目内所有算法在代码里写死（ALGO_CLASSES），后台「算法中心」页面只读展示，
 * 不可新增/删除。新增算法 = 实现 AlgoInterface 的类 + 在 ALGO_CLASSES 末尾追加类名，
 * 页面自动出现，无需改控制器/模板。
 */
class AlgoRegistry
{
    /** 代码注册的算法类（只读，写死） */
    const ALGO_CLASSES = [
        ImageHashAlgo::class,
    ];

    /** 获取所有注册算法元信息（规范化） */
    public static function all(): array
    {
        $list = [];
        foreach (self::ALGO_CLASSES as $class) {
            if (!is_subclass_of($class, AlgoInterface::class)) {
                continue;
            }
            $list[] = self::normalize($class::meta());
        }
        return $list;
    }

    /** 按 algo_key 取元信息（未注册返回 null） */
    public static function byKey(string $algoKey): ?array
    {
        foreach (self::all() as $meta) {
            if ($meta['algo_key'] === $algoKey) {
                return $meta;
            }
        }
        return null;
    }

    private static function normalize(array $meta): array
    {
        return [
            'algo_key'     => (string)($meta['algo_key'] ?? ''),
            'name'         => (string)($meta['name'] ?? ''),
            'category'     => (string)($meta['category'] ?? ''),
            'status'       => (string)($meta['status'] ?? 'develop'),
            'summary'      => (string)($meta['summary'] ?? ''),
            'principle'    => (string)($meta['principle'] ?? ''),
            'key_params'   => (array)($meta['key_params'] ?? []),
            'applications' => (array)($meta['applications'] ?? []),
            'impl'         => (array)($meta['impl'] ?? []),
        ];
    }
}
