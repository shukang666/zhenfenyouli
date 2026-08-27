<template>
	<view class="container">
		<u-image v-if="imgUrl" :src="`${imgUrl}rank-bg.png`" width="750" height="504"></u-image>
		<view class="card-all u-shadow u-rela" style="margin-top: -200rpx;">
			<view class="u-flex u-row-between u-font-28 u-hui" style="padding: 0 30rpx 0;">
				<view class="u-w-100">排名</view>
				<view class="u-w-320">用户</view>
				<view class="u-w-150">签到次数</view>
			</view>
			<view class="u-flex u-row-between u-font-28 u-hui u-m-t-30 u-radius-10" v-for="item,i in dataList" :key="i" style="padding: 30rpx;" :style="{background:returnColor(i)}">
				<view class="u-w-100 u-text-center">
					<image src="/static/images/rank1.png" class="u-w-80 u-h-50" mode="aspectFill" v-if="i == 0"></image>
					<image src="/static/images/rank2.png" class="u-w-80 u-h-50" mode="aspectFill" v-else-if="i == 1"></image>
					<image src="/static/images/rank3.png" class="u-w-80 u-h-50" mode="aspectFill" v-else-if="i == 2"></image>
					<view v-else class="u-font-30 u-font-bold">{{i+1}}</view>
				</view>
				<view class="u-w-320 u-flex">
					<u-avatar size="56" :src="item.member_info.avatar"></u-avatar>
					<view class="u-font-26 u-m-l-15">{{item.member_info.nickname}}</view>
				</view>
				<view class="u-w-150 u-font-26 u-main-c u-text-right">{{item.clock_count}}次</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				id: '',
				dataList: [],
			};
		},
		onLoad(op) {
			this.id = op.id
			this.get_new_active_ranking()
		},
		methods: {
			async get_new_active_ranking() {
				// try {
				// 	let params = {
				// 		page,
				// 		active_id: this.id
				// 	}
				// 	let res = await this.$u.api.get_new_active_ranking(params)
				// 	this.$refs.paging.complete(res.data.data);
				// } catch (e) {
				// 	console.log(e);
				// 	this.$refs.paging.complete([]);
				// }
				try {
					let params = {
						active_id: this.id
					}
					let res = await this.$u.api.get_new_active_ranking(params)
					this.dataList = res.data
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			returnColor(i) {
				if (i == 0) return '#FEF7E6'
				if (i == 1) return '#F0F5FE'
				if (i == 2) return '#FEF5EB'
				return '#ffffff'
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F5F5;
	}
</style>