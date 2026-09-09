<template>
    <view class="u-p-lr-30">
        <view class="coupon" v-for="item in coupon_list" :key="item.id">
            <view class="u-flex u-flex-1">
                <view class="u-flex-col u-w-186 u-row-center u-col-center">
                    <view class="u-font-26 u-type-error">
                        &yen;<text class="u-font-55 u-font-bold u-m-l-5">{{item.amount}}</text>
                    </view>
                    <view class="u-font-26">
                        满{{item.full_amount}}可用
                    </view>
                </view>
                <view class="line"></view>
                <view class="u-m-l-30">
                    <view class="u-font-32 u-font-bold">
                        {{item.name}}
                    </view>
                    <view class="u-font-26 u-m-t-10">
                        {{item.shop_name || '平台'}}
                    </view>
                    <view class="u-font-26 u-m-t-10" v-if="item.indate_cate==2">
                        {有效期至{{$u.timeFormat(item.end_time, 'yyyy.mm.dd')}}}
                    </view>
                    <view class="u-font-26 u-m-t-10" v-else>
                        有效期{{item.indate_day}}天
                    </view>
                </view>
            </view>
            <u-button type="error" shape="circle" size="mini" @click="receive_coupon(item)"
                v-if="item.status==0">领取</u-button>
            <u-button type="error" hover-class="none" :customStyle="{background: '#82848a'}" shape="circle" size="mini"
                v-else>已领取</u-button>
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
                exam_id: 0
            }
        },
        onLoad(option) {
            this.exam_id = option.id
            this.get_shop_coupon_list()
        },
        methods: {
            // 优惠卷
            get_shop_coupon_list() {
                this.$u.api.get_shop_coupon_list({
                    page: this.page
                }).then(res => {
                    this.coupon_list.push(...res.data.data);
                    this.total = res.data.total
                })
            },
            // 领取优惠券
            receive_coupon(item) {
                this.$u.api.receive_coupon({
                    id: item.id
                }).then(res => {
                    item.status = 1
                    this.$u.toast(res.msg)
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            }
        },
        onReachBottom() {
            if (this.coupon_list.length >= this.total) return
            this.page++
            this.get_shop_coupon_list()
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