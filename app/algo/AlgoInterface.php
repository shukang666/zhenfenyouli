<?php

namespace app\algo;

/**
 * 算法元信息提供接口
 *
 * 项目里每个可复用的算法 = 实现本接口的一个类，并在 AlgoRegistry::ALGO_CLASSES 中注册。
 * 后台「算法中心」页面即根据注册清单自动展示（列表 + 详情），只读、不可后台新增删除——代码即清单源。
 */
interface AlgoInterface
{
    /**
     * 算法元信息（代码写死，纯静态数据，供后台展示）。
     *
     * 返回结构示例：
     * [
     *   'algo_key'     => 'forum_image_hash',          // 唯一标识，稳定不变
     *   'name'         => '垃圾分类打卡图片查重',        // 后台展示名称
     *   'category'     => '图像处理 · 内容查重',          // 分类
     *   'status'       => 'online',                     // online=使用中 develop=开发中 offline=已下线
     *   'summary'      => '一句话简介(列表页)',
     *   'principle'    => "原理说明正文(支持换行)\n第二段",
     *   'key_params'   => [                             // 关键参数表
     *       ['label' => '指纹长度', 'value' => '64 bit', 'desc' => '说明'],
     *   ],
     *   'applications' => [                             // 应用场景/落地点
     *       ['where' => '发布实时拦截', 'detail' => '…', 'status' => '已上线'],
     *   ],
     *   'impl'         => ['class' => '…', 'file' => '相对路径'], // 代码位置
     * ]
     *
     * @return array
     */
    public static function meta(): array;
}
