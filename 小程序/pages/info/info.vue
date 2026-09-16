<template>
    <view class="">
        <view class="u-p-30">
            <u-form :model="form" ref="uForm" :error-type="['toast']">
                <view class="u-m-t-30 u-type-white-bg u-p-lr-30 u-radius-10">
                    <u-form-item label="姓名" prop="true_name" labelWidth="160">
                        <u-input v-model="form.true_name" placeholder="请输入姓名" />
                    </u-form-item>
                    <u-form-item label="联系电话" prop="mobile" labelWidth="160">
                        <u-input v-model="userInfo.mobile" placeholder="请输入联系电话" disabled />
                    </u-form-item>
                    <u-form-item label="所在小区" prop="community_name" labelWidth="160">
                        <u-input v-model="form.community_name" type="select" placeholder="请选择"
                            @click="cate_show = true" />
                    </u-form-item>
                </view>
            </u-form>
        </view>
		<!--  -->
		<view class="u-p-60 u-flex u-row-between">
			<u-button type="success" @click="submit" customStyle="flex:1;margin-right:20rpx">确认提交</u-button>
			<u-button type="primary" @click="goBack" customStyle="flex:1;margin-left:20rpx">确认</u-button>
		</view>
        <!-- 选择类型 -->
        <u-select v-model="cate_show" label-name="name" value-name="id" :list="cate_list" @confirm="confirm"></u-select>
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
                    true_name: '',
                    community_id: 0,
                    community_name: ''
                },
                rules: {
                    community_name: [{
                        required: true,
                        message: '请选择所在小区',
                        trigger: ['change', 'blur'],
                    }],
                    true_name: [{
                        required: true,
                        message: '请输入姓名',
                        trigger: ['change', 'blur'],
                    }],
                },
                cate_show: false,
                cate_list: []
            };
        },
        onLoad(option) {
            this.form.type = option.type
            this.get_community_list()
        },
        methods: {
            // 点位信息
            get_community_list() {
                this.$u.api.get_community_list().then(res => {
                    this.cate_list = res.data
                })
            },
            // 上传图片
            confirm(e) {
                this.form.community_id = e[0].value
                this.form.community_name = e[0].label
            },
			// 提交
			submit() {
				this.$refs.uForm.validate(valid => {
					if (valid) {
						this.$u.api.updateMember(this.form).then(res => {
							this.$u.func.showToast({
								title: res.msg,
								icon: 'success',
								back: true
							})
							this.getUserInfo()
						}).catch(res => {
							this.$u.toast(res.msg);
						})
					} else {
						console.log('验证失败');
					}
				});
			},
			// 直接返回（等同返回键）
			goBack() {
				uni.navigateBack()
			},
            // 获取用户信息
            getUserInfo() {
                this.$u.api.getUserInfo().then(res => {
                    this.$u.vuex('userInfo', res.data)
                }).catch(err => {
                    this.$u.vuex('userInfo', {})
                    this.$u.vuex('isLogin', false)
                })
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