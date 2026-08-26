<?php /*a:2:{s:43:"themes/admin_fengiy/admin\member\index.html";i:1787706314;s:79:"D:\phpstudy_pro\WWW\xiayuxuan.com\public/themes/admin_fengiy/public\header.html";i:1693132508;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <!-- Set render engine for 360 browser -->
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="/static/layui/css/layui.css" rel="stylesheet" type="text/css">
    <script src="/static/layui/layui.js" type="text/javascript"></script>

    <script type="text/javascript" src="/themes/admin_fengiy/public/assets/js/fingerprint2.min.js"></script>
    <!-- HTML5 shim for IE8 support of HTML5 elements -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <![endif]-->
    <link href="/themes/admin_fengiy/public/assets/themes/<?php echo cmf_get_admin_style(); ?>/bootstrap.min.css" rel="stylesheet">
    <link href="/themes/admin_fengiy/public/assets/simpleboot3/css/simplebootadmin.css" rel="stylesheet">
    <link href="/static/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!--[if lt IE 9]>
    <script src="https://cdn.bootcss.com/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        form .input-order {
            margin-bottom: 0px;
            padding: 0 2px;
            width: 42px;
            font-size: 12px;
        }

        form .input-order:focus {
            outline: none;
        }

        .table-actions {
            margin-top: 5px;
            margin-bottom: 5px;
            padding: 0px;
        }

        .table-list {
            margin-bottom: 0px;
        }

        .form-required {
            color: red;
        }
    </style>
    <?php 
        $cmf_version=cmf_version();
        if (strpos(cmf_version(), '6.') === 0) {
            $_app=app()->http->getName();
        }else{
            $_app=request()->module();
        }
     ?>
    <script type="text/javascript">
        //全局变量
        var GV = {
            ROOT: "/",
            WEB_ROOT: "/",
            JS_ROOT: "static/js/",
            APP: '<?php echo $_app; ?>'/*当前应用名*/
        };
    </script>
    <script src="/themes/admin_fengiy/public/assets/js/jquery-1.10.2.min.js"></script>
    <script src="/static/js/wind.js"></script>
    <script src="/themes/admin_fengiy/public/assets/js/bootstrap.min.js"></script>
    <script>
        Wind.css('artDialog');
        Wind.css('layer');
        $(function () {
            $("[data-toggle='tooltip']").tooltip({
                container:'body',
                html:true,
            });
            $("li.dropdown").hover(function () {
                $(this).addClass("open");
            }, function () {
                $(this).removeClass("open");
            });
        });
    </script>
    <?php if(APP_DEBUG): ?>
        <style>
            #think_page_trace_open {
                z-index: 9999;
            }
        </style>
    <?php endif; ?>

