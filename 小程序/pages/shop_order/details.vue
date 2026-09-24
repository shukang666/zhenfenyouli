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
            <!-- <view class="u-flex u-row-between u-shrink-no u-m-l-30">
                <view class="u-shrink-no">
                    <u-icon
                        name="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250611/63089d98cac3cae570a8579657102419.png"
                        size="50" label-size="20" label-pos="bottom" label="服务电话"
                        @click="makePhoneCall(Info.shop_info.mobile)"></u-icon>
                </view>
                <view class="u-m-l-30 u-shrink-no">
                    <u-icon
                        name="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250610/84e2b2be6e16a76dc62f6f5d8756d953.png"
                        size="50" label-size="20" label-pos="bottom" label="导航"
                        @click="openLocation(Info.shop_info.lat,Info.shop_info.lng,Info.shop_info.address)"></u-icon>
                </view>
            </view> -->
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
                <view class="u-flex u-row-right" v-if="Info.delivery_type==1">
                    <u-button type="primary" hoverClass="none" :custom-style="{background: '#18AF38'}" size="mini"
                        shape="circle" @click="showLog = true">确认发货
                    </u-button>
                </view>
                <view class="u-flex u-row-right" v-else>
                    <u-button type="primary" hoverClass="none" :custom-style="{background: '#18AF38'}" size="mini"
                        shape="circle" @click="cancelCancel">确认核销
                    </u-button>
                </view>
            </block>
        </view>
        <!-- 显示快递弹窗 -->
        <u-popup v-model="showLog" mode="bottom" closeable border-radius="20">
            <view class="u-p-30">
                <view class="u-font-32 u-font-bold u-text-center">
                    确认发货
                </view>
                <view class="u-m-t-30">
                    <view class="u-type-info-light-bg u-p-10 u-radius-6">
                        <u-input v-model="express_name" placeholder="请输入物流公司" />
                    </view>
                    <view class="u-type-info-light-bg u-p-10 u-radius-6 u-m-t-30">
                        <u-input v-model="express_sn" placeholder="请输入物流单号" />
                    </view>
                </view>
                <view class="u-p-30 u-m-t-30">
                    <u-button type="primary" hoverClass="none" :custom-style="{background: '#18AF38'}" shape="circle"
                        @click="shop_send">确认发货
                    </u-button>
                </view>
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
                goods: {},
                express_name: '',
                express_sn: ''
            };
        },
        onLoad(option) {
            this.order_detail(option.id)
        },
        methods: {
            //取消订单
            cancelCancel() {
                let that = this;
                uni.showModal({
                    title: '提示',
                    content: '确认要核销此订单',
                    success(str) {
                        if (str.confirm) {
                            that.shop_send()
                        };
                    }
                })
            },
            // 确认发货
            shop_send() {
                if(this.Info.delivery_type==1){
                    if (!this.express_name) return this.$u.toast('请输入物流公司')
                    if (!this.express_sn) return this.$u.toast('请输入物流单号')
                }
                this.$u.api.shop_send({
                    id: this.Info.id,
                    express_name: this.express_name,
                    express_sn: this.express_sn
                }).then(res => {
                    this.showLog = false
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