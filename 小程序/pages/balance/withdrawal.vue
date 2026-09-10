<template>
    <view class="u-p-30">
        <view class="u-p-30 u-radius-10 white" style="background: linear-gradient( 135deg, #A4DB88 0%, #18AF38 100%);">
            <view class="">
                可提现金额(元)
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
                    <u-input v-model="ali_name" placeholder="请输入收款人真实姓名"></u-input>
                </view>
            </view>
            <view class="u-font-24 u-type-info u-m-t-20">
                姓名需与收款微信的实名信息一致，默认发放至微信零钱
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
                    <view class="u-flex u-row-between u-m-t-20">
                        <view class="u-type-info u-font-24">
                            单笔最低 0.1 元
                        </view>
                        <view class="u-type-primary u-font-24" @click="all_withdraw">
                            全部提现
                        </view>
                    </view>
                </view>
                <view class="u-border-top u-m-t-20 u-p-t-20" v-if="price">
                    <view class="u-flex u-row-between u-font-26 u-m-b-10">
                        <text class="u-type-info">提现金额</text>
                        <text>¥{{formatMoney(priceNum)}}</text>
                    </view>
                    <view class="u-flex u-row-between u-font-26 u-m-b-10">
                        <text class="u-type-info">手续费({{feeRate}}%)</text>
                        <text>-¥{{formatMoney(feeAmount)}}</text>
                    </view>
                    <view class="u-flex u-row-between u-font-26">
                        <text class="u-type-info">预计到账</text>
                        <text class="u-type-error u-font-bold">¥{{formatMoney(arrivalAmount)}}</text>
                    </view>
                </view>
            </view>
        </view>

        <!-- 提现规则 -->
        <view class="u-type-white-bg u-radius-12 u-m-t-30 u-p-30">
            <view class="u-font-bold">
                提现规则
            </view>
            <view class="withdraw-rule u-m-t-20">
                <view class="rule-item">
                    <text class="rule-label">1. 可提现额度：</text>
                    <text>单笔提现金额最低 0.1 元，最高不超过当前可提现余额（¥{{userInfo.balance}}），可提现金额以本页展示的金额为准。</text>
                </view>
                <view class="rule-item">
                    <text class="rule-label">2. 提现手续费：</text>
                    <text v-if="feeRate > 0">按提现金额的 {{feeRate}}% 收取，手续费从提现金额中扣除，实际到账金额 = 提现金额 - 手续费。</text>
                    <text v-else>平台暂不收取提现手续费，提现金额全额发放。</text>
                </view>
                <view class="rule-item">
                    <text class="rule-label">3. 每日提现次数：</text>
                    <text>每日提现次数不限，每一笔提现均需经管理员审核。</text>
                </view>
                <view class="rule-item">
                    <text class="rule-label">4. 提现时间：</text>
                    <text>7×24 小时均可提交提现申请。</text>
                </view>
                <view class="rule-item">
                    <text class="rule-label">5. 审核与到账时间：</text>
                    <text>提现申请提交后 1-3 个工作日内完成审核；审核通过后以微信商家转账方式发放至你的微信零钱，请在「收益明细」中点击“确认收款”领取，确认后即时到账。</text>
                </view>
                <view class="rule-item">
                    <text class="rule-label">6. 其他说明：</text>
                    <text>提现申请提交后不可撤销；若审核未通过，提现金额将退回至可提现余额，具体以提现记录状态为准。</text>
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
        computed: {
            // 手续费比例(%)
            feeRate() {
                const rate = parseFloat(this.setting && this.setting.fee_price)
                return isNaN(rate) ? 0 : rate
            },
            // 输入的提现金额
            priceNum() {
                const num = parseFloat(this.price)
                return isNaN(num) ? 0 : num
            },
            // 手续费
            feeAmount() {
                return Math.round(this.priceNum * this.feeRate) / 100
            },
            // 实际到账金额
            arrivalAmount() {
                return Math.round((this.priceNum - this.feeAmount) * 100) / 100
            }
        },
        onLoad(option) {},
        onShow() {
            this.getUserInfo()
            if (!this.isLogin) return this.$u.route('/pages/public/login')
        },
        methods: {
            // 金额保留两位小数
            formatMoney(num) {
                const value = parseFloat(num)
                return isNaN(value) ? '0.00' : value.toFixed(2)
            },
            // 提现
            withdraw() {
                const num = parseFloat(this.price)
                if (!num || isNaN(num) || num <= 0) return this.$u.toast('请输入正确的提现金额')
                if (num < 0.1) return this.$u.toast('提现金额不能低于0.1元')
                const balance = parseFloat(this.userInfo.balance)
                if (!isNaN(balance) && num > balance) return this.$u.toast('提现金额不能超过可提现余额')
                if (!this.ali_name) return this.$u.toast('请填写收款人真实姓名')
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
                    if (!this.ali_name && res.data.true_name) {
                        this.ali_name = res.data.true_name
                    }
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
    .withdraw-rule {
        .rule-item {
            font-size: 24rpx;
            line-height: 40rpx;
            color: #666;
            margin-bottom: 12rpx;
        }

        .rule-label {
            color: #333;
            font-weight: bold;
        }
    }
</style>
