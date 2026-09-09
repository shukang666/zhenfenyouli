<template>
	<view class="container">
		<view class="u-flex u-row-center" style="padding: 200rpx 0 0;">
			<view class="u-w-500 u-text-center" v-if="info.status  == 1">
				<image src="/static/shz.png" class="u-w-432 u-h-440" mode="aspectFill"></image>
				<view class="u-font-28 u-m-t-15">申请已提交审核，请您耐心等待</view>
			</view>
			<view class="u-w-500 u-text-center" v-if="info.status  == 3">
				<image src="/static/shsb.png" class="u-w-432 u-h-440" mode="aspectFill"></image>
				<view class="u-font-28 u-m-t-15">申请未通过</view>
				<view class="u-font-28 u-m-t-15 u-price-c">{{info.msg}}</view>
			</view>
		</view>
		<view class="submit-btn u-p-b" v-if="info.status  == 3">
			<u-button @click="goApply">重新审核</u-button>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				info: {},
			};
		},
		onLoad() {
			this.get_shop_new()
		},
		methods: {
			goApply() {
				uni.redirectTo({
					url: '/pages/zzhd/apply-shop'
				})
			},
			async get_shop_new() {
				try {
					let params = {}
					let res = await this.$u.api.get_shop_new(params)
					this.info = res.data
					uni.setNavigationBarTitle({
						title: res.data.status_name
					})
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background-color: white;
	}
</style>