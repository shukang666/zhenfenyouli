<?php /*a:2:{s:42:"themes/admin_fengiy/admin/forum/index.html";i:1758249961;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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
<style>
    /*.layui-text-overflow {*/
    /*    width: 200px; !* 容器宽度 *!*/
    /*    overflow: hidden; !* 隐藏超出的内容 *!*/
    /*    text-overflow: ellipsis; !* 用省略号表示超出的部分 *!*/
    /*    !*white-space: nowrap; !* 不换行 *!*!*/
    /*}*/


    /*.layui-text-overflow {*/
    /*    width: 200px; !* 容器宽度 *!*/
    /*    overflow: hidden; !* 隐藏超出的内容 *!*/
    /*    text-overflow: ellipsis; !* 用省略号表示超出的部分 *!*/
    /*    white-space: nowrap; !* 不换行 *!*/
    /*}*/


    /*定义表格的布局方式为固定布局 */
    .table {
        table-layout: fixed;
    }

    /* 定义文本的显示方式为不换行，并隐藏超出部分，用省略号表示 */
    .text {
        white-space: nowrap;
        /* 自适应宽度*/
        overflow: hidden;
        text-overflow: ellipsis;
        /*省略号显示*/
    }
</style>
<div class="wrap js-check-wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="<?php echo url('index',['type'=>$params['type']]); ?>">列表</a></li>
        <!--			        <li><a href="<?php echo url('update',['type'=>1]); ?>">添加</a></li>-->
    </ul>
    <form class="ajax-form well form-inline margin-top-20 layui-form" method="get"
          action="<?php echo url('index',['type'=>$params['type']]); ?>">
        帖子：
        <input type="text" class="form-control" name="keyword" style="width: 140px;"
               value="<?php echo input('request.keyword/s',''); ?>" placeholder="帖子内容">

        <input type="hidden" name="type" value="<?php echo $params['type']; ?>">

        用户：
        <input type="text" class="form-control" name="name" style="width: 140px;"
               value="<?php echo input('request.name/s',''); ?>" placeholder="用户昵称，手机号">
        id：
        <input type="text" class="form-control" name="id" style="width: 140px;" value="<?php echo input('request.id/s',''); ?>"
               placeholder="请输入用户id">


        <?php if($params['type']==2): 
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
        <?php endif; ?>



            <span>时间：</span>
            <div class="layui-inline" id="create-time">
                <div class="layui-input-inline" style="width: 140px;">
                    <input type="text" autocomplete="off" id="create-startDate" class="layui-input" name="start_time"
                           value="<?php echo input('request.start_time'); ?>" placeholder="开始日期">
                </div>
                -
                <div class="layui-input-inline" style="width: 140px;">
                    <input type="text" autocomplete="off" id="create-endDate" class="layui-input" name="end_time"
                           value="<?php echo input('request.end_time'); ?>" placeholder="结束日期">
                </div>
            </div>

        <?php 
            $cate_list= cmf_table('forum_cate')->where('pid','=',2)->select();
         if($params['type']==1): ?>
        分类:
                <div class="form-group layui-form">
                <select name="cate_id" >
                    <?php if(is_array($cate_list) || $cate_list instanceof \think\Collection || $cate_list instanceof \think\Paginator): if( count($cate_list)==0 ) : echo "" ;else: foreach($cate_list as $key=>$vo): if(!(empty($params['cate_id']) || (($params['cate_id'] instanceof \think\Collection || $params['cate_id'] instanceof \think\Paginator ) && $params['cate_id']->isEmpty()))): ?>
                            <option value="<?php echo $vo['id']; ?>"
                            <?php if($params['cate_id'] == $vo['id']): ?>selected<?php endif; ?>
                            ><?php echo $vo['name']; ?></option>
                            <?php else: ?>
                            <option value="<?php echo $vo['id']; ?>"><?php echo $vo['name']; ?></option>
                        <?php endif; ?>

                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </select>
                </div>
        <?php endif; ?>


        <input type="submit" class="btn btn-primary" value="搜索" style="margin-left: 25px"/>
        <a class="btn btn-danger" href="<?php echo url('index',['type'=>$params['type']]); ?>">清空</a>
        <?php if($params['type']==2): ?>
            <input type="hidden" class="export" name="export" value="0">
            <a class="btn btn-success ajax-export" href="javascript:;">导出</a>
        <?php endif; ?>

    </form>
    <form method="post" class="js-ajax-form margin-top-20" action="">

        <!--        			<button class="btn btn-primary btn-sm js-ajax-submit" type="submit"-->
        <!--        				data-action="<?php echo url('toggle',array('yes'=>1)); ?>" data-subcheck="true">批量审核通过-->
        <!--        			</button>-->
        <button class="btn layui-bg-red btn-sm js-ajax-submit" type="submit"
                data-action="<?php echo url('delete_all'); ?>" data-subcheck="true">批量删除
        </button>
        <!--                        <div class="table-actions">-->
        <!--                            <button class="btn btn-primary btn-sm js-ajax-submit" type="submit"><?php echo lang('SORT'); ?></button>-->
        <!--                        </div>-->
        <table class=" table table-hover table-bordered margin-top-20">
            <thead>
            <tr>
                <th><label><input type="checkbox" class="js-check-all" data-direction="x"
                                  data-checklist="js-check-x"></label></th>
                <th>ID</th>

                <th>用户</th>
                <?php if($is_title): ?>
                    <th>标题</th>
                <?php endif; ?>

                <!--                <th>封面</th>-->
                <?php if($is_handle): ?>
                    <th>分类</th>
                <?php endif; ?>
                <th width="300px">内容</th>
                <?php if($params['type']==2): ?>
                    <th>垃圾分类</th>
                <?php endif; ?>

                <th>图片</th>
                <!--                <th>支付凭证</th>-->
                <?php if($is_handle): ?>
                    <th>状态</th>
                <?php endif; if($params['type']==2): ?>
                    <th>小区</th>
                    <th>积分</th>
                <?php endif; ?>

                <!--                <th>删除时间</th>-->
                <th>点赞数</th>

                <th>添加时间</th>
                <th><?php echo lang('ACTIONS'); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if(is_array($list) || $list instanceof \think\Collection || $list instanceof \think\Paginator): if( count($list)==0 ) : echo "" ;else: foreach($list as $key=>$vo): ?>
                <tr>
                    <td><input type="checkbox" class="js-check" data-yid="js-check-y" data-xid="js-check-x"
                               name="ids[]" value="<?php echo $vo['id']; ?>"></td>
                    <td><?php echo $vo['id']; ?></td>


                    <td><?php echo $vo['user_info']; ?></td>
                    <?php if($is_title): ?>
                        <td><?php echo $vo['title']; ?></td>
                    <?php endif; ?>

                    <!--                    <td>-->
                    <!--                        <?php if(!empty($vo['thumb'])): ?>-->
                    <!--                            <a href="javascript:imagePreviewDialog('<?php echo cmf_get_image_preview_url($vo['thumb']); ?>');">-->
                    <!--                                <img src="<?php echo cmf_get_image_preview_url($vo['thumb']); ?>"-->
                    <!--                                     style="width:40px;height:40px;">-->
                    <!--                            </a>-->
                    <!--                        <?php endif; ?>-->
                    <!--                    </td>-->
                    <?php if($is_handle): ?>
                        <td><?php echo $vo['cate_name']; ?></td>
                    <?php endif; ?>
                    <td class="text">
                        <?php echo $vo['content']; ?>
                    </td>

                    <?php if($params['type']==2): ?>
                        <td><?php echo $vo['cate_name']; ?></td>
                    <?php endif; ?>

                    <td>
                        <?php if(!empty($vo['image'])): if(is_array($vo['image']) || $vo['image'] instanceof \think\Collection || $vo['image'] instanceof \think\Paginator): if( count($vo['image'])==0 ) : echo "" ;else: foreach($vo['image'] as $key=>$item): ?>
                                <a href="javascript:imagePreviewDialog('<?php echo cmf_get_image_preview_url($item); ?>');">
                                    <img src="<?php echo cmf_get_image_preview_url($item); ?>"
                                         style="width:40px;height:40px;round-clip: 40px">
                                </a>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        <?php endif; ?>
                    </td>


                    <!--                    <td>-->
                    <!--                        <?php if(!empty($vo['video'])): ?>-->
                    <!--                            <a class="btn btn-info inline" id="file-video-preview"-->
                    <!--                               href="<?php echo cmf_get_file_download_url($vo['video']); ?>"-->
                    <!--                               target="_blank"><i class="fa fa-play-circle fa-fw"></i></a>-->
                    <!--                        <?php endif; ?>-->
                    <!--                    </td>-->

                    <!--                    <td>-->
                    <!--                        <?php if(!empty($vo['delete_time'])): ?>-->
                    <!--                            <?php echo date("Y-m-d H:i:s",!is_numeric($vo['delete_time'])? strtotime($vo['delete_time']) : $vo['delete_time']); ?>-->
                    <!--                        <?php endif; ?>-->
                    <!--                    </td>-->
                    <?php if($is_handle): ?>
                        <td><?php echo $vo['status_name']; ?></td>
                    <?php endif; if($params['type']==2): ?>
                        <td><?php echo $vo['community_name']; ?></td>
                        <td><?php echo $vo['point']; ?></td>
                    <?php endif; ?>
                    <td><?php echo $vo['like_total']; ?></td>

                    <td><?php echo date("Y-m-d H:i:s",!is_numeric($vo['create_time'])? strtotime($vo['create_time']) : $vo['create_time']); ?></td>

                    <td>
                        <!--                        <?php if($vo['status'] == 1): ?>-->
                        <!--                            <a class="layui-btn layui-btn-xs layui-bg-cyan" onclick='check("<?php echo $vo['id']; ?>")'>审核</a>-->
                        <!--                        <?php endif; ?>-->
                        <?php if($is_handle && $vo['status'] == 2): ?>
                            <a class="btn btn-xs btn-primary"
                               href="javascript:parent.openIframeLayer('<?php echo url('handle',array('id'=>$vo['id'])); ?>','处理内容',{area:['40%','40%']});">处理内容</a>
                        <?php endif; ?>
                        <a class="btn btn-xs btn-primary"
                          href="javascript:parent.openIframeLayer('<?php echo url('forum_comment/index',array('info_id'=>$vo['id'])); ?>','评论',{area:['60%','80%']});">评论</a>

                        <a class="btn btn-xs btn-danger js-ajax-delete"
                           href="<?php echo url('delete',array('id'=>$vo['id'])); ?>"><?php echo lang('DELETE'); ?></a>
                    </td>
                </tr>
            <?php endforeach; endif; else: echo "" ;endif; ?>
            </tbody>
        </table>
    </form>
    <div class="pagination"><?php echo $page; ?></div>

