<template>
	<view class="guide-overlay" :style="overlayStyle" @touchmove.stop.prevent="noop" @tap="onOverlayTap">
		<view class="guide-card-wrap" :class="placeBelow ? 'guide-card-wrap--below' : 'guide-card-wrap--above'" :style="cardWrapCss || cardWrapStyle">
			<view class="guide-card" @tap.stop="noop">
				<view class="guide-card__head">
					<view class="guide-card__badge">
						<text class="guide-card__badge-num">{{ stepIndex + 1 }}</text>
					</view>
					<view class="guide-card__head-main">
						<text class="guide-card__title">{{ currentStep.title }}</text>
						<view class="guide-card__dots">
							<view
								v-for="(item, idx) in steps"
								:key="idx"
								class="guide-card__dot"
								:class="{ 'guide-card__dot--on': idx === stepIndex }"
							></view>
						</view>
					</view>
				</view>
				<text class="guide-card__desc">{{ currentStep.desc }}</text>
				<view class="guide-card__footer">
					<view class="guide-card__skip" @tap.stop="close" hover-class="guide-card__skip--on">先跳过</view>
					<view class="guide-card__next" @tap.stop="next" hover-class="guide-card__next--on">
						{{ stepIndex === steps.length - 1 ? '开始使用' : '下一步' }}
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'new-user-guide',
	options: {
		virtualHost: true,
		styleIsolation: 'apply-shared'
	},
	props: {
		steps: { type: Array, default: () => [] },
		padding: { type: Number, default: 8 },
		targetRect: { type: Object, default: null },
		cardTop: { type: Number, default: 0 },
		placeBelow: { type: Boolean, default: true },
		cardWrapCss: { type: String, default: '' }
	},
	data() {
		return {
			stepIndex: 0,
			retryCount: 0,
			maxRetries: 16,
			queryTimer: null,
			stepReadyAt: 0
		}
	},
	computed: {
		currentStep() {
			return this.steps[this.stepIndex] || { title: '', desc: '', selector: '' }
		},
		sysInfo() {
			return uni.getSystemInfoSync()
		},
		hole() {
			const rect = this.targetRect
			if (!rect) return null
			const winW = this.sysInfo.windowWidth
			const winH = this.sysInfo.windowHeight
			const pad = this.padding
			const l = Math.max(0, rect.left - pad)
			const t = Math.max(0, rect.top - pad)
			const r = Math.min(winW, rect.left + rect.width + pad)
			const b = Math.min(winH, rect.top + rect.height + pad)
			const width = r - l
			const height = b - t
			if (width < 20 || height < 16) return null
			if (height > winH * 0.5) return null
			return {
				left: l,
				top: t,
				right: r,
				bottom: b,
				width,
				height,
				rightRest: Math.max(0, winW - r),
				bottomRest: Math.max(0, winH - b)
			}
		},
		highlightStyle() {
			const h = this.hole
			if (!h) return {}
			return this.boxStyle(h.left, h.top, h.width, h.height)
		},
		overlayStyle() {
			return {
				position: 'absolute',
				left: '0px',
				top: '0px',
				width: '100%',
				height: '100%',
				zIndex: 100053,
				backgroundColor: 'transparent'
			}
		},
		cardWrapStyle() {
			const sys = this.sysInfo
			let minTop = (sys.statusBarHeight || 20) + 60
			try {
				const menu = uni.getMenuButtonBoundingClientRect()
				if (menu && menu.bottom) minTop = Math.max(minTop, Math.ceil(menu.bottom) + 16)
			} catch (e) {}
			const top = this.cardTop > minTop ? this.cardTop : Math.max(minTop, Math.round(sys.windowHeight * 0.42))
			return `top:${top}px;left:40rpx;right:40rpx;position:absolute;transform:none;`
		}
	},
	watch: {
		stepIndex() {
			this.retryCount = 0
			this.stepReadyAt = Date.now()
			this.queryTargetRect()
		},
		targetRect(val) {
			if (val && val.width > 20) this.clearTimers()
		},
	},
	mounted() {
		this.stepReadyAt = Date.now()
		this.$nextTick(() => this.queryTargetRect())
	},
	beforeDestroy() {
		this.clearTimers()
	},
	methods: {
		noop() {},
		boxStyle(left, top, width, height) {
			const w = typeof width === 'number' ? width + 'px' : width
			const h = typeof height === 'number' ? height + 'px' : height
			return {
				position: 'absolute',
				left: left + 'px',
				top: top + 'px',
				width: w,
				height: h
			}
		},
		resetQuery() {
			this.retryCount = 0
			this.stepReadyAt = Date.now()
		},
		clearTimers() {
			if (this.queryTimer) {
				clearTimeout(this.queryTimer)
				this.queryTimer = null
			}
		},
		onOverlayTap() {
			if (Date.now() - this.stepReadyAt < 280) return
			this.next()
		},
		queryTargetRect() {
			const selector = this.currentStep.selector
			if (!selector) {
				this.$emit('query-target', '')
				return
			}
			this.clearTimers()
			this.retryCount = 0
			this.queryTimer = setTimeout(() => this._doQuery(selector), 80)
		},
		_doQuery(selector) {
			this.$emit('query-target', selector)
			this.retryCount++
			if (this.retryCount <= this.maxRetries) {
				this.queryTimer = setTimeout(() => this._doQuery(selector), 200)
			}
		},
		next() {
			if (this.stepIndex < this.steps.length - 1) this.stepIndex++
			else this.close()
		},
		close() {
			this.$emit('close')
			this.clearTimers()
		}
	}
}
</script>

