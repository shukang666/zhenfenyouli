<template>
	<view class="mod-page">
		<view class="mod-hero" :style="{ background: heroGradient }">
			<text class="mod-emoji">{{ moduleUi.emoji }}</text>
			<text class="mod-title">{{ category }}</text>
			<text class="mod-sub">{{ moduleUi.tagline }}</text>
			<text class="mod-hint">选一篇开始：先读短文，文末立刻测本篇</text>
			<view class="mod-progress">
				<text>本模块通关 {{ passedCount }}/{{ articleCount }}</text>
			</view>
		</view>

		<view class="mod-body">
			<view class="mod-loading" v-if="loading">
				<u-loading mode="circle" color="#0F6E56" size="44"></u-loading>
			</view>
			<view class="mod-empty" v-else-if="articles.length === 0">
				<text class="mod-empty-text">该模块内容筹备中，敬请期待</text>
			</view>
			<view class="mod-mosaic" v-else>
				<view
					class="mod-piece"
					v-for="(art, idx) in articles"
					:key="art.id"
					:class="[
						idx % 5 === 0 ? 'mod-piece--wide' : '',
						idx % 3 === 1 ? 'mod-piece--tall' : '',
						art.passed ? 'is-done' : ''
					]"
					@tap="onTapArticle(idx)"
				>
					<image
						v-if="art.image"
						class="mod-piece-bg"
						:src="art.image"
						mode="aspectFill"
						lazy-load
					></image>
					<view class="mod-piece-mask"></view>
					<view class="mod-piece-inner">
						<text class="mod-piece-state" v-if="art.passed">已过关</text>
						<text class="mod-piece-state mod-piece-state--new" v-else>边学边测</text>
						<text class="mod-piece-title">{{ art.title }}</text>
						<text class="mod-piece-meta">{{ art.question_count }} 题 · 读完后测验</text>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
import { getModuleUi } from '@/common/qxlLearnModules.js'

export default {
	data() {
		return {
			category: '',
			loading: true,
			articles: [],
			passedCount: 0,
			articleCount: 0,
		}
	},
	computed: {
		moduleUi() {
			return getModuleUi(this.category)
		},
		heroGradient() {
			return this.moduleUi.gradient || 'linear-gradient(145deg, #0f6e56 0%, #1fa971 100%)'
		},
	},
	onLoad(query) {
		this.category = decodeURIComponent(query.category || '')
		if (this.category) {
			uni.setNavigationBarTitle({ title: this.category + ' · 学考' })
		}
	},
	onShow() {
		this.loadArticles()
	},
	methods: {
		loadArticles() {
			if (!this.category) {
				this.loading = false
				return
			}
			this.loading = true
			this.$u.api.getQxlModuleArticles({ category: this.category }).then(res => {
				if (res.code == 1) {
					const data = res.data || {}
					this.articles = data.articles || []
					this.passedCount = data.passed_count || 0
					this.articleCount = data.article_count || 0
				}
			}).finally(() => {
				this.loading = false
			})
		},
		onTapArticle(idx) {
			const art = (this.articles || [])[idx]
			if (!art || !art.id) return
			this.$u.route('/pages/activity/qxl-learn-chapter?id=' + art.id)
		},
	},
}
</script>

<style lang="scss" scoped>
.mod-page {
	min-height: 100vh;
	background: #eef3f0;
}
.mod-hero {
	padding: 36rpx 28rpx 56rpx;
	color: #fff;
	border-radius: 0 0 32rpx 32rpx;
}
.mod-emoji {
	font-size: 48rpx;
}
.mod-title {
	display: block;
	margin-top: 12rpx;
	font-size: 40rpx;
	font-weight: 800;
}
.mod-sub {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	opacity: 0.9;
}
.mod-hint {
	display: block;
	margin-top: 16rpx;
	font-size: 22rpx;
	opacity: 0.85;
	line-height: 1.5;
}
.mod-progress {
	margin-top: 20rpx;
	display: inline-block;
	padding: 10rpx 18rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.2);
	font-size: 22rpx;
	font-weight: 600;
}
.mod-body {
	padding: 20rpx 20rpx 80rpx;
	margin-top: -24rpx;
}
.mod-loading, .mod-empty {
	padding: 60rpx 0;
	text-align: center;
}
.mod-empty-text {
	font-size: 26rpx;
	color: #7a8b84;
}
.mod-mosaic {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16rpx;
	grid-auto-flow: dense;
}
.mod-piece {
	position: relative;
	border-radius: 22rpx;
	overflow: hidden;
	min-height: 200rpx;
	background: linear-gradient(145deg, #0f6e56, #52b788);
	box-shadow: 0 10rpx 24rpx rgba(15, 110, 86, 0.12);
}
.mod-piece--wide {
	grid-column: span 2;
	min-height: 220rpx;
}
.mod-piece--tall {
	grid-row: span 2;
	min-height: 320rpx;
}
.mod-piece:active {
	opacity: 0.94;
}
.mod-piece.is-done {
	opacity: 0.92;
}
.mod-piece-bg {
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
}
.mod-piece-mask {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	background: linear-gradient(180deg, rgba(8, 50, 40, 0.15) 0%, rgba(8, 50, 40, 0.75) 100%);
}
.mod-piece-inner {
	position: relative;
	z-index: 2;
	height: 100%;
	min-height: inherit;
	padding: 20rpx 18rpx;
	display: flex;
	flex-direction: column;
	justify-content: flex-end;
	color: #fff;
}
.mod-piece-state {
	align-self: flex-start;
	font-size: 20rpx;
	padding: 4rpx 12rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.25);
	margin-bottom: 10rpx;
	font-weight: 700;
}
.mod-piece-state--new {
	background: rgba(255, 236, 179, 0.35);
}
.mod-piece-title {
	font-size: 28rpx;
	font-weight: 800;
	line-height: 1.4;
}
.mod-piece-meta {
	margin-top: 6rpx;
	font-size: 20rpx;
	opacity: 0.88;
}
</style>