</div>
<script src="/static/js/admin.js"></script>
<script>
    layui.use(['layer', 'form'], function () {
        let layer = layui.layer, form = layui.form;

        var laydate = layui.laydate;
        // 日期时间范围
        laydate.render({
            elem: '#create-time',
            range: ['#create-startDate', '#create-endDate'],
            // range: true
            rangeLinked: true
        });
    });
</script>
<script>





    //导出数据
    $(".ajax-export").click(function () {
        // console.log($(".ajax-form"));
        $(".export").val(1);
        $(".ajax-form").submit();
        $(".export").val(0);
        // console.log($(".ajax-form").submit())
        return false;
    });


    layui.use(['form', 'element'], function () {
        var form = layui.form,
            element = layui.element;

        var first_cate = "<?php echo isset($params['cate_id']) ? $params['cate_id'] : 0; ?>"
        var second_cate = "<?php echo input('request.cate_child_id/s',''); ?>"
        getCateList(0, $("#first_cate"), 'first_cate');

        // if (first_cate) {
        // 	getCateList(first_cate, $("#second_cate"), 'second_cate');
        // }

        form.on('select(first_cate)', function (data) {
            first_cate = data.value;
            getCateList(data.value, $("#second_cate"), 'second_cate');
        })

        function getCateList(pid, target, type) {
            $.ajax({
                type: 'POST',
                url: "<?php echo url('getJsonCateList'); ?>",
                data: {
                    pid: pid,
                },
                dataType: 'json',
                success: function (data) {
                    var cate_list = data.data;
                    var str = "<option value=''>" + "请选择" + "</option>";
                    for (var i = 0; i < cate_list.length; i++) {

                        if (type == 'first_cate') {
                            var where = first_cate;
                        } else if (type == 'second_cate') {
                            var where = second_cate;
                        }
                        if (where == cate_list[i].id) {
                            str += "<option value='" + cate_list[i].id + "' selected>" + cate_list[i]
                                .cate_name + "</option>";
                        } else {
                            str += "<option value='" + cate_list[i].id + "'>" + cate_list[i]
                                .cate_name + "</option>";
                        }
                    }
                    target.html(str);
                    form.render('select');
                }
            });
        }
    })
