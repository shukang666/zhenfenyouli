<template>
	<view class="u-p-30">
		<view class="" v-if="goods[0].is_express==1&&goods[0].is_take==1">
			<u-tabs :list="list" :is-scroll="false" :current="current" @change="change" active-color="#18af38"></u-tabs>
		</view>
		<u-cell-group v-if="current==0&&(goods[0]?goods[0].is_express==1:'')">
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
		<block v-else>
			<view class="u-p-30 u-type-white-bg u-flex u-row-between u-radius-0"
				v-if="goods[0]?goods[0].shop_info.id!=-1:''">
				<view class="u-flex u-width-100">
					<u-image width="100" height="100" :src="goods[0].shop_info.avatar"></u-image>
					<view class="u-m-l-20 u-width-100">
						<view class="u-font-bold">
							{{goods[0].shop_info.shop_name}}
						</view>
						<view class="u-m-t-20 u-font-22 u-line-1">
							{{goods[0].shop_info.address}}
						</view>
					</view>
				</view>
				<view class="">
					<u-icon
						name="https://oss.ausite.cn/xcxkf052/admin/20250610/84e2b2be6e16a76dc62f6f5d8756d953.png?watermark"
						size="50"
						@click="openLocation(goods[0].shop_info.lat,goods[0].shop_info.lng,goods[0].shop_info.address)"></u-icon>
				</view>
			</view>
			<view class="u-p-30 u-type-white-bg u-flex u-row-between u-radius-0" v-else
				@click="$u.route('/pages/mall/address?type=1')">
				<view class="u-flex u-width-100" v-if="Object.keys(position).length">
					<u-image width="100" height="100" borderRadius="10" :src="position.thumb"></u-image>
					<view class="u-m-l-20 u-width-100">
						<view class="u-font-bold">
							{{position.name}}
						</view>
						<view class="u-m-t-20 u-font-22 u-line-1">
							{{position.address}}
						</view>
					</view>
				</view>
				<view class="u-width-100 u-font-bold" v-else>
					请选择自提点
				</view>
				<view class="">
					<u-icon name="arrow-right" size="34" color="#9b9c9e"></u-icon>
				</view>
			</view>
		</block>



		<!-- 商品 -->
		<view class="u-m-t-30 u-type-white-bg u-radius-14" v-for="(item,index) in goods" :key="index">
			<view class="u-font-bold u-p-30 u-border-bottom">
				{{item.shop_info.shop_name}}
			</view>
			<view class="u-flex u-p-tb-30 u-m-lr-30 u-border-bottom">
				<u-image width="130" height="130" border-radius='10rpx' :src="item.thumb||item.image"></u-image>
				<view class="u-m-l-20 u-width-100">
					<view class="u-font-32 u-font-bold">
						{{item.goods_name}}
					</view>
					<view class="u-font-24 u-tips-color u-m-t-10" v-if="item.sku_name">{{item.sku_name}}</view>
					<view class="u-flex u-row-between u-m-t-20">
						<view class="u-font-bold u-type-error">
							<!-- ¥{{item.price}} -->
							<text v-if="Number(item.price)">{{item.price}}元</text>
							<text v-if="Number(item.price) && item.point"> + </text>
							<text v-if="item.point">{{item.point}}积分</text>
						</view>
						<view class="u-type-info">
							x{{item.count}}
						</view>
					</view>
				</view>
			</view>
			<view class="u-flex u-row-between u-p-30" @click="$u.route('/pages/coupon/sy_coupon?id='+item.goods_id)">
				<view class="u-font-bold">
					优惠卷
				</view>
				<view class="u-flex" style="color: #999;">
					<text class="u-type-error" v-if="coupon.id">-¥{{coupon.amount}}</text>
					<text v-else>选择优惠卷</text>
					<u-icon name="arrow-right"></u-icon>
				</view>
			</view>
		</view>
		<!-- 备注 -->
		<!-- <view class="u-p-30 u-type-white-bg u-radius-10 u-m-t-30 u-flex u-row-between">
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
		</view> -->
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
				list: [{
					name: '送货上门'
				}, {
					name: '到店自提'
				}],
				current: 0,
				info: {},
				coupon: {},
				position: {}
			}
		},
		onLoad(option) {
			this.goods = uni.getStorageSync('goods')
			// this.position = this.goods[0].shop_info
			this.type = option.type
			this.get_address_info()
			this.calculation()
		},
		methods: {
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
				this.$u.api.add_order({
					action: 'info',
					goods_id: this.goods[0].goods_id,
					count: this.goods[0].count,
					coupon_id: this.coupon.id,
					address_id: this.address.id,
					delivery_type: this.current + 1,
					type: this.checked ? 1 : 0
				}).then(res => {
					// console.log(res)
					this.info = res.data
				}).catch(err => {
					// this.$u.toast(err.msg)
				})
			},
			// 添加订单
			add_order() {

				// 只有一个商品 需鉴权是否支持自提或送货
				let isExpress = this.goods[0].is_express //1or2
				let isTake = this.goods[0].is_take //1or2

				if(isExpress == 1 && isTake == 1) {
					//选择送货
					if (!this.current) {
						if (isExpress == 1) {
							if (this.address.id) this.addOrder()
							else return this.$u.toast('请先添加收货地址')
						} else {
							return this.$u.toast('该商品不支持送货上门')
						}
					}
					//选择自提
					if (this.current) {
						if (isTake == 1) {
							if (this.position.id) this.addOrder()
							else return this.$u.toast('请先选择自提地址')
						} else {
							return this.$u.toast('该商品不支持自提')
						}
					}
				}else if(isExpress == 1) {
					if (this.address.id) this.addOrder()
					else return this.$u.toast('请先添加收货地址')
				}else if(isTake == 1) {
					if(this.goods[0].shop_info.id == -1 && this.position.id) this.addOrder()
					else return this.$u.toast('请先选择自提地址')
				}
					
				
				
				// 	if ((this.goods[0].is_express == 1 && this.goods[0].is_take == 1 && !this.current) || this.goods[0]
				// 		.is_express == 1) {
				// 		if (Object.keys(this.address).length == 0) return this.$u.toast('请先添加收货地址')
				// 	}
				// if ((this.goods[0].is_express == 1 && this.goods[0].is_take == 1 && this.current && this.goods[0]
				// 		.shop_info.id == -1) || (this.goods[0].is_take == 1 && this.goods[0].shop_info.id == -1)) {
				// 	if (Object.keys(this.position).length == 0) return this.$u.toast('请先选择自提地址')
				// }
			},
			addOrder() {
				this.$u.api.add_order({
					goods_id: this.goods[0].goods_id,
					count: this.goods[0].count,
					coupon_id: this.coupon.id,
					address_id: this.address.id,
					delivery_type: this.current + 1,
					type: this.checked ? 1 : 0,
					position_id: this.position.id
				}).then(res => {
					if (res.data.status == 1) {
						this.payOrder(res.data)
					} else {
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