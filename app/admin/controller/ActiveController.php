<?php

namespace app\admin\controller;

use api\wxapp\model\CommonModel;
use app\admin\model\active\ActiveCateModel;
use app\admin\model\active\ActiveCommunityCateModel;
use app\admin\model\active\ActiveClockModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\member\CommunityCateModel;
use think\App;
use think\db\exception\DbException;

class ActiveController extends ToolController
{
    public function __construct(App $app)
    {
        parent::__construct($app);

        $this->model = new ActiveModel();
    }

    /**
     * 列表默认展示的参加方式：1=小程序参加（活动列表） 2=扫码参加（签到列表）
     * @var int
     */
    protected $joinType = 1;

    public function setIndex()
    {

        return $this->model->where($this->setWhere())->order('list_order asc,id desc')->paginate(15);
    }

    /**
     * 列表
     * @return mixed
     * @throws DbException
     */
    public function index()
    {
        $params = $this->request->param();
        $list = $this->setIndex()->each(function ($item) {
            return $this->getList($item);
        });
//        $list = $this->getParamData($list);
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }

    public function setWhere()
    {
        $name = $this->request->param('name', '', 'trim');
        $type = $this->request->param('type', '', 'trim');

        $where = [];
        if ($name) {
            $where[] = ['name', 'like', '%' . $name . '%'];
        }
        if ($type !== '') {
            $where[] = ['type', '=', $type];
        }
        // 参加方式：1=小程序参加 2=扫码参加，两类活动分开管理，列表互不混显
        $join_type = $this->request->param('join_type', '', 'trim');
        if ($join_type === '') {
            $join_type = $this->joinType;
        }
        $where[] = ['join_type', '=', $join_type];

        $community_cate_list = ActiveCommunityCateModel::where('cate_group', 'qxl')
            ->whereNotIn('id', [6])
            ->order('list_order asc,id desc')
            ->select();
        $xsdld_cate_list = ActiveCommunityCateModel::where('cate_group', 'xsdld')
            ->order('list_order asc,id desc')
            ->select();
        $this->assign('community_cate_list', $community_cate_list);
        $this->assign('xsdld_cate_list', $xsdld_cate_list);
        $this->assign('cate_list', (new  ActiveCateModel())->select());
        return $where;
    }

    public function get_params()
    {
        $community_cate_list = ActiveCommunityCateModel::where('cate_group', 'qxl')
            ->whereNotIn('id', [6])
            ->order('list_order asc,id desc')
            ->select();
        $xsdld_cate_list = ActiveCommunityCateModel::where('cate_group', 'xsdld')
            ->order('list_order asc,id desc')
            ->select();
        $this->assign('community_cate_list', $community_cate_list);
        $this->assign('xsdld_cate_list', $xsdld_cate_list);
        $this->assign('cate_list', (new  ActiveCateModel())->select());
    }

    public function index1()
    {
        return redirect((string)url('index', ['type' => 1]));
    }

    public function index2()
    {
        return redirect((string)url('index', ['type' => 2]));
    }

    public function index3()
    {
        return redirect((string)url('index', ['type' => 3]));
    }

    /**
     * 签到列表：扫码参加的活动（join_type=2），不限制活动类型
     * @return mixed
     * @throws DbException
     */
    public function sign_list()
    {
        // 固定只查扫码参加的活动
        $this->joinType = 2;

        $params = $this->request->param();
        $params['join_type'] = 2;

        $list = $this->setIndex()->each(function ($item) {
            $item = $this->getList($item);
            $item['sign_count'] = ActiveClockModel::where('active_id', $item['id'])->count();
            return $item;
        });
        $this->assign('params', $params);
        $this->assign('list', $list);
        $list->appends($params);
        $this->assign('page', $list->render());

        return $this->fetch();
    }


    public function setData($item, $type = 1)
    {
        if ($type == 1) {
            if (!empty($item['date'])) $item['date'] = strtotime($item['date']);
        } else {
            if (!empty($item['date'])) $item['date'] = date('Y-m-d', $item['date']);
        }

        return $item;
    }

    //操作页面
    public function update()
    {
        $params = $this->request->param();

        $this->get_params();

        $this->assign('params', $params);
        $this->outParam();
        if (empty($params['id'])) {
            // 添加模式也渲染 update.html，赋空 item 避免模板读取 $item.xxx 报"未定义变量"
            $this->assign('item', []);
            return $this->fetch();
        }
        $item = $this->setEdit($params);
        if (!empty($item['content'])) {
            $item['content'] = htmlspecialchars_decode($item['content']);
        }
        if (!empty($item['role'])) {
            $item['role'] = explode(',', $item['role']);
        }
        $item = $this->getParamData($item);
        $this->assign('item', $item);
        $badge_list = '';
        $this->assign('badge_list', $badge_list);

        return $this->fetch();
    }

