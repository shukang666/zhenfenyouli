<?php
// 覆盖 vendor：admin_fengiy 首页模板依赖 $set_config（应用名称/Logo）。
namespace app\admin\controller;

use app\admin\model\AdminMenuModel;
use app\admin\service\AdminMenuService;
use cmf\controller\AdminBaseController;

class IndexController extends AdminBaseController
{
    /** 菜单树缓存键版本（调整 cmf_admin_menu 层级后需递增以刷新侧栏） */
    private const ADMIN_MENU_CACHE_PREFIX = 'admin_menus_v2_';

    public function initialize()
    {
        $adminSettings = cmf_get_option('admin_settings');

        if (
            empty($adminSettings['admin_password'])
            || ($adminSettings['admin_password'] == str_replace('.html', '', $this->request->pathinfo()))
        ) {
            $adminId = cmf_get_current_admin_id();
            if (empty($adminId)) {
                session('__LOGIN_BY_CMF_ADMIN_PW__', 1);
            }
        }

        parent::initialize();
    }

    public function index(AdminMenuService $service)
    {
        $content = hook_one('admin_index_index_view');
        if (!empty($content)) {
            return $content;
        }

        $adminMenuModel = new AdminMenuModel();
        $menuCacheKey   = self::ADMIN_MENU_CACHE_PREFIX . cmf_get_current_admin_id();
        $menus          = cache($menuCacheKey, '', null, 'admin_menus');

        if (empty($menus)) {
            $menus = $adminMenuModel->menuTree();
            cache($menuCacheKey, $menus, null, 'admin_menus');
        }

        $this->assign('menus', $menus);

        $result   = $service->getAll();
        $menusTmp = [];
        foreach ($result as $item) {
            $indexTmp            = $item['app'] . $item['controller'] . $item['action'];
            $indexTmp            = preg_replace('/[\\/|_]/', '', $indexTmp);
            $indexTmp            = strtolower($indexTmp);
            $menusTmp[$indexTmp] = $item;
        }
        $this->assign('menus_js_var', json_encode($menusTmp));
        $this->assign('set_config', $this->buildSetConfig());

        return $this->fetch();
    }

    /**
     * @return array{app_name:string,app_logo:string}
     */
    protected function buildSetConfig(): array
    {
        $setConfig = cmf_get_option('set_config');
        if (!is_array($setConfig)) {
            $setConfig = [];
        }
        $siteInfo = cmf_get_option('site_info');
        if (!is_array($siteInfo)) {
            $siteInfo = [];
        }

        return array_merge(
            [
                'app_name' => $siteInfo['site_name'] ?? '管理后台',
                'app_logo' => '',
            ],
            $setConfig
        );
    }
}
