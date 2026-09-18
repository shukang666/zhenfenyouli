<template>
	<view class="guide-overlay" v-if="visible" @touchmove.stop.prevent="noop" @tap="onOverlayTap">
		<!-- 遮罩 + 挖洞：box-shadow 9999px 向外发散，洞本身透明 -->
		<view class="guide-mask" :style="maskStyle"></view>

		<!-- 高亮边框（独立层，带脉冲动画） -->
		<view class="guide-highlight" :style="highlightStyle" v-if="targetRect"></view>

		<!-- 引导描述卡片 -->
		<view class="guide-card" :style="cardStyle" catchtap="noop">
			<!-- 顶部装饰条 -->
			<view class="guide-card__bar">
				<view class="guide-card__bar-inner"></view>
			</view>
			<!-- 主体内容 -->
			<view class="guide-card__body">
				<view class="guide-card__title">
					<text v-if="currentStep.icon" class="guide-card__icon">{{ currentStep.icon }}</text>
					<text class="guide-card__title-text">{{ currentStep.title }}</text>
				</view>
				<view class="guide-card__desc">{{ currentStep.desc }}</view>
			</view>
			<!-- 底部操作区 -->
			<view class="guide-card__footer">
				<view class="guide-card__step">
					<text class="guide-card__step-current">{{ stepIndex + 1 }}</text>
					<text class="guide-card__step-total"> / {{ steps.length }}</text>
				</view>
				<view class="guide-card__actions">
					<view class="guide-card__skip" catchtap="close" hover-class="guide-card__skip--hover">跳过</view>
					<view class="guide-card__next" catchtap="next" hover-class="guide-card__next--hover">
						<text>{{ stepIndex === steps.length - 1 ? '开启体验' : '下一步' }}</text>
						<text v-if="stepIndex !== steps.length - 1" class="guide-card__next-arrow">›</text>
					</view>
				</view>
			</view>
			<!-- 指向高亮元素的箭头（朝上/朝下自适应） -->
			<view v-if="targetRect && arrowStyle" class="guide-card__arrow"
				:class="{ 'guide-card__arrow--up': isArrowUp, 'guide-card__arrow--down': !isArrowUp }"
				:style="arrowStyle"></view>
		</view>
	</view>
</template>

<script>
/**
 * 新手引导组件
 *
 * 使用方式：
 *  <new-user-guide :visible.sync="showGuide" :steps="guideSteps" @close="onGuideClose" />
 *
 * steps 配置：
 *  guideSteps: [
 *    { selector: '.hello-card', icon: '👋', title: '问候与快捷打卡', desc: '...' },
 *    { selector: '.home-search-wrap', icon: '🔍', title: '搜索活动', desc: '...' },
 *    // selector 为空时，卡片居中显示，不挖洞
 *    { selector: '', icon: '🎉', title: '开始体验', desc: '...' },
 *  ]
 *
 * 卡片位置自适应：
 *  - 高亮元素位于屏幕上半部分时，卡片显示在元素下方
 *  - 高亮元素位于屏幕下半部分时，卡片显示在元素上方
 *  - 无 selector 时，卡片居中显示
 */

