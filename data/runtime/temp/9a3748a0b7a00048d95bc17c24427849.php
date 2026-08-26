<?php /*a:2:{s:44:"themes/admin_fengiy/admin/problem/index.html";i:1751371499;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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
        <li class="active"><a href="<?php echo url('index',['type'=>$params['type']]); ?>">列表</a></li>

            <li><a href="<?php echo url('update',['type'=>$params['type']]); ?>">添加</a></li>
       <!-- <?php if($params['type'] != 1): ?> <?php endif; ?>-->
    </ul>
    <form class="well form-inline margin-top-20" method="get" action="<?php echo url('index',['type'=>$params['type']]); ?>">
        关键字：
        <input type="text" class="form-control" name="title" style="width: 140px;"
               value="<?php echo input('request.title/s',''); ?>" placeholder="请输入名称">
        <input type="submit" class="btn btn-primary" value="搜索" style="margin-left: 25px"/>
        <a class="btn btn-danger" href="<?php echo url('index',['type'=>$params['type']]); ?>">清空</a>

    </form>
    <form method="post" class="js-ajax-form margin-top-20 layui-form" action="<?php echo url('listOrder'); ?>">
        <div class="table-actions">
            <button class="btn btn-primary btn-sm js-ajax-submit" type="submit"><?php echo lang('SORT'); ?></button>
        </div>
        <table class="table table-hover table-bordered margin-top-20">
            <thead>
            <tr>
                <!--                    <th width="16"><label><input type="checkbox" class="js-check-all" data-direction="x"-->
                <!--                                                 data-checklist="js-check-x"></label></th>-->
                <th>排序</th>
                <th>ID</th>
                <th>名称</th>
                <th>启用</th>


                <th width="250"><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <!--                        <td><input type="checkbox" class="js-check" data-yid="js-check-y" data-xid="js-check-x"-->
                    <!--                                   name="ids[]" value="<?php echo $vo['id']; ?>"></td>-->
                    <td>
                        <input name="list_orders[<?php echo $vo['id']; ?>]" class="input-order" type="text"
                               value="<?php echo $vo['list_order']; ?>">
                    </td>
                    <td><?php echo $vo['id']; ?></td>
                    <td><?php echo $vo['name']; ?></td>
                    <td>
                        <div class="layui-form">
                            <input type="checkbox" lay-skin="switch" lay-text="是|否" lay-filter="status" name="status"
                                   value="<?php echo $vo['id']; ?>"
                            <?php if($vo['status'] == 1): ?>checked<?php endif; ?>
                            >
                        </div>
                    </td>

                    <td>

                        <a class="btn btn-xs btn-info"
                           href="javascript:parent.openIframeLayer('<?php echo url('question/index',array('pid'=>$vo['id'])); ?>','<?php echo $vo['name']; ?>的题目',{area:['90%','90%']});">题目</a>
                        <a class="btn btn-xs btn-success"
                           href="javascript:parent.openIframeLayer('<?php echo url('course/answer',array('exam_id'=>$vo['id'])); ?>','<?php echo $vo['name']; ?>的答题记录',{area:['90%','90%']});">答题记录</a>

                        <!--                        <a class="btn btn-xs layui-bg-purple" href="javascript:parent.openIframeLayer('<?php echo url('answer_result/index',array('data_id'=>$vo['id'])); ?>','<?php echo $vo['name']; ?>的结果',{area:['90%','90%']});">结果</a>-->


                        <a class="btn btn-xs btn-primary"
                           href="<?php echo url('update',array('id'=>$vo['id'],'type'=>$params['type'])); ?>"><?php echo lang('EDIT'); ?></a>

                            <a class="btn btn-xs btn-danger js-ajax-delete"
                               href="<?php echo url('delete',array('id'=>$vo['id'])); ?>"><?php echo lang('DELETE'); ?></a>
                        <?php if($params['type'] != 1): ?>    <?php endif; ?>

                        <!--                        <?php if($vo['id']  > 8): ?>-->

                        <!--                        <a class="btn btn-xs btn-primary"-->
                        <!--                           href="<?php echo url('update',array('id'=>$vo['id'])); ?>"><?php echo lang('EDIT'); ?></a>-->
                        <!--
                        <!--                        <?php endif; ?>  &lt;!&ndash;                                        <a class="btn btn-xs btn-warning" href="javascript:parent.openIframeLayer('<?php echo url('cate_data/index',array('cate_id'=>$vo['id'])); ?>','',{area:['70%','70%']});">内容</a>&ndash;&gt;-->
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
<script>
    layui.use(['layer', 'form'], function () {
        let layer = layui.layer, form = layui.form;
        form.on('switch(status)', function (data) {
            setStatus(data.value, 'status', this.checked ? 1 : 2)
        });
        //发布不发布
        form.on('switch(is_top)', function (data) {
            setStatus(data.value, 'is_top', this.checked ? 1 : 0)
        });

        pro_type='<?php echo $params['type']; ?>';

        function setStatus(id, type, status) {
            $.ajax({
                url: '<?php echo url('setStatus'); ?>',
                type: "POST",
                data: {id: id, type: type,pro_type: pro_type, status: status, cate: 0},
                success: function (res) {
                    if (res.code === 1) {
                        layer.msg(res.msg, {icon: 1});
                    } else {
                        layer.msg(res.msg, {icon: 7});
                    }
                    if (pro_type == 1) {
                        setInterval(function () {
                            window.location.reload();
                        },  1000)
                    }

                }
            });
        }
    });

</script>
</body>
</html>