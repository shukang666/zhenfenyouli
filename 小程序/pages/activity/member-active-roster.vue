<template>
	<view class="rost-page">
		<view class="rost-nav">
			<view class="rost-nav__back" @click="goBack">
				<u-icon name="arrow-left" size="36" color="#1a2e22"></u-icon>
			</view>
			<text class="rost-nav__title u-line-1">{{ activeName || '活动名单' }}</text>
		</view>

		<view class="rost-tabs">
			<view
				class="rost-tab"
				:class="{ 'rost-tab--on': tab === 'sign' }"
				@click="switchTab('sign')"
			>
				<text>报名名单</text>
				<text class="rost-tab__n" v-if="signTotal >= 0">{{ signTotal }}</text>
			</view>
			<view
				class="rost-tab"
				:class="{ 'rost-tab--on': tab === 'checkin' }"
				@click="switchTab('checkin')"
			>
				<text>签到名单</text>
				<text class="rost-tab__n" v-if="checkinTotal >= 0">{{ checkinTotal }}</text>
			</view>
		</view>

		<scroll-view
			class="rost-scroll"
			scroll-y
			@scrolltolower="loadMore"
			:lower-threshold="80"
		>
			<view class="rost-row" v-for="row in list" :key="tab + '-' + row.id">
				<view class="rost-row__avatar">
					<u-image
						v-if="row.avatar"
						width="80"
						height="80"
						:src="row.avatar"
						mode="aspectFill"
						border-radius="40"
					></u-image>
					<text v-else class="rost-row__ph">{{ (row.nickname || '居').slice(0, 1) }}</text>
				</view>
				<view class="rost-row__main">
					<text class="rost-row__name">{{ row.nickname || '居民' }}</text>
					<text class="rost-row__sub">{{ row.mobile_mask }} · {{ row.time_text }}</text>
				</view>
			</view>

			<view class="rost-empty" v-if="!loading && !list.length">
				<text>{{ tab === 'sign' ? '暂无报名记录' : '暂无现场签到' }}</text>
				<text class="rost-empty__hint" v-if="tab === 'checkin'">参与者需在活动页完成定位或口令签到后才会出现在此</text>
			</view>

			<view class="rost-foot" v-if="loading">
				<text>加载中…</text>
			</view>
			<view class="rost-foot" v-else-if="finished && list.length">
				<text>— 已加载全部 —</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			activeId: 0,
			activeName: '',
			tab: 'sign',
			list: [],
			page: 1,
			lastPage: 1,
			loading: false,
			finished: false,
			signTotal: -1,
			checkinTotal: -1,
		}
	},
	onLoad(query) {
		this.activeId = Number(query.active_id) || 0
		this.activeName = decodeURIComponent(query.title || '')
		const t = String(query.tab || 'sign')
		this.tab = t === 'checkin' ? 'checkin' : 'sign'
		if (!this.$u.func.checkLogin()) return
		if (!this.activeId) {
			uni.showToast({ title: '缺少活动信息', icon: 'none' })
			return
		}
		this.reload()
	},
	methods: {
		goBack() {
			uni.navigateBack({ fail: () => uni.navigateBack() })
		},
		switchTab(key) {
			if (this.tab === key) return
			this.tab = key
			this.reload()
		},
		reload() {
			this.list = []
			this.page = 1
			this.lastPage = 1
			this.finished = false
			this.fetch(true)
		},
		loadMore() {
			if (this.loading || this.finished) return
			if (this.page >= this.lastPage) {
				this.finished = true
				return
			}
			this.page += 1
			this.fetch(false)
		},
		fetch(reset) {
			if (!this.activeId) return
			this.loading = true
			const api =
				this.tab === 'checkin'
					? this.$u.api.memberActivePublishCheckinList
					: this.$u.api.memberActivePublishSignList
			api({
				active_id: this.activeId,
				page: this.page,
				page_size: 20,
			})
				.then((res) => {
					const data = res.data || {}
					if (data.active && data.active.name) {
						this.activeName = data.active.name
					}
					const rows = data.list || []
					this.lastPage = Number(data.last_page) || 1
					const total = Number(data.total) || 0
					if (this.tab === 'sign') {
						this.signTotal = total
					} else {
						this.checkinTotal = total
					}
					if (reset) {
						this.list = rows
					} else {
						this.list = this.list.concat(rows)
					}
					this.finished = this.page >= this.lastPage
				})
				.finally(() => {
					this.loading = false
				})
		},
	},
}
</script>

<style lang="scss" scoped>
.rost-page {
	min-height: 100vh;
	background: #f3f6f4;
	display: flex;
	flex-direction: column;
}
.rost-nav {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: calc(env(safe-area-inset-top) + 12rpx) 88rpx 12rpx;
	position: relative;
	background: #fff;
}
.rost-nav__back {
	position: absolute;
	left: 12rpx;
	top: calc(env(safe-area-inset-top) + 12rpx);
	width: 72rpx;
	height: 72rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.rost-nav__title {
	font-size: 32rpx;
	font-weight: 800;
	color: #1a2e22;
	max-width: 100%;
}
.rost-tabs {
	display: flex;
	margin: 16rpx 24rpx;
	padding: 6rpx;
	border-radius: 16rpx;
	background: #e8efe9;
}
.rost-tab {
	flex: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8rpx;
	padding: 16rpx 0;
	border-radius: 12rpx;
	font-size: 26rpx;
	font-weight: 600;
	color: #5f7166;
}
.rost-tab--on {
	background: #fff;
	color: #16864b;
	box-shadow: 0 4rpx 12rpx rgba(22, 53, 36, 0.06);
}
.rost-tab__n {
	font-size: 22rpx;
	opacity: 0.85;
}
.rost-scroll {
	flex: 1;
	height: calc(100vh - 220rpx - env(safe-area-inset-top));
	padding: 0 24rpx 40rpx;
	box-sizing: border-box;
}
.rost-row {
	display: flex;
	align-items: center;
	padding: 20rpx;
	margin-bottom: 12rpx;
	border-radius: 16rpx;
	background: #fff;
	box-shadow: 0 4rpx 16rpx rgba(22, 53, 36, 0.04);
}
.rost-row__avatar {
	flex: 0 0 80rpx;
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	overflow: hidden;
	background: #eef4f0;
	display: flex;
	align-items: center;
	justify-content: center;
}
.rost-row__ph {
	font-size: 32rpx;
	font-weight: 700;
	color: #18af38;
}
.rost-row__main {
	flex: 1;
	min-width: 0;
	margin-left: 16rpx;
}
.rost-row__name {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #1a2e22;
}
.rost-row__sub {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #8a9690;
}
.rost-empty {
	padding: 80rpx 24rpx;
	text-align: center;
	font-size: 26rpx;
	color: #8a9690;
}
.rost-empty__hint {
	display: block;
	margin-top: 12rpx;
	font-size: 22rpx;
	line-height: 1.5;
	color: #aab5af;
}
.rost-foot {
	padding: 24rpx;
	text-align: center;
	font-size: 22rpx;
	color: #aab5af;
}
</style>
