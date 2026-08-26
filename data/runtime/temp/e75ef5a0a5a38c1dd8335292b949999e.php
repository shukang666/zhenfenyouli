<?php /*a:2:{s:41:"themes/admin_fengiy/admin\main\index.html";i:1655281954;s:79:"D:\phpstudy_pro\WWW\xiayuxuan.com\public/themes/admin_fengiy/public\header.html";i:1693132508;}*/ ?>
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
	<div class="wrap">
		<div class="layui-row">
			<div class="layui-col-xs12">
				<div class="panel panel-default" style="margin-bottom: 10px;">
					<div class="panel-body">
						<?php echo lang('WELCOME_USER',array('user_nickname' => empty($admin['user_nickname'])?$admin['user_login'] : $admin['user_nickname'])); ?>登陆后台管理中心
					</div>
				</div>
			</div>
		</div>
		<!--<div class="layui-row layui-col-space15">
			<div class="layui-col-xs4">
				<div class="layui-row layui-col-space15">
					<div class="layui-col-xs6">
						<a href="javascript:parent.openapp('/admin/member/index.html?type=1','用户管理','会员管理');">
							<div class="panel panel-default" style="margin-bottom: 0;">
								<div class="panel-body text-center">
									<i class="fa fa-users" style="font-size: 24px; color: #69C0FF;"></i>
									<div class="margin-top-10">会员管理</div>
								</div>
							</div>
						</a>
					</div>
					<div class="layui-col-xs6">
						<a href="javascript:parent.openapp('/admin/shop_info/index.html','门店设置','门店信息');">
							<div class="panel panel-default" style="margin-bottom: 0;">
								<div class="panel-body text-center" style="margin-bottom: 0;">
									<i class="fa fa-cube" style="font-size: 24px; color: #5CDBD3;"></i>
									<div class="margin-top-10">门店设置</div>
								</div>
							</div>
						</a>
					</div>
				</div>
			</div>
			<div class="layui-col-xs4">
				<div class="layui-row layui-col-space15">
					<div class="layui-col-xs6">
						<a href="javascript:parent.openapp('/admin/position/index.html','职位管理','职位信息');">
							<div class="panel panel-default" style="margin-bottom: 0;">
								<div class="panel-body text-center">
									<i class="fa fa-id-card" style="font-size: 24px; color: #B37FEB;"></i>
									<div class="margin-top-10">职位管理</div>
								</div>
							</div>
						</a>
					</div>
					<div class="layui-col-xs6">
						<a href="javascript:parent.openapp('/admin/goods/index.html','积分商城','商品管理');">
							<div class="panel panel-default" style="margin-bottom: 0;">
								<div class="panel-body text-center">
									<i class="fa fa-shopping-cart" style="font-size: 24px; color: #FF9C6E;"></i>
									<div class="margin-top-10">积分商城</div>
								</div>
							</div>
						</a>
					</div>
				</div>
			</div>
			<div class="layui-col-xs4">
				<div class="layui-row layui-col-space15">
					<div class="layui-col-xs6">
						<a href="javascript:parent.openapp('/admin/app_info/index.html','应用设置','应用设置');">
							<div class="panel panel-default" style="margin-bottom: 0;">
								<div class="panel-body text-center">
									<i class="fa fa-cog" style="font-size: 24px; color: #95DE64;"></i>
									<div class="margin-top-10">应用设置</div>
								</div>
							</div>
						</a>
					</div>
					<div class="layui-col-xs6">
						<a href="javascript:parent.openapp('/admin/rbac/index.html','门店管理','门店管理');">
							<div class="panel panel-default" style="margin-bottom: 0;">
								<div class="panel-body text-center">
									<i class="fa fa-cogs" style="font-size: 24px; color: #FFC069;"></i>
									<div class="margin-top-10">门店管理</div>
								</div>
							</div>
						</a>
					</div>
				</div>
			</div>
		</div>
		<div class="layui-row layui-col-space15">
			<div class="layui-col-xs12">
				&lt;!&ndash; layui-col-sm6 layui-col-md6 &ndash;&gt;
				<div class="panel panel-default" style="margin-bottom: 10px;">
					&lt;!&ndash; <div class="panel-heading">
						<i class="fa fa-pie-chart" style="font-size: 16px; color: #1890FF;"></i>
						<span>订单</span>
					</div> &ndash;&gt;
					<div class="panel-body">
						<div id="order" style="width: 100%;height:280px;"></div>
					</div>
				</div>
			</div>
			<div class="layui-col-xs12">
				<div class="panel panel-default" style="margin-bottom: 0;">
					&lt;!&ndash; <div class="panel-heading">
						<i class="fa fa-user-circle" style="font-size: 16px; color: #1890FF;"></i>
						<span>用户</span>
					</div> &ndash;&gt;
					<div class="panel-body">
						<div id="member" style="width: 100%;height:280px;"></div>
					</div>
				</div>
			</div>
		</div>-->
	</div>
	<script src="/static/js/admin.js"></script>
	<script src="/themes/admin_fengiy/public/assets/simpleboot3/js/adminindex.js"></script>
	<script src="/themes/admin_fengiy/public/assets/js/echarts.js"></script>
	<script src="/themes/admin_fengiy/public/assets/simpleboot3/js/indexnum.js"></script>
	<?php 
    hook('admin_before_body_end',null,false);
 ?>
	<script type="text/javascript">
		/*//图表
		$(function () {
			//订单数据量趋势
			orderChart([10, 11, 13, 11, 12, 12, 9], [1, 2, 2, 5, 3, 2, 0]);
			//用户数据量趋势
			userChart([820, 932, 901, 934, 1290, 1330, 1320]);
		})*/
	</script>
</body>
</html>
