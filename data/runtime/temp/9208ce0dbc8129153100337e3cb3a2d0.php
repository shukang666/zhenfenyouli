<?php /*a:2:{s:51:"themes/admin_fengiy/admin/goods_position/index.html";i:1750315385;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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
        <li class="active"><a href="<?php echo url('index'); ?>">列表</a></li>
                <li><a href="<?php echo url('update'); ?>">添加</a></li>
        <li ><a href="<?php echo url('addMany'); ?>">导入</a></li>
    </ul>
    <form class="well form-inline margin-top-20" method="get" action="<?php echo url('index'); ?>">
        关键字：
        <input type="text" class="form-control" name="name" style="width: 140px;"
               value="<?php echo input('request.name/s',''); ?>" placeholder="请输入名称">
        <input type="submit" class="btn btn-primary" value="搜索" style="margin-left: 25px"/>
        <a class="btn btn-danger" href="<?php echo url('index'); ?>">清空</a>

    </form>


    <!--    <form method="post" class="js-ajax-form margin-top-20" action="<?php echo url('listOrder'); ?>">-->
    <!--        <form class="js-ajax-form" action="" method="post">-->
    <!--            <div>-->
    <!--                <button class="btn btn-primary btn-sm js-ajax-submit" type="submit"-->
    <!--                        data-action="<?php echo url('toggle',array('yes'=>1)); ?>" data-subcheck="true">审核通过-->
    <!--                </button>-->
    <!--                <button class="btn btn-primary btn-sm js-ajax-submit" type="submit"-->
    <!--                        data-action="<?php echo url('toggle',array('no'=>1)); ?>" data-subcheck="true">审核不通过-->
    <!--                </button>-->
    <!--            </div>-->
    <table class="table table-hover table-bordered margin-top-20">
        <thead>
        <tr>
            <!--                    <th width="16"><label><input type="checkbox" class="js-check-all" data-direction="x"-->
            <!--                                                 data-checklist="js-check-x"></label></th>-->
            <th>ID</th>
            <th>名称</th>


            <th width="200"><?php echo lang('ACTIONS'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
            <tr>
                <!--                        <td><input type="checkbox" class="js-check" data-yid="js-check-y" data-xid="js-check-x"-->
                <!--                                   name="ids[]" value="<?php echo $vo['id']; ?>"></td>-->
                <td><?php echo $vo['id']; ?></td>
                <td><?php echo $vo['name']; ?></td>

                <td>
                    <a class="btn btn-xs btn-primary" href="<?php echo url('update',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>

<!--                    <a class="btn btn-xs btn-primary"-->
<!--                       href="javascript:parent.openIframeLayer('<?php echo url('edit',array('id'=>$vo['id'])); ?>','',{area:['80%','80%']});"><?php echo lang('EDIT'); ?></a>-->
                    <a class="btn btn-xs btn-danger js-ajax-delete"
                       href="<?php echo url('delete',array('id'=>$vo['id'])); ?>"><?php echo lang('DELETE'); ?></a>
                    <!--                                        <a class="btn btn-xs btn-warning" href="javascript:parent.openIframeLayer('<?php echo url('cate_data/index',array('data_id'=>$vo['id'])); ?>','',{area:['70%','70%']});">内容</a>-->
                </td>

            </tr>
        <?php endforeach; endif; else: echo "" ;endif; ?>
        </tbody>
    </table>
    </form>

    <div class="pagination"><?php echo $page; ?></div>
</div>
<script src="/static/js/admin.js"></script>

</body>
</html>