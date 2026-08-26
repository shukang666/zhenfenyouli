<?php /*a:2:{s:47:"themes/admin_fengiy/admin/community/update.html";i:1747273659;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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

<!--        <div class="form-group">-->
<!--            <div class="layui-form-item">-->
<!--                <label class="col-sm-2 control-label"><span class="form-required">*</span>地区:</label>-->
<!--                <div class="col-md-6 col-sm-10" style="z-index: 2000!important;">-->
<!--                    <div class="layui-inline">-->
<!--                        <select name="province" id="province" lay-verify="required" lay-search lay-filter="province">-->
<!--                            <option value="">请选择</option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                    <div class="layui-inline">-->
<!--                        <select name="city" id="city" lay-verify="required" lay-search lay-filter="city">-->
<!--                            <option value="">请选择</option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                    <div class="layui-inline">-->
<!--                        <select name="county" id="county" lay-verify="required" lay-search>-->
<!--                            <option value="">请选择</option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->


        <div class="form-group">
            <label class="col-sm-2 control-label">名称:</label>
            <div class="col-md-4 col-sm-10">
                <input  type="text" class="form-control" name="name" value="<?php echo (isset($item['name']) && ($item['name'] !== '')?$item['name']:''); ?>" >
            </div>
        </div>

<!--        <div class="form-group">-->
<!--            <label class="col-sm-2 control-label">描述:</label>-->
<!--            <div class="col-md-4 col-sm-10">-->
<!--                <input  type="text" class="form-control" name="desc" value="<?php echo (isset($item['desc']) && ($item['desc'] !== '')?$item['desc']:''); ?>" >-->
<!--            </div>-->
<!--        </div>-->


<!--        <div class="form-group">-->
<!--            <label class="col-sm-2 control-label">图片:</label>-->
<!--            <div class="col-md-6 col-sm-10">-->
<!--                <?php   $thumb='thumb'; ?>-->
<!--                <input type="hidden" name="<?php echo $thumb; ?>" id="<?php echo $thumb; ?>" value="<?php echo (isset($item[$thumb]) && ($item[$thumb] !== '')?$item[$thumb]:''); ?>">-->
<!--                <a href="javascript:uploadOneImage('图片上传','#<?php echo $thumb; ?>');">-->
<!--                    <?php if(empty($item[$thumb])): ?>-->
<!--                        <img src="/themes/admin_fengiy/public/assets/images/default-thumbnail.png"-->
<!--                             id="<?php echo $thumb; ?>-preview"-->
<!--                             width="135" style="cursor: pointer"/>-->
<!--                        <?php else: ?>-->
<!--                        <img src="<?php echo cmf_get_image_preview_url($item[$thumb]); ?>"-->
<!--                             id="<?php echo $thumb; ?>-preview"-->
<!--                             width="135" style="cursor: pointer"/>-->
<!--                    <?php endif; ?>-->
<!--                </a>-->
<!--            </div>-->
<!--        </div>-->

        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang("SAVE"); ?></button>
            <a class="btn btn-default" href="<?php echo url('index'); ?>"><?php echo lang('BACK'); ?></a>
        </div>
    </form>

</div>
<script src="/static/js/admin.js"></script>
<script>
    layui.use(['form', 'element'], function () {
        var form = layui.form, element = layui.element;

        var province = "<?php echo isset($item['province']) ? $item['province'] : ''; ?>"
        var city = "<?php echo isset($item['city']) ? $item['city'] : ''; ?>"
        var county = "<?php echo isset($item['county']) ? $item['county'] : ''; ?>"
        getCateList(0, $("#province"), 'province');
        getCateList(province, $("#city"), 'city');
        getCateList(city, $("#county"), 'county');
        getCateList(0, $("#province"), 'province');
        form.on('select(province)', function (data) {
            province = data.value;
            getCateList(data.value, $("#city"), 'city');
        })

        form.on('select(city)', function (data) {
            city = data.value;
            getCateList(data.value, $("#county"), 'county');
        })

        function getCateList(pid, target, type) {
            console.log(pid)
            $.ajax({
                type: 'POST',
                url: "<?php echo url('getJsonAreaList'); ?>",
                data: {
                    pid: pid,
                },
                dataType: 'json',
                success: function (data) {
                    var cate_list = data.data;
                    // console.log(cate_list)
                    var str = "<option value=''>" + "请选择" + "</option>";
                    for (var i = 0; i < cate_list.length; i++) {

                        if (type == 'province') {
                            var where = province;
                        } else if (type == 'city') {
                            var where = city;
                        } else {
                            var where = county;
                        }
                        if (where == cate_list[i].code) {
                            str += "<option value='" + cate_list[i].code + "' selected>" + cate_list[i].name + "</option>";
                        } else {
                            str += "<option value='" + cate_list[i].code + "'>" + cate_list[i].name + "</option>";
                        }
                    }
                    target.html(str);
                    form.render('select');
                }
            });
        }
    })
</script>
</body>
</html>