<template>
	<view class="entry-page">
		<view class="entry-card">
			<text class="entry-title">{{ title }}</text>
			<text class="entry-hint">{{ statusText }}</text>
			<view v-if="showLoginBtn" class="entry-btn" hover-class="entry-btn--press" @tap="goLogin">去登录</view>
			<view v-if="showRetry" class="entry-btn entry-btn--ghost" hover-class="entry-btn--press" @tap="tryEnter">重试</view>
		</view>
	</view>
</template>

<script>
import settings from '@/common/settings.js'
import { buildH5WrapperPath } from '@/common/activeOpen.js'
import {
	buildWebActiveEntryPath,
	savePendingWebActiveEntry,
	parseActiveIdFromWebLaunchUrl,
} from '@/common/webActiveEntry.js'

const DEFAULT_TITLE = '1+1 七日挑战'

export default {
	data() {
		return {
			activeId: '',
			title: DEFAULT_TITLE,
			statusText: '正在进入活动…',
			entering: false,
			showLoginBtn: false,
			showRetry: false,
		}
	},
	onLoad(options) {
		this.activeId = String(options.active_id || options.id || '').trim()
		if (!this.activeId && options.url) {
			let raw = String(options.url)
			try {
				raw = decodeURIComponent(raw)
			} catch (e) {}
			this.activeId = parseActiveIdFromWebLaunchUrl(raw) || ''
		}
		if (options.title) {
			try {
				this.title = decodeURIComponent(options.title)
			} catch (e) {
				this.title = String(options.title)
			}
		}
		if (!this.activeId) {
			this.statusText = '缺少活动参数，请从分享卡片重新打开'
			this.showRetry = false
			return
		}
		uni.setNavigationBarTitle({ title: this.title || DEFAULT_TITLE })
	},
	onShow() {
		if (this.activeId) {
			this.tryEnter()
		}
	},
	onShareAppMessage() {
		const path = buildWebActiveEntryPath(this.activeId, { title: this.title })
		return {
			title: this.title || DEFAULT_TITLE,
			path,
			imageUrl: settings.shareCoverUrl,
		}
	},
	methods: {
		goLogin() {
			if (!this.activeId) return
			savePendingWebActiveEntry(this.activeId)
			uni.navigateTo({ url: '/pages/public/login' })
		},
		tryEnter() {
			if (!this.activeId || this.entering) return
			this.showLoginBtn = false
			this.showRetry = false
			if (!this.isLogin || !this.userInfo?.openid) {
				this.statusText = '参与本活动需先登录小程序'
				this.showLoginBtn = true
				savePendingWebActiveEntry(this.activeId)
				return
			}
			this.entering = true
			this.statusText = '正在为您打开活动…'
			this.$u.api.issue_web_context({ active_id: this.activeId }).then((res) => {
				const launch = (res.data && res.data.web_launch_url) || ''
				if (!launch) {
					throw new Error('无法获取活动地址')
				}
				uni.redirectTo({
					url: buildH5WrapperPath(launch, this.activeId),
					fail: () => {
						uni.navigateTo({ url: buildH5WrapperPath(launch, this.activeId) })
					},
				})
			}).catch((err) => {
				this.entering = false
				this.statusText = (err && err.msg) || (err && err.message) || '打开失败，请稍后重试'
				this.showRetry = true
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.entry-page {
	min-height: 100vh;
	box-sizing: border-box;
	padding: 80rpx 48rpx;
	background: linear-gradient(180deg, #e8f5ee 0%, #f4f7f5 45%);
	display: flex;
	align-items: center;
	justify-content: center;
}
.entry-card {
	width: 100%;
	max-width: 640rpx;
	padding: 48rpx 40rpx;
	border-radius: 24rpx;
	background: #fff;
	box-shadow: 0 12rpx 40rpx rgba(30, 90, 55, 0.08);
	text-align: center;
}
.entry-title {
	display: block;
	font-size: 36rpx;
	font-weight: 700;
	color: #1a3d2e;
	margin-bottom: 24rpx;
}
.entry-hint {
	display: block;
	font-size: 28rpx;
	line-height: 1.55;
	color: #5a7268;
	margin-bottom: 32rpx;
}
.entry-btn {
	margin: 0 auto;
	max-width: 400rpx;
	height: 88rpx;
	line-height: 88rpx;
	border-radius: 999rpx;
	background: #16864b;
	color: #fff;
	font-size: 30rpx;
	font-weight: 600;
}
.entry-btn--ghost {
	margin-top: 20rpx;
	background: #fff;
	color: #16864b;
	border: 2rpx solid #16864b;
}
.entry-btn--press {
	opacity: 0.88;
}
</style>
