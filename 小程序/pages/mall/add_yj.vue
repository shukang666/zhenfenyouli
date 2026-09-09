<template>
    <view class="u-p-30">
        <u-cell-group v-if="type==1">
            <u-cell-item :label="address.phone?'':'您还没有添加地址'" @click="$u.route('/pages/address/index?source=1&type=1')">
                <view class="u-m-r-10" slot="icon">
                    <u-icon name="/static/map.png" size="48"></u-icon>
                </view>
                <view class="" slot="title">
                    <view class="" v-if="!Object.keys(address).length">
                        添加地址
                    </view>
                    <view class="" v-else>
                        <view class="u-m-t-10">
                            {{address.province+address.city+address.county}}{{address.address}}
                        </view>
                        <text class="u-font-28 u-font-bold u-content-color">{{address.username}}</text>
                        <text class="u-m-l-20">{{address.phone}}</text>
                    </view>
                </view>
            </u-cell-item>
        </u-cell-group>

        <!-- 商品 -->
        <view class="u-m-t-30 u-type-white-bg u-radius-14" v-for="(item,index) in goods" :key="index">
            <view class="u-font-bold u-p-30">
                {{item.shop_name}}
            </view>
            <block v-if="type==2">
                <view class="u-p-10 u-font-24 u-flex u-row-between u-m-lr-30" style="background-color: #EFF9F1;"
                    v-if="item.shop_id==0" @click="$u.route('/pages/mall/address')">
                    <view class="">
                        {{position_name||'请选择自提点'}}
                    </view>
                    <view class="">
                        <u-icon name="arrow-right"></u-icon>
                    </view>
                </view>
                <view class="u-p-10 u-font-24 u-m-lr-30" style="background-color: #EFF9F1;" v-else>
                    {{item.address}}
                </view>
            </block>

            <view class="u-flex u-p-tb-30 u-m-lr-30 u-border-bottom" v-for="(item1,index1) in item.children"
                :key="index1">
                <u-image width="130" height="130" border-radius='10rpx' :src="item1.thumb||item1.image"></u-image>
                <view class="u-m-l-20 u-width-100">
                    <view class="u-font-32 u-font-bold">
                        {{item1.goods_name}}
                    </view>
                    <view class="u-font-24 u-tips-color u-m-t-10" v-if="item1.sku_name">{{item1.sku_name}}</view>
                    <view class="u-flex u-row-between u-m-t-20">
                        <view class="u-font-bold u-type-error">
                            ¥{{item1.price}}
                        </view>
                        <view class="u-type-info">
                            x{{item1.count}}
                        </view>
                    </view>
                </view>
            </view>
            <view class="u-flex u-row-between u-p-30" @click="select_brn(item,index)">
                <view class="u-font-bold">
                    优惠卷
                </view>
                <view class="u-flex" style="color: #999;">
                    <text class="u-type-error" v-if="item.coupon_id">-¥{{item.full_amount}}</text>
                    <text v-else>选择优惠卷</text>
                    <u-icon name="arrow-right"></u-icon>
                </view>
            </view>
        </view>
        <!-- 备注 -->
        <view class="u-p-30 u-type-white-bg u-radius-10 u-m-t-30 u-flex u-row-between">
            <view class="u-flex u-col-top">
                <u-icon name="/static/jifen.png" size="46"></u-icon>
                <view class="u-m-l-10">
                    <view class="u-font-bold">
                        积分抵扣 <text style="color: #18AF38;">({{userInfo.point}})</text>
                    </view>
                    <view class="u-font-24 u-type-error u-m-t-10" v-if="checked">
                        {{info.point}}积分可抵扣{{info.pay_point}}元
                    </view>
                </view>
            </view>
            <view class="">
                <u-switch v-model="checked" active-color="#18af38" @change="calculation"></u-switch>
            </view>
        </view>
        <u-gap height="120"></u-gap>
        <!-- 支付按钮 -->
        <view class="submit-btn u-flex u-row-between u-p-30 u-type-white-bg">
            <view class="" style="color: #18AF38;">
                <text style="color: #000;">合计:</text>
                <text class="u-font-bold">￥</text>
                <text class="u-font-34 u-font-bold">{{info.price}}</text>
            </view>
            <view class="">
                <u-button type="warning" shape="circle" hover-class="none"
                    :custom-style="{padding:'0 50rpx', background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                    @click="add_order">
                    提交订单</u-button>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                goods: [],
                address: {},
                remark: '',
                type: '',
                checked: false,
                info: {},
                cart_coupon_arr: [],
                position_id: 0,
                position_name: ''
            }
        },
        onLoad(option) {
            this.goods = uni.getStorageSync('goods')
            this.type = option.type
            this.get_address_info()
            this.calculation()
        },
        methods: {
            // 选择优惠卷
            select_brn(item, index) {
                var cart_id = []
                item.children.forEach(item => {
                    cart_id.push(item.id)
                })
                this.$u.route('/pages/coupon/sy_coupon?shop_id=' + item.shop_id + '&cart_id=' + JSON.stringify(
                    cart_id) + '&index=' + index)
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
            change(index) {
                this.current = index;
            },
            //计算总金额
            calculation() {
                var cart_id = []
                var cart_coupon_arr = []
                this.goods.forEach(item => {
                    item.children.forEach(obj => {
                        cart_id.push(obj.id)
                    })
                    if(item.coupon_id){
                        cart_coupon_arr.push({
                            shop_id: item.shop_id,
                            coupon_id: item.coupon_id
                        })
                    }
                })
                this.$u.api.add_order({
                    action: 'info',
                    cart_id: cart_id,
                    cart_coupon_arr: cart_coupon_arr,
                    address_id: this.address.id,
                    delivery_type: this.type,
                    type: this.checked ? 1 : 0
                }).then(res => {
                    this.info = res.data
                }).catch(err => {
                    // this.$u.toast(err.msg)
                })
            },
            // 添加订单
            add_order() {
                if (Object.keys(this.address).length == 0) return this.$u.toast('请先添加收货地址')
                var cart_id = []
                var cart_coupon_arr = []
                this.goods.forEach(item => {
                    item.children.forEach(obj => {
                        cart_id.push(obj.id)
                    })
                    if(item.coupon_id){
                        cart_coupon_arr.push({
                            shop_id: item.shop_id,
                            coupon_id: item.coupon_id
                        })
                    }
                })
                this.$u.api.add_order({
                    cart_id: cart_id,
                    coupon_id: this.coupon_id,
                    address_id: this.address.id,
                    delivery_type: this.type,
                    type: this.checked ? 1 : 0,
                    position_id: this.position_id
                }).then(res => {
                    if(res.data.status==1){
                        this.payOrder(res.data)
                    }else{
                        this.$u.func.showToast({
                            title: res.msg,
                            url: '/pages/order_list/order_list'
                        })
                    }
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            //提交-支付订单 代付款去付款
            payOrder(data) {
                this.$u.api.pay_order({
                    order_num: data.order_num,
                    pay_type: 1,
                }).then(res => {
                    // #ifdef MP-WEIXIN
                    this.$u.func.wxappPay({
                        payInfo: res.data,
                        success: res1 => {
                            this.$u.func.showToast({
                                title: '下单成功',
                                url: '/pages/order_list/order_list'
                            })
                        },
                        fail: err1 => {
                            this.$u.func.showToast({
                                title: '支付失败，请稍后重试',
                                // url: '/pages/order_list/order_list'
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
            // 获取地址信息
            get_address_info() {
                this.$u.api.get_address_info().then(res => {
                    res.data.forEach(item => {
                        if (item.is_default == 1) {
                            this.address = item
                        }
                    })
                })
            },
        }
    }
</script>

<style>
    .radiobox {
        margin-top: 30rpx;
        padding: 20rpx 30rpx;
    }
</style>