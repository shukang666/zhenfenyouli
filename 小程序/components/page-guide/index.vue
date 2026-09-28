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
				<view class="pg-body">
					<view class="pg-bud"></view>
					<view class="pg-hand pg-hand-l">
						<view class="pg-leaf">
							<view class="pg-leaf-vein"></view>
						</view>
						<view class="pg-palm">
							<view class="pg-finger"></view>
							<view class="pg-finger pg-finger-2"></view>
						</view>
					</view>
					<view class="pg-hand pg-hand-r">
						<view class="pg-sprout">
							<view class="pg-sprout-stem"></view>
							<view class="pg-sprout-leaf pg-sprout-leaf-l"></view>
							<view class="pg-sprout-leaf pg-sprout-leaf-r"></view>
						</view>
						<view class="pg-palm pg-palm-r">
							<view class="pg-finger"></view>
							<view class="pg-finger pg-finger-2"></view>
						</view>
					</view>
					<view class="pg-eye pg-eye-l"></view>
					<view class="pg-eye pg-eye-r"></view>
					<view class="pg-blush pg-blush-l"></view>
					<view class="pg-blush pg-blush-r"></view>
					<view class="pg-mouth"></view>
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
			const tabbar = (108 + 16) * sys.windowWidth / 750
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
				b: 108 * rpx + 16 * rpx + inset + 12 * rpx
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
			if (
				Math.abs(next.r - this.mascotRight) < 2 &&
				Math.abs(next.b - this.mascotBottom) < 2
			) {
				return
			}
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
			const bottomGap = 16 * rpx + inset
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
	width: 112rpx;
	height: 112rpx;
}
.pg-body {
	position: relative;
	width: 112rpx;
	height: 112rpx;
	border-radius: 50%;
	background: radial-gradient(circle at 42% 32%, #d4f5b8 0%, #8ee06a 38%, #5ec948 72%, #45b838 100%);
	box-shadow: 0 8rpx 18rpx rgba(46, 125, 50, 0.32);
	overflow: visible;
}
.pg-hand {
	position: absolute;
	z-index: 4;
}
.pg-hand-l {
	left: -12rpx;
	bottom: 24rpx;
	width: 50rpx;
	height: 58rpx;
}
.pg-hand-r {
	right: -10rpx;
	bottom: 26rpx;
	width: 42rpx;
	height: 54rpx;
}
.pg-palm {
	position: absolute;
	width: 22rpx;
	height: 22rpx;
	border-radius: 50%;
	background: radial-gradient(circle at 38% 32%, #a8e878 0%, #5cb848 55%, #3d9a38 100%);
	border: 2rpx solid rgba(45, 110, 50, 0.45);
	box-shadow: 0 2rpx 6rpx rgba(34, 100, 40, 0.25);
	z-index: 5;
}
.pg-hand-l .pg-palm {
	right: 0;
	bottom: 8rpx;
}
.pg-palm-r {
	left: 0;
	bottom: 6rpx;
}
.pg-finger {
	position: absolute;
	width: 8rpx;
	height: 8rpx;
	border-radius: 50%;
	background: #5cb848;
	border: 1rpx solid rgba(45, 110, 50, 0.35);
	top: -2rpx;
	left: 2rpx;
}
.pg-finger-2 {
	left: auto;
	right: 0;
	top: 0;
}
.pg-bud {
	position: absolute;
	top: 2rpx;
	right: 22rpx;
	width: 18rpx;
	height: 18rpx;
	border-radius: 50%;
	background: radial-gradient(circle at 35% 30%, #ffd080 0%, #f5a623 100%);
	box-shadow: 0 2rpx 4rpx rgba(180, 100, 20, 0.25);
	z-index: 3;
}
.pg-leaf {
	position: absolute;
	left: -2rpx;
	bottom: 10rpx;
	width: 36rpx;
	height: 48rpx;
	border-radius: 0 80% 0 80%;
	background: linear-gradient(145deg, #8ee06a 0%, #3d9e3a 100%);
	transform: rotate(-20deg);
	transform-origin: 85% 88%;
	box-shadow: 0 2rpx 6rpx rgba(34, 100, 40, 0.15);
	z-index: 3;
}
.pg-leaf-vein {
	position: absolute;
	left: 50%;
	top: 12%;
	width: 2rpx;
	height: 72%;
	margin-left: -1rpx;
	background: rgba(34, 100, 40, 0.35);
	border-radius: 2rpx;
}
.pg-sprout {
	position: absolute;
	right: 2rpx;
	bottom: 14rpx;
	width: 28rpx;
	height: 38rpx;
	z-index: 3;
}
.pg-sprout-stem {
	position: absolute;
	left: 10rpx;
	bottom: 0;
	width: 4rpx;
	height: 24rpx;
	border-radius: 4rpx;
	background: #3d8f3a;
	transform: rotate(8deg);
}
.pg-sprout-leaf {
	position: absolute;
	width: 14rpx;
	height: 10rpx;
	border-radius: 0 100% 0 100%;
	background: #6fcf4a;
}
.pg-sprout-leaf-l {
	left: 2rpx;
	top: 0;
	transform: rotate(-35deg);
}
.pg-sprout-leaf-r {
	right: 0;
	top: 2rpx;
	transform: rotate(25deg);
	background: #5cb85c;
}
.pg-eye {
	position: absolute;
	top: 42rpx;
	width: 12rpx;
	height: 12rpx;
	border-radius: 50%;
	background: #1a1a1a;
	z-index: 5;
}
.pg-eye-l { left: 30rpx; }
.pg-eye-r { right: 30rpx; }
.pg-blush {
	position: absolute;
	top: 54rpx;
	width: 14rpx;
	height: 9rpx;
	border-radius: 50%;
	background: rgba(255, 160, 160, 0.55);
	z-index: 5;
}
.pg-blush-l { left: 18rpx; }
.pg-blush-r { right: 18rpx; }
.pg-mouth {
	position: absolute;
	left: 50%;
	top: 60rpx;
	width: 18rpx;
	height: 8rpx;
	margin-left: -9rpx;
	border-bottom: 3rpx solid #2d5a2a;
	border-radius: 0 0 10rpx 10rpx;
	z-index: 5;
}
@keyframes pgBreathe {
	0%, 100% { transform: scale(1) translateY(0); }
	50% { transform: scale(1.05) translateY(-5rpx); }
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
	bottom: calc(16rpx + constant(safe-area-inset-bottom));
	bottom: calc(16rpx + env(safe-area-inset-bottom));
	height: 108rpx;
	pointer-events: none;
	z-index: 1;
}
</style>
