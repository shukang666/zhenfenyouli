<?php /*a:2:{s:44:"themes/admin_fengiy/admin/member/update.html";i:1756973535;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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

<style type="text/css">
    .pic-list li {
        margin-bottom: 5px;
    }
</style>

</head>
<body>
<div class="wrap js-check-wrap">

    <ul class="nav nav-tabs">
        <li><a href="<?php echo url('index'); ?>">列表</a></li>
        <?php if(!(empty($item['id']) || (($item['id'] instanceof \think\Collection || $item['id'] instanceof \think\Paginator ) && $item['id']->isEmpty()))): ?>
            <li class="active"><a href="">编辑</a></li>
            <?php else: ?>
            <li class="active"><a href="<?php echo url('update'); ?>">添加</a></li>
        <?php endif; ?>
    </ul>
    <form method="post" class="form-horizontal js-ajax-form margin-top-20 layui-form" action="<?php echo url('updatePost'); ?>">

        <input type="hidden" value="<?php echo (isset($item['id']) && ($item['id'] !== '')?$item['id']:''); ?>" name="id">



        <?php 
            $list= cmf_table('member_community')->where('delete_time',0)->select();
         ?>

        <div class="form-group">
            <label class="col-sm-2 control-label"><span class="form-required">*</span>小区:</label>
            <div class="col-md-6 col-sm-10">
                <select name="community_id" class="form-control">
                    <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): if(!(empty($item['community_id']) || (($item['community_id'] instanceof \think\Collection || $item['community_id'] instanceof \think\Paginator ) && $item['community_id']->isEmpty()))): ?>
                            <option value="<?php echo $vo['id']; ?>"
                            <?php if($item['community_id'] == $vo['id']): ?>selected<?php endif; ?>
                            ><?php echo $vo['name']; ?></option>
                            <?php else: ?>
                            <option value="<?php echo $vo['id']; ?>"><?php echo $vo['name']; ?></option>
                        <?php endif; ?>

                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </select>
            </div>
        </div>




        <div class="form-group">
            <label class="col-sm-2 control-label"><span class="form-required">*</span>昵称:</label>
            <div class="col-md-6 col-sm-10">
                <input style="width: 212px" type="text" class="form-control" name="nickname"
                       value="<?php echo (isset($item['nickname']) && ($item['nickname'] !== '')?$item['nickname']:''); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label class="col-sm-2 control-label"><span class="form-required">*</span>电话:</label>
            <div class="col-md-6 col-sm-10">
                <input style="width: 212px" type="text" class="form-control" name="mobile"
                       value="<?php echo (isset($item['mobile']) && ($item['mobile'] !== '')?$item['mobile']:''); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"><span class="form-required">*</span>头像:</label>
            <div class="col-md-6 col-sm-10">
                <input type="hidden" name="avatar" id="avatar" value="<?php echo (isset($item['avatar']) && ($item['avatar'] !== '')?$item['avatar']:''); ?>">
                <a href="javascript:uploadOneImage('图片上传','#avatar');">
                    <?php if(!(empty($item['avatar']) || (($item['avatar'] instanceof \think\Collection || $item['avatar'] instanceof \think\Paginator ) && $item['avatar']->isEmpty()))): ?>
                        <img src="<?php echo cmf_get_image_preview_url($item['avatar']); ?>" id="avatar-preview" width="135"
                             style="cursor: hand"/>
                        <?php else: ?>
                        <img src="/themes/admin_fengiy/public/assets/images/default-image.png" id="avatar-preview" width="135"
                             style="cursor: hand"/>
                    <?php endif; ?>
                </a>
                <input type="button" class="btn btn-sm"
                       onclick="$('#avatar-preview').attr('src','/themes/admin_fengiy/public/assets/images/default-image.png');$('#avatar').val('');return false;"
                       value="取消图片">
            </div>
        </div>
        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang("SAVE"); ?></button>
            <a class="btn btn-default" href="<?php echo url('index'); ?>"><?php echo lang('BACK'); ?></a>
        </div>
    </form>

</div>
<script src="/static/js/admin.js"></script>
<script>

    layui.use(['form', 'laydate', 'util'], function () {
        var form = layui.form;
        var layer = layui.layer;

    // form.on('select(vip_id)', function (data) {
    //     console.log(data.value);
    //     if (data.value ==0) {
    //         $('#price-group').show();
    //     } else {
    //         $('#price-group').hide();
    //     }
    // });

    // // 初始化时根据当前选择的vip_id隐藏或显示价格字段
    // if ($('#vip_id').val() !== '') {
    //     $('#price-group').hide();
    // }
    });
</script>

</body>
</html>