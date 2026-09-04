<?php

namespace app\admin\controller;

use app\admin\model\active\ActiveClockActionFormModel;
use app\admin\model\active\ActiveClockActionModel;
use app\admin\model\active\ActiveClockActionUserModel;
use app\admin\model\active\ActiveClockPushModel;
use app\admin\model\active\ActiveClockPushUserModel;
use app\admin\model\active\ActiveModel;
use app\admin\model\member\MemberModel;
use think\App;
use think\db\exception\DbException;
use think\facade\Db;

/**
 * 活动打卡动作配置控制器
 */
class ActiveClockActionController extends ToolController
{
    protected $actionModel;
    protected $userModel;
    protected $pushModel;
    protected $pushUserModel;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->actionModel = new ActiveClockActionModel();
        $this->userModel = new ActiveClockActionUserModel();
        $this->pushModel = new ActiveClockPushModel();
        $this->pushUserModel = new ActiveClockPushUserModel();
    }

    /**
     * 动作配置列表入口 - 弹窗页面
     * 从活动列表点击"打卡动作"按钮触发
     */
    public function index()
    {
        $activeId = $this->request->param('active_id', 0, 'intval');
        if (empty($activeId)) {
            $this->error('活动ID不能为空');
        }

        $active = ActiveModel::where('id', $activeId)->find();
        if (!$active) {
            $this->error('活动不存在');
        }

        // 获取所有动作配置
        $actions = $this->actionModel->getActiveActions($activeId);

        // 加载每个动作的表单字段
        foreach ($actions as &$action) {
            $action['forms'] = ActiveClockActionFormModel::where('action_id', $action['id'])
                ->where('delete_time', 0)
                ->order('list_order asc, id asc')
                ->select()
                ->toArray();

            // 解码选项
            foreach ($action['forms'] as &$form) {
                $form['field_options_arr'] = json_decode($form['field_options'] ?? '[]', true);
                if (!is_array($form['field_options_arr'])) {
                    $form['field_options_arr'] = [];
                }
            }

            // 格式化时效
            $action['expire_time_text'] = empty($action['expire_time']) ? '永久有效' : date('Y-m-d H:i', $action['expire_time']);
        }

        $this->assign('active_id', $activeId);
        $this->assign('active_name', $active['name']);
        $this->assign('actions', $actions);
        $this->assign('actions_json', json_encode($actions, JSON_UNESCAPED_UNICODE));
        $this->assign('notify_type_options', ActiveClockActionModel::getNotifyTypeOptions());
        $this->assign('field_type_options', ActiveClockActionFormModel::getFieldTypeOptions());

        return $this->fetch('active/clock_action_index');
    }

    /**
     * 保存动作配置（AJAX）
     */
    public function save()
    {
        if (!$this->request->isAjax()) {
            $this->error('非法请求');
        }

        $activeId = $this->request->param('active_id', 0, 'intval');
        if (empty($activeId)) {
            $this->error('活动ID不能为空');
        }

        $actionsData = $this->request->param('actions', []);
        if (empty($actionsData) || !is_array($actionsData)) {
            $this->error('请至少配置一个打卡动作');
        }

        // 验证输入
        foreach ($actionsData as $action) {
            if (empty($action['clock_count']) || intval($action['clock_count']) <= 0) {
                $this->error('打卡次数必须大于0');
            }
            if (!in_array($action['notify_type'] ?? 1, [1, 2])) {
                $this->error('通知类型无效');
            }
        }

        Db::startTrans();
        try {
            // 删除该活动下所有旧动作配置
            $oldIds = $this->actionModel->where('active_id', $activeId)->column('id');
            if (!empty($oldIds)) {
                ActiveClockActionFormModel::whereIn('action_id', $oldIds)->update(['delete_time' => time()]);
            }
            $this->actionModel->where('active_id', $activeId)->update(['delete_time' => time()]);

            $now = time();
            foreach ($actionsData as $index => $action) {
                $expireTime = 0;
                if (!empty($action['expire_date'])) {
                    $expireTime = strtotime($action['expire_date'] . ' 23:59:59');
                    if ($expireTime === false) $expireTime = 0;
                }

                $actionRecord = $this->actionModel->create([
                    'active_id' => $activeId,
                    'clock_count' => intval($action['clock_count']),
                    'is_continuous' => empty($action['is_continuous']) ? 0 : 1,
                    'expire_time' => $expireTime,
                    'notify_type' => intval($action['notify_type']),
                    'status' => empty($action['status']) ? 1 : intval($action['status']),
                    'list_order' => $index,
                    'template_id' => trim($action['template_id'] ?? ''),
                    'create_time' => $now,
                    'update_time' => $now,
                ]);

                // 保存表单字段
                if (!empty($action['forms']) && is_array($action['forms'])) {
                    ActiveClockActionFormModel::batchSave($actionRecord['id'], $action['forms']);
                }
            }

            Db::commit();
            $this->success('保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('保存失败：' . $e->getMessage());
        }
    }

    /**
     * 用户提交列表
     */
    public function user_list()
    {
        $export = $this->request->param('export', 0, 'intval');
        $activeId = $this->request->param('active_id', 0, 'intval');
        $actionId = $this->request->param('action_id', 0, 'intval');
        $keyword = $this->request->param('keyword', '', 'trim');

        $sql = $this->userModel->alias('a')
            ->join('member b', 'a.user_id = b.id', 'LEFT')
            ->join('active c', 'a.active_id = c.id', 'LEFT')
            ->join('active_clock_action d', 'a.action_id = d.id', 'LEFT')
            ->field('a.*, b.nickname, b.mobile, c.name as active_name, d.clock_count, d.notify_type');

        if ($activeId) {
            $sql = $sql->where('a.active_id', $activeId);
        }
        if ($actionId) {
            $sql = $sql->where('a.action_id', $actionId);
        }
        if ($keyword) {
            $sql = $sql->where('b.nickname|b.mobile|a.user_id', 'like', '%' . $keyword . '%');
        }

        $sql = $sql->order('a.id desc');

        if ($export) {
            $list = $sql->select();
        } else {
            $list = $sql->paginate(15);
        }

        $notifyTypeOptions = ActiveClockActionModel::getNotifyTypeOptions();

        $list->each(function ($item) use ($notifyTypeOptions) {
            $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['notify_type_text'] = $notifyTypeOptions[$item['notify_type']] ?? '';
            $item['form_data_arr'] = json_decode($item['form_data'] ?? '[]', true);
            if (!is_array($item['form_data_arr'])) {
                $item['form_data_arr'] = [];
            }
            return $item;
        });

        // 获取活动列表（用于筛选）
        $activeList = ActiveModel::where('delete_time', 0)
            ->where('status', 1)
            ->where('type', 1)
            ->order('id desc')
            ->column('name', 'id');

        // 导出
        if ($export) {
            $headers = ['ID', '活动名称', '打卡次数', '通知类型', '用户昵称', '手机号', '提交时间', '表单数据'];
            $keys = ['id', 'active_name', 'clock_count', 'notify_type_text', 'nickname', 'mobile', 'create_time_text', 'form_data'];
            $this->exportCsv('用户动作提交_' . date('Y-m-d'), $list->toArray(), $headers, $keys);
            exit;
        }

        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('active_list', $activeList);
        $this->assign('params', $this->request->param());

        return $this->fetch('active/clock_action_user_list');
    }

    /**
     * 推送管理列表
     */
    public function push_list()
    {
        $export = $this->request->param('export', 0, 'intval');
        $activeId = $this->request->param('active_id', 0, 'intval');
        $actionId = $this->request->param('action_id', 0, 'intval');

        $sql = $this->pushModel->alias('a')
            ->join('active b', 'a.active_id = b.id', 'LEFT')
            ->join('active_clock_action c', 'a.action_id = c.id', 'LEFT')
            ->field('a.*, b.name as active_name, c.clock_count');

        if ($activeId) {
            $sql = $sql->where('a.active_id', $activeId);
        }
        if ($actionId) {
            $sql = $sql->where('a.action_id', $actionId);
        }

        $sql = $sql->order('a.id desc');

        if ($export) {
            $list = $sql->select();
        } else {
            $list = $sql->paginate(15);
        }

        $list->each(function ($item) {
            $item['create_time_text'] = date('Y-m-d H:i:s', $item['create_time']);
            $item['push_time_text'] = empty($item['push_time']) ? '-' : date('Y-m-d H:i:s', $item['push_time']);
            $item['push_status_text'] = $item['push_status'] == 1 ? '已推送' : '未推送';
            $item['push_fields_arr'] = json_decode($item['push_fields'] ?? '[]', true);
            if (!is_array($item['push_fields_arr'])) {
                $item['push_fields_arr'] = [];
            }
            return $item;
        });

        // 获取活动列表
        $activeList = ActiveModel::where('delete_time', 0)
            ->where('status', 1)
            ->where('type', 1)
            ->order('id desc')
            ->column('name', 'id');

        if ($export) {
            $headers = ['ID', '活动名称', '打卡次数', '推送标题', '推送状态', '创建时间', '推送时间'];
            $keys = ['id', 'active_name', 'clock_count', 'push_title', 'push_status_text', 'create_time_text', 'push_time_text'];
            $this->exportCsv('推送记录_' . date('Y-m-d'), $list->toArray(), $headers, $keys);
            exit;
        }

        $this->assign('list', $list);
        $this->assign('page', $list->render());
        $this->assign('active_list', $activeList);
        $this->assign('params', $this->request->param());

        return $this->fetch('active/clock_action_push_list');
    }

    /**
     * 创建/编辑推送配置（弹窗）
     */
    public function push_edit()
    {
        $id = $this->request->param('id', 0, 'intval');
        $actionId = $this->request->param('action_id', 0, 'intval');

        $item = null;
        if ($id) {
            $item = $this->pushModel->where('id', $id)->find();
            if ($item) {
                $item = $item->toArray();
                $item['push_fields_arr'] = json_decode($item['push_fields'] ?? '[]', true);
                if (!is_array($item['push_fields_arr'])) {
                    $item['push_fields_arr'] = [];
                }
            }
        }

        // 获取动作关联的活动信息
        $action = null;
        $active = null;
        if ($actionId) {
            $action = ActiveClockActionModel::where('id', $actionId)->find();
            if ($action) {
                $active = ActiveModel::where('id', $action['active_id'])->find();
            }
        }

        $this->assign('item', $item);
        $this->assign('action', $action);
        $this->assign('active', $active);
        $this->assign('item_json', json_encode($item ?? [], JSON_UNESCAPED_UNICODE));

        return $this->fetch('active/clock_action_push_edit');
    }

    /**
     * 保存推送配置（AJAX）
     */
    public function push_save()
    {
        if (!$this->request->isAjax()) {
            $this->error('非法请求');
        }

        $id = $this->request->param('id', 0, 'intval');
        $actionId = $this->request->param('action_id', 0, 'intval');
        $pushTitle = $this->request->param('push_title', '', 'trim');
        $pushFields = $this->request->param('push_fields', []);

        if (empty($actionId)) {
            $this->error('动作ID不能为空');
        }
        if (empty($pushTitle)) {
            $this->error('推送标题不能为空');
        }

        // 过滤空字段
        $pushFields = array_values(array_filter($pushFields, function ($field) {
            return !empty($field['label']) && isset($field['value']);
        }));

        $data = [
            'action_id' => $actionId,
            'push_title' => $pushTitle,
            'push_fields' => json_encode($pushFields, JSON_UNESCAPED_UNICODE),
        ];

        if ($id) {
            $data['update_time'] = time();
            $this->pushModel->where('id', $id)->update($data);
        } else {
            $action = ActiveClockActionModel::where('id', $actionId)->find();
            if (!$action) {
                $this->error('动作配置不存在');
            }
            $data['active_id'] = $action['active_id'];
            $data['create_time'] = time();
            $this->pushModel->create($data);
        }

        $this->success('保存成功');
    }

    /**
     * 执行推送（AJAX）
     * 向已订阅的用户发送微信小程序服务消息
     */
    public function do_push()
    {
        if (!$this->request->isAjax()) {
            $this->error('非法请求');
        }

        $pushId = $this->request->param('push_id', 0, 'intval');
        $push = $this->pushModel->where('id', $pushId)->find();
        if (!$push) {
            $this->error('推送配置不存在');
        }

        if ($push['push_status'] == 1) {
            $this->error('该推送已执行过');
        }

        // 获取动作配置
        $action = ActiveClockActionModel::where('id', $push['action_id'])->find();
        if (!$action) {
            $this->error('动作配置不存在');
        }

        // 获取已订阅用户
        $userIds = $this->pushUserModel->getSubscribedUsers($push['action_id'], $push['active_id']);
        if (empty($userIds)) {
            $this->error('暂无已订阅的用户');
        }

        // 解码推送字段
        $pushFields = json_decode($push['push_fields'] ?? '[]', true);
        if (!is_array($pushFields)) {
            $pushFields = [];
        }

        // 读取动作配置的模板ID
        $templateId = $action['template_id'] ?? '';

        // 调用微信推送
        $pushResult = $this->sendWechatMessage($userIds, $push['push_title'], $pushFields, $pushId, $templateId);

        // 更新推送状态
        $this->pushModel->where('id', $pushId)->update([
            'push_status' => 1,
            'push_time' => time(),
        ]);

        $this->success("推送完成，共" . count($userIds) . "人，成功：{$pushResult['success']}，失败：{$pushResult['fail']}");
    }

    /**
     * 发送微信服务消息
     * @param array $userIds 用户ID列表
     * @param string $title 推送标题
     * @param array $fields 推送字段
     * @param int $pushId 推送记录ID
     * @return array ['success' => int, 'fail' => int]
     */
    protected function sendWechatMessage(array $userIds, string $title, array $fields, int $pushId, string $templateId = ''): array
    {
        if (empty($templateId)) {
            return ['success' => 0, 'fail' => count($userIds)];
        }

        $success = 0;
        $fail = 0;

        foreach ($userIds as $userId) {
            try {
                $member = MemberModel::where('id', $userId)->where('status', 1)->find();
                if (!$member || empty($member['openid'])) {
                    $fail++;
                    $this->pushUserModel->markPushFailed($pushId, $userId, '用户openid不存在');
                    continue;
                }

                // 调用微信订阅消息推送接口
                $result = $this->callWechatSubscribeMessage($member['openid'], $title, $fields, $templateId);

                if ($result) {
                    $this->pushUserModel->markPushed($pushId, $userId);
                    $success++;
                } else {
                    $this->pushUserModel->markPushFailed($pushId, $userId, '推送失败');
                    $fail++;
                }
            } catch (\Exception $e) {
                $this->pushUserModel->markPushFailed($pushId, $userId, $e->getMessage());
                $fail++;
            }
        }

        return ['success' => $success, 'fail' => $fail];
    }

    /**
     * 调用微信订阅消息接口
     * @param string openid 用户openid
     * @param string title 消息标题
     * @param array fields 推送字段
     * @return bool
     */
    protected function callWechatSubscribeMessage(string $openid, string $title, array $fields, string $templateId): bool
    {
        $appid = env('WECHAT_MINI_APPID', '');
        $secret = env('WECHAT_MINI_SECRET', '');

        if (empty($appid) || empty($secret) || empty($templateId)) {
            return false;
        }

        // 获取access_token
        $tokenUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $tokenResult = $this->httpGet($tokenUrl);
        $tokenData = json_decode($tokenResult, true);

        if (empty($tokenData['access_token'])) {
            return false;
        }

        $accessToken = $tokenData['access_token'];

        // 构造订阅消息数据
        $messageData = [
            'touser' => $openid,
            'template_id' => $templateId,
            'page' => 'pages/low_carbon/lsxf/details-new',
            'data' => [],
        ];

        // 根据字段生成消息数据
        foreach ($fields as $index => $field) {
            $key = 'keyword' . ($index + 1);
            if ($index < 6) { // 微信订阅消息最多支持6个字段
                $messageData['data'][$key] = ['value' => ($field['label'] ?? '') . '：' . ($field['value'] ?? '')];
            }
        }

        // 发送订阅消息
        $sendUrl = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        $sendResult = $this->httpPost($sendUrl, json_encode($messageData, JSON_UNESCAPED_UNICODE));
        $sendData = json_decode($sendResult, true);

        return isset($sendData['errcode']) && $sendData['errcode'] == 0;
    }

    /**
     * HTTP GET请求
     */
    protected function httpGet(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ?: '';
    }

    /**
     * HTTP POST请求
     */
    protected function httpPost(string $url, string $data): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result ?: '';
    }

    /**
     * 删除推送记录
     */
    public function push_delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        $this->pushModel->where('id', $id)->delete();
        ActiveClockPushUserModel::where('push_id', $id)->delete();
        $this->success('删除成功');
    }

    /**
     * 导出CSV
     */
    protected function exportCsv(string $filename, array $list, array $headers, array $keys)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $filename .= '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        // BOM for UTF-8
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($list as $row) {
            $line = [];
            foreach ($keys as $key) {
                $value = $row[$key] ?? '';
                if (is_array($value)) {
                    $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                }
                $line[] = $value;
            }
            fputcsv($output, $line);
        }
        fclose($output);
        exit;
    }
}