export default {
	name: 'new-user-guide',
	props: {
		// 引导步骤配置
		steps: {
			type: Array,
			default: () => []
		},
		// 是否显示引导
		visible: {
			type: Boolean,
			default: false
		},
		// 高亮区域向外扩展的内边距（px）
		padding: {
			type: Number,
			default: 8
		},
		// 卡片距离高亮元素的间距（px）
		gap: {
			type: Number,
			default: 16
		}
	},
	data() {
		return {
			stepIndex: 0,
			targetRect: null,
			retryCount: 0,
			maxRetries: 15,
			queryTimer: null,
			// 卡片高度估算值（px），用于定位计算
			cardHeightEst: 220
		}
	},
	computed: {
		currentStep() {
			return this.steps[this.stepIndex] || { title: '', desc: '', selector: '', icon: '' }
		},
		sysInfo() {
			return uni.getSystemInfoSync()
		},
		/**
		 * 遮罩 + 挖洞样式
		 * 原理：一个矩形 view 盖在洞位置，向外发散 9999px 的 box-shadow 形成全屏遮罩
		 * 洞本身保持透明，露出底层内容
		 */
		maskStyle() {
			if (!this.targetRect) {
				// 无目标元素：纯全屏遮罩
				return {
					position: 'fixed',
					top: 0,
					left: 0,
					width: '100%',
					height: '100%',
					background: 'rgba(0, 0, 0, 0.78)',
					zIndex: 9998
				}
			}
			const { left, top, width, height } = this.targetRect
			const pad = this.padding
			return {
				position: 'fixed',
				left: (left - pad) + 'px',
				top: (top - pad) + 'px',
				width: (width + pad * 2) + 'px',
				height: (height + pad * 2) + 'px',
				background: 'transparent',
				boxShadow: '0 0 0 9999px rgba(0, 0, 0, 0.78)',
				borderRadius: '12px',
				zIndex: 9998,
				transition: 'all 0.3s ease'
			}
		},
		/**
		 * 高亮边框样式：与洞精确对齐，带脉冲动画
		 */
		highlightStyle() {
			if (!this.targetRect) return {}
			const { left, top, width, height } = this.targetRect
			const pad = this.padding
			return {
				position: 'fixed',
				left: (left - pad) + 'px',
				top: (top - pad) + 'px',
				width: (width + pad * 2) + 'px',
				height: (height + pad * 2) + 'px',
				border: '2px solid #18AF38',
				borderRadius: '12px',
				boxShadow: '0 0 16px rgba(24, 175, 56, 0.6), inset 0 0 8px rgba(24, 175, 56, 0.25)',
				zIndex: 9999,
				pointerEvents: 'none',
				transition: 'all 0.3s ease'
			}
		},
		/**
		 * 卡片样式：位置自适应跟随高亮元素
		 */
		cardStyle() {
			const winW = this.sysInfo.windowWidth
			const winH = this.sysInfo.windowHeight
			// 卡片宽度：屏幕宽 - 60rpx 边距，转 px 大约 winW - 32
			const cardWidth = winW - 32
			const left = 16
			if (!this.targetRect) {
				// 无目标：屏幕居中（略偏上 40%）
				const top = Math.floor((winH - this.cardHeightEst) / 2 - winH * 0.05)
				return {
					position: 'fixed',
					left: left + 'px',
					top: Math.max(40, top) + 'px',
					width: cardWidth + 'px',
					zIndex: 10000
				}
			}
			const { top: rectTop, height: rectH } = this.targetRect
			const pad = this.padding
			// 高亮洞的上下沿
			const holeTop = rectTop - pad
			const holeBottom = rectTop + rectH + pad
			// 高亮元素在屏幕上半部 → 卡片显示在下方；下半部 → 上方
			let cardTop
			if (rectTop + rectH / 2 < winH / 2) {
				// 卡片在洞下方
				cardTop = holeBottom + this.gap
				// 防止溢出屏幕底部
				const maxTop = winH - this.cardHeightEst - 20
				if (cardTop > maxTop) cardTop = maxTop
			} else {
				// 卡片在洞上方
				cardTop = holeTop - this.gap - this.cardHeightEst
				if (cardTop < 40) cardTop = 40
			}
			return {
				position: 'fixed',
				left: left + 'px',
				top: cardTop + 'px',
				width: cardWidth + 'px',
				zIndex: 10000
			}
		},
		/**
		 * 卡片是否位于高亮元素下方（箭头朝上指向元素）
		 */
		isArrowUp() {
			if (!this.targetRect) return true
			const winH = this.sysInfo.windowHeight
			const { top: rectTop, height: rectH } = this.targetRect
			return rectTop + rectH / 2 < winH / 2
		},
		/**
		 * 箭头样式：指向高亮元素
		 * 卡片在洞下方时箭头朝上（在卡片顶部），反之朝下（在卡片底部）
		 */
		arrowStyle() {
			if (!this.targetRect) return null
			const { left: rectLeft, width: rectW } = this.targetRect
			const cardLeft = parseFloat(this.cardStyle.left)
			const cardW = parseFloat(this.cardStyle.width)
			// 箭头水平位置：对齐高亮元素中心，但不超出卡片范围
			const arrowHalf = 12
			const holeCenterX = rectLeft + rectW / 2
			let arrowLeft = holeCenterX - cardLeft - arrowHalf
			arrowLeft = Math.max(20, Math.min(arrowLeft, cardW - 44))
			return {
				left: arrowLeft + 'px'
			}
		}
	},
	watch: {
		visible: {
			immediate: false,
			handler(val) {
				if (val) {
					this.stepIndex = 0
					this.targetRect = null
					this.retryCount = 0
					this.$nextTick(() => {
						this.queryTargetRect()
					})
				} else {
					if (this.queryTimer) clearTimeout(this.queryTimer)
				}
			}
		},
		stepIndex() {
			this.targetRect = null
			this.retryCount = 0
			this.queryTargetRect()
		}
	},
	methods: {
		noop() {},
		// 点击遮罩区域（洞外）→ 进入下一步；点击高亮洞内 → 不响应（由 overlay 统一处理）
		onOverlayTap() {
			this.next()
		},
		queryTargetRect() {
			const step = this.currentStep
			if (!step.selector) {
				this.targetRect = null
				return
			}
			if (this.queryTimer) clearTimeout(this.queryTimer)
			// 首次查询用短延迟（50ms）快速拿到节点；失败再以 200ms 退避重试
			this.queryTimer = setTimeout(() => {
				this._doQuery(step.selector)
			}, this.retryCount === 0 ? 50 : 200)
		},
		_doQuery(selector) {
			const query = uni.createSelectorQuery()
			query.select(selector).boundingClientRect(rect => {
				if (rect && rect.width > 0 && rect.height > 0) {
					this.targetRect = rect
					this.retryCount = 0
				} else if (this.retryCount < this.maxRetries) {
					this.retryCount++
					this.queryTimer = setTimeout(() => this._doQuery(selector), 200)
				} else {
					this.targetRect = null
				}
			}).exec()
		},
		next() {
			if (this.stepIndex < this.steps.length - 1) {
				this.stepIndex++
			} else {
				this.close()
			}
		},
		close() {
			this.$emit('update:visible', false)
			this.$emit('close')
			if (this.queryTimer) clearTimeout(this.queryTimer)
			this.targetRect = null
			this.retryCount = 0
		}
	}
}
</script>

