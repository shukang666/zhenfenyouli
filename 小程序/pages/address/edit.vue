<template>
    <view class="u-p-30">
        <view class="u-p-lr-30 u-type-white-bg u-radius-10">
            <u-form :model="form" ref="uForm" :error-type="['toast', 'border-bottom']">
                <u-form-item label="姓名" prop="username" label-width="150">
                    <u-input v-model="form.username" placeholder="请输入姓名" />
                </u-form-item>
                <u-form-item label="联系电话" prop="phone" label-width="150">
                    <u-input v-model="form.phone " placeholder="请输入联系电话" />
                </u-form-item>
                <u-form-item label="所在地区" prop="area" label-width="150" right-icon="arrow-right">
                    <u-input v-model="form.area" :disabled="true" placeholder="请选择所在地区" @click="cityShow = true" />
                </u-form-item>
                <u-form-item label="详细地址" prop="address" label-width="150" label-position="top">
                    <u-input v-model="form.address" placeholder="如道路、门牌号、小区、楼栋号、单元室等" />
                </u-form-item>
                <u-form-item label="设为默认地址" label-width="300">
                    <u-switch slot="right" v-model="switchVal" size="35" activeColor="#18AF38"></u-switch>
                </u-form-item>
            </u-form>
        </view>

        <view class="submit-btn safe-area-inset-bottom">
            <u-button type="warning" shape="circle" hover-class="none"
                :custom-style="{margin:'20rpx 30rpx',background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="submitBtn">
                确认</u-button>
        </view>

        <!-- 选择地址 -->
        <city-select v-model="cityShow" @city-change="cityChange"></city-select>
    </view>
</template>

<script>
    import citySelect from './components/u-city-select.vue'; //uview自带省市县
    // import citySelect from './components/city-select.vue'; //后台返回省市县
    export default {
        components: {
            citySelect
        },
        data() {
            return {
                type: '',
                form: {
                    username: '',
                    phone: '',
                    area: '',
                    address: ''
                },
                rules: {
                    username: [{
                        required: true,
                        min: 2,
                        max: 8,
                        message: '请输入收货人姓名',
                        trigger: ['change', 'blur'],
                    }],
                    phone: [{
                        required: true,
                        message: '请填写收货人手机号',
                        trigger: ['change', 'blur'],

                    }, {
                        validator: (rule, value, callback) => {
                            return this.$u.test.mobile(this.form.phone);
                        },
                        message: '电话号码不正确',
                        trigger: ['change', 'blur'],
                    }],
                    area: [{
                        required: true,
                        message: '请选择收货地址',
                        trigger: ['change'],
                    }],
                    address: [{
                        required: true,
                        message: '请输入详细地址',
                        trigger: ['change', 'blur'],
                    }]
                },
                cityShow: false,
                switchVal: false
            };
        },
        onReady() {
            this.$refs.uForm.setRules(this.rules);
        },
        onLoad(option) {
            this.type = option.type;
            uni.setNavigationBarTitle({
                title: option.type == 'add' ? '添加收货地址' : '编辑收货地址'
            });
            if (option.id) this.getUserAddress(option.id); //获取地址信息
        },
        methods: {
            //获取地址信息
            async getUserAddress(id) {
                const res = await this.$u.api.get_address_info({
                    id
                });
                this.form = res.data;
                this.form.area = this.form.province + '-' + this.form.city + '-' + this.form.county;
                if (this.form.is_default) this.switchVal = true;
            },
            //选择收货地址
            cityChange(e) {
                this.form.area = e.province.label + '-' + e.city.label + '-' + e.area.label;
            },
            //提交信息
            submitBtn() {
                this.$refs.uForm.validate(valid => {
                    if (valid) {
                        this.$u.api.post_address_data({
                            type: this.type,
                            id: this.form.id,
                            province: this.form.area.split('-')[0],
                            city: this.form.area.split('-')[1],
                            county: this.form.area.split('-')[2],
                            address: this.form.address,
                            username: this.form.username,
                            phone: this.form.phone,
                            is_default: this.switchVal ? 1 : ''
                        }).then(res => {
                            this.$u.func.showToast({
                                title: res.msg,
                                icon: 'success',
                                back: true
                            })
                        }).catch(err => {
                            this.$u.toast(err.msg)
                        })
                    }
                });
            }
        }
    }
</script>

<style lang="scss">

</style>