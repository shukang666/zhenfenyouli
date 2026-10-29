<template>
	<view class="pub-page">
		<view class="pub-nav">
			<view class="pub-nav__back" @click="goBack">
				<u-icon name="arrow-left" size="36" color="#1a2e22"></u-icon>
			</view>
			<text class="pub-nav__title">我的发布</text>
			<view class="pub-nav__add" @click="goPublish">
				<u-icon name="plus" size="32" color="#18AF38"></u-icon>
			</view>
		</view>

		<view class="pub-stats">
			<view class="pub-stats__item">
				<text class="pub-stats__val">{{ stats.total_published }}</text>
				<text class="pub-stats__label">累计发布</text>
			</view>
			<view class="pub-stats__sep"></view>
			<view class="pub-stats__item">
				<text class="pub-stats__val">{{ stats.total_signups }}</text>
				<text class="pub-stats__label">累计报名</text>
			</view>
			<view class="pub-stats__sep"></view>
			<view class="pub-stats__item">
				<text class="pub-stats__val">{{ stats.rating }}</text>
				<text class="pub-stats__label">活动评分</text>
			</view>
		</view>

		<scroll-view scroll-x class="pub-tabs" :show-scrollbar="false">
			<view class="pub-tabs__inner">
				<view
					v-for="tab in tabOptions"
					:key="tab.key"
					class="pub-tab"
					:class="{ 'pub-tab--on': filter === tab.key }"
					@click="switchTab(tab.key)"
				>
					<text>{{ tab.label }}</text>
					<text class="pub-tab__count" v-if="tabCount(tab.key)">{{ tabCount(tab.key) }}</text>
				</view>
			</view>
		</scroll-view>

		<view class="pub-list">
			<view class="pub-card" v-for="item in list" :key="item.id">
				<view class="pub-card__main" @click="onPreview(item)">
					<view class="pub-card__thumb">
						<u-image
							v-if="item.thumb"
							width="128rpx"
							height="128rpx"
							:src="item.thumb"
							mode="aspectFill"
							border-radius="16"
						></u-image>
						<text v-else class="pub-card__ph">🎯</text>
					</view>
					<view class="pub-card__body">
						<view class="pub-card__head">
							<text class="pub-card__name u-line-2">{{ item.name }}</text>
							<text class="pub-card__badge" :class="'pub-card__badge--' + item.status_tone">{{
								item.status_label
							}}</text>
						</view>
						<text class="pub-card__meta u-line-2">{{ item.meta_line }}</text>
						<text class="pub-card__progress" v-if="item.progress_line">{{ item.progress_line }}</text>
					</view>
				</view>
				<view class="pub-card__foot">
					<text class="pub-card__hint u-line-2">{{ item.foot_hint }}</text>
					<view class="pub-card__actions">
						<block v-for="(act, idx) in item.actions" :key="act.key">
							<text v-if="idx" class="pub-card__sep">|</text>
							<text class="pub-card__action" @click.stop="onAction(act, item)">{{ act.label }}</text>
						</block>
					</view>
				</view>
			</view>

			<view class="pub-empty" v-if="!loading && !list.length">
				<text>还没有发布过活动</text>
				<view class="pub-empty__btn" @click="goPublish">发起第一场</view>
			</view>
		</view>

		<view class="pub-cta">
			<view class="pub-cta__copy">
				<text class="pub-cta__title">有新的活动想法？</text>
				<text class="pub-cta__sub">创建草稿，完善后再提交审核</text>
			</view>
			<view class="pub-cta__btn" @click="goPublish">发起活动</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			filter: 'all',
			loading: false,
			stats: { total_published: 0, total_signups: 0, rating: '—' },
			tab_counts: { all: 0, auditing: 0, enrolling: 0, ended: 0 },
			list: [],
			tabOptions: [
				{ key: 'all', label: '全部' },
				{ key: 'auditing', label: '审核中' },
				{ key: 'enrolling', label: '报名中' },
				{ key: 'ended', label: '已结束' },
			],
		}
	},
	onShow() {
		if (!this.$u.func.checkLogin()) return
		this.load()
	},
	methods: {
		goBack() {
			uni.navigateBack({ fail: () => uni.switchTab({ url: '/pages/user/index' }) })
		},
		tabCount(key) {
			const n = Number(this.tab_counts[key]) || 0
			if (key === 'all') return n > 0 ? n : 0
			return n > 0 ? n : 0
		},
		switchTab(key) {
			if (this.filter === key) return
			this.filter = key
			this.load()
		},
		load() {
			this.loading = true
			this.$u.api
				.memberActiveMyPublish({ filter: this.filter })
				.then((res) => {
					const data = res.data || {}
					this.stats = data.stats || this.stats
					this.tab_counts = data.tab_counts || this.tab_counts
					this.list = data.list || []
				})
				.finally(() => {
					this.loading = false
				})
		},
		goPublish() {
			this.$u.route('/pages/activity/member-active-publish')
		},
		onPreview(item) {
			if (!item || !item.id) return
			this.$u.route('/pages/activity/lsxf/details-new?id=' + item.id)
		},
		onAction(act, item) {
			if (!act || !item) return
			const key = act.key
			if (key === 'withdraw') {
				uni.showModal({
					title: '撤回发布',
					content: '撤回后本条将不再进入审核，确定撤回吗？',
					success: (r) => {
						if (!r.confirm) return
						this.$u.api.memberActiveWithdraw({ active_id: item.id }).then(() => {
							uni.showToast({ title: '已撤回', icon: 'success' })
							this.load()
						})
					},
				})
				return
			}
			if (key === 'edit') {
				uni.showToast({ title: '请重新发起活动并补充说明', icon: 'none' })
				this.goPublish()
				return
			}
			if (key === 'preview' || key === 'sign_list' || key === 'checkin') {
				this.onPreview(item)
				return
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.pub-page {
	min-height: 100vh;
	background: #f3f6f4;
	padding-bottom: 160rpx;
}
.pub-nav {
	display: flex;
	align-items: center;
	justify-content: center;
	padding: calc(env(safe-area-inset-top) + 12rpx) 24rpx 12rpx;
	position: relative;
}
.pub-nav__back,
.pub-nav__add {
	position: absolute;
	top: calc(env(safe-area-inset-top) + 12rpx);
	width: 72rpx;
	height: 72rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.pub-nav__back {
	left: 12rpx;
}
.pub-nav__add {
	right: 12rpx;
}
.pub-nav__title {
	font-size: 34rpx;
	font-weight: 800;
	color: #1a2e22;
}
.pub-stats {
	display: flex;
	align-items: center;
	margin: 8rpx 24rpx 20rpx;
	padding: 28rpx 16rpx;
	border-radius: 24rpx;
	background: linear-gradient(135deg, #0d5c3a 0%, #1a9d62 100%);
	color: #fff;
	box-shadow: 0 12rpx 32rpx rgba(13, 92, 58, 0.22);
}
.pub-stats__item {
	flex: 1;
	text-align: center;
}
.pub-stats__val {
	display: block;
	font-size: 40rpx;
	font-weight: 800;
}
.pub-stats__label {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	opacity: 0.9;
}
.pub-stats__sep {
	width: 1px;
	height: 56rpx;
	background: rgba(255, 255, 255, 0.25);
}
.pub-tabs {
	margin-bottom: 8rpx;
	white-space: nowrap;
}
.pub-tabs__inner {
	display: inline-flex;
	padding: 0 24rpx 12rpx;
	gap: 12rpx;
}
.pub-tab {
	display: inline-flex;
	align-items: center;
	gap: 6rpx;
	padding: 12rpx 24rpx;
	border-radius: 999rpx;
	background: #fff;
	font-size: 24rpx;
	font-weight: 600;
	color: #5f7166;
	box-shadow: 0 4rpx 12rpx rgba(22, 53, 36, 0.05);
}
.pub-tab--on {
	background: #1a2420;
	color: #fff;
}
.pub-tab__count {
	font-size: 22rpx;
	opacity: 0.85;
}
.pub-list {
	padding: 0 24rpx;
}
.pub-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 20rpx;
	margin-bottom: 16rpx;
	box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.06);
}
.pub-card__main {
	display: flex;
	flex-direction: row;
}
.pub-card__thumb {
	flex: 0 0 128rpx;
	width: 128rpx;
	height: 128rpx;
	border-radius: 16rpx;
	overflow: hidden;
	background: #eef4f0;
	display: flex;
	align-items: center;
	justify-content: center;
}
.pub-card__ph {
	font-size: 48rpx;
}
.pub-card__body {
	flex: 1;
	min-width: 0;
	margin-left: 18rpx;
}
.pub-card__head {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: 8rpx;
}
.pub-card__name {
	flex: 1;
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e22;
	line-height: 1.35;
}
.pub-card__badge {
	flex-shrink: 0;
	font-size: 20rpx;
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
	background: #e8f6ef;
	color: #16864b;
	font-weight: 600;
}
.pub-card__badge--warn {
	background: #fff3e6;
	color: #e86b20;
}
.pub-card__badge--danger {
	background: #ffecef;
	color: #d6455d;
}
.pub-card__badge--muted {
	background: #f0f2f5;
	color: #8a9690;
}
.pub-card__meta {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #8a9690;
	line-height: 1.4;
}
.pub-card__progress {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	font-weight: 600;
	color: #e86b20;
}
.pub-card__foot {
	margin-top: 16rpx;
	padding-top: 14rpx;
	border-top: 1px solid #f0f2f5;
}
.pub-card__hint {
	display: block;
	font-size: 22rpx;
	color: #8a9690;
	line-height: 1.45;
	margin-bottom: 10rpx;
}
.pub-card__actions {
	display: flex;
	flex-wrap: wrap;
	justify-content: flex-end;
	gap: 4rpx;
}
.pub-card__action {
	font-size: 24rpx;
	font-weight: 600;
	color: #18af38;
}
.pub-card__sep {
	color: #cfd8d3;
	margin: 0 8rpx;
}
.pub-empty {
	padding: 80rpx 0;
	text-align: center;
	font-size: 26rpx;
	color: #8a9690;
}
.pub-empty__btn {
	display: inline-block;
	margin-top: 20rpx;
	padding: 12rpx 32rpx;
	border-radius: 999rpx;
	background: #18af38;
	color: #fff;
	font-size: 26rpx;
	font-weight: 600;
}
.pub-cta {
	position: fixed;
	left: 24rpx;
	right: 24rpx;
	bottom: calc(24rpx + env(safe-area-inset-bottom));
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 20rpx 22rpx;
	border-radius: 20rpx;
	background: #e8f6ef;
	box-shadow: 0 8rpx 24rpx rgba(22, 53, 36, 0.08);
}
.pub-cta__copy {
	flex: 1;
	min-width: 0;
}
.pub-cta__title {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #1a2e22;
}
.pub-cta__sub {
	display: block;
	margin-top: 4rpx;
	font-size: 22rpx;
	color: #5f7166;
}
.pub-cta__btn {
	flex-shrink: 0;
	padding: 14rpx 28rpx;
	border-radius: 999rpx;
	background: #18af38;
	color: #fff;
	font-size: 26rpx;
	font-weight: 700;
}
</style>