<style lang="scss" scoped>
.guide-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 9998;
}

.guide-mask {
	/* 样式由 JS 内联控制 */
}

.guide-highlight {
	animation: guidePulse 1.8s ease-in-out infinite;
}

@keyframes guidePulse {
	0%, 100% {
		transform: scale(1);
		opacity: 1;
	}
	50% {
		transform: scale(1.015);
		opacity: 0.85;
	}
}

.guide-card {
	background: linear-gradient(180deg, #ffffff 0%, #fbfbfb 100%);
	border-radius: 24rpx;
	padding: 0 0 28rpx;
	box-shadow: 0 16rpx 48rpx rgba(0, 0, 0, 0.22), 0 4rpx 12rpx rgba(0, 0, 0, 0.08);
	overflow: visible;
	position: relative;

	/* 顶部装饰条 */
	&__bar {
		height: 8rpx;
		padding: 0 32rpx;
		margin-bottom: 28rpx;
		margin-top: 0;

		&-inner {
			width: 64rpx;
			height: 8rpx;
			background: linear-gradient(90deg, #18AF38 0%, #4cd964 100%);
			border-radius: 4rpx;
			margin: 0 auto;
		}
	}

	&__body {
		padding: 0 36rpx;
	}

	&__title {
		display: flex;
		align-items: center;
		margin-bottom: 16rpx;

		&-text {
			font-size: 34rpx;
			font-weight: 600;
			color: #1a1a1a;
			line-height: 1.4;
		}
	}

	&__icon {
		font-size: 36rpx;
		margin-right: 12rpx;
		line-height: 1;
	}

	&__desc {
		font-size: 26rpx;
		color: #5e5e5e;
		line-height: 1.7;
		margin-bottom: 32rpx;
		letter-spacing: 0.5rpx;
	}

	&__footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 36rpx;
		border-top: 1rpx solid #f0f0f0;
		padding-top: 20rpx;
	}

	&__step {
		font-size: 26rpx;
		color: #999;
		letter-spacing: 1rpx;

		&-current {
			color: #18AF38;
			font-weight: 700;
			font-size: 30rpx;
		}

		&-total {
			color: #bbb;
		}
	}

	&__actions {
		display: flex;
		align-items: center;
	}

	&__skip {
		font-size: 26rpx;
		color: #999;
		padding: 14rpx 28rpx;
		border-radius: 36rpx;
		background: #f5f5f5;
		margin-right: 16rpx;
		transition: all 0.15s;

		&--hover {
			background: #efefef;
			color: #888;
		}
	}

	&__next {
		display: flex;
		align-items: center;
		font-size: 28rpx;
		color: #fff;
		background: linear-gradient(135deg, #18AF38 0%, #0d8a2b 100%);
		border-radius: 36rpx;
		padding: 14rpx 36rpx;
		font-weight: 600;
		box-shadow: 0 6rpx 16rpx rgba(24, 175, 56, 0.35);
		transition: all 0.15s;

		&-arrow {
			margin-left: 6rpx;
			font-size: 32rpx;
			line-height: 1;
		}

		&--hover {
			transform: scale(0.96);
			box-shadow: 0 4rpx 10rpx rgba(24, 175, 56, 0.3);
		}
	}

	/* 指向高亮元素的箭头：用 border 三角形实现 */
	&__arrow {
		position: absolute;
		width: 0;
		height: 0;
		border-left: 16rpx solid transparent;
		border-right: 16rpx solid transparent;
	}

	/* 箭头在卡片顶部、朝上指（卡片在洞下方时） */
	&__arrow--up {
		top: -16rpx;
		border-bottom: 16rpx solid #fff;
	}

	/* 箭头在卡片底部、朝下指（卡片在洞上方时） */
	&__arrow--down {
		bottom: -16rpx;
		border-top: 16rpx solid #fff;
	}
}
</style>
