<template>
    <view class="u-p-30">
        <view class="">
            <u-section :title="Info.status_name" fontSize="32" :arrow="false" :right="false"
                line-color="#18AF38"></u-section>
        </view>
        <view class="u-type-white-bg u-radius-10 u-m-t-30 u-p-30 u-flex" v-if="Info.delivery_type==1">
            <view class="u-m-r-10" slot="icon">
                <u-icon name="map-fill" size="48" color="#6db781"></u-icon>
            </view>
            <view class="u-m-t-10" slot="title">
                <view class="u-font-bold">
                    {{Info.province+Info.city+Info.county}}{{Info.address}}
                </view>
                <view class="u-font-26 u-type-info u-m-t-10">
                    <text class="">{{Info.username}}</text>
                    <text class="u-m-l-20">{{Info.phone}}</text>
                </view>
            </view>
        </view>
        <view class="u-p-30 u-type-white-bg u-flex u-row-between u-radius-10 u-m-t-30" v-else>
            <view class="u-flex u-width-100">
                <u-image width="100" height="100" :src="Info.shop_info.avatar"></u-image>
                <view class="u-m-l-20 u-width-100">
                    <view class="u-font-bold">
                        {{Info.shop_info.shop_name}}
                    </view>
                    <view class="u-m-t-20 u-font-22 u-line-1">
                        {{Info.shop_info.address}}
                    </view>
                </view>
            </view>
            <view class="u-flex u-row-between u-shrink-no u-m-l-30">
                <view class="u-shrink-no">
                    <u-icon
                        name="https://oss.ausite.cn/xcxkf052/admin/20250611/63089d98cac3cae570a8579657102419.png?watermark"
                        size="50" label-size="20" label-pos="bottom" label="服务电话"
                        @click="makePhoneCall(Info.shop_info.mobile)"></u-icon>
                </view>
                <view class="u-m-l-30 u-shrink-no">
                    <u-icon
                        name="https://oss.ausite.cn/xcxkf052/admin/20250610/84e2b2be6e16a76dc62f6f5d8756d953.png?watermark"
                        size="50" label-size="20" label-pos="bottom" label="导航"
                        @click="openLocation(Info.shop_info.lat,Info.shop_info.lng,Info.shop_info.address)"></u-icon>
                </view>
            </view>
        </view>

        <view class="u-type-white-bg u-radius-10 u-m-t-30 u-p-30">
            <view class="u-font-bold u-p-b-30">
                商品信息
            </view>
            <view class="u-flex u-col-top u-m-b-30" v-for="(item,index) in Info.goods_list" :key="index">
                <view class="u-m-r-20">
                    <u-image :src="item.thumb" width="140" height="140" border-radius="8"></u-image>
                </view>
                <view class="u-width-100">
                    <view class="u-font-28 u-font-bold u-line-1">{{item.goods_name}}</view>
                    <view class="u-type-info u-m-t-20" v-if="item.sku_name">
                        {{item.sku_name}}
                    </view>
                    <view class="u-flex u-row-between u-m-tb-30">
                        <view class="">
                            <text class="u-font-24">&yen;</text>
                            <text class="u-font-32 u-font-bold">{{item.total_price}}</text>
                        </view>
                        <view class="u-m-l-30">
                            <text>x {{item.count}}</text>
                        </view>
                    </view>
                </view>
            </view>
        </view>

        <view class="u-type-white-bg u-radius-10 u-m-t-30 u-p-30">
            <view class="u-font-30 u-font-bold">订单信息</view>
            <view class="u-flex u-row-between u-font-28 u-m-t-20">
                <text>订单编号</text>
                <text class="u-font-bold">{{Info.order_num}}</text>
                <!-- <text class="u-type-error" @click="handleCopy(Info.order_num)">复制</text> -->
            </view>

            <view class="u-font-28 u-m-t-15 u-flex u-row-between" v-if="Info.status>1&&Info.status<10">
                <text> 积分抵扣</text>
                <text class="u-font-bold">
                    -￥{{Info.point_pay}}
                </text>
            </view>
            <view class="u-font-28 u-m-t-15 u-flex u-row-between" v-if="Info.status>1&&Info.status<10">
                <text>优惠券</text>
                <text class="u-font-bold">-￥{{Info.coupon_price}}</text>
            </view>
            <view class="u-font-28 u-m-t-15 u-flex u-row-between" v-if="Info.status>1&&Info.status<10">
                <text>支付时间</text>
                <text class="u-font-bold">{{Info.pay_time|date('yy-mm-dd hh:MM')}}</text>
            </view>
            <view class="u-font-28 u-m-t-15 u-flex u-row-between" v-if="Info.status>1&&Info.status<10">
                <text>实际支付</text>
                <text class="u-font-bold">￥{{Info.price}}</text>
            </view>
            <view class="u-font-28 u-m-t-15 u-flex u-row-between" v-if="Info.status>=4&&Info.status<10">
                <text>发货时间</text>
                <text class="u-font-bold">{{Info.send_time|date('yy-mm-dd hh:MM')}}</text>
            </view>
            <view class="u-font-28 u-m-t-15 u-flex u-row-between" v-if="Info.status>=8&&Info.status<10">
                <text>完成时间</text>
                <text class="u-font-bold">{{Info.finish_time|date('yy-mm-dd hh:MM')}}</text>
            </view>
        </view>

        <u-gap height="150"></u-gap>
        <view class="submit-btn safe-area-inset-bottom u-p-30">
            <block v-if="Info.status == 1">
                <view class="u-flex u-row-right">
                    <view class="">
                        <u-button type="default" size="mini" shape="circle" @click="handleCancel()">取消订单</u-button>
                    </view>
                    <view class="u-m-l-30">
                        <u-button type="error" size="mini" shape="circle" hoverClass="none"
                            :custom-style="{background: '#98CEA7'}" @click="payOrder">立即支付</u-button>
                    </view>
                </view>
            </block>
            <block v-if="Info.status == 2">
                <view class="u-flex u-row-right">
                    <u-button type="default" size="mini" shape="circle" @click="handleCancel()">取消订单</u-button>
                </view>
            </block>
            <block v-if="Info.status == 4||Info.status == 6||Info.status == 8">
                <view class="u-flex u-row-right">
                    <view class="u-m-l-30" v-if="Info.delivery_type==1">
                        <u-button type="default" shape="circle" size="mini" @click="showLog = true">
                            查看物流</u-button>
                    </view>
                    <view class="u-m-l-30" v-if="Info.status == 4">
                        <u-button type="primary" hoverClass="none" :custom-style="{background: '#18AF38'}" size="mini"
                            shape="circle" @click="showModal">确认收货
                        </u-button>
                    </view>
                    <view class="u-m-l-30" v-if="Info.status == 6">
                        <u-button type="primary" hoverClass="none" :custom-style="{background: '#18AF38'}" size="mini"
                            shape="circle" @click="$u.route('/pages/order_list/add_evaluate?id='+Info.order_num)">立即评价
                        </u-button>
                    </view>
                </view>
            </block>
        </view>
        <!-- 显示快递弹窗 -->
        <u-popup v-model="showLog" mode="bottom" closeable border-radius="10">
            <view class="u-p-30 u-type-white-bg">
                <view class="u-flex u-row-between u-p-20 u-m-tb-30" style="background-color: #f5f5f5;">
                    <view class="">
                        {{Info.express_name}} | {{Info.express_sn}}
                    </view>
                    <!-- <view class="$u-type-info-dark" @click="copyOrder">
                        复制
                    </view> -->
                </view>
                <scroll-view scroll-y="true" style="height: 800rpx;">
                    <view class="u-m-l-15">
                        <u-time-line>
                            <u-time-line-item nodeTop="2" v-for="item in queryList.list" :key="item.id">
                                <!-- 此处自定义了左边内容，用一个图标替代 -->
                                <template v-slot:node>
                                    <view class="u-node" style="background: #18AF38;">
                                        <!-- 此处为uView的icon组件 -->
                                        <u-icon name="pushpin-fill" color="#fff" :size="24"></u-icon>
                                    </view>
                                </template>
                                <template v-slot:content>
                                    <view>
                                        <!-- <view class="u-order-title">待取件</view> -->
                                        <view class="u-order-desc">
                                            {{item.status}}
                                        </view>
                                        <view class="u-order-time $u-type-info-dark u-m-t-10 u-font-26">{{item.time}}
                                        </view>
                                    </view>
                                </template>
                            </u-time-line-item>
                        </u-time-line>
                    </view>
                </scroll-view>
            </view>
        </u-popup>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                Info: {},
                queryList: [],
                showLog: false,
                show: false,
                goods: {}
            };
        },
        onLoad(option) {
            this.order_detail(option.order_num)
        },
        methods: {
            // 拨打电话
            makePhoneCall(phone) {
                uni.makePhoneCall({
                    phoneNumber: phone
                });
            },
            // 查看位置
            openLocation(latitude, longitude, name) {
                uni.openLocation({
                    latitude: Number(latitude),
                    longitude: Number(longitude),
                    name,
                    success: function() {
                        console.log('success');
                    }
                });
            },
            // 
            get_express_info() {
                this.$u.api.get_express_info({
                    order_num: this.Info.order_num
                }).then(res => {
                    this.queryList = res.data.data.result
                })
            },
            //确认收货弹窗
            showModal() {
                let that = this;
                uni.showModal({
                    title: '提示',
                    content: '是否确认收到货物',
                    success(str) {
                        if (str.confirm) {
                            that.confirm_order()
                        };
                    }
                })
            },
            // 确认收货
            confirm_order() {
                this.$u.api.confirm_order({
                    order_num: this.Info.order_num
                }).then(res => {
                    this.$u.func.showToast({
                        title: res.msg,
                        back: true
                    })
                    this.$u.func.prePage().Reload();
                }).catch(err => {
                    this.$u.func.showToast({
                        title: err.msg,
                    })
                })
            },
            //提交-支付订单 代付款去付款
            payOrder(data) {
                this.$u.api.wx_pay_mini({
                    order_num: data.order_num,
                    order_type: 10,
                }).then(res => {
                    // #ifdef MP-WEIXIN
                    this.$u.func.wxappPay({
                        payInfo: res.data,
                        success: res1 => {
                            this.$u.func.prePage().Reload();
                            this.$u.func.showToast({
                                title: '支付成功',
                                back: true
                            })
                        },
                        fail: err1 => {
                            this.$u.func.showToast({
                                title: '支付失败，请稍后重试',
                            })
                        }
                    })
                    // #endif
                    // #ifndef MP-WEIXIN
                    this.$u.toast('请在微信小程序打开');
                    // #endif  
                }).catch(err => {
                    this.$u.func.showToast({
                        title: err.msg,
                    })
                })
            },
            //取消订单
            handleCancel() {
                let that = this;
                uni.showModal({
                    title: '提示',
                    content: '确认要取消此订单',
                    success(str) {
                        if (str.confirm) {
                            that.cancel_order()
                        };
                    }
                })
            },
            // 取消订单
            cancel_order() {
                this.$u.api.cancel_order({
                    order_num: this.Info.order_num
                }).then(res => {
                    this.$u.func.showToast({
                        title: res.msg,
                        back: true
                    })
                    this.$u.func.prePage().Reload();
                }).catch(err => {
                    this.$u.func.showToast({
                        title: err.msg,
                    })
                })
            },
            // 订单详情
            order_detail(order_num) {
                this.$u.api.order_detail({
                    order_num: order_num
                }).then(res => {
                    this.Info = res.data
                    if (!this.Info.express_sn) return
                    this.get_express_info()
                })
            },
            //复制单号
            handleCopy(data) {
                let that = this;
                uni.setClipboardData({
                    data: data,
                    success: function() {
                        console.log('success');
                    }
                });
            }
        }
    }
</script>

<style lang="scss">
    page {
        background: #F7F7F7;
    }
</style>