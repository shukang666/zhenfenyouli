<template>
	<view class="intro-page">
		<image class="intro-hero" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/secondhand-intro-banner.png" mode="widthFix"></image>
		<view class="intro-card">
			<view class="intro-line"><text class="intro-num">01</text><text>发布照片、描述、价格和联系方式</text></view>
			<view class="intro-line"><text class="intro-num">02</text><text>选择同小区、同社区或整个七贤岭</text></view>
			<view class="intro-line"><text class="intro-num">03</text><text>买卖双方自行沟通、线下交接</text></view>
		</view>
		<view class="intro-note">平台仅提供信息展示，不代收款、不使用积分交易。</view>
		<view class="intro-actions">
			<view class="intro-button intro-button--main" @tap="goList">浏览社区闲置</view>
			<view class="intro-button intro-button--plain" @tap="goPublish">发布我的闲置</view>
		</view>
	</view>
</template>

<script>
import { secondhandListUrl, secondhandPublishUrl } from '@/common/secondhandNav.js'

export default {
	methods: {
		goList() { this.$u.route(secondhandListUrl()) },
		goPublish() {
			if (!this.$u.func.checkLogin()) return
			this.$u.route(secondhandPublishUrl())
		},
	},
}
</script>

<style lang="scss" scoped>
.intro-page { min-height: 100vh; box-sizing: border-box; padding: 40rpx 30rpx calc(48rpx + env(safe-area-inset-bottom)); background: #f4f7f5; }
.intro-hero { display: block; width: 100%; border-radius: 28rpx; }
.intro-card { margin-top: 26rpx; padding: 12rpx 28rpx; border-radius: 24rpx; background: #fff; box-shadow: 0 8rpx 28rpx rgba(30,90,55,.06); }
.intro-line { display: flex; align-items: center; min-height: 100rpx; border-bottom: 1rpx solid #edf1ee; font-size: 27rpx; color: #34453b; }
.intro-line:last-child { border-bottom: 0; }
.intro-num { margin-right: 22rpx; color: #16864b; font-size: 24rpx; font-weight: 700; }
.intro-note { margin: 24rpx 8rpx; color: #849189; font-size: 23rpx; line-height: 1.6; }
.intro-actions { display: flex; flex-direction: column; gap: 18rpx; margin-top: 34rpx; }
.intro-button { height: 92rpx; line-height: 92rpx; border-radius: 999rpx; text-align: center; font-size: 30rpx; font-weight: 600; }
.intro-button--main { color: #fff; background: #16864b; }
.intro-button--plain { color: #16864b; background: #e6f3eb; }
</style>
