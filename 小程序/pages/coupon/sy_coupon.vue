<template>
    <view class="u-p-lr-30">
        <view class="coupon" v-for="item in coupon_list" :key="item.id">
            <view class="u-flex u-flex-1">
                <view class="u-flex-col u-w-186 u-row-center u-col-center">
                    <view class="u-font-26 u-type-error">
                        &yen;<text class="u-font-55 u-font-bold u-m-l-5">{{item.amount}}</text>
                    </view>
                    <view class="grey u-font-26">
                        满{{item.full_amount}}可用
                    </view>
                </view>
                <view class="line"></view>
                <view class="u-m-l-30">
                    <view class="u-font-32 u-font-bold">
                        {{item.name}}
                    </view>
                    <view class="u-font-26 grey u-m-t-20" v-if="item.indate_cate==2">
                        有效期至{{$u.timeFormat(item.end_time, 'yyyy.mm.dd')}}
                    </view>
                    <view class="u-font-26 grey u-m-t-20" v-else>
                        有效期{{item.indate_day}}天
                    </view>
                </view>
            </view>
            <u-button type="error" :custom-style="{'width':'130rpx','height':'55rpx','font-size':'26rpx'}"
                @click="navigator(item)" shape="circle">使用</u-button>
            <!-- <u-button v-else
                :custom-style="{'width':'133rpx','height':'52rpx','font-size':'26rpx','background-color':'#999999','color':'#fff'}"
                shape="circle">已领取</u-button> -->
        </view>
        <!--  -->
        <u-empty mode="coupon" marginTop="450" v-if="!coupon_list.length"></u-empty>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                coupon_list: [],
                page: 1,
                total: 0,
                goods_id: 0,
                shop_id: 0,
                cart_id: [],
                index: 0
            }
        },
        onLoad(option) {
            this.goods_id = option.id || 0
            this.shop_id = option.shop_id || 0
            this.index = option.index || 0
            this.cart_id = option.cart_id ? JSON.parse(option.cart_id) : []
            this.get_can_use_coupon()
        },
        methods: {
            // 使用优惠卷
            navigator(item) {
                if (this.goods_id) {
                    this.$u.func.prePage().coupon = item
                }
                if (this.shop_id) {
                    this.$u.func.prePage().goods[this.index].full_amount = item.full_amount
                    this.$u.func.prePage().goods[this.index].coupon_id = item.id
                }
                this.$u.func.prePage().calculation()
                uni.navigateBack()
            },
            // 优惠卷
            get_can_use_coupon() {
                this.$u.api.get_can_use_coupon({
                    page: this.page,
                    goods_id: this.goods_id,
                    cart_id: this.cart_id,
                    shop_id: this.shop_id
                }).then(res => {
                    this.coupon_list.push(...res.data.data);
                    this.total = res.data.total
                })
            },
        },
        onReachBottom() {
            if (this.coupon_list.length >= this.total) return
            this.page++
            this.get_can_use_coupon()
        }
    }
</script>

<style lang="scss">
    .coupon {
        margin-top: 25rpx;
        width: 690rpx;
        height: 188rpx;
        background: #FFFFFF;
        border-radius: 20rpx 20rpx 20rpx 20rpx;
        border: 2rpx solid #fa3534;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-right: 20rpx;

        .line {
            height: 86rpx;
            width: 0rpx;
            border-radius: 0rpx 0rpx 0rpx 0rpx;
            border: 2rpx solid #EEEEEE;
        }
    }
</style>