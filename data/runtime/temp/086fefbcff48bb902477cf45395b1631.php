<?php /*a:2:{s:49:"themes/admin_fengiy/admin/member/add_balance.html";i:1752737897;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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
        <li class="active"><a href="">账户调整</a></li>
    </ul>
    <form method="post" class="form-horizontal js-ajax-form margin-top-20 layui-form" action="<?php echo url('addBalance'); ?>">

        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="form-group">
            <label class="col-sm-2 control-label"><span class="form-required">*</span>账户类型:</label>
            <div class="col-md-6 col-sm-10">
                <select name="account_type" class="form-control">
<!--                    <option value = "1">余额</option>-->
                    <option value = "2">积分</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"><span class="form-required">*</span>余额增减:</label>
            <div class="col-md-6 col-sm-10">
                <select name="direction" class="form-control">
                    <option value = "1">增加</option>
                    <option value = "2">减少</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label"><span class="form-required">*</span>调整数:</label>
            <div class="col-md-6 col-sm-10">
                <input type="number" class="form-control" name="change" value="" required>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-2 control-label">备注:</label>
            <div class="col-md-6 col-sm-10">
                <input type="text" class="form-control" name="remark" value="">
            </div>
        </div>


        <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang("SAVE"); ?></button>
        </div>
    </form>

</div>
<script src="/static/js/admin.js"></script>
<script>
    layui.use(['layer', 'form'], function(){
        var layer = layui.layer,form = layui.form;

        form.on('select(group)', function(data) {
            //console.log(data);
            var this_id = data.value;

            var this_ele = $('#leader');

            if(this_id == 1){
                this_ele.css('display','block');
                $('.show_div').css('display','block');
            }else{
                this_ele.css('display','none');
                $('.show_div').css('display','none');
            }
        })
    });



</script>

</body>
</html>