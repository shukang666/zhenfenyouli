<template>
	<view class="scf-guide-page">
		<view class="scf-guide-head">
			<text class="scf-guide-title">{{ pageTitle }}</text>
			<text class="scf-guide-intro">{{ introText }}</text>
		</view>
		<view
			v-for="item in tutorialItems"
			:key="item.key"
			class="scf-guide-card"
		>
			<text class="scf-guide-card-title">{{ item.title }}</text>
			<text class="scf-guide-card-desc">{{ item.desc }}</text>
			<image
				v-if="item.exampleImgUrl"
				class="scf-guide-card-img"
				:src="item.exampleImgUrl"
				mode="widthFix"
				@error="onImgError(item.key)"
			/>
		</view>
		<view class="scf-guide-foot">
			<view class="scf-btn scf-btn--primary scf-btn--block" hover-class="scf-card--press" @tap="goBack">
				返回继续真分
			</view>
		</view>
	</view>
</template>

<script>
import settings from '@/common/settings'
import {
	getPointSetupGuideNavTitle,
	getPointSetupTutorialIntroForHome,
	getPointSetupTutorialIntroForStreet,
	getHomePointSetupTutorialItems,
	getStreetPointSetupTutorialItems,
} from '@/common/sceneClockUserCopy.js'

export default {
	data() {
		return {
			hub: 'home',
			cateKw: '',
			imgOk: {},
		}
	},
	computed: {
		pageTitle() {
			return getPointSetupGuideNavTitle()
		},
		introText() {
			return this.hub === 'street'
				? getPointSetupTutorialIntroForStreet()
				: getPointSetupTutorialIntroForHome()
		},
		cosBase() {
			const base = settings.staticAssetBase || ''
			return base + (base.endsWith('/') ? '' : '/') + 'scene-pick/'
		},
		tutorialItems() {
			const okMap = this.imgOk || {}
			const raw = this.hub === 'street'
				? getStreetPointSetupTutorialItems(this.cateKw || '其他')
				: getHomePointSetupTutorialItems()
			return (raw || []).map(item => {
				const key = item.key || item.title
				const imgKey = item.exampleImgKey || ''
				const exampleImgUrl = imgKey && okMap[key] !== false ? this.cosBase + imgKey + '.png' : ''
				return Object.assign({}, item, { exampleImgUrl })
			})
		},
	},
	onLoad(option) {
		const hub = String((option && option.hub) || 'home').trim()
		this.hub = hub === 'street' ? 'street' : 'home'
		this.cateKw = String((option && option.cate_kw) || '').trim()
		uni.setNavigationBarTitle({ title: getPointSetupGuideNavTitle() })
	},
	methods: {
		onImgError(key) {
			if (!key) return
			this.$set(this.imgOk, key, false)
		},
		goBack() {
			uni.navigateBack({
				fail: () => {
					uni.redirectTo({ url: '/pages/activity/scene-clock-pick' })
				},
			})
		},
	},
}
</script>

<style lang="scss" scoped>
@import '@/common/sceneClock/scene-clock-styles.scss';

.scf-guide-page {
	min-height: 100vh;
	padding: 32rpx 28rpx 48rpx;
	background: #f4faf6;
	box-sizing: border-box;
}
.scf-guide-head {
	margin-bottom: 28rpx;
}
.scf-guide-title {
	display: block;
	font-size: 36rpx;
	font-weight: 700;
	color: #1a3d2e;
	margin-bottom: 16rpx;
}
.scf-guide-intro {
	display: block;
	font-size: 28rpx;
	color: #4a6358;
	line-height: 1.55;
}
.scf-guide-card {
	background: #fff;
	border-radius: 20rpx;
	padding: 28rpx;
	margin-bottom: 24rpx;
	border: 2rpx solid #e3efe8;
}
.scf-guide-card-title {
	display: block;
	font-size: 30rpx;
	font-weight: 600;
	color: #2d4a3a;
	margin-bottom: 12rpx;
}
.scf-guide-card-desc {
	display: block;
	font-size: 26rpx;
	color: #5a7268;
	line-height: 1.55;
	margin-bottom: 16rpx;
}
.scf-guide-card-img {
	width: 100%;
	border-radius: 12rpx;
	background: #f0f5f2;
}
.scf-guide-foot {
	margin-top: 16rpx;
}
</style>