    /**
     * Web 式活动编辑：折叠字段未提交或为空时保留库内原值，避免只改封面时被清空。
     */
    protected function mergeWebActiveEditParams(array $params, array $existing): array
    {
        $copyKeys = [
            'event_kind', 'offline_sign_start', 'offline_sign_end', 'offline_sign_password',
            'is_clock', 'is_clock_challenge', 'clock_challenge_days', 'clock_challenge_point',
            'clock_start_time', 'clock_end_time', 'send_point', 'role', 'sign_day', 'is_badge',
            'badge_name', 'badge_image', 'point', 'share_point', 'phone', 'address', 'lat', 'lng',
            'flow', 'gain', 'suit_people', 'duration', 'day_type', 'area_range', 'content', 'date', 'is_date_pending',
            'is_month_recommend',
        ];
        foreach ($copyKeys as $key) {
            if (!array_key_exists($key, $params)) {
                if (array_key_exists($key, $existing)) {
                    $params[$key] = $existing[$key];
                }
                continue;
            }
            if ($params[$key] === '' || $params[$key] === null) {
                if (in_array($key, ['phone', 'address', 'lat', 'lng', 'content', 'flow', 'gain', 'badge_name', 'day_type', 'area_range', 'suit_people', 'duration'], true)
                    && isset($existing[$key]) && $existing[$key] !== '' && $existing[$key] !== null) {
                    $params[$key] = $existing[$key];
                }
            }
        }
        if (!array_key_exists('role', $params) && !empty($existing['role'])) {
            $params['role'] = $existing['role'];
        }
        if (($params['date'] ?? '') === '' && empty($params['is_date_pending']) && !empty($existing['date'])) {
            $params['date'] = $existing['date'];
        }
        if (!array_key_exists('is_date_pending', $params) && array_key_exists('is_date_pending', $existing)) {
            $params['is_date_pending'] = $existing['is_date_pending'];
        }
        return $params;
    }

