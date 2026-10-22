<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * 小程序健康 Tab 顶部 Banner 与快捷入口配置
 */
class HealthTabConfigController extends AdminBaseController
{
    private const OPTION_NAME = 'health_tab_config';

    public function index()
    {
        if ($this->request->isPost()) {
            $banner = $this->request->param('banner/a', []);
            $shortcuts = $this->request->param('shortcuts/a', []);

            if (count($shortcuts) !== 2) {
                $this->error('必须配置 2 个快捷入口');
            }

            $cleanBanner = [
                'title'      => trim((string)($banner['title'] ?? '')),
                'subtitle'   => trim((string)($banner['subtitle'] ?? '')),
                'image'      => trim((string)($banner['image'] ?? '')),
                'link_mode'  => trim((string)($banner['link_mode'] ?? 'exam')),
                'exam_id'    => max(0, (int)($banner['exam_id'] ?? 0)),
                'url'        => trim((string)($banner['url'] ?? '')),
                'route_type' => trim((string)($banner['route_type'] ?? 'navigateTo')),
            ];

            $cleanShortcuts = [];
            foreach (array_values($shortcuts) as $item) {
                $cleanShortcuts[] = [
                    'key'        => trim((string)($item['key'] ?? '')),
                    'name'       => trim((string)($item['name'] ?? '')),
                    'desc'       => trim((string)($item['desc'] ?? '')),
                    'icon'       => trim((string)($item['icon'] ?? 'grid')),
                    'url'        => trim((string)($item['url'] ?? '')),
                    'route_type' => trim((string)($item['route_type'] ?? 'navigateTo')),
                ];
            }

            $payload = [
                'banner'    => $cleanBanner,
                'shortcuts' => $cleanShortcuts,
            ];

            $res = cmf_set_option(self::OPTION_NAME, $payload, true);
            if ($res !== false) {
                $this->success('保存成功');
            }
            $this->error('保存失败');
        }

        $config = cmf_get_option(self::OPTION_NAME);
        if (!is_array($config) || empty($config['banner']) || empty($config['shortcuts'])) {
            $config = self::defaultConfig();
        }

        $this->assign('config', $config);
        $this->assign('shortcuts', $config['shortcuts']);
        $this->assign('exam_list', $this->fetchExamOptions());

        return $this->fetch();
    }

    public static function defaultConfig(): array
    {
        return [
            'banner' => [
                'title'      => '瓦房店中心医院公共卫生满意度调查',
                'subtitle'   => '诚邀您参与，助力公共卫生服务提升',
                'image'      => '',
                'link_mode'  => 'exam',
                'exam_id'    => 0,
                'url'        => '',
                'route_type' => 'navigateTo',
            ],
            'shortcuts' => [
                [
                    'key'        => 'survey_tool',
                    'name'       => '医疗机构问卷',
                    'desc'       => '问卷列表与填写',
                    'icon'       => 'file-text',
                    'url'        => '/pages/activity/research',
                    'route_type' => 'navigateTo',
                ],
                [
                    'key'        => 'health_quiz',
                    'name'       => '健康问答竞赛',
                    'desc'       => '趣味答题涨知识',
                    'icon'       => 'edit-pen',
                    'url'        => '/pages/sjhd/list?type=3',
                    'route_type' => 'navigateTo',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function fetchExamOptions(): array
    {
        $rows = \think\facade\Db::name('exam')
            ->where('status', 1)
            ->order('list_order asc,id desc')
            ->limit(200)
            ->field('id,name')
            ->select()
            ->toArray();

        return is_array($rows) ? $rows : [];
    }
}
