<?php /*a:2:{s:45:"themes/admin_fengiy/admin/app_info/index.html";i:1757063669;s:77:"/www/wwwroot/xcxkf052.aubye.com/public/themes/admin_fengiy/public/header.html";i:1693132508;}*/ ?>
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

<!-- 多图上传引入开始-->
<script type="text/html" id="photos-item-tpl">
    <li id="saved-image{id}" style="cursor:pointer;margin-bottom: 5px;margin-right: 10px;">
        <input id="photo-{id}" type="hidden" name="photos[]" value="{filepath}">
        <input class="form-control" id="photo-{id}-name" type="hidden" name="photo_names[]" value="{name}"
               style="width: 200px;" title="图片名称">
        <img id="photo-{id}-preview" src="{url}" style="height:80px;width: 80px;">
        <div>
            <a href="javascript:uploadOneImage('图片上传','#photo-{id}');">替换</a>
            <a href="javascript:(function(){$('#saved-image{id}').remove();})();">移除</a>
        </div>
    </li>
</script>
<!-- 多图上传引入结束-->
</head>
<body>
<div class="wrap">
    <ul class="nav nav-tabs">
        <li class="active"><a href="#A" data-toggle="tab">基础设置</a></li>
        <!-- <li><a href="#B" data-toggle="tab">公告设置</a></li>-->
    </ul>

    <form method="post" class="form-horizontal js-ajax-form margin-top-20" action="<?php echo url('AppInfo/index'); ?>">
        <fieldset>
            <div class="tabbable">
                <div class="tab-content">
                    <div class="tab-pane active" id="A">
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>应用名称</label>
                            <div class="col-md-6 col-sm-10">
                                <input type="text" class="form-control" name="app_name"
                                       value="<?php echo (isset($data['app_name']) && ($data['app_name'] !== '')?$data['app_name']:''); ?>" required>
                            </div>
                        </div>
                        <!--                        <?php if(APP_DEBUG): ?>-->
                        <!--                              <div class="form-group">-->
                        <!--                                    <label class="col-sm-2 control-label"><span class="form-required">*</span>应用到期时间</label>-->
                        <!--                                    <div class="col-md-6 col-sm-10">-->
                        <!--                                        <input type="text" class="form-control js-date" autocomplete="off" name="app_endtime" value="<?php echo (isset($data['app_endtime']) && ($data['app_endtime'] !== '')?$data['app_endtime']:''); ?>" required>-->
                        <!--                                        <span class="help-block text-danger">到期后则不允许登录小程序限制访问</span>-->
                        <!--                                    </div>-->
                        <!--                                </div>-->
                        <!--                        <?php endif; ?>-->
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>应用logo:</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="hidden" name="app_logo" id="app_logo" value="<?php echo (isset($data['app_logo']) && ($data['app_logo'] !== '')?$data['app_logo']:''); ?>">
                                <a href="javascript:uploadOneImage('图片上传','#app_logo');">
                                    <?php if(!(empty($data['app_logo']) || (($data['app_logo'] instanceof \think\Collection || $data['app_logo'] instanceof \think\Paginator ) && $data['app_logo']->isEmpty()))): ?>
                                        <img src="<?php echo cmf_get_image_preview_url($data['app_logo']); ?>" id="app_logo-preview"
                                             width="135" style="cursor: hand"/>
                                        <?php else: ?>
                                        <img src="/themes/admin_fengiy/public/assets/images/default-image.png" id="app_logo-preview"
                                             width="135" style="cursor: hand"/>
                                    <?php endif; ?>
                                </a>
                                <input type="button" class="btn btn-sm"
                                       onclick="$('#app_logo-preview').attr('src','/themes/admin_fengiy/public/assets/images/default-image.png');$('#app_logo').val('');return false;"
                                       value="取消图片">
                            </div>


                            <label class="col-sm-1 control-label">小logo:</label>
                            <div class="col-md-1 col-sm-10">
                                <?php  $thumb='min_logo'; ?>
                                <input type="hidden" name="<?php echo $thumb; ?>" id="<?php echo $thumb; ?>" value="<?php echo (isset($data[$thumb]) && ($data[$thumb] !== '')?$data[$thumb]:''); ?>">
                                <a href="javascript:uploadOneImage('图片上传','#<?php echo $thumb; ?>');">
                                    <?php if(empty($data[$thumb])): ?>
                                        <img src="/themes/admin_fengiy/public/assets/images/default-thumbnail.png"
                                             id="<?php echo $thumb; ?>-preview"
                                             width="135" style="cursor: pointer"/>
                                        <?php else: ?>
                                        <img src="<?php echo cmf_get_image_preview_url($data[$thumb]); ?>"
                                             id="<?php echo $thumb; ?>-preview"
                                             width="135" style="cursor: pointer"/>
                                    <?php endif; ?>
                                </a>
                                <input type="button" class="btn btn-sm"
                                       onclick="$('#<?php echo $thumb; ?>-preview').attr('src','/themes/admin_fengiy/public/assets/images/default-image.png');$('#<?php echo $thumb; ?>').val('');return false;"
                                       value="取消图片">


                            </div>
                        </div>


                        <!--                        <div class="form-group">-->
                        <!--                            <label class="col-sm-2 control-label">垃圾分类上报图例:</label>-->
                        <!--                            <div class="col-md-6 col-sm-10">-->
                        <!--                                <?php   $thumb='waste_thumb'; ?>-->
                        <!--                                <input type="hidden" name="<?php echo $thumb; ?>" id="<?php echo $thumb; ?>" value="<?php echo (isset($item[$thumb]) && ($item[$thumb] !== '')?$item[$thumb]:''); ?>">-->
                        <!--                                <a href="javascript:uploadOneImage('图片上传','#<?php echo $thumb; ?>');">-->
                        <!--                                    <?php if(empty($item[$thumb])): ?>-->
                        <!--                                        <img src="/themes/admin_fengiy/public/assets/images/default-thumbnail.png"-->
                        <!--                                             id="<?php echo $thumb; ?>-preview"-->
                        <!--                                             width="135" style="cursor: pointer"/>-->
                        <!--                                        <?php else: ?>-->
                        <!--                                        <img src="<?php echo cmf_get_image_preview_url($item[$thumb]); ?>"-->
                        <!--                                             id="<?php echo $thumb; ?>-preview"-->
                        <!--                                             width="135" style="cursor: pointer"/>-->
                        <!--                                    <?php endif; ?>-->
                        <!--                                </a>-->
                        <!--                            </div>-->
                        <!--                        </div>-->

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>视频号ID</label>
                            <div class="col-md-2 col-sm-10">
                                <input type="text" class="form-control" name="video_id"
                                       value="<?php echo (isset($data['video_id']) && ($data['video_id'] !== '')?$data['video_id']:''); ?>" required>
                            </div>

                            <label class="col-sm-1 control-label"><span class="form-required">*</span>小程序ID</label>
                            <div class="col-md-2 col-sm-10">
                                <input type="text" class="form-control" name="min_app_id"
                                       value="<?php echo (isset($data['min_app_id']) && ($data['min_app_id'] !== '')?$data['min_app_id']:''); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>平台商家手机号</label>
                            <div class="col-md-2 col-sm-10">
                                <input type="number" class="form-control" min="1" name="platform_mobile"
                                       value="<?php echo (isset($data['platform_mobile']) && ($data['platform_mobile'] !== '')?$data['platform_mobile']:''); ?>" required>
                            </div>
                        </div>



                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>上报获得多少积分</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="number" class="form-control" min="0" name="carbon"
                                       value="<?php echo (isset($data['carbon']) && ($data['carbon'] !== '')?$data['carbon']:''); ?>" required>
                            </div>

                            <label class="col-sm-1 control-label"><span class="form-required">*</span> 健康生活社区得多少积分</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="number" class="form-control" min="0" name="health_point"
                                       value="<?php echo (isset($data['health_point']) && ($data['health_point'] !== '')?$data['health_point']:''); ?>" required>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span
                                    class="form-required">*</span>课程培训</label>
                            <div class="col-md-1 col-sm-10">
                                <select name="is_course" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['is_course']) && $data['is_course'] == 0): ?>selected<?php endif; ?>
                                    >关闭</option>
                                    <option value="1"
                                    <?php if(isset($data['is_course']) && $data['is_course'] == 1): ?>selected<?php endif; ?>
                                    >开启</option>
                                </select>
                            </div>

                            <label class="col-sm-1 control-label"><span
                                    class="form-required">*</span>社区活动</label>
                            <div class="col-md-1 col-sm-10">
                                <select name="is_active" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['is_active']) && $data['is_active'] == 0): ?>selected<?php endif; ?>
                                    >关闭</option>
                                    <option value="1"
                                    <?php if(isset($data['is_active']) && $data['is_active'] == 1): ?>selected<?php endif; ?>
                                    >开启</option>
                                </select>
                            </div>
                            <label class="col-sm-1 control-label"><span
                                    class="form-required">*</span>社区知识竞赛</label>
                            <div class="col-md-1 col-sm-10">
                                <select name="is_match" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['is_match']) && $data['is_match'] == 0): ?>selected<?php endif; ?>
                                    >关闭</option>
                                    <option value="1"
                                    <?php if(isset($data['is_match']) && $data['is_match'] == 1): ?>selected<?php endif; ?>
                                    >开启</option>
                                </select>
                            </div>
                        </div>




                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span
                                    class="form-required">*</span>环保论坛</label>
                            <div class="col-md-1 col-sm-10">
                                <select name="is_environmental" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['is_environmental']) && $data['is_environmental'] == 0): ?>selected<?php endif; ?>
                                    >关闭</option>
                                    <option value="1"
                                    <?php if(isset($data['is_environmentalis_environmental']) && $data['is_environmental'] == 1): ?>selected<?php endif; ?>
                                    >开启</option>
                                </select>
                            </div>

                            <label class="col-sm-1 control-label"><span
                                    class="form-required">*</span>互助平台</label>
                            <div class="col-md-1 col-sm-10">
                                <select name="is_mutual" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['is_mutual']) && $data['is_mutual'] == 0): ?>selected<?php endif; ?>
                                    >关闭</option>
                                    <option value="1"
                                    <?php if(isset($data['is_mutual']) && $data['is_mutual'] == 1): ?>selected<?php endif; ?>
                                    >开启</option>
                                </select>
                            </div>
                            <label class="col-sm-1 control-label"><span
                                    class="form-required">*</span>优秀事迹</label>
                            <div class="col-md-1 col-sm-10">
                                <select name="is_deed" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['is_deed']) && $data['is_deed'] == 0): ?>selected<?php endif; ?>
                                    >关闭</option>
                                    <option value="1"
                                    <?php if(isset($data['is_deed']) && $data['is_deed'] == 1): ?>selected<?php endif; ?>
                                    >开启</option>
                                </select>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>点赞</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="number" class="form-control" min="0" name="like_num"
                                       value="<?php echo (isset($data['like_num']) && ($data['like_num'] !== '')?$data['like_num']:''); ?>" required>
                            </div>
                            <label class="col-sm-1 control-label"><span class="form-required">*</span>送积分</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="number" class="form-control" min="0" name="like_point"
                                       value="<?php echo (isset($data['like_point']) && ($data['like_point'] !== '')?$data['like_point']:''); ?>" required>
                            </div>
                            <span class="help-block">点赞多少获得多少积分</span>
                        </div>



                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>评论</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="number" class="form-control" min="0" name="comment_num"
                                       value="<?php echo (isset($data['comment_num']) && ($data['comment_num'] !== '')?$data['comment_num']:''); ?>" required>
                            </div>
                            <label class="col-sm-1 control-label"><span class="form-required">*</span>送积分</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="number" class="form-control" min="0" name="comment_point"
                                       value="<?php echo (isset($data['comment_point']) && ($data['comment_point'] !== '')?$data['comment_point']:''); ?>" required>
                            </div>
                            <span class="help-block">评论多少获得多少积分</span>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>抵扣1元需要多少积分</label>
                            <div class="col-md-1 col-sm-10">
                                <input type="number" class="form-control" name="point_ratio"
                                       value="<?php echo (isset($data['point_ratio']) && ($data['point_ratio'] !== '')?$data['point_ratio']:''); ?>" required>
                            </div>
                        </div>
f
                        <!--                    </div>-->
                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>开启审核</label>
                            <div class="col-md-6 col-sm-10">
                                <select name="app_test" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['app_test']) && $data['app_test'] == 0): ?>selected<?php endif; ?>
                                    >关闭</option>
                                    <option value="1"
                                    <?php if(isset($data['app_test']) && $data['app_test'] == 1): ?>selected<?php endif; ?>
                                    >开启</option>
                                </select>
                                <span class="help-block text-danger">用于小程序审核</span>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>垃圾分类</label>
                            <div class="col-md-6 col-sm-10">
                                <select name="is_waste" class="form-control">
                                    <option value="0"
                                    <?php if(isset($data['is_waste']) && $data['is_waste'] == 0): ?>selected<?php endif; ?>
                                    >隐藏</option>
                                    <option value="1"
                                    <?php if(isset($data['is_waste']) && $data['is_waste'] == 1): ?>selected<?php endif; ?>
                                    >显示</option>
                                </select>
                                <span class="help-block text-danger">用于垃圾分类显示或隐藏</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span
                                    class="form-required">*</span>客服二维码:</label>
                            <div class="col-md-6 col-sm-10">
                                <input type="hidden" name="wx_code" id="wx_code" value="<?php echo (isset($data['wx_code']) && ($data['wx_code'] !== '')?$data['wx_code']:''); ?>">
                                <a href="javascript:uploadOneImage('图片上传','#wx_code');">
                                    <?php if(!(empty($data['wx_code']) || (($data['wx_code'] instanceof \think\Collection || $data['wx_code'] instanceof \think\Paginator ) && $data['wx_code']->isEmpty()))): ?>
                                        <img src="<?php echo cmf_get_image_preview_url($data['wx_code']); ?>" id="wx_code-preview"
                                             width="135" style="cursor: hand"/>
                                        <?php else: ?>
                                        <img src="/themes/admin_fengiy/public/assets/images/default-image.png" id="wx_code-preview"
                                             width="135" style="cursor: hand"/>
                                    <?php endif; ?>
                                </a>
                                <input type="button" class="btn btn-sm"
                                       onclick="$('#wx_code-preview').attr('src','/themes/admin_fengiy/public/assets/images/default-image.png');$('#wx_code').val('');return false;"
                                       value="取消图片">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label">低碳公告设置</label>
                            <div class="col-md-6 col-sm-10">
                                <textarea class="form-control"
                                          rows="5"
                                          name="carbon_notice"><?php echo (isset($data['carbon_notice']) && ($data['carbon_notice'] !== '')?$data['carbon_notice']:''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-sm-2 control-label"><span class="form-required">*</span>参与问卷与预约标题</label>
                            <div class="col-md-2 col-sm-10">
                                <input type="text" class="form-control" name="health_title"
                                       value="<?php echo (isset($data['health_title']) && ($data['health_title'] !== '')?$data['health_title']:''); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">参与问卷与预约内容</label>
                            <div class="col-md-6 col-sm-10">
                                <textarea class="form-control"
                                          rows="5"
                                          name="health_notice"><?php echo (isset($data['health_notice']) && ($data['health_notice'] !== '')?$data['health_notice']:''); ?></textarea>
                            </div>
                        </div>


                        <div class="form-group">
                            <label class="col-sm-2 control-label">文明标兵介绍</label>
                            <div class="col-md-6 col-sm-10">
                                <textarea class="form-control"
                                          rows="5"
                                          name="civilization_notice"><?php echo (isset($data['civilization_notice']) && ($data['civilization_notice'] !== '')?$data['civilization_notice']:''); ?></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="B">

                        <div class="form-group">
                            <div class="col-sm-offset-2 col-sm-10">
                                <button type="submit" class="btn btn-primary js-ajax-submit"><?php echo lang('SAVE'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </form>
</div>
<script src="/static/js/admin.js"></script>
<!-----多图上传排序引入------>
<script>
    //针对多图上传支持排序
    new Sortable(photos, {
        animation: 150,
        ghostClass: 'blue-background-class'
    });
</script>
</body>
</html>