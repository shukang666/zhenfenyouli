<?php /*a:2:{s:42:"themes/admin_fengiy/admin/order/index.html";i:1755765091;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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

<link href="/themes/admin_fengiy/public/assets/css/common.css" rel="stylesheet">
<style>
    .layui-tab-content {
        margin-top: 15px;
    }
</style>
</head>
<body>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('Order/index'); ?>">订单管理</a></li>
    </ul>
    <form class="layui-form well form-inline margin-top-20" method="get" action="<?php echo url('Order/index'); ?>">
        <div>
            订单号:
            <input type="text" class="form-control" name="order_num" style="width: 200px;"
                   value="<?php echo input('order_num/s',''); ?>" placeholder="请输入订单号">
            商品名称:
            <input type="text" class="form-control" name="goods_name" style="width: 240px;"
                   value="<?php echo input('goods_name/s',''); ?>" placeholder="请输入商品名称">
            收货人:
            <input type="text" class="form-control" name="username" style="width: 150px;"
                   value="<?php echo input('username/s',''); ?>" placeholder="请输入收货人">
            收货人手机:
            <input type="text" class="form-control" name="phone" style="width: 180px;" value="<?php echo input('phone/s',''); ?>"
                   placeholder="请输入收货人手机号">
        </div>
        <div style="margin-top: 10px;display: flex">
            <div style="margin-left: 10px">
                <span>下单时间：</span>
                <div class="layui-inline" id="create-time">
                    <div class="layui-input-inline" style="width: 140px;">
                        <input type="text" autocomplete="off" id="create-startDate" class="layui-input"
                               name="start_time" value="<?php echo input('request.start_time'); ?>" placeholder="开始日期">
                    </div>
                    -
                    <div class="layui-input-inline" style="width: 140px;">
                        <input type="text" autocomplete="off" id="create-endDate" class="layui-input" name="end_time"
                               value="<?php echo input('request.end_time'); ?>" placeholder="结束日期">
                    </div>
                </div>
            </div>
            <div>
                <span>收货方式：</span>
                <div class="layui-inline">
                    <select name="delivery_type" lay-filter="vip_id">
                        <option value="">全部</option>
                        <?php if(is_array($delivery_type_list) || $delivery_type_list instanceof \think\Collection || $delivery_type_list instanceof \think\Paginator): if( count($delivery_type_list)==0 ) : echo "" ;else: foreach($delivery_type_list as $key=>$vo): if(!(empty($delivery_type) || (($delivery_type instanceof \think\Collection || $delivery_type instanceof \think\Paginator ) && $delivery_type->isEmpty()))): ?>
                                <option value="<?php echo $key; ?>"
                                <?php if($delivery_type == $key): ?>selected<?php endif; ?>
                                ><?php echo $vo; ?></option>
                                <?php else: ?>
                                <option value="<?php echo $key; ?>"><?php echo $vo; ?></option>
                            <?php endif; ?>

                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </div>
            </div>

            <div>
                <span>支付方式：</span>
                <div class="layui-inline">
                    <select name="pay_type" lay-filter="vip_id">
                        <option value="">全部</option>
                        <?php if(is_array($pay_list) || $pay_list instanceof \think\Collection || $pay_list instanceof \think\Paginator): if( count($pay_list)==0 ) : echo "" ;else: foreach($pay_list as $key=>$vo): if(!(empty($pay_type) || (($pay_type instanceof \think\Collection || $pay_type instanceof \think\Paginator ) && $pay_type->isEmpty()))): ?>
                                <option value="<?php echo $key; ?>"
                                <?php if($pay_type == $key): ?>selected<?php endif; ?>
                                ><?php echo $vo; ?></option>
                                <?php else: ?>
                                <option value="<?php echo $key; ?>"><?php echo $vo; ?></option>
                            <?php endif; ?>

                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </div>
            </div>
            状态：
            <div class="layui-inline" style="width: 120px">
                <select name="status" lay-filter="pay_type">
                    <option value="">全部</option>
                    <?php if(is_array($check_status_list) || $check_status_list instanceof \think\Collection || $check_status_list instanceof \think\Paginator): if( count($check_status_list)==0 ) : echo "" ;else: foreach($check_status_list as $key=>$vo): if(!(empty($params['status']) || (($params['status'] instanceof \think\Collection || $params['status'] instanceof \think\Paginator ) && $params['status']->isEmpty()))): ?>
                            <option value="<?php echo $key; ?>"
                            <?php if($params['status'] == $key): ?>selected<?php endif; ?>
                            ><?php echo $vo; ?></option>
                            <?php else: ?>
                            <option value="<?php echo $key; ?>"><?php echo $vo; ?></option>
                        <?php endif; ?>

                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </select>
            </div>
            <!--            <div style="margin-left: 10px">-->
            <!--                <span>订单类型：</span>-->
            <!--                <div class="layui-inline">-->
            <!--                    <select name="order_type" lay-search>-->
            <!--                        <option value="0" class="form-control">全部</option>-->
            <!--                        <option value="1" class="form-control"-->
            <!--                        <?php if(input('request.order_type') == 1): ?>selected<?php endif; ?>-->
            <!--                        >普通订单</option>-->
            <!--                        <option value="2" class="form-control"-->
            <!--                        <?php if(input('request.order_type') == 2): ?>selected<?php endif; ?>-->
            <!--                        >福袋订单</option>-->
            <!--                    </select>-->
            <!--                </div>-->
            <!--            </div>-->
            <div style="margin-left: 10px">
                <input type="submit" class="btn btn-primary" value="搜索"/>
                <a class="btn btn-danger" href="<?php echo url('index'); ?>">清空</a>
            </div>
        </div>
    </form>
    <div class="layui-tab layui-tab-brief">
        <ul class="layui-tab-title order-tab">
            <?php if(is_array($status_menu) || $status_menu instanceof \think\Collection || $status_menu instanceof \think\Paginator): if( count($status_menu)==0 ) : echo "" ;else: foreach($status_menu as $key=>$menu_item): ?>
                <li
                <?php if($menu_item['status'] == $status): ?>class="layui-this"<?php endif; ?>
                >
                <a href="<?php echo url('index',['status'=>$menu_item['status']]); ?>" style='text-decoration:none;color: inherit'>
                    <?php echo $menu_item['status_name']; if($menu_item['status'] != 0): ?>(<?php echo $menu_item['status_count']; ?>)<?php endif; ?>
                </a>
                </li>
            <?php endforeach; endif; else: echo "" ;endif; ?>
        </ul>
        <div class="layui-tab-content table-responsive">
            <table class="table order-table">
                <thead>
                <tr class="tab-header">
                    <th width="60%">
                        <table class="th-table">
                            <tr>
                                <th width="5%">商品ID</th>
                                <th width="65%">商品信息</th>
                                <th width="15%">单价</th>
                                <th width="15%">数量</th>
                            </tr>
                        </table>
                    </th>
                    <th width="8%">优惠券</th>
<!--                                        <th width="8%">积分</th>-->
                    <th width="8%">实付款</th>
                    <th width="8%">状态</th>
                    <th width="8%">操作</th>
                </tr>
                </thead>

                <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$item): ?>
                    <tbody>
                    <tr class="tr-pipe"></tr>
                    </tbody>
                    <tbody>
                    <tr class="tr-header">
                        <td colspan="6">
                            <b><?php echo date('Y-m-d H:i:s',!is_numeric($item['create_time'])? strtotime($item['create_time']) : $item['create_time']); ?></b>
                            <span style="margin: 0 10px"><cite>订单号：</cite><?php echo $item['pay_order_num']; ?></span>
                            <img class="layui-circle" src="<?php echo $item['avatar']; ?>" alt="" width="20" height="20">
                            <span style="margin-right: 10px;"><?php echo $item['nickname']; ?>(<?php echo !empty($item['mobile']) ? $item['mobile'] : $item['user_id']; ?>)</span>
                            <i class="layui-icon layui-icon-reply-fill cursor" style="color: #FF5722"
                               data-toggle="tooltip" title="<?php echo $item['remark']; ?>"></i>
                            <span class="layui-btn layui-btn-primary layui-border-green layui-btn-xs"
                                  style="margin-left: 20px;"><?php echo $item['delivery_type_name']; ?></span>

                            <?php if($item['aftersales_status'] == 2): ?>
                                <a class="layui-btn layui-bg-purple " style="float: right;margin-right: 85px"
                                   href="javascript:parent.openIframeLayer('<?php echo url('goods_detail',array('order_num'=>$item['order_num'])); ?>','退货商品',{area:['70%','70%']});">退货商品</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="tr-goods">
                        <td>
                            <table class="goods-item">

                                <?php if(is_array($item['goods_list']) || $item['goods_list'] instanceof \think\Collection || $item['goods_list'] instanceof \think\Paginator): if( count($item['goods_list'])==0 ) : echo "" ;else: foreach($item['goods_list'] as $key=>$goods_item): ?>
                                    <tr>
                                        <td width="5%" class="text-center"><?php echo $goods_item['goods_id']; ?></td>
                                        <td width="65%">
                                            <div class="img">
                                                <img class="img-rounded" src="<?php echo $goods_item['thumb']; ?>" alt="" width="70"
                                                     height="70">
                                            </div>
                                            <div class="title"><?php echo $goods_item['goods_name']; ?></div>
                                            <?php if(!(empty($goods_item['sku_name']) || (($goods_item['sku_name'] instanceof \think\Collection || $goods_item['sku_name'] instanceof \think\Paginator ) && $goods_item['sku_name']->isEmpty()))): ?>
                                                <span class="tags"><?php echo $goods_item['sku_name']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td width="15%" class="text-center">&yen; <?php echo $goods_item['one_price']; ?></td>
                                        <td width="15%" class="text-center"><?php echo $goods_item['count']; ?></td>
                                    </tr>
                                <?php endforeach; endif; else: echo "" ;endif; ?>


                            </table>
                        </td>
                        <td class="text-center">
                            <div class="price">&yen; <?php echo $item['coupon_price']; ?></div>
                        </td>
<!--                                                <td class="text-center">-->
<!--                                                    <div class="price">&yen;<?php echo $item['point_pay']; ?>(<?php echo $item['point']; ?>积分)</div>-->
<!--                                                </td>-->
                        <td class="text-center">
                            <div class="price">&yen; <?php echo $item['price']; ?></div>
                            <!--                            <div class="free">(含运费￥<?php echo $item['express_price']; ?>)</div>-->
                            <span class="layui-badge layui-bg-green"><?php echo $item['pay_type_name']; ?></span>
                        </td>
                        <td class="text-center">
                            <div class="status">
                                <!-- 1待付款 2待发货 4待收货 6待评价 8已完成 10已取消(未付款) 12申请退款 14待发货(退款不通过) 16已取消(退款通过) -->

                                <?php if(is_array($status_list) || $status_list instanceof \think\Collection || $status_list instanceof \think\Paginator): if( count($status_list)==0 ) : echo "" ;else: foreach($status_list as $key=>$status_item): if($item['status'] == $status_item['status']): ?>
                                        <div class="layui-btn layui-btn-xs <?php echo $status_item['color']; ?>">
                                            <?php echo $status_item['status_name']; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; endif; else: echo "" ;endif; if($item['status'] >= 12): ?>
                                    <a style="margin-left: 5px" data-toggle="tooltip" title="退款原因"
                                       href="javascript:parent.openIframeLayer('<?php echo url('reason',['id'=>$item['id']]); ?>','查看退款原因',{area: ['800px', '460px']});">
                                        <i class="layui-icon layui-icon-question layui-font-22 layui-font-red"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="operate">

                                <a class="layui-bg-blue-light layui-circle op-btn" lay-on="order-tips"
                                   data-toggle="tooltip"
                                   title='<?php if(!(empty($item['admin_remark']) || (($item['admin_remark'] instanceof \think\Collection || $item['admin_remark'] instanceof \think\Paginator ) && $item['admin_remark']->isEmpty()))): ?>修改备注<?php else: ?>添加备注<?php endif; ?>'
                                   data-id="<?php echo $item['id']; ?>" data-notes="<?php echo $item['admin_remark']; ?>">
                                    <i class="layui-icon layui-icon-edit"></i>
                                </a>
                                <?php if($item['status'] == 2 && $item['delivery_type'] == 1): ?>
                                    <a class="layui-bg-green-light layui-circle op-btn"
                                       href="javascript:parent.openIframeLayer('<?php echo url('send',['id'=>$item['id']]); ?>','立即发货',{area: ['800px', '460px']});"
                                       data-toggle="tooltip" title="立即发货">
                                        <i class="layui-icon layui-icon-release"></i>
                                    </a>
                                <?php endif; if($item['status'] == 4 && $item['delivery_type'] == 1): ?>
                                    <a class="layui-bg-green-light layui-circle op-btn"
                                       href="javascript:parent.openIframeLayer('<?php echo url('send',['id'=>$item['id']]); ?>','发货详情',{area: ['800px', '460px']});"
                                       data-toggle="tooltip" title="发货详情">
                                        <i class="layui-icon layui-icon-release"></i>
                                    </a>
                                <?php endif; if($item['status'] == 12): ?>
                                    <a class="layui-bg-red-light layui-circle op-btn" lay-on="order-refund"
                                       data-toggle="tooltip" title="处理退款" data-id="<?php echo $item['id']; ?>">
                                        <i class="layui-icon layui-icon-util"></i>
                                    </a>
                                <?php endif; if($item['status'] == 18): ?>
                                    <a class="layui-bg-red-light layui-circle op-btn" lay-on="order-goods-refund"
                                       data-toggle="tooltip" title="处理退货" data-id="<?php echo $item['id']; ?>">
                                        <i class="layui-icon layui-icon-util"></i>
                                    </a>
                                <?php endif; if(($item['status'] == 2||$item['status'] == 3) && $item['delivery_type'] == 2): ?>
                                    <a class="layui-bg-green-light layui-circle op-btn js-ajax-dialog-btn"
                                       href="<?php echo url('finish',['id'=>$item['id']]); ?>'" data-toggle="tooltip" title="核销按钮"
                                       data-id="<?php echo $item['id']; ?>">
                                        <i class="layui-icon layui-icon-success"></i>
                                    </a>
                                <?php endif; ?>
                                <a class="layui-bg-gray-light layui-circle op-btn"
                                   href="javascript:parent.openIframeLayer('<?php echo url('details',['id'=>$item['id']]); ?>','订单详情',{area: ['80%', '80%']});"
                                   data-toggle="tooltip" title="订单详情">
                                    <i class="layui-icon layui-icon-more"></i>
                                </a>
                                <?php if($item['status'] == 22): ?>
                                    <a class="layui-bg-red-light layui-circle op-btn" lay-on="order-goods-refund-price"
                                       data-toggle="tooltip" title="处理退款" data-id="<?php echo $item['id']; ?>">
                                        <i class="layui-icon layui-icon-util"></i>
                                    </a>
                                <?php endif; ?>
                                <a class="layui-bg-red-light layui-circle op-btn js-ajax-delete"
                                   href="<?php echo url('delete',array('id'=>$item['id'])); ?>"> <i class="layui-icon layui-icon-delete"></i></a>


                            </div>
                        </td>
                    </tr>
                    <tr class="tr-footer">
                        <td colspan="6">
                            <span>收货人：<?php echo $item['username']; ?></span>
                            <span style="margin: 0 5px;">电话：<?php echo $item['phone']; ?></span>
                            <span>地址：<?php echo $item['user_address']; ?></span>
                            <?php if(!(empty($item['admin_remark']) || (($item['admin_remark'] instanceof \think\Collection || $item['admin_remark'] instanceof \think\Paginator ) && $item['admin_remark']->isEmpty()))): ?>
                                <div class="layui-font-orange" style="margin-top: 5px;">管理备注：<?php echo $item['admin_remark']; ?>
                                </div>
                            <?php endif; ?>

                        </td>
                    </tr>
                    </tbody>
                <?php endforeach; endif; else: echo "" ;endif; ?>

            </table>
        </div>
    </div>
    <div class="pagination"><?php echo $page; ?></div>
</div>

<script src="/static/js/admin.js"></script>
<script>
    layui.use(function () {
        var laydate = layui.laydate,
            layer = layui.layer,
            util = layui.util;

        // 日期时间范围
        laydate.render({
            elem: '#create-time',
            range: ['#create-startDate', '#create-endDate']
        });

        //操作订单
        util.on('lay-on', {
            //管理员添加备注信息
            'order-tips': function () {
                var orderid = $(this).attr("data-id"); //当前订单ID
                var value = $(this).attr("data-notes"); //当前订单备注信息
                layer.prompt({title: '请输入备注信息', value, formType: 2}, function (value, index, elem) {
                    if (value === '') return elem.focus();
                    $.ajax({
                        url: '/admin/order/addAdminRemark',
                        type: "POST",
                        data: {id: orderid, admin_remark: util.escape(value)},
                        success: function (res) {
                            if (res.code === 1) {
                                location.reload();
                            } else {
                                layer.msg(res.msg, {icon: 7});
                            }
                        }
                    });
                    //layer.msg('获得：'+ util.escape(value)); // 显示 value
                    layer.close(index); // 关闭 prompt

                });
            },
            //处理退款
            'order-refund': function () {
                var orderid = $(this).attr("data-id"); //当前订单ID
                layer.confirm('确认要通过审核？', {
                    btn: ['确定', '拒绝'] //按钮
                }, function () {
                    doRefund(orderid, 16)
                }, function () {
                    layer.prompt({title: '请输入拒绝原因', formType: 2}, function (value, index, elem) {
                        if (value === '') return elem.focus();
                        doRefund(orderid, 14, util.escape(value))
                        layer.close(index); // 关闭 prompt
                    });
                });
            },//处理退货退款
            'order-goods-refund': function () {
                var orderid = $(this).attr("data-id"); //当前订单ID
                layer.confirm('确认要通过审核？', {
                    btn: ['确定', '拒绝'] //按钮
                }, function () {
                    doRefund(orderid, 20)
                }, function () {
                    layer.prompt({title: '请输入拒绝原因', formType: 2}, function (value, index, elem) {
                        if (value === '') return elem.focus();
                        doRefund(orderid, 14, util.escape(value))
                        layer.close(index); // 关闭 prompt
                    });
                });
            }, 'order-goods-refund-price': function () {
                var orderid = $(this).attr("data-id"); //当前订单ID
                layer.confirm('确认要通过审核？', {
                    btn: ['确定', '取消'] //按钮
                }, function () {
                    doRefund(orderid, 22)
                }, function () {

                });
            },
        })


        function doRefund(id, status, text) {
            $.ajax({
                url: '/admin/order/confirmRefund',
                type: "POST",
                data: {id: id, status: status, text: text},
                success: function (res) {
                    //console.log(res);
                    if (res.code == 1) {
                        layer.msg(res.msg, {icon: 1});
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    } else {
                        layer.msg(res.msg, {icon: 7});
                    }
                }
            });
        }

    })

    //展示退款图片
    function showPhotos(images, index) {
        layer.photos({
            photos: {
                "title": "Photos Demo",
                "start": index,
                "data": images
            }
        });
    }
</script>
</body>
</html>
