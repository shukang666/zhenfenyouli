<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

namespace plugins\form\controller;

//Demo插件英文名，改成你的插件英文就行了

use cmf\controller\PluginAdminBaseController;
use plugins\form\model\FormModel;
use think\Model;

/**
 * Class AdminIndexController.
 */
class AdminIndexController extends PluginAdminBaseController
{
    /**
     * @var Model
     */
    private $model;

    protected function initialize()
    {
        parent::initialize();
        $adminId = cmf_get_current_admin_id(); //获取后台管理员id，可判断是否登录
        if (!empty($adminId)) {
            $this->assign('admin_id', $adminId);
        }

        $this->model = new FormModel();
    }

    /**
     * 列表
     */
    public function index()
    {


        return $this->fetch('/setting');
    }

    /**
     * 设置
     */
    public function setting()
    {

        return $this->fetch('/setting');
    }


    public function settingPost()
    {

        $parm = $this->request->param();
        $result = $this->validate($parm, 'Form');

        if ($result !== true) {
            $this->error($result);
        }
        $controllers = $parm['controllers'];

        // 得到表中的所有字段
        $fields = $this->model->getField($parm['table_name']);
        if (count($fields) < 2) {
            $this->error("获取表中的字段少于2");
        }
        foreach ($fields as $key => $val) {
            $field = '{$vo.' . $val["COLUMN_NAME"] . '}';
            $fields[$key]['field'] = $field;
            if (strpos($val['DATA_TYPE'], 'int') !== false) {
                $fields[$key]['type'] = 'number';
            } else {
                $fields[$key]['type'] = 'text';
            }

            if (strpos($val['COLUMN_COMMENT'], ',') !== false) {
                $val['COLUMN_COMMENT'] = substr($val['COLUMN_COMMENT'], 0, strpos($val['COLUMN_COMMENT'], ','));
            }

            $fields[$key]['COLUMN_COMMENT'] = $val['COLUMN_COMMENT'];
            $fields[$key]['comment_name'] = $val['COLUMN_COMMENT'];

            //default
            $fields[$key]['value'] = "{\${$val['COLUMN_NAME']}|default=''}";
        }

        $this->build_base_file();

        $controller_content = $this->controllerViews($parm, $controllers, $fields);

        $res1 = $this->model->template_build(APP_PATH . "/admin/controller/" . ucfirst($parm['controllers']) . 'Controller.php', $controller_content);

        $model_content = $this->modelViews($parm, $controllers, $fields);
        $res2 = $this->model->template_build(APP_PATH . "/admin/model/" . ucfirst($parm['model_name']) . '.php', $model_content);

        $res3 = $this->model->template_View($parm,
            $this->indexViews($parm, $controllers, $fields),

            $this->updateViews($parm, $controllers, $fields)
//            $this->editViews($parm, $controllers, $fields)
//            $this->addViews($parm, $controllers, $fields),
//            $this->formViews($parm, $controllers, $fields)
        );

        if ($res1 && $res2 && $res3
        ) {
            $this->success("生成成功");
        }

        $this->success("生成失败");

    }

    public function build_base_file()
    {

        $copy_func = static function (string $form, string $to_file) {
            if (file_exists($to_file)) {
                return true;
            }
            $to = dirname($to_file);

            if (!is_dir($to)) {
                if (!mkdir($to, 0777, true) && !is_dir($to)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $to));
                }
            }
            if (!copy($form, $to_file)) {
                throw new \RuntimeException("copy is error");
            }
        };

        $copy_func(CMF_ROOT . "/public/plugins/form/view/template/controller/BaseController.php",
            CMF_ROOT . '/app/admin/controller/BaseController.php');

