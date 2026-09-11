<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

/**
 * PC 后台 - AI 工作流
 *
 * 规划中的 AI 能力入口。当前为空白占位页，只在模板里沉淀"这个页面能做什么"的场景清单，
 * 暂未接入任何模型、不读写业务数据。等具体场景确定后再在这里逐个落地。
 */
class AiWorkflowController extends AdminBaseController
{
    /** 规划说明页（暂无实际功能） */
    public function index()
    {
        return $this->fetch('index');
    }
}
