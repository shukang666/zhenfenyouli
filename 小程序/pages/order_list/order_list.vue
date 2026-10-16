<template>
	<view>
		<u-sticky h5-nav-height="0">
			<u-tabs :list="tabList" :is-scroll="true" :current="current" active-color="#18AF38" @change="tabChange">
			</u-tabs>
		</u-sticky>

		<block v-if="List.length">
			<view class="u-p-30">
				<view class="u-type-white-bg u-radius-10 u-m-b-30 u-p-30" v-for="(item,index) in List" :key="index"
					@click="navigateTo('/pages/order_list/details?order_num=' + item.order_num)">
					<view class="u-flex u-row-between u-font-24 u-font-bold">
						<text>订单号：{{item.order_num}}</text>
						<text style="color: #18AF38;">{{item.status_name}}</text>
					</view>
					<view class="u-flex u-row-between u-font-24 u-m-t-20" v-if="item.express_sn">
						<view>
							<text class="u-type-info">快递{{item.express_name ? '（' + item.express_name + '）' : ''}}：</text>
							<text>{{item.express_sn}}</text>
						</view>
						<text class="u-type-primary" @click.stop="copyExpressSn(item.express_sn)">复制</text>
					</view>
					<u-line color="#F7F7F7" margin="30rpx 0" />
					<view class="u-flex u-col-top u-m-b-30" v-for="(obj,val) in item.goods_list" :key="val">
						<view class="u-m-r-20">
							<u-image :src="obj.thumb || 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/errorImage.jpg'" width="160" height="160"
								border-radius="8"></u-image>
						</view>
						<view class="u-width-100">
							<view class="u-font-28 u-font-bold u-line-1">{{obj.goods_name}}</view>
							<view class="u-m-tb-10" v-if="obj.sku_name">
								<u-tag :text="obj.sku_name" mode="dark" color="#878787" bg-color="#F9F9F9"
									size="mini"></u-tag>
							</view>
							<view class="u-flex u-row-between u-m-t-40">
								<view class="">
									<!-- <text class="u-font-24">&yen;</text>
                                    <text class="u-font-32 u-font-bold">{{obj.one_price}}</text> -->
									<text v-if="Number(obj.one_price)">{{obj.one_price}}元</text>
									<text v-if="Number(obj.one_price) && obj.point"> + </text>
									<text v-if="obj.point">{{obj.point}}积分</text>
								</view>
								<text>x {{obj.count}}</text>
							</view>
						</view>
					</view>
					<view class="u-flex u-row-right">
						<text class="u-font-bold">合计：</text>
						<view class="u-flex ">
							<text class="u-font-24">&yen;</text>
							<text class="u-font-32 u-font-bold" style="color: #18AF38;">{{item.price}}</text>
						</view>
					</view>
					<u-line color="#F7F7F7" margin="30rpx 0" />
					<view class="u-text-right">
						<view class="" v-if="item.status==1">
							<u-button type="default" shape="circle" size="mini" hoverClass="none"
								@click="handleCancel(item)">取消订单
							</u-button>
							<u-button type="success" shape="circle" size="mini" hoverClass="none"
								@click="payOrder(item)">支付订单
							</u-button>
						</view>
						<u-button type="success" shape="circle" size="mini" hoverClass="none"
							@click="navigateTo('/pages/order_list/details?order_num=' + item.order_num)"
							v-if="item.status==4">确认收货
						</u-button>
						<block v-if="item.status == 6 || item.status == 8">
							<u-button v-if="!item.invoice_status" type="success" shape="circle" size="mini"
								@click="navigateTo('/pages/order_list/addInvoice?order_num=' + item.pay_order_num + '&amount=' + item.price)">申请开票
							</u-button>
							<u-button v-if="item.invoice_status == 1" type="success" shape="circle" size="mini">开票中
							</u-button>
							<u-button v-if="item.invoice_status == 2" type="success" shape="circle" size="mini"
								@click="navigateTo('/pages/order_list/invoiceImg?order_num=' + item.pay_order_num)">开票详情
							</u-button>
						</block>
						<!-- <u-button type="success" shape="circle" size="mini" hoverClass="none"
                             @click.stop="navigateTo(item)"
                            v-else>查看详情
                        </u-button> -->
					</view>
				</view>
			</view>
		</block>

		<view class="empty-tip" v-if="!List.length">
			<u-empty text="暂无订单" mode="list"></u-empty>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				tabList: [{
						status: 0,
						name: '全部'
					},
					// {
					//     status: 1,
					//     name: '待付款'
					// },
					{
						status: 2,
						name: '待发货'
					},
					{
						status: 4,
						name: '待收货'
					},
					{
						status: 6,
						name: '待评价'
					},
					{
						status: 8,
						name: '已完成'
					},
					{
						status: 10,
						name: '已取消'
					}
				],
				status: 0,
				current: 0,
				List: [],
				page: 1,
				pageCount: 0,
			};
		},
		onLoad(option) {
			if (option.type) {
				this.current = option.type;
			}
			this.my_order(); //获取订单列表
		},
		onUnload() {
			uni.switchTab({
				url: '/pages/user/index'
			})
		},
		methods: {
			copyExpressSn(expressSn) {
				uni.setClipboardData({
					data: expressSn
				});
			},
			// 取消订单
			cancel_order(item) {
				this.$u.api.cancel_order({
					order_num: item.order_num
				}).then(res => {
					this.$u.func.showToast({
						title: res.msg,
					})
					this.Reload();
				}).catch(err => {
					this.$u.func.showToast({
						title: err.msg,
					})
				})
			},
			//菜单切换
			tabChange(index) {
				this.current = index;
				this.Reload();
			},
			//获取订单列表
			async my_order() {
				const res = await this.$u.api.my_order({
					status: this.tabList[this.current].status,
					page: this.page,
				});
				this.List.push(...res.data.data);
				this.pageCount = res.data.total;
				uni.stopPullDownRefresh();
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
								url: '/pages/my_order/my_order'
							})
						},
						fail: err1 => {
							this.$u.func.showToast({
								title: '支付失败，请稍后重试',
								url: '/pages/my_order/my_order'
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
			handleCancel(item) {
				let that = this;
				uni.showModal({
					title: '提示',
					content: '确认要取消此订单',
					success(str) {
						if (str.confirm) {
							that.cancel_order(item)
						};
					}
				})
			},
			//详情
			navigateTo(url) {
				this.$u.route(url)
			},
			//重载数据
			Reload() {
				this.page = 1;
				this.List = [];
				this.my_order();
			},
		},
		onReachBottom() {
			if (this.List.length >= this.pageCount) return
			this.page++
			this.my_order()
		},
		onPullDownRefresh() {
			this.Reload();
		}
	}
</script>

<style lang="scss">
	page {
		background: #F7F7F7;
	}
</style>