//		$copy_func(CMF_ROOT."/public/plugins/form/view/template/model/Base.php",
//			CMF_ROOT.'/app/common/model/Base.php');

    }

    private function controllerViews($parm, $controllers, $fields)
    {

        $content = file_get_contents(CMF_ROOT . 'public/plugins/form/view/template/controller/FooController.html');

        $root_menu_name = '';
        if ($parm['root_menu_name']) {
            $root_menu_name = <<<EOF
/**
    * @adminMenuRoot(
    *     'name'   =>'{$parm['root_menu_name']}',
    *     'action' =>'default',
    *     'parent' =>'',
    *     'display'=> true,
    *     'order'  => 10000,
    *     'icon'   =>'cogs',
    *     'remark' =>''
    * )
    */
EOF;
        }
        $content = str_replace(
            ['%root_menu_name%', '%menu%', '%menu_parent%', '%controller_name%', '%model_name%'],
            [$root_menu_name, $parm['menu'], $parm['menu_parent'], $parm['controllers'], $parm['model_name']],
            $content
        );
        return $content;
    }


    private function modelViews($parm, $controllers, $fields)
    {
        $content = file_get_contents(CMF_ROOT . '/public/plugins/form/view/template/model/Foo.html');

        $this->assign('model_name', $parm['model_name']);
        $this->assign('table_name', $parm['table_name']);

        $content = str_replace(
            ['%model_name%', '%table_name%'],
            [$parm['model_name'], $parm['table_name']],
            $content
        );
        return $content;
    }

    protected function indexViews($parm, $controllers, $fields)
    {


        $this->assign('head', ' <include file="public@header"/>');
        $this->assign('foreach', ' <foreach name="list" item="vo">');
        $this->assign('foreachEnd', '</foreach>');
        $this->assign('id', '{$vo.id}');
        $this->assign('page', '<div class="pagination">{$page|default=\'\'}</div>');
        //字段
        $this->assign('fields', $fields);
        $this->assign("url_list", "{:url('index')}");
        $this->assign("url_add", "{:url('add')}");
        $this->assign("url_edit", "{:url('edit',array('id'=>\$vo['id']))}");
        $this->assign("url_delete_all", "{:url('delete')}");
        $this->assign("url_delete", "{:url('delete',array('id'=>\$vo['id']))}");

        $this->assign('fields', array_filter($fields, static function ($item) {

            $not_show = ['delete_time'];
            if (in_array(strtolower($item['COLUMN_NAME']), $not_show)) {
                return false;
            }
            return true;
        }));


        return $this->fetch("/template/view/index");
    }

    protected function addViews($parm, $controllers, $fields)
    {
        $this->assign('controllers_name', $this->model->uncamelize($controllers));//控制器
        $this->assign('head', ' <include file="public@header"/>');

        $this->assign("url_list", "{:url('index')}");
        $this->assign("url_add", "{:url('add')}");
        $this->assign("url_add_post", "{:url('add_post')}");

        return $this->fetch("/template/view/add");
    }

    protected function editViews($parm, $controllers, $fields)
    {
        $this->assign('head', ' <include file="public@header"/>');
        $this->assign('id', '{$id}');
        $this->assign('controllers_name', $this->model->uncamelize($controllers));//控制器

        $this->assign("url_list", "{:url('index')}");
        $this->assign("url_add", "{:url('add')}");
        $this->assign("url_edit_post", "{:url('edit_post')}");

        return $this->fetch("/template/view/edit");
    }

    protected function updateViews($parm, $controllers, $fields)
    {
        $this->assign('head', ' <include file="public@header"/>');
        $this->assign('id', '{$id}');
        $this->assign('controllers_name', $this->model->uncamelize($controllers));//控制器
        //字段
        $this->assign('fields', $fields);
        $this->assign("url_list", "{:url('index')}");
        $this->assign("url_add", "{:url('update')}");
        $this->assign("url_edit_post", "{:url('updatePost')}");

        return $this->fetch("/template/view/update");
    }

    protected function formViews($parm, $controllers, $fields)
    {

        $this->assign('fields', array_filter($fields, static function ($item) {

            $not_show = ['id', 'create_time', 'update_time', 'delete_time'];
            if (in_array(strtolower($item['COLUMN_NAME']), $not_show)) {
                return false;
            }
            return true;
        }));

        return $this->fetch("/template/view/form");
    }

}
