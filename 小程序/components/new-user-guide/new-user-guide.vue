<template>
	<view class="guide-overlay" v-if="visible" @touchmove.stop.prevent>
		<!-- 高亮挖洞：通过内联 clip-path 实现 -->
		<view class="guide-mask" :style="maskStyle"></view>

		<!-- 引导描述卡 -->
		<view class="guide-card" :style="cardStyle">
			<view class="guide-card__title">{{ currentStep.title }}</view>
			<view class="guide-card__desc">{{ currentStep.desc }}</view>
			<view class="guide-card__footer">
				<view class="guide-card__step">{{ stepIndex + 1 }} / {{ steps.length }}</view>
				<view class="guide-card__actions">
					<view class="guide-card__skip" @click="close">跳过</view>
					<view class="guide-card__next" @click="next">
						{{ stepIndex === steps.length - 1 ? '开启体验' : '下一步' }}
					</view>
				</view>
			</view>
		</view>

		<!-- 高亮边框动画 -->
		<view class="guide-highlight" :style="highlightStyle" v-if="currentStep.selector"></view>
	</view>
</template>

<script>
/**
 * 新手引导组件
 * 
 * 使用方式：
 * 1. 模板中引用：<new-user-guide ref="guide" :steps="guideSteps" :visible.sync="showGuide" />
 * 2. 步骤配置示例（selector 为空时高亮屏幕中央空白，介绍整体功能）：
 *  guideSteps: [
 *    { selector: '.hello-card', title: '问候与快捷打卡', desc: '这里是您的个人问候卡片，可以一键完成垃圾分类打卡，获取环保积分。' },
 *    { selector: '.home-search-wrap', title: '搜索活动', desc: '想参与社区活动？点击这里搜索感兴趣的活动名称或地点。' },
 *    { selector: '.entry-item', title: '核心功能', desc: '四大核心入口：社区活动、健康测试、积分商城、社区互动，满足您的日常需求。' },
 *  ]
 */

// 全局缓存节点位置，避免每次切换都查询
let cachedRect = null;

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
		// 遮罩内边距（高亮区域向外扩展的像素）
		padding: {
			type: Number,
			default: 12
		}
	},
	data() {
		return {
			stepIndex: 0,
			targetRect: null,
			// 用于强制重试获取节点位置
			retryCount: 0,
			maxRetries: 15,
			queryTimer: null
		}
	},
	computed: {
		currentStep() {
			return this.steps[this.stepIndex] || { title: '', desc: '', selector: '' }
		},
		// 遮罩样式（全屏）
		maskStyle() {
			return {
				position: 'fixed',
				top: 0,
				left: 0,
				right: 0,
				bottom: 0,
				zIndex: 9998,
				background: 'rgba(0, 0, 0, 0.75)',
				...(this.maskClipStyle || '')
			}
		},
		// 高亮挖洞的 clip-path 样式
		maskClipStyle() {
			if (!this.targetRect) return {}
			const { left, top, width, height } = this.targetRect
			const pad = this.padding
			// 挖一个椭圆洞（inset 反向用法）
			const holeTop = (top - pad) + 'px'
			const holeLeft = (left - pad) + 'px'
			const holeRight = Math.max(0, (uni.getSystemInfoSync().windowWidth - left - width - pad)) + 'px'
			const holeBottom = Math.max(0, (uni.getSystemInfoSync().windowHeight - top - height - pad)) + 'px'
			return {
				clipPath: `inset(${holeTop} ${holeRight} ${holeBottom} ${holeLeft})`
			}
		},
		// 高亮边框样式（额外视觉强调）
		highlightStyle() {
			if (!this.targetRect) return {}
			const { left, top, width, height } = this.targetRect
			const pad = this.padding
			return {
				position: 'fixed',
				top: (top - pad) + 'px',
				left: (left - pad) + 'px',
				width: (width + pad * 2) + 'px',
				height: (height + pad * 2) + 'px',
				zIndex: 9999,
				border: '2px solid #18AF38',
				borderRadius: '16px',
				pointerEvents: 'none',
				boxShadow: '0 0 0 9999px transparent'
			}
		},
		// 描述卡样式：固定在屏幕中间偏上
		cardStyle() {
			const sysInfo = uni.getSystemInfoSync()
			const cardWidth = sysInfo.windowWidth - 60
			// 固定在屏幕中间偏上（距离顶部约 35%）
			return {
				position: 'fixed',
				left: '30px',
				top: Math.floor(sysInfo.windowHeight * 0.35) + 'px',
				width: cardWidth + 'px',
				zIndex: 10000,
			}
		}
	},
	watchers: {
		visible(val) {
			if (val) {
				this.stepIndex = 0
				this.$nextTick(() => {
					this.queryTargetRect()
				})
			}
		},
		stepIndex() {
			this.queryTargetRect()
		}
	},
	methods: {
		// 查询目标节点位置
		queryTargetRect() {
			const step = this.currentStep
			if (!step.selector) {
				this.targetRect = null
				return
			}
			// 延迟等待 DOM 渲染完成
			if (this.queryTimer) clearTimeout(this.queryTimer)
			this.queryTimer = setTimeout(() => {
				this._doQuery(step.selector)
			}, 350)
		},
		_doQuery(selector) {
			// 微信小程序：直接 query 整个页面（不 .in 限定），因为高亮元素可能在组件外部
			const query = uni.createSelectorQuery()
			query.select(selector).boundingClientRect(rect => {
				if (rect && rect.width > 0 && rect.height > 0) {
					this.targetRect = rect
					this.retryCount = 0
					cachedRect = { selector, ...rect }
				} else if (this.retryCount < this.maxRetries) {
					this.retryCount++
					this.queryTimer = setTimeout(() => this._doQuery(selector), 200)
				} else {
					// 节点获取失败，跳过该步骤的高亮
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
			cachedRect = null
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
	transition: clip-path 0.3s ease;
}

.guide-card {
	background: #FFFFFF;
	border-radius: 24rpx;
	padding: 40rpx 36rpx 32rpx;
	box-shadow: 0 20rpx 60rpx rgba(0, 0, 0, 0.15);

	&__title {
		font-size: 36rpx;
		font-weight: bold;
		color: #1a1a1a;
		margin-bottom: 16rpx;
		line-height: 1.4;
	}

	&__desc {
		font-size: 28rpx;
		color: #666;
		line-height: 1.7;
		margin-bottom: 32rpx;
	}

	&__footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	&__step {
		font-size: 26rpx;
		color: #999;
	}

	&__actions {
		display: flex;
		align-items: center;
		gap: 20rpx;
	}

	&__skip {
		font-size: 28rpx;
		color: #999;
		padding: 12rpx 24rpx;
	}

	&__next {
		font-size: 28rpx;
		color: #fff;
		background: linear-gradient(135deg, #18AF38 0%, #0d8a2b 100%);
		border-radius: 32rpx;
		padding: 16rpx 40rpx;
		font-weight: bold;
	}
}

.guide-highlight {
	animation: guidePulse 1.5s ease-in-out infinite;
}

@keyframes guidePulse {
	0%, 100% {
		transform: scale(1);
		opacity: 1;
	}
	50% {
		transform: scale(1.02);
		opacity: 0.85;
	}
}
</style>