    /** 生成活动小程序码；失败时不抛错，避免仅改封面等保存被阻断 */
    protected function tryGenerateActiveQrCode(string $scene, int $activeId, string $shareUrl): string
    {
        try {
            $path = CommonModel::setShareImage($scene, 'active_' . $activeId, 'dz1111', $shareUrl);
            return (is_string($path) && $path !== '') ? $path : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected function applyWebActiveCreateDefaults(array $params): array
    {
        if (!isset($params['point']) || $params['point'] === '') {
            $params['point'] = 0;
        }
        if (!isset($params['share_point']) || $params['share_point'] === '') {
            $params['share_point'] = 0;
        }
        if (empty($params['content'])) {
            $params['content'] = '<p></p>';
        }
        if (empty($params['is_date_pending']) && empty($params['date'])) {
            $params['is_date_pending'] = 1;
        }
        $params['event_kind'] = ActiveModel::EVENT_KIND_NORMAL;
        return $params;
    }

    //操作功能
    public function updatePost()
    {
        if ($this->request->isAjax()) {
            $params = $this->request->param();

            $params['display_mode'] = (int)($params['display_mode'] ?? ActiveModel::DISPLAY_MODE_CONFIG);
            if (!in_array($params['display_mode'], [ActiveModel::DISPLAY_MODE_CONFIG, ActiveModel::DISPLAY_MODE_WEB], true)) {
                $params['display_mode'] = ActiveModel::DISPLAY_MODE_CONFIG;
            }
            $isWebDisplay = $params['display_mode'] === ActiveModel::DISPLAY_MODE_WEB;
            $existingRow = null;
            if (!empty($params['id'])) {
                $existingRow = ActiveModel::where('id', (int)$params['id'])->find();
            }
            if ($isWebDisplay && $existingRow) {
                $params = $this->mergeWebActiveEditParams($params, $existingRow->toArray());
            }
            if ($isWebDisplay && empty($params['id'])) {
                $params = $this->applyWebActiveCreateDefaults($params);
            }
            if ($isWebDisplay) {
                // Web 式与配置式「正常/线下」无关，统一按正常活动存库，避免误触线下签到校验
                $params['event_kind'] = ActiveModel::EVENT_KIND_NORMAL;
            }

            if (isset($params['role']) && is_array($params['role'])) {
                $params['role'] = implode(',', $params['role']);
            }
            if (!empty($params['clock_start_time']) && !is_numeric($params['clock_start_time'])) {
                $params['clock_start_time'] = strtotime($params['clock_start_time']);
            }
            if (!empty($params['clock_end_time']) && !is_numeric($params['clock_end_time'])) {
                $params['clock_end_time'] = strtotime($params['clock_end_time']);
            }
            $params['event_kind'] = (int)($params['event_kind'] ?? ActiveModel::EVENT_KIND_NORMAL);
            if (!in_array($params['event_kind'], [ActiveModel::EVENT_KIND_NORMAL, ActiveModel::EVENT_KIND_OFFLINE], true)) {
                $params['event_kind'] = ActiveModel::EVENT_KIND_NORMAL;
            }
            if (!empty($params['offline_sign_start']) && !is_numeric($params['offline_sign_start'])) {
                $params['offline_sign_start'] = strtotime($params['offline_sign_start']);
            } elseif (empty($params['offline_sign_start'])) {
                $params['offline_sign_start'] = 0;
            }
            if (!empty($params['offline_sign_end']) && !is_numeric($params['offline_sign_end'])) {
                $params['offline_sign_end'] = strtotime($params['offline_sign_end']);
            } elseif (empty($params['offline_sign_end'])) {
                $params['offline_sign_end'] = 0;
            }
            $params['offline_sign_password'] = trim((string)($params['offline_sign_password'] ?? ''));
            if (!$isWebDisplay && $params['event_kind'] === ActiveModel::EVENT_KIND_OFFLINE) {
                $params['is_clock'] = 0;
                $params['is_clock_challenge'] = 0;
                $params['clock_challenge_days'] = 0;
                $params['clock_challenge_point'] = 0;
                $params['join_type'] = 1;
                if (empty($params['offline_sign_start']) || empty($params['offline_sign_end'])) {
                    $this->error('线下活动请在「活动类型」下方绿色区域填写「允许签到开始/结束」时间');
                }
                if ($params['offline_sign_end'] <= $params['offline_sign_start']) {
                    $this->error('签到结束时间须晚于开始时间');
                }
                if (empty($params['lat']) || empty($params['lng'])) {
                    $this->error('线下活动请在地图上设置活动地点经纬度');
                }
            }
            $params['is_clock_challenge'] = empty($params['is_clock_challenge']) ? 0 : 1;
            $params['clock_challenge_days'] = max(0, (int)($params['clock_challenge_days'] ?? 0));
            if (!$isWebDisplay && $params['event_kind'] !== ActiveModel::EVENT_KIND_OFFLINE && $params['is_clock_challenge']) {
                $params['is_clock'] = 1;
                $params['clock_challenge_point'] = 0;
                if ($params['clock_challenge_days'] < 1) {
                    $this->error('开启打卡兑礼时请填写所需打卡天数（至少 1 天）');
                }
                if (empty($params['clock_start_time']) || empty($params['clock_end_time'])) {
                    $this->error('打卡兑礼请填写下方「活动开始/结束日期」（打卡有效期）');
                }
            } elseif (!$params['is_clock_challenge']) {
                $params['clock_challenge_days'] = 0;
                $params['clock_challenge_point'] = 0;
            }
            $params['is_clock'] = empty($params['is_clock']) ? 0 : 1;
            $params['is_badge'] = empty($params['is_badge']) ? 0 : 1;
            $params['is_month_recommend'] = empty($params['is_month_recommend']) ? 0 : 1;
            $params['is_date_pending'] = empty($params['is_date_pending']) ? 0 : 1;
            if ($params['is_date_pending']) {
                if (empty($params['date'])) {
                    $params['date'] = 0;
                }
            } elseif (empty($params['date'])) {
                if ($isWebDisplay) {
                    $params['is_date_pending'] = 1;
                    $params['date'] = 0;
                } else {
                    $this->error('请填写活动截止日期，或勾选「时间待定」');
                }
            }

            $params['web_url'] = trim((string)($params['web_url'] ?? ''));
            $params['rules_url'] = trim((string)($params['rules_url'] ?? ''));
            $params['safety_url'] = trim((string)($params['safety_url'] ?? ''));
            if ($isWebDisplay) {
                if ($params['web_url'] === '') {
                    $this->error('Web 式活动请填写 H5 地址');
                }
                if (!preg_match('#^https?://#i', $params['web_url'])) {
                    $this->error('Web 式活动 H5 地址须以 http:// 或 https:// 开头');
                }
            } else {
                $params['web_url'] = '';
            }

            $params = $this->setParamData($params);
            $share_url = 'pages/activity/lsxf/details-new';
            if (empty($params['id'])) {
                $result = ActiveModel::create($params);
                $scene = (string)$result['id'];
                $qr_code = $this->tryGenerateActiveQrCode($scene, (int)$result['id'], $share_url);
                if ($qr_code !== '') {
                    $this->model->where('id', $result['id'])->update(['qr_code' => $qr_code]);
                }
            } else {
                $item = ActiveModel::where('id', $params['id'])->find();
                if (empty($item['qr_code'])) {
                    $scene = (string)$params['id'];
                    $qr_code = $this->tryGenerateActiveQrCode($scene, (int)$params['id'], $share_url);
                    if ($qr_code !== '') {
                        $params['qr_code'] = $qr_code;
                    }
                }
                $result = $this->setEditPost($params);
            }


            $this->getMsg(!empty($result));
        } else {
            $this->error('非法请求');
        }
    }
}