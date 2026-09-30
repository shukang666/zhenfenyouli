<?php

namespace api\wxapp\service;

/**
 * 打卡前统一学习（写死内容，不读 cmf_waste 列表）
 */
class ClockPreLearnService
{
    /**
     * @return array<int, array{id:string,title:string,content:string}>
     */
    public static function getSections(): array
    {
        return [
            [
                'id'    => 'rules',
                'title' => '真分打卡功能规范',
                'content' => <<<'HTML'
<p><strong>1. 适用对象</strong><br/>本小程序「真分打卡」面向已绑定社区/小区信息的居民，用于记录您在家或社区投放点的分类成果，参与社区环保积分活动。</p>
<p><strong>2. 打卡含义</strong><br/>打卡是<strong>记录您已认真分类并正确投放</strong>，不是考核你会不会分。请按所选场景、桶的种类，拍摄<strong>分好后的垃圾</strong>或投放过程照片，勿上传与本次分类无关的图片。</p>
<p><strong>3. 家庭场景</strong><br/>需先设置家中分类位置（如厨房厨余桶、客厅可回收桶等），每次选择对应位置与垃圾种类后上传成果图；社区场景按指引顺序拍照（扔之前、对准桶、扔完后）。</p>
<p><strong>4. 诚信与复核</strong><br/>重复、明显无关或弄虚作假的照片，社区有权不予计分或撤销积分。对审核有疑问可联系社区工作人员。</p>
<p><strong>5. 积分说明</strong><br/>积分规则以社区配置为准，到账可能有延迟，请在「我的积分」中查看；打卡天数等统计以系统记录为准。</p>
HTML,
            ],
            [
                'id'    => 'basic',
                'title' => '垃圾分类基础知识',
                'content' => <<<'HTML'
<p><strong>四类常见划分（以本地要求为准）：</strong></p>
<p><strong>厨余垃圾</strong>：菜叶、果皮、剩饭剩菜、茶叶渣等易腐生物质，尽量沥干水分、去除外包装后再投。</p>
<p><strong>可回收物</strong>：纸类、塑料、金属、玻璃、织物等可资源化利用的废弃物，需<strong>干净、干燥</strong>，压扁捆扎便于收运。</p>
<p><strong>有害垃圾</strong>：电池、灯管、过期药品、油漆桶等对人体或环境有害的废弃物，宜<strong>单独收集</strong>，投至指定回收点。</p>
<p><strong>其他垃圾</strong>：除上述三类以外的废弃物，如污染纸张、一次性餐具、烟蒂、尘土等。</p>
<p>分不清时，可先按社区宣传栏、桶身标识对照；仍不确定的，宁可投「其他垃圾」，避免污染可回收与厨余。</p>
HTML,
            ],
            [
                'id'    => 'notice',
                'title' => '垃圾分类注意事项',
                'content' => <<<'HTML'
<p><strong>投放前</strong>：外卖盒、牛奶盒等先简单冲洗或沥干；大骨头、贝壳、硬壳类一般按「其他垃圾」处理（以社区规定为准）。</p>
<p><strong>常见误投</strong>：塑料袋、餐巾纸、湿巾<strong>不要</strong>投进可回收；电池、药品<strong>不要</strong>混进其他垃圾或厨余。</p>
<p><strong>安全卫生</strong>：投放时戴手套或洗手；厨余桶加盖，及时清运，减少异味和蚊虫。</p>
<p><strong>拍照打卡</strong>：光线充足、对焦清晰；涉及隐私时注意遮挡门牌、证件等个人信息。</p>
<p>坚持正确分类，既方便清运，也帮助资源再利用，共建干净社区。</p>
HTML,
            ],
        ];
    }
}