</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('index'); ?>">用户列表</a></li>
    </ul>

    <!-- 搜索和排序表单 -->
    <form class="well form-inline margin-top-20 layui-form" method="get" action="<?php echo url('index'); ?>">
        <div class="layui-inline">
            关键字：
            <input type="text" class="form-control" name="keyword" style="width: 140px;"
                   value="<?php echo input('request.keyword/s',''); ?>"  placeholder="请输入名称">
        </div>

        <?php 
            $community_list= cmf_table('member_community')->where('delete_time',0)->select();
         ?>
        小区：
        <div class="form-group layui-form">
            <select name="community_id" lay-search id="user_list" lay-filter="user_list">
                <option value="">全部</option>
                <?php if(is_array($community_list) || $community_list instanceof \think\Collection || $community_list instanceof \think\Paginator): if( count($community_list)==0 ) : echo "" ;else: foreach($community_list as $key=>$vo): if(!(empty($params['community_id']) || (($params['community_id'] instanceof \think\Collection || $params['community_id'] instanceof \think\Paginator ) && $params['community_id']->isEmpty()))): ?>
                        <option value="<?php echo $vo['id']; ?>"
                        <?php if($params['community_id'] == $vo['id']): ?>selected<?php endif; ?>
                        ><?php echo $vo['name']; ?></option>
                        <?php else: ?>
                        <option value="<?php echo $vo['id']; ?>"><?php echo $vo['name']; ?></option>
                    <?php endif; ?>
                <?php endforeach; endif; else: echo "" ;endif; ?>
            </select>
        </div>



        <input type="submit" class="btn btn-primary" value="搜索" style="margin-left: 25px"/>
        <a class="btn btn-danger" href="<?php echo url('index'); ?>">重置</a>
        <a class="btn btn-success" href="<?php echo url('outData',input('get.')); ?>">导出</a>
    </form>

    <!-- 用户列表表格 -->
    <table class="table table-hover table-bordered margin-top-20">
        <thead>
        <tr>
            <th width="50">ID</th>
            <th>昵称</th>
            <th>小区</th>
            <th width="80">头像</th>
            <th width="120">手机号</th>
            <th width="100">
                <a href="<?php echo url('index', array_merge(input('get.'), ['sort_field'=>'point', 'sort_order'=>input('sort_order/s')=='asc'?'desc':'asc'])); ?>">
                    积分
                    <?php if(input('sort_field/s') == 'point'): ?>
                        <?php echo input('sort_order/s') == 'asc' ? '↑' : '↓'; ?>
                    <?php endif; ?>
                </a>
            </th>
            <th width="160">
                <a href="<?php echo url('index', array_merge(input('get.'), ['sort_field'=>'create_time', 'sort_order'=>input('sort_order/s')=='asc'?'desc':'asc'])); ?>">
                    创建时间
                    <?php if(input('sort_field/s') == 'create_time'): ?>
                        <?php echo input('sort_order/s') == 'asc' ? '↑' : '↓'; ?>
                    <?php endif; ?>
                </a>
            </th>
            <th width="200"><?php echo lang('ACTIONS'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
            <tr>
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['nickname']; ?></td>
                <td><?php echo $vo['community_name']; ?></td>
                <td>
                    <?php if(!empty($vo['avatar'])): ?>
                        <a href="javascript:imagePreviewDialog('<?php echo cmf_get_image_preview_url($vo['avatar']); ?>');">
                            <img src="<?php echo cmf_get_image_preview_url($vo['avatar']); ?>"
                                 style="width:40px;height:40px;border-radius: 40px;">
                        </a>
                    <?php endif; ?>
                </td>
                <td><?php echo $vo['mobile']; ?></td>
                <td><?php echo $vo['point']; ?></td>
                <td><?php echo date("Y-m-d H:i:s",!is_numeric($vo['create_time'])? strtotime($vo['create_time']) : $vo['create_time']); ?></td>
                <td>
                    <a class="btn btn-xs btn-primary" href="<?php echo url('update',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>
                    <a class="btn btn-xs btn-warning" href="javascript:parent.openIframeLayer('<?php echo url('addBalance',array('id'=>$vo['id'])); ?>',' 账户调整',{area:['70%','70%']});">账户设置</a>
                    <a class="btn btn-xs btn-danger js-ajax-delete" href="<?php echo url('delete',array('id'=>$vo['id'])); ?>"><?php echo lang('DELETE'); ?></a>
                </td>
            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>

    <!-- 分页 -->
    <div class="pagination">
        <?php echo $page; ?>
        <div class="page-jump" style="display:inline-block; margin-left:15px; vertical-align:middle;">
            <span>第</span>
            <input type="text" id="jump_page_input" class="form-control" style="width:50px; display:inline-block; height:32px; text-align:center;" value="<?php echo input('page/d',1); ?>">
            <span>页</span>
            <button type="button" class="btn btn-primary btn-sm" id="jump_page_btn" style="height:32px;">跳转</button>
        </div>
    </div>
</div>
<script src="/static/js/admin.js"></script>
<script>
    // 页码跳转
    function jumpToPage(page){
        var url = window.location.href;
        // 解析当前 URL 的 query 参数
        var hashIndex = url.indexOf('#');
        if (hashIndex > -1) url = url.substring(0, hashIndex);
        var queryIndex = url.indexOf('?');
        var base = url;
        var params = {};
        if (queryIndex > -1) {
            base = url.substring(0, queryIndex);
            var queryString = url.substring(queryIndex + 1);
            var pairs = queryString.split('&');
            for (var i = 0; i < pairs.length; i++) {
                var pair = pairs[i].split('=');
                if (pair[0]) {
                    params[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
                }
            }
        }
        params['page'] = page;
        var query = $.param(params);
        window.location.href = base + '?' + query;
    }

    $(function () {
        $('#jump_page_btn').click(function () {
            var page = parseInt($('#jump_page_input').val(), 10);
            if (isNaN(page) || page < 1) {
                layer.msg('请输入有效的页码', {icon: 2});
                return;
            }
            jumpToPage(page);
        });
        $('#jump_page_input').on('keypress', function (e) {
            if (e.which === 13) {
                $('#jump_page_btn').click();
            }
        });
    });
</script>
</body>
</html>