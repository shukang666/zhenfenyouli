<template>
    <view class="u-p-30">
        <view class="u-radius-10 u-p-20 white u-flex"
            style="background: linear-gradient( 135deg, #A4DB88 0%, #18AF38 100%);">
            <u-image width="120" height="120" :src="shop.avatar"></u-image>
            <view class="u-m-l-20 u-flex u-row-between u-col-top u-flex-col u-h-120 u-width-100">
                <view class="u-font-bold">
                    {{shop.shop_name}}
                </view>
                <view class="u-font-24">
                    {{shop.mobile}}
                </view>
                <view class="u-font-24 u-line-1 u-width-100">
                    {{shop.address}}
                </view>
            </view>
        </view>
        <view class="u-m-t-30 u-type-white-bg u-radius-10 u-p-30">
            <view class="u-font-bold">
                订单统计
            </view>
            <view class="u-flex u-text-center u-m-t-30">
                <view class="u-flex-1">
                    <view class="u-font-bold">
                        {{total.status2}}
                    </view>
                    <view class="u-font-24 u-type-info u-m-t-10">
                        待发货
                    </view>
                </view>
                <view class="" style="color: #EFEFEF;">
                    |
                </view>
                <view class="u-flex-1">
                    <view class="u-font-bold">
                        {{total.status4}}
                    </view>
                    <view class="u-font-24 u-type-info u-m-t-10">
                        待收货
                    </view>
                </view>
                <view class="" style="color: #EFEFEF;">
                    |
                </view>
                <view class="u-flex-1">
                    <view class="u-font-bold">
                        {{total.status6}}
                    </view>
                    <view class="u-font-24 u-type-info u-m-t-10">
                        待核销
                    </view>
                </view>
                <view class="" style="color: #EFEFEF;">
                    |
                </view>
                <view class="u-flex-1">
                    <view class="u-font-bold">
                        {{total.status8}}
                    </view>
                    <view class="u-font-24 u-type-info u-m-t-10">
                        已完成
                    </view>
                </view>
            </view>
        </view>
        <view class="u-m-t-30 u-type-white-bg u-radius-10">
            <view class="u-font-bold u-p-lr-30 u-m-t-30">
                常用功能
            </view>
            <u-grid :col="4" :border="false">
                <u-grid-item @click="$u.route('/pages/balance/balance')">
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-1.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">收益提现</view>
                </u-grid-item>
                <u-grid-item @click="$u.route('/pages/store/goods_list')">
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-2.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">商品管理</view>
                </u-grid-item>
                <u-grid-item @click="$u.route('/pages/store/coupon_list')">
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-3.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">优惠券管理</view>
                </u-grid-item>
                <u-grid-item @click="$u.route('/pages/settled/settled')">
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-4.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">门店信息</view>
                </u-grid-item>
                <!-- <u-grid-item>
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-5.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">扫码核销</view>
                </u-grid-item>
                <u-grid-item>
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-6.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">优惠券核销记录</view>
                </u-grid-item> -->
                <u-grid-item @click="$u.route('/pages/shop_order/shop_order')">
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-7.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">订单管理</view>
                </u-grid-item>
                <u-grid-item @click="$u.route('/pages/store/statistics')">
                    <u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/shop-8.png" :size="50"></u-icon>
                    <view class="u-font-24 u-m-t-10">数据统计</view>
                </u-grid-item>
            </u-grid>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                shop: {},
                total: {}
            };
        },
        onLoad() {
            this.get_shop_order_total()
        },
        onShow() {
            this.get_shop()
        },
        methods: {
            // 订单统计
            get_shop_order_total() {
                this.$u.api.get_shop_order_total().then(res => {
                    this.total = res.data
                })
            },
            // 
            get_shop() {
                this.$u.api.get_shop().then(res => {
                    this.shop = res.data
                })
            }
        }
    }
</script>

<style lang="scss">

</style>