</script>
<script>
    $('.text').mouseenter(function () {
        var text = $(this).text();
        let that = $(this);


        let wid;

        if (text.length > 1000) {
            wid = '900px';
        } else if (text.length > 600) {
            wid = '700px';
        } else if (text.length > 150) {
            wid = '500px';
        } else if (text.length > 100) {
            wid = '250px';
        } else if (text.length > 50) {
            wid = '150px';
        }
        layui.use('layer', function (layer) {
            tip_index = layer.tips('<span style="word-wrap: break-word;">' + text + '</span>', that, {
                tips: [4, "#000000"],
                time: 0,
                area: [wid, 'auto']
            });
        });
    })

    $('.text').mouseout(function () {
        layui.use('layer', function (layer) {
            layer.close(tip_index);
        });
    });


    function check(id) {
        layer.confirm('确认审核通过？', {
            btn: ['通过', '拒绝'] //按钮
        }, function () {
            doCheck(id, 1)
        }, function () {
            layer.prompt({
                title: '请说明拒绝原因',
                formType: 2
            }, function (text, index) {
                doCheck(id, 2, text);
            });
        });
    }

    function doCheck(id, status, text) {
        $.ajax({
            url: "<?php echo url('fix_status'); ?>",
            type: "POST",
            data: {
                id: id,
                status: status,
                text: text
            },
            success: function (res) {
                if (res.code == 1) {
                    layer.msg(res.msg, {
                        icon: 1
                    });
                    setTimeout(function () {
                        location.reload();
                    }, 1000);

                } else {
                    layer.msg(res.msg, {
                        icon: 7
                    });
                }
            }
        });
    }
</script>
</body>
</html>
<style>
    .text-container {
        width: 200px;
        height: 50px;
        overflow: hidden;
    }
</style>

</script>