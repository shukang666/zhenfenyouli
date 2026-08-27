<template>
	<view class="container">
		<view class="u-p-30 u-text-center">
			<image :src="userInfo.avatar" class="u-w-140 u-h-140 u-radius-o" style="border: 2rpx solid #18AF38;" mode="aspectFill"></image>
			<view class="u-m-t-10 u-font-32 u-hui u-font-bold">
				我的勋章 <text class="u-main-c u-font-64">{{dataList.length}}</text>枚
			</view>
		</view>
		<view class="u-flex u-flex-wrap u-col-top">
			<view class="u-text-center u-m-t-30 u-p-15" style="width: 33%;" v-for="item in dataList" :key="item.id">
				<image :src="item.image" class="u-w-180 u-h-180" mode="aspectFill"></image>
				<view class="u-font-26 u-m-t-15">{{item.name}}</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				dataList: [],
			};
		},
		onLoad() {
			this.get_new_badge_list()
		},
		methods: {
			async get_new_badge_list() {
				try {
					let params = {}
					let res = await this.$u.api.get_new_badge_list(params)
					this.dataList = res.data
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
		background: white;
		padding-top: 30rpx;
	}
</style>