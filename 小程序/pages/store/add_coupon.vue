<template>
    <view class="">
        <view class="u-p-30">
            <u-form :model="form" ref="uForm" :error-type="['border-bottom','toast']">
                <view class="u-type-white-bg u-radius-10 u-p-lr-30">
                    <u-form-item label="优惠券名称" prop="name" labelWidth="300">
                        <u-input v-model="form.name" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="抵扣金额(元)" prop="full_amount" labelWidth="300">
                        <u-input v-model="form.full_amount" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="满多少元可用" prop="amount" labelWidth="300">
                        <u-input v-model="form.amount" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="有效期" prop="indate_day" labelWidth="300">
                        <u-input v-model="form.indate_day" placeholder="请填写" input-align="right" />
						<text slot="right">天</text>
                    </u-form-item>
                </view>
            </u-form>
            <u-gap height="120"></u-gap>
            <view class="submit-btn u-p-30">
                <u-button type="primary" shape="circle" hover-class="none"
                    :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                    :disabled="disabled" @click="submit">确认提交</u-button>
            </view>
        </view>
        <!-- 选择时间 -->
        <u-select v-model="select_show" :list="select_list" label-name="cate_name" value-name="id"
            @confirm="confirm"></u-select>
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
                    id: 0,
                    name: '',
                    full_amount: '',
                    amount: '',
                    indate_day: ''
                },
                rules: {
                    indate_day: [{
                        required: true,
                        message: '请输入有效期',
                        trigger: ['change', 'blur'],
                    }],
                    amount: [{
                        required: true,
                        message: '请输入满多少可用',
                        trigger: ['change', 'blur'],
                    }],
                    full_amount: [{
                        required: true,
                        message: '请输入折扣金额',
                        trigger: ['change', 'blur'],
                    }],
                    name: [{
                        required: true,
                        message: '请输入优惠卷名称',
                        trigger: ['change', 'blur'],
                    }],
                },
            };
        },
        onLoad(option) {
            if(option.id){
                this.get_shop_coupon_list(option.id)
            }
        },
        methods: {
            // 详情
            get_shop_coupon_list(id) {
                this.$u.api.get_shop_coupon_list({
                    id
                }).then(res => {
                    if (!res.data) return
                    for (var key in this.form) {
                        this.form[key] = res.data[key]
                    }
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 提交
            submit() {
                this.$refs.uForm.validate(valid => {
                    if (valid) {
                        this.$u.api.add_coupon(this.form).then(res => {
							this.$u.toast(res.msg, 'success')
                            setTimeout(() => {
								this.$u.func.prePage().Reload()
								uni.navigateBack()
							}, 1500)
                        }).catch(err => {
                            this.$u.toast(err.msg)
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