<style lang="scss">
.guide-overlay {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	width: 100%;
	height: 100%;
	z-index: 100053;
	background-color: transparent;
}

.guide-mask {
	background: rgba(8, 22, 14, 0.62);
	z-index: 1;
}

.guide-mask--full {
	position: absolute;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	width: 100%;
	height: 100%;
}

.guide-highlight {
	z-index: 2;
	border: 4rpx solid #ffffff;
	border-radius: 24rpx;
	box-sizing: border-box;
	box-shadow: 0 0 0 6rpx #18af38, 0 0 24rpx rgba(24, 175, 56, 0.55);
	pointer-events: none;
}

.guide-hole-block {
	z-index: 3;
	background: transparent;
}

.guide-card-wrap {
	position: absolute;
	left: 40rpx;
	right: 40rpx;
	z-index: 100054;
	pointer-events: none;

	&--below .guide-card::after {
		content: '';
		position: absolute;
		left: 56rpx;
		top: -16rpx;
		width: 0;
		height: 0;
		border-left: 16rpx solid transparent;
		border-right: 16rpx solid transparent;
		border-bottom: 18rpx solid #ffffff;
	}

	&--above .guide-card::after {
		content: '';
		position: absolute;
		left: 56rpx;
		bottom: -16rpx;
		width: 0;
		height: 0;
		border-left: 16rpx solid transparent;
		border-right: 16rpx solid transparent;
		border-top: 18rpx solid #ffffff;
	}
}

.guide-card {
	pointer-events: auto;
	background: #ffffff;
	border-radius: 32rpx;
	padding: 28rpx 28rpx 24rpx;
	box-shadow: 0 20rpx 60rpx rgba(16, 64, 28, 0.28);
	position: relative;
	overflow: visible;

	&__head {
		display: flex;
		align-items: center;
		margin-bottom: 16rpx;
	}

	&__badge {
		width: 64rpx;
		height: 64rpx;
		border-radius: 20rpx;
		background: linear-gradient(180deg, #2ad24e 0%, #18af38 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		margin-right: 18rpx;
		flex-shrink: 0;
	}

	&__badge-num {
		color: #ffffff;
		font-size: 32rpx;
		font-weight: 700;
		line-height: 1;
	}

	&__head-main {
		flex: 1;
		min-width: 0;
	}

	&__title {
		display: block;
		font-size: 36rpx;
		font-weight: 700;
		color: #142018;
		line-height: 1.3;
	}

	&__dots {
		display: flex;
		align-items: center;
		margin-top: 12rpx;
	}

	&__dot {
		width: 12rpx;
		height: 12rpx;
		border-radius: 12rpx;
		background: #d8e8d9;
		margin-right: 10rpx;

		&--on {
			width: 28rpx;
			background: #18af38;
		}
	}

	&__desc {
		display: block;
		font-size: 30rpx;
		color: #3d4a40;
		line-height: 1.65;
	}

	&__footer {
		display: flex;
		align-items: center;
		margin-top: 22rpx;
	}

	&__skip {
		flex: 1;
		height: 80rpx;
		line-height: 80rpx;
		text-align: center;
		font-size: 30rpx;
		color: #6b756e;
		background: #f3f6f3;
		border-radius: 40rpx;
		margin-right: 16rpx;
	}

	&__skip--on {
		background: #e7ece7;
	}

	&__next {
		flex: 1.35;
		height: 80rpx;
		line-height: 80rpx;
		text-align: center;
		font-size: 32rpx;
		font-weight: 700;
		color: #ffffff;
		background: linear-gradient(135deg, #2ad24e 0%, #129a32 100%);
		border-radius: 40rpx;
	}

	&__next--on {
		transform: scale(0.98);
	}
}
</style>
