<template>
	<view class="page">
		<z-paging ref="paging" v-model="dataList" @query="queryList" :fixed="true">
			<template slot="top">
				<u-navbar :is-back="true" title="我的进行中" title-color="#1a2e22" :background="{ background: '#eef4f0' }" :border-bottom="false"></u-navbar>
			</template>
			<view class="list-wrap">
				<view
					class="ongoing-card"
					v-for="item in dataList"
					:key="item.id + '-' + item.role"
					hover-class="ongoing-card--on"
					:hover-stay-time="80"
					@click="openDetail(item)"
				>
					<view class="ongoing-card__cal">
						<text class="ongoing-card__day">{{ item.cal_day }}</text>
						<text class="ongoing-card__sub">{{ item.cal_sub }}</text>
					</view>
					<view class="ongoing-card__body">
						<text class="ongoing-card__name u-line-2">{{ item.name }}</text>
						<text class="ongoing-card__loc u-line-2">{{ item.location_line }}</text>
						<view class="ongoing-card__tag">
							<text>{{ item.status_hint }}</text>
						</view>
					</view>
				</view>
			</view>
		</z-paging>
	</view>
</template>

<script>
export default {
	data() {
		return {
			dataList: [],
		}
	},
	methods: {
		queryList(page) {
			if (!this.$u.func.checkLogin()) {
				this.$refs.paging.complete([])
				return
			}
			this.$u.api
				.get_my_ongoing_list({ page, page_size: 10 })
				.then((res) => {
					const d = res.data || {}
					this.$refs.paging.complete(d.list || [])
				})
				.catch(() => {
					this.$refs.paging.complete([])
				})
		},
		openDetail(item) {
			if (!item || !item.id) return
			this.$u.route('/pages/activity/lsxf/details-new?id=' + item.id)
		},
	},
}
</script>

<style lang="scss" scoped>
.page {
	min-height: 100vh;
	background: #eef4f0;
}
.list-wrap {
	padding: 16rpx 24rpx 32rpx;
}
.ongoing-card {
	display: flex;
	flex-direction: row;
	align-items: stretch;
	padding: 22rpx 20rpx;
	margin-bottom: 14rpx;
	border-radius: 20rpx;
	background: #ffffff;
	box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.06);
}
.ongoing-card--on {
	opacity: 0.92;
}
.ongoing-card__cal {
	flex: 0 0 112rpx;
	width: 112rpx;
	margin-right: 18rpx;
	padding: 14rpx 8rpx;
	border-radius: 16rpx;
	background: #e8f7ec;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}
.ongoing-card__day {
	font-size: 40rpx;
	font-weight: 800;
	color: #12962e;
	line-height: 1.1;
}
.ongoing-card__sub {
	margin-top: 6rpx;
	font-size: 20rpx;
	font-weight: 600;
	color: #5f7166;
	text-align: center;
	line-height: 1.25;
}
.ongoing-card__body {
	flex: 1;
	min-width: 0;
}
.ongoing-card__name {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e22;
	line-height: 1.35;
}
.ongoing-card__loc {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #7a8a80;
	line-height: 1.4;
}
.ongoing-card__tag {
	display: inline-flex;
	margin-top: 12rpx;
	padding: 6rpx 14rpx;
	border-radius: 999rpx;
	background: #edf9f0;
}
.ongoing-card__tag text {
	font-size: 20rpx;
	font-weight: 600;
	color: #12962e;
}
</style>
