<template>
    <view class="" v-if="!setting.app_test">
        <view class="u-p-30">
            <u-form :model="form" ref="uForm" :error-type="['toast']">
                <view class="u-type-white-bg u-p-lr-30 u-radius-10">
                    <u-form-item label="填写标题" prop="title" labelWidth="160">
                        <u-input v-model="form.title" input-align="right" placeholder="请输入" />
                    </u-form-item>
                </view>
                <view class="u-m-t-30 u-type-white-bg u-p-lr-30 u-radius-10">
                    <u-form-item label="上报内容" prop="content" labelWidth="160" labelPosition="bottom"
                        :border-bottom="false">
                        <u-input v-model="form.content" type="textarea" height="200" maxlength="-1"
                            placeholder="请输入上报内容~" />
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
            <u-button type="success" hover-class="none" @click="submit">确认提交</u-button>
        </view>
        <!-- <view class="u-text-center u-type-success" @click="$u.route('/pages/zzhd/yxsj/record?type='+form.type)">
            发布记录
        </view> -->
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
                    type: 6,
                    title: '',
                    content: '',
                    image: []
                },
                rules: {
                    content: [{
                        required: true,
                        message: '请输入上报内容',
                        trigger: ['change', 'blur'],
                    }],
                    title: [{
                        required: true,
                        message: '请输入标题',
                        trigger: ['change', 'blur'],
                    }],
                },
            };
        },
        onLoad(option) {
            this.form.type = option.type
        },
        methods: {
            // 上传图片
            onSuccess(reslut) {
                this.form.image.push(reslut.data.url)
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