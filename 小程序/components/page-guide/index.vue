<template>
	<view class="pg-root">
		<view
			v-if="!showGuide"
			class="pg-mascot"
			:class="{ 'pg-mascot--drag': dragging }"
			:style="mascotStyle"
			@touchstart.stop="onPetTouchStart"
			@touchmove.stop.prevent="onPetTouchMove"
			@touchend.stop="onPetTouchEnd"
			@touchcancel.stop="onPetTouchEnd"
		>
			<view class="pg-bubble" v-if="showBubble">
				<text class="pg-bubble-text">遇到困难了吗？可以点我~</text>
			</view>
			<view class="pg-pet">
				<view class="pg-ear pg-ear-l"></view>
				<view class="pg-ear pg-ear-r"></view>
				<view class="pg-face">
					<view class="pg-eye pg-eye-l"></view>
					<view class="pg-eye pg-eye-r"></view>
					<view class="pg-blush pg-blush-l"></view>
					<view class="pg-blush pg-blush-r"></view>
					<view class="pg-mouth"></view>
					<view class="pg-dot"></view>
				</view>
			</view>
		</view>

		<block v-if="guideOnPage && showGuide && guideHole">
			<view :style="guideDimCss(0, 0, guideWinW, guideHole.top)"></view>
			<view :style="guideDimCss(0, guideHole.bottom, guideWinW, guideHole.bottomRest)"></view>
			<view :style="guideDimCss(0, guideHole.top, guideHole.left, guideHole.height)"></view>
			<view :style="guideDimCss(guideHole.right, guideHole.top, guideHole.rightRest, guideHole.height)"></view>
			<view :style="guideSpotCss"></view>
		</block>
		<view
			v-else-if="guideOnPage && showGuide"
			class="pg-dim-full"
		></view>
		<view
			v-if="guideOnPage && showGuide"
			class="pg-guide-root"
			@touchmove.stop.prevent="noop"
		>
			<new-user-guide
				:steps="playingSteps"
				:target-rect="guideTargetRect"
				:card-top="guideCardTop"
				:place-below="guideCardPlaceBelow"
				:card-wrap-css="guideCardWrapCss"
				@close="onGuideClose"
				@query-target="onGuideQueryTarget"
			></new-user-guide>
		</view>

		<view id="pg-tabbar" class="pg-tabbar-area"></view>
	</view>
</template>

<script>
import NewUserGuide from '@/components/new-user-guide/new-user-guide.vue'
import { getPageGuide } from '@/common/pageGuides.js'

export default {
	name: 'page-guide',
	components: { NewUserGuide },
	options: {
		virtualHost: true,
		styleIsolation: 'apply-shared'
	},
	props: {
		page: { type: String, required: true }
	},
	data() {
		return {
			showBubble: true,
			showGuide: false,
			guideOnPage: true,
			playingSteps: [],
			guideTargetRect: null,
			stepPrefer: '',
			mascotRight: 0,
			mascotBottom: 0,
			posReady: false,
			dragging: false,
			_lock: false
		}
	},
	computed: {
		cfg() {
			return getPageGuide(this.page)
		},
		mascotStyle() {
			if (!this.posReady) return 'position:fixed;right:18px;bottom:120px;z-index:10050;pointer-events:auto;'
			return `position:fixed;right:${this.mascotRight}px;bottom:${this.mascotBottom}px;left:auto;top:auto;z-index:10050;pointer-events:auto;`
		},
		guideWinW() {
			return uni.getSystemInfoSync().windowWidth
		},
		guideHole() {
			const rect = this.guideTargetRect
			if (!rect) return null
			const sys = uni.getSystemInfoSync()
			const winW = sys.windowWidth
			const winH = sys.windowHeight
			const pad = 8
			const left = Math.max(0, rect.left - pad)
			const top = Math.max(0, rect.top - pad)
			const right = Math.min(winW, rect.left + rect.width + pad)
			const bottom = Math.min(winH, rect.top + rect.height + pad)
			const width = right - left
			const height = bottom - top
			if (width < 20 || height < 16) return null
			return {
				left, top, right, bottom, width, height,
				rightRest: Math.max(0, winW - right),
				bottomRest: Math.max(0, winH - bottom)
			}
		},
		guideSpotCss() {
			const h = this.guideHole
			if (!h) return ''
			return `position:fixed;left:${h.left}px;top:${h.top}px;width:${h.width}px;height:${h.height}px;border:3px solid #18AF38;border-radius:12px;box-sizing:border-box;background:transparent;z-index:999998;`
		},
		guideSafeTop() {
			const sys = uni.getSystemInfoSync()
			let top = (sys.statusBarHeight || 20) + 44 + 16
			try {
				const menu = uni.getMenuButtonBoundingClientRect()
				if (menu && menu.bottom) top = Math.max(top, Math.ceil(menu.bottom) + 16)
			} catch (e) {}
			return top
		},
		guideSafeBottom() {
			const sys = uni.getSystemInfoSync()
			const tabbar = 108 * sys.windowWidth / 750 + 8
			const inset = (sys.safeAreaInsets && sys.safeAreaInsets.bottom) || 0
			return Math.ceil(tabbar + inset + 20)
		},
		guideCardTop() {
			const sys = uni.getSystemInfoSync()
			const winH = sys.windowHeight
			const cardH = 230
			const gap = 18
			const minTop = this.guideSafeTop
			const maxTop = winH - this.guideSafeBottom - cardH
			const hole = this.guideHole
			if (!hole || maxTop < minTop) {
				return Math.max(minTop, Math.min(maxTop, Math.round((winH - cardH) / 2)))
			}
			const belowTop = hole.bottom + gap
			const aboveTop = hole.top - gap - cardH
			const belowOk = belowTop <= maxTop
			const aboveOk = aboveTop >= minTop
			const holeCenter = (hole.top + hole.bottom) / 2
			let preferBelow = holeCenter < winH * 0.58
			if (this.stepPrefer === 'below') preferBelow = true
			if (this.stepPrefer === 'above') preferBelow = false
			if (preferBelow && belowOk) return belowTop
			if (!preferBelow && aboveOk) return aboveTop
			if (belowOk) return belowTop
			if (aboveOk) return aboveTop
			return Math.max(minTop, Math.min(maxTop, preferBelow ? belowTop : aboveTop))
		},
		guideCardPlaceBelow() {
			const hole = this.guideHole
			if (!hole) return true
			return this.guideCardTop >= hole.bottom - 8
		},
		guideCardWrapCss() {
			return `position:absolute;left:40rpx;right:40rpx;top:${this.guideCardTop}px;z-index:100054;transform:none;`
		}
	},
	created() {
		this.applyMascotPos()
		this._onMascotMoved = () => this.applyMascotPos()
		uni.$on('help_mascot_moved', this._onMascotMoved)
		this.startBubbleLoop()
	},
	beforeDestroy() {
		this.clearBubbleLoop()
		if (this._scrollTimer) {
			clearTimeout(this._scrollTimer)
			this._scrollTimer = null
		}
		if (this._onMascotMoved) uni.$off('help_mascot_moved', this._onMascotMoved)
	},
	methods: {
		noop() {},
		rpxToPx(rpx) {
			const n = parseFloat(rpx)
			if (isNaN(n)) return 0
			return n * uni.getSystemInfoSync().windowWidth / 750
		},
		screenSize() {
			const sys = uni.getSystemInfoSync()
			return {
				w: sys.windowWidth || sys.screenWidth,
				h: sys.windowHeight || sys.screenHeight,
				inset: (sys.safeAreaInsets && sys.safeAreaInsets.bottom) || 0
			}
		},
		defaultRB() {
			const { inset } = this.screenSize()
			const rpx = uni.getSystemInfoSync().windowWidth / 750
			return {
				r: 18 * rpx,
				b: 108 * rpx + 4 * rpx + inset + 12 * rpx
			}
		},
		clampRB(r, b) {
			const { w, h } = this.screenSize()
			const pet = this.rpxToPx(88)
			const maxR = Math.max(4, w - pet - 4)
			const maxB = Math.max(4, h - pet - 4)
			return {
				r: Math.max(4, Math.min(r, maxR)),
				b: Math.max(4, Math.min(b, maxB))
			}
		},
		applyMascotPos() {
			const def = this.defaultRB()
			let r = def.r
			let b = def.b
			try {
				const p = uni.getStorageSync('help_mascot_rb')
				if (p && isFinite(Number(p.r)) && isFinite(Number(p.b))) {
					r = Number(p.r)
					b = Number(p.b)
				}
			} catch (e) {}
			const c = this.clampRB(r, b)
			this.mascotRight = c.r
			this.mascotBottom = c.b
			this.posReady = true
		},
		saveMascotPos() {
			try {
				uni.setStorageSync('help_mascot_rb', { r: this.mascotRight, b: this.mascotBottom })
				uni.$emit('help_mascot_moved')
			} catch (e) {}
		},
		clearBubbleLoop() {
			if (this._bubbleTimer) {
				clearTimeout(this._bubbleTimer)
				this._bubbleTimer = null
			}
		},
		startBubbleLoop() {
			this.clearBubbleLoop()
			if (this.showGuide) {
				this.showBubble = false
				return
			}
			this.showBubble = true
			const waitHide = () => {
				this._bubbleTimer = setTimeout(() => {
					if (this.showGuide) return
					this.showBubble = false
					this._bubbleTimer = setTimeout(() => {
						if (this.showGuide) return
						this.showBubble = true
						waitHide()
					}, 5000)
				}, 3000)
			}
			waitHide()
		},
		onPetTouchStart(e) {
			const t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0])
			if (!t) return
			this.dragging = true
			this._moved = false
			this._sx = t.clientX
			this._sy = t.clientY
			this._ox = this.mascotRight
			this._oy = this.mascotBottom
		},
		onPetTouchMove(e) {
			if (!this.dragging) return
			const t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0])
			if (!t) return
			const dx = t.clientX - this._sx
			const dy = t.clientY - this._sy
			if (Math.abs(dx) > 5 || Math.abs(dy) > 5) this._moved = true
			const next = this.clampRB(this._ox - dx, this._oy - dy)
			this.mascotRight = next.r
			this.mascotBottom = next.b
		},
		onPetTouchEnd() {
			if (!this.dragging) return
			this.dragging = false
			if (this._moved) {
				this.saveMascotPos()
				return
			}
			this.onMascotTap()
		},
		onMascotTap() {
			this.clearBubbleLoop()
			this.showBubble = false
			this.startGuide()
		},
		startGuide() {
			if (this._lock || this.showGuide) return
			const steps = (this.cfg.steps || []).slice()
			if (!steps.length) {
				uni.showToast({ title: '本页暂无教程', icon: 'none' })
				return
			}
			this.clearBubbleLoop()
			this.showBubble = false
			this._lock = true
			this.guideOnPage = true
			this.playingSteps = steps
			this.guideTargetRect = null
			this.stepPrefer = ''
			this._scrollSel = ''
			this.showGuide = true
		},
		pause() {
			this.guideOnPage = false
		},
		onGuideClose() {
			this._lock = false
			this.showGuide = false
			this.guideTargetRect = null
			this.stepPrefer = ''
			this._scrollSel = ''
			if (this._scrollTimer) {
				clearTimeout(this._scrollTimer)
				this._scrollTimer = null
			}
			this.startBubbleLoop()
		},
		getPageVm() {
			try {
				const pages = getCurrentPages()
				const p = pages[pages.length - 1]
				if (p && p.$vm) return p.$vm
			} catch (e) {}
			return this.$root || this
		},
		onGuideQueryTarget(selector) {
			if (!selector) {
				this.guideTargetRect = null
				this.stepPrefer = ''
				this._scrollSel = ''
				return
			}
			const steps = this.playingSteps || []
			let prefer = ''
			let isTabbar = selector === '#pg-tabbar' || selector === '#guide-tabbar'
			for (let i = 0; i < steps.length; i++) {
				if (steps[i].selector === selector) {
					prefer = steps[i].prefer || ''
					if (steps[i].type === 'tabbar') isTabbar = true
					break
				}
			}
			this.stepPrefer = prefer
			if (isTabbar) {
				this._scrollSel = ''
				this.guideTargetRect = this.getTabbarRect()
				return
			}
			const allowScroll = this._scrollSel !== selector
			if (allowScroll) this._scrollSel = selector
			this.queryTarget(selector, allowScroll)
		},
		queryTarget(selector, allowScroll) {
			this.$nextTick(() => {
				const vm = this.getPageVm()
				const q = uni.createSelectorQuery().in(vm)
				q.select(selector).boundingClientRect()
				q.selectViewport().scrollOffset()
				q.exec(res => {
					let rect = res && res[0]
					const offset = (res && res[1]) || {}
					const apply = (r) => {
						if (!r || r.width < 20 || r.height < 16) return
						if (allowScroll) {
							const delta = this.computeScrollDelta(r)
							if (Math.abs(delta) > 12) {
								const nextTop = Math.max(0, (offset.scrollTop || 0) + delta)
								uni.pageScrollTo({
									scrollTop: nextTop,
									duration: 280
								})
								if (this._scrollTimer) clearTimeout(this._scrollTimer)
								this._scrollTimer = setTimeout(() => {
									this._scrollTimer = null
									if (!this.showGuide) return
									this.queryTarget(selector, false)
								}, 320)
								return
							}
						}
						this.guideTargetRect = {
							left: r.left,
							top: r.top,
							width: r.width,
							height: r.height
						}
					}
					if (rect && rect.width > 20 && rect.height > 16) {
						apply(rect)
						return
					}
					uni.createSelectorQuery().select(selector).boundingClientRect(r => apply(r)).exec()
				})
			})
		},
		computeScrollDelta(rect) {
			const sys = uni.getSystemInfoSync()
			const winH = sys.windowHeight
			const safeTop = this.guideSafeTop
			const safeBottom = this.guideSafeBottom
			const pad = 8
			const gap = 18
			const cardH = 230
			const holeTop = rect.top - pad
			const holeH = rect.height + pad * 2
			const holeCenter = rect.top + rect.height / 2
			let preferAbove = holeCenter >= winH * 0.58
			if (this.stepPrefer === 'below') preferAbove = false
			if (this.stepPrefer === 'above') preferAbove = true
			let desiredTop
			if (preferAbove) {
				desiredTop = safeTop + cardH + gap
				const maxBottom = winH - safeBottom - 8
				if (desiredTop + holeH > maxBottom) {
					desiredTop = Math.max(safeTop + 8, maxBottom - Math.min(holeH, maxBottom - safeTop - 8))
				}
			} else {
				desiredTop = safeTop + 8
				const cardBottom = desiredTop + holeH + gap + cardH
				const limit = winH - safeBottom
				if (cardBottom > limit) {
					desiredTop = limit - cardH - gap - holeH
					if (desiredTop < safeTop + 8) desiredTop = safeTop + 8
				}
			}
			return holeTop - desiredTop
		},
		getTabbarRect() {
			const sys = uni.getSystemInfoSync()
			const rpx = sys.windowWidth / 750
			let inset = 0
			if (sys.safeAreaInsets && typeof sys.safeAreaInsets.bottom === 'number') {
				inset = sys.safeAreaInsets.bottom
			} else if (sys.safeArea && sys.screenHeight) {
				inset = Math.max(0, sys.screenHeight - sys.safeArea.bottom)
			}
			const height = 108 * rpx
			const left = 24 * rpx
			const width = sys.windowWidth - 48 * rpx
			const bottomGap = 4 * rpx + inset
			const winH = sys.windowHeight || sys.screenHeight
			const top = winH - bottomGap - height
			return { left, top, width, height }
		},
		guideDimCss(left, top, width, height) {
			const w = Math.max(0, width)
			const h = Math.max(0, height)
			return `position:fixed;left:${left}px;top:${top}px;width:${w}px;height:${h}px;background:rgba(0,0,0,0.72);z-index:999998;pointer-events:auto;`
		}
	}
}
</script>

<style lang="scss" scoped>
.pg-root {
	position: fixed;
	left: 0;
	top: 0;
	width: 100%;
	height: 100%;
	pointer-events: none;
	z-index: 10050;
}
.pg-mascot {
	position: fixed;
	right: 18px;
	bottom: 120px;
	z-index: 10050;
	pointer-events: auto;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
}
.pg-mascot--drag .pg-pet {
	animation: none;
}
.pg-bubble {
	max-width: 280rpx;
	margin: 0 8rpx 10rpx 0;
	padding: 12rpx 16rpx;
	background: #fff;
	border-radius: 16rpx 16rpx 4rpx 16rpx;
	box-shadow: 0 8rpx 24rpx rgba(16, 48, 24, 0.16);
}
.pg-bubble-text {
	display: block;
	font-size: 22rpx;
	color: #1c2b1e;
	line-height: 1.45;
}
.pg-pet {
	position: relative;
	width: 88rpx;
	height: 88rpx;
	animation: pgBreathe 2.4s ease-in-out infinite;
}
.pg-face {
	position: relative;
	width: 88rpx;
	height: 88rpx;
	border-radius: 50%;
	background: radial-gradient(circle at 38% 32%, #bbf7d0 0%, #4ade80 42%, #22c55e 100%);
	box-shadow: 0 8rpx 16rpx rgba(22, 101, 52, 0.28);
}
.pg-ear {
	position: absolute;
	top: -6rpx;
	width: 28rpx;
	height: 36rpx;
	border-radius: 50% 50% 40% 40%;
	background: #16a34a;
	z-index: 0;
}
.pg-ear-l {
	left: 8rpx;
	transform: rotate(-22deg);
}
.pg-ear-r {
	right: 8rpx;
	transform: rotate(22deg);
	background: #15803d;
}
.pg-eye {
	position: absolute;
	top: 32rpx;
	width: 10rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #14532d;
	z-index: 1;
}
.pg-eye-l { left: 24rpx; }
.pg-eye-r { right: 24rpx; }
.pg-blush {
	position: absolute;
	top: 44rpx;
	width: 12rpx;
	height: 8rpx;
	border-radius: 50%;
	background: rgba(251, 146, 60, 0.45);
	z-index: 1;
}
.pg-blush-l { left: 14rpx; }
.pg-blush-r { right: 14rpx; }
.pg-mouth {
	position: absolute;
	left: 50%;
	top: 50rpx;
	width: 22rpx;
	height: 10rpx;
	margin-left: -11rpx;
	border-bottom: 4rpx solid #14532d;
	border-radius: 0 0 12rpx 12rpx;
	z-index: 1;
}
.pg-dot {
	position: absolute;
	right: 8rpx;
	bottom: 16rpx;
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #fbbf24;
	z-index: 1;
}
@keyframes pgBreathe {
	0%, 100% { transform: scale(1) translateY(0); }
	50% { transform: scale(1.08) translateY(-6rpx); }
}
.pg-dim-full {
	position: fixed;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.72);
	z-index: 999998;
	pointer-events: auto;
}
.pg-guide-root {
	position: fixed;
	left: 0;
	top: 0;
	right: 0;
	bottom: 0;
	width: 100%;
	height: 100%;
	z-index: 999999;
	pointer-events: auto;
}
.pg-tabbar-area {
	position: fixed;
	left: 24rpx;
	right: 24rpx;
	bottom: calc(4rpx + constant(safe-area-inset-bottom));
	bottom: calc(4rpx + env(safe-area-inset-bottom));
	height: 108rpx;
	pointer-events: none;
	z-index: 1;
}
</style>
