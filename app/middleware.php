<?php
// +----------------------------------------------------------------------
// | 全局中间件
// +----------------------------------------------------------------------
// | 注意：本文件只有 public/index.php 这个入口会加载它
// |      （think\Http::loadMiddleware 读的是 getBasePath().'middleware.php'，
// |        而 getBasePath 取的是 APP_NAMESPACE，public/index.php 没有定义它，
// |        所以等于 app/；public/api.php 里定义了 APP_NAMESPACE=api，
// |        小程序接口走的是 api/middleware.php，与本文件无关）
// |
// | 加载顺序还多一步：cmf-route/src/Http.php::loadMiddleware() 会先引入本文件，
// | 再引入 vendor/thinkcmf/cmf-{根命名空间}/src/middleware.php（即 cmf-app 的，
// | 里面是 SessionInit）。两个数组合并成一个队列，越靠前越"外层"、越先执行。
// +----------------------------------------------------------------------

return [
    // !!! 必须放在第一位 !!!
    // SessionInit 原本只来自 cmf-app 的 middleware.php，排在下面 AdminDeleteGuard 之后，
    // 也就是跑在守卫"里面"。而 Pipeline 是洋葱模型，守卫属于外层，会在 SessionInit
    // 完成 session 绑定之前就去读写 session —— 此时 Store 还没加载 PHPSESSID 对应的数据，
    // 于是守卫每次请求都生成一个新令牌，和页面下发给前端的令牌永远对不上，
    // 「带上正确令牌」也会被拦。显式写在这里让它排到最外层先执行。
    // （下方 cmf-app 里重复的那份会被 Middleware::add() 里的 array_unique 去重，不会重复执行）
    \think\middleware\SessionInit::class,

    // 后台「删除类」请求的服务端二次校验
    // 前端弹窗（admin-delete-confirm.js）只能防误点，防不了直接调接口，
    // 校验必须放在服务端，这里就是那个关卡。必须排在 SessionInit 之后（数组越后越内层）。
    \app\middleware\AdminDeleteGuard::class,
];
