<template>
    <view class="u-p-30">
        <view class="u-p-30 u-radius-10 white" style="background: linear-gradient( 135deg, #A4DB88 0%, #18AF38 100%);">
            <view class="">
                可提金额
            </view>
            <view class="u-font-42  u-font-bold u-m-t-30">
                {{userInfo.balance}}
            </view>
        </view>
        <view class="u-type-white-bg u-p-30 u-m-t-30 u-radius-10">
            <view class="u-font-bold">
                提现信息
            </view>
            <view class="u-m-t-30 u-flex">
                <u-icon name="weixin-circle-fill" color="#28c445" size="50"></u-icon>
                <view class="u-m-l-20 u-flex-1">
                    <u-input v-model="ali_name" placeholder="请输入姓名"></u-input>
                </view>
            </view>
        </view>
        <view class="u-type-white-bg u-radius-12 u-m-t-50 u-p-30">
            <view class="">
                <view class="u-font-bold">
                    提现金额
                </view>
                <view class="u-m-t-30">
                    <view class="">
                        <u-input v-model="price" type="digit" :border="true" placeholder="请输入提现金额" />
                    </view>
                    <!-- <view class="u-type-primary u-font-24 u-m-t-20 u-text-right" @click="all_withdraw">
                        全部提现
                    </view> -->
                </view>
            </view>
        </view>

        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30 ">
            <u-button type="primary" shape="circle" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="withdraw">立即提现</u-button>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                price: null,
                ali_name: '',
                ali_account: '',
                set_point: {},
                type: 0,
                fee: 0,
                real_amount: 0
            };
        },
        onLoad(option) {},
        onShow() {
            this.getUserInfo()
            if (!this.isLogin) return this.$u.route('/pages/public/login')
        },
        methods: {
            // 提现
            withdraw() {
                if (!this.price) return this.$u.toast('请填提现金额')
                this.$u.api.withdraw({
                    num: this.price,
                    ali_name: this.ali_name
                }).then(res => {
                    this.$u.toast(res.msg)
                    this.price = ''
                    this.getUserInfo()
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 全部提现
            all_withdraw() {
                this.price = this.userInfo.balance
            },
            // 获取用户信息
            getUserInfo() {
                this.$u.api.getUserInfo().then(res => {
                    this.$u.vuex('userInfo', res.data)
                }).catch(err => {
                    this.$u.vuex('userInfo', {})
                    this.$u.vuex('isLogin', false);
                    this.$u.route('/pages/public/login')
                })
            },
        }
    }
</script>

<style lang="scss">
</style>