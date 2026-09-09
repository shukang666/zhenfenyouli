<template>
    <view class="" v-if="!setting.app_test">
        <view class="u-p-30">
            <u-form :model="form" ref="uForm" :error-type="['toast']">
                <view class="u-type-white-bg u-p-lr-30 u-radius-10">
                    <u-form-item label="请选择类别" prop="cate_name" labelWidth="160">
                        <u-input v-model="form.cate_name" input-align="right" placeholder="请选择" type="select"
                            @click="cate_show = true" />
                    </u-form-item>
                    <u-form-item label="填写标题" prop="title" labelWidth="160">
                        <u-input v-model="form.title" input-align="right" placeholder="请输入" />
                    </u-form-item>
                </view>
                <view class="u-m-t-30 u-type-white-bg u-p-lr-30 u-radius-10">
                    <u-form-item label="分享内容" prop="content" labelWidth="160" labelPosition="bottom"
                        :border-bottom="false">
                        <u-input v-model="form.content" type="textarea" height="200" maxlength="-1"
                            placeholder="请输入分享内容~" />
                    </u-form-item>
                    <view class="u-p-tb-30">
                        <cl-upload v-model="form.image" :action="uploadUrl" @onSuccess="onSuccess" fileType="image">
                            <template v-slot:addImg>
                                <u-image width="138" height="138" src="/static/upload.png"></u-image>
                            </template>
                        </cl-upload>
                    </view>
                </view>
            </u-form>
        </view>
        <!--  -->
        <view class="u-p-60">
            <u-button type="primary" hover-class="none"
                @click="submit">确认提交</u-button>
        </view>
        <view class="u-text-center" style="color: #3974f2;" @click="$u.route('/pages/community/record')">
            发布记录
        </view>
        <!-- 选择类型 -->
        <u-select v-model="cate_show" label-name="name" value-name="id" :list="cate_list" @confirm="confirm"></u-select>
    </view>
    <view class="" v-else>
        <u-image width="138" height="138" :src="setting.app_logo"></u-image>
        <view class="u-m-t-30 u-text-center u-type-info">
            cnm,sg
        </view>
    </view>
</template>

<script>
    import {
        uploadUrl
    } from '@/common/settings'
    export default {
        data() {
            return {
                uploadUrl,
                form: {
                    type:1,
                    cate_name: '',
                    cate_id: 0,
                    title: '',
                    content: '',
                    image: []
                },
                rules: {
                    content: [{
                        required: true,
                        message: '请输入您要上报的内容',
                        trigger: ['change', 'blur'],
                    }],
                    title: [{
                        required: true,
                        message: '请输入标题',
                        trigger: ['change', 'blur'],
                    }],
                    cate_name: [{
                        required: true,
                        message: '请选择类别',
                        trigger: ['change', 'blur'],
                    }],
                },
                cate_show: false,
                cate_list: [],
            };
        },
        onLoad(option) {
            this.get_cate_list()
            if (option.id) {
                this.get_forum_list(option.id)
            }
        },
        methods: {
            // 详情
            get_forum_list(id) {
                this.$u.api.get_forum_list({
                    id
                }).then(res => {
                    this.form.id = res.data.id
                    this.form.type = res.data.type
                    this.form.cate_id = res.data.cate_id
                    this.form.cate_name = res.data.cate_name
                    this.form.title = res.data.title
                    this.form.content = res.data.content
                    this.form.image = res.data.image
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 分类
            get_cate_list() {
                this.$u.api.get_cate_list().then(res => {
                    this.cate_list = res.data
                })
            },
            // 上传图片
            onSuccess(reslut) {
                this.form.image.push(reslut.data.url)
            },
            // 类型选择
            confirm(e) {
                this.form.cate_name = e[0].label
                this.form.cate_id = e[0].value
            },
            // 提交
            submit() {
                this.$refs.uForm.validate(valid => {
                    if (valid) {
                        this.$u.api.add_forum(this.form).then(res => {
                            this.$u.func.showToast({
                                title: res.msg,
                                icon: 'success',
                                back: true
                            })
                            this.$u.func.prePage().forum_list = []
                            this.$u.func.prePage().page = 1
                            this.$u.func.prePage().get_forum_list()
                        }).catch(res => {
                            this.$u.toast(res.msg);
                        })
                    } else {
                        console.log('验证失败');
                    }
                });
            },
        },
        // 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
        onReady() {
            this.$refs.uForm.setRules(this.rules);
        }
    }
</script>

<style lang="scss">

</style>