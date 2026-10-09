<template>
	<view class="container">
		<view :class="heroClass">
			<text class="spot-hero-emoji">{{ moduleEmoji }}</text>
			<text class="spot-hero-title">{{ moduleName }}</text>
			<text class="spot-hero-sub">{{ moduleSubtitle }}</text>
		</view>

		<view class="spot-body">
			<view class="spot-section-head">
				<text class="spot-section-label">活动地点</text>
				<text class="spot-section-hint">选择地点进入活动</text>
			</view>

			<view class="spot-list" v-if="venues.length">
				<view
					class="spot-card"
					v-for="(item, vIdx) in venues"
					:key="vIdx"
					@tap="onTapVenue(vIdx)"
				>
					<view class="spot-card-cover" v-if="item.thumb">
						<image class="spot-card-img" :src="item.thumb" mode="aspectFill" lazy-load></image>
					</view>
					<view class="spot-card-body">
						<view class="spot-card-title u-line-2">{{ item.title }}</view>
						<view class="spot-card-meta">
							<view class="spot-meta-row" v-if="item.date_text">
								<u-icon name="clock" size="20" color="#65676B"></u-icon>
								<text>{{ item.date_text }}</text>
							</view>
							<view class="spot-meta-row" v-if="showVenueAddress(item)">
								<u-icon name="map" size="20" color="#65676B"></u-icon>
								<text class="u-line-1">{{ item.address }}</text>
							</view>
						</view>
						<view class="spot-card-foot" v-if="item.active_count > 0">
							<text class="spot-count">{{ item.active_count }}人参与</text>
						</view>
					</view>
					<text class="spot-card-arrow">›</text>
				</view>
			</view>

			<view class="spot-empty" v-else-if="!loading">
				<text class="spot-empty-text">暂无活动地点，敬请期待</text>
			</view>
			<view class="spot-empty" v-else>
				<text class="spot-empty-text">加载中...</text>
			</view>
		</view>
	</view>
</template>

<script>
import { openActiveDetailWithContext, appendActiveIdToWebUrl, buildH5WrapperPath, isWebDisplayActive } from '@/common/activeOpen.js'

const SPOT_DECOR = {
	hiking: { emoji: '🥾', subtitle: '边走边捡 · 洁净山野' },
	flea_market: { emoji: '♻️', subtitle: '闲置循环 · 绿色生活' },
	zhenfen_class: { emoji: '📗', subtitle: '学分类 · 真投放' },
}

export default {
	data() {
		return {
			spotKey: '',
			moduleName: '',
			moduleSubtitle: '',
			moduleEmoji: '📌',
			venues: [],
			loading: true,
		}
	},
	computed: {
		heroClass() {
			const k = this.spotKey || 'default'
			return 'spot-hero spot-hero--' + k
		},
	},
	onLoad(options) {
		this.spotKey = String(options.spot || options.spot_key || '').trim()
		const nameFromQuery = options.name ? decodeURIComponent(options.name) : ''
		if (nameFromQuery) {
			this.moduleName = nameFromQuery
		}
		const decor = SPOT_DECOR[this.spotKey] || {}
		this.moduleEmoji = decor.emoji || '📌'
		if (!this.moduleSubtitle) {
			this.moduleSubtitle = decor.subtitle || ''
		}
		this.loadHub()
	},
	methods: {
		showVenueAddress(item) {
			if (!item || !item.address) return false
			const addr = String(item.address).trim()
			const title = String(item.title || '').trim()
			if (!addr) return false
			if (!title) return true
			if (addr === title) return false
			if (addr.indexOf(title) >= 0 || title.indexOf(addr) >= 0) return false
			return true
		},
		loadHub() {
			if (!this.spotKey) {
				this.loading = false
				this.$u.toast('专题参数缺失')
				return
			}
			this.loading = true
			this.$u.api.get_activity_spot_venues({ spot_key: this.spotKey }).then(res => {
				const data = res.data || {}
				const mod = data.module || {}
				if (mod.name) this.moduleName = mod.name
				if (mod.subtitle) this.moduleSubtitle = mod.subtitle
				if (mod.emoji) this.moduleEmoji = mod.emoji
				this.venues = data.venues || []
				if (this.moduleName) {
					uni.setNavigationBarTitle({ title: this.moduleName })
				}
			}).catch(err => {
				this.$u.toast(err.msg || '加载失败')
			}).finally(() => {
				this.loading = false
			})
		},
		onTapVenue(vIdx) {
			const item = this.venues[vIdx]
			if (!item || !item.active_id) return
			const payload = {
				id: item.active_id,
				display_mode: item.display_mode,
				web_url: item.web_url,
				web_launch_url: item.web_launch_url,
				web_page_url: item.web_page_url,
			}
			if (isWebDisplayActive(payload)) {
				let pageUrl = item.web_launch_url || item.web_page_url || ''
				if (!pageUrl && item.web_url) {
					pageUrl = appendActiveIdToWebUrl(item.web_url, item.active_id)
				}
				if (item.session_id) {
					pageUrl += '#session/' + encodeURIComponent(item.session_id)
				}
				const issueContext = typeof this.$u.api.issue_web_context === 'function'
					? (id) => this.$u.api.issue_web_context({ active_id: id })
					: null
				if (issueContext) {
					openActiveDetailWithContext(payload, null, issueContext)
					return
				}
				uni.navigateTo({ url: buildH5WrapperPath(pageUrl) })
				return
			}
			this.$u.route(`/pages/activity/lsxf/details-new?id=${item.active_id}`)
		},
	},
}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #f0f2f5;
}
.spot-hero {
	padding: 48rpx 32rpx 56rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
	color: #fff;
}
.spot-hero--hiking {
	background: linear-gradient(135deg, #2d6a4f 0%, #52b788 100%);
}
.spot-hero--flea_market {
	background: linear-gradient(135deg, #40916c 0%, #74c69d 100%);
}
.spot-hero--zhenfen_class {
	background: linear-gradient(135deg, #1877f2 0%, #34c759 100%);
}
.spot-hero-emoji {
	font-size: 56rpx;
}
.spot-hero-title {
	font-size: 40rpx;
	font-weight: 800;
	letter-spacing: 2rpx;
}
.spot-hero-sub {
	font-size: 24rpx;
	opacity: 0.92;
}
.spot-body {
	margin-top: -28rpx;
	padding: 0 20rpx 80rpx;
	position: relative;
	z-index: 2;
}
.spot-section-head {
	background: #fff;
	border-radius: 20rpx 20rpx 0 0;
	padding: 24rpx 24rpx 12rpx;
	border: 1rpx solid #e4e6eb;
	border-bottom: none;
}
.spot-section-label {
	font-size: 30rpx;
	font-weight: 700;
	color: #050505;
}
.spot-section-hint {
	font-size: 22rpx;
	color: #8a8a8a;
	margin-left: 12rpx;
}
.spot-list {
	background: #fff;
	border-radius: 0 0 20rpx 20rpx;
	padding: 8rpx 16rpx 20rpx;
	border: 1rpx solid #e4e6eb;
	border-top: none;
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.spot-card {
	display: flex;
	align-items: stretch;
	background: #fafbfc;
	border-radius: 16rpx;
	border: 1rpx solid #edeef0;
	overflow: hidden;
}
.spot-card:active {
	opacity: 0.88;
}
.spot-card-cover {
	width: 168rpx;
	flex-shrink: 0;
}
.spot-card-img {
	width: 168rpx;
	height: 140rpx;
	display: block;
}
.spot-card-body {
	flex: 1;
	min-width: 0;
	padding: 16rpx 12rpx 16rpx 16rpx;
	display: flex;
	flex-direction: column;
	justify-content: center;
	gap: 8rpx;
}
.spot-card-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #050505;
	line-height: 1.35;
}
.spot-card-meta {
	display: flex;
	flex-direction: column;
	gap: 6rpx;
}
.spot-meta-row {
	display: flex;
	align-items: center;
	gap: 6rpx;
	font-size: 22rpx;
	color: #65676b;
	min-width: 0;
}
.spot-meta-row text {
	flex: 1;
	overflow: hidden;
}
.spot-card-foot {
	margin-top: 4rpx;
}
.spot-count {
	font-size: 20rpx;
	color: #18af38;
	font-weight: 600;
}
.spot-card-arrow {
	align-self: center;
	padding: 0 16rpx;
	font-size: 36rpx;
	color: #bcc0c4;
}
.spot-empty {
	background: #fff;
	border-radius: 0 0 20rpx 20rpx;
	padding: 48rpx 24rpx;
	text-align: center;
	border: 1rpx solid #e4e6eb;
	border-top: none;
}
.spot-empty-text {
	font-size: 26rpx;
	color: #8a8a8a;
}
</style>
