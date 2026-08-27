<template>
	<view>
		<view class="u-p-30">
			<view class="u-p-30 u-radius-10 u-flex white u-text-center"
				style="background: linear-gradient( 135deg, #A4DB88 0%, #18AF38 100%);">
				<view class="u-flex-1">
					<view class="u-font-32  u-font-bold">
						{{userInfo.shop_balance_total}}
					</view>
					<view class="u-m-t-30">
						累计收益(元)
					</view>
				</view>
				<view class="u-flex-1">
					<view class="u-font-32  u-font-bold ">
						{{userInfo.balance}}
					</view>
					<view class="u-m-t-30">
						可提现金额(元)
					</view>
				</view>
			</view>
			<view class="u-m-t-25 u-radius-5">
				<view class="u-flex u-row-between u-m-b-25">
					<view class="u-font-32 u-font-bold">
						收益明细
					</view>
					<view class="" @click="show=true">
						<text v-if="start_time">
							{{`${start_time}~${end_time}`}}
						</text>
						<text v-else>日期选择</text> <u-icon name="arrow-down-fill" size="25" class="u-m-l-5"></u-icon>
					</view>
				</view>
				<view class="u-p-25 u-border-bottom u-flex u-row-between u-type-white-bg" v-for="item in dataList"
					:key="item.id">
					<view class="">
						<view class="u-font-bold">
							{{item.type_name}} <text v-if="item.order_num">-{{item.order_num}}</text>
						</view>
						<view class="$u-type-info u-font-26 u-m-t-10">
							{{$u.timeFormat(item.create_time, 'yyyy-mm-dd hh:MM')}}
						</view>
					</view>
					<view class="u-text-right">
						<view class="">
							{{item.change_amount}}
						</view>
						<view class="u-m-t-10" v-if="item.type==310 && item.withdraw_info.status == 1">
							<u-button type="primary" size="mini" shape="circle"
								@click="requestMerchantTransfer(item.withdraw_info)">确认收款</u-button>
						</view>
						<view class="u-m-t-10" v-else>
							<view class="u-m-b-10 u-type-warning u-font-24" v-if="item.withdraw_info.status == 2">
								{{item.withdraw_info.refuse_reason}}
							</view>
							<view class="u-m-b-10 u-type-success u-font-24" v-if="item.withdraw_info.status == 3">
								已提现
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<u-gap height="120"></u-gap>
		<view class="submit-btn u-p-30 ">
			<u-button type="primary" shape="circle" hover-class="none"
				:customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
				@click="$u.route('/pages/balance/withdrawal')">提现</u-button>
		</view>
		<u-calendar v-model="show" :mode="rang" @change="change"></u-calendar>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				dataList: [],
				page: 1,
				total: 0,
				end_time: '',
				start_time: '',
				show: false,
				account_type: 1
			}
		},
		onLoad(option) {
			this.account_type = option.type
			this.get_log_list()
		},
		methods: {
			// 确认收款
			requestMerchantTransfer(item) {
				if (wx.canIUse('requestMerchantTransfer')) {
					wx.requestMerchantTransfer({
						mchId: item.mch_id,
						appId: wx.getAccountInfoSync().miniProgram.appId,
						package: item.package_info,
						success: (res) => {
							console.log('success:', res);
						},
						fail: (res) => {
							console.log('fail:', res);
						},
					});
				} else {
					wx.showModal({
						content: '你的微信版本过低，请更新至最新版本。',
						showCancel: false,
					});
				}
			},
			// 选择日期
			change(e) {
				console.log(e);
				this.start_time = e.startDate
				this.end_time = e.endDate
				this.dataList = []
				this.page = 1
				this.get_log_list()
			},
			// 获取记录
			get_log_list() {
				this.$u.api.get_log_list({
					account_type: 1,
					page: this.page,
					end_time: this.end_time,
					start_time: this.start_time
				}).then(res => {
					this.dataList.push(...res.data.data);
					this.total = res.data.total
				})
			},
		},
		onReachBottom() {
			if (this.dataList.length >= this.total) return
			this.page++
			this.get_log_list()
		}
	}
</script>

<style lang="scss">
	.balance_bg {
		width: 690rpx;
		height: 244rpx;
		background: #07C160;
		border-radius: 12rpx 12rpx 12rpx 12rpx;
		color: #fff;
		padding: 50rpx 40rpx;
	}

	.fixed {
		background-color: #fff;
		padding: 30rpx;
	}
</style>