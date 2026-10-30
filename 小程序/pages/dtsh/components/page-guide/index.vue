<template>
	<view class="pg-root">
		<view
			v-if="!showGuide && !cfg.hideMascot"
			class="pg-mascot"
			:class="{
				'pg-mascot--drag': dragging,
				'pg-mascot--left': chipDockLeft,
				'pg-mascot--snap': snapAnim
			}"
			:style="mascotStyle"
			@touchstart.stop="onPetTouchStart"
			@touchmove.stop.prevent="onPetTouchMove"
			@touchend.stop="onPetTouchEnd"
			@touchcancel.stop="onPetTouchEnd"
		>
			<view class="pg-chip">
				<view class="pg-chip-icon">
					<view class="pg-chip-dot"></view>
					<view class="pg-chip-dot pg-chip-dot--alt"></view>
				</view>
				<text class="pg-chip-text">小助手</text>
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
			showGuide: false,
			guideOnPage: true,
			playingSteps: [],
			guideTargetRect: null,
			stepPrefer: '',
			mascotSide: 'right',
			mascotRight: 0,
			mascotBottom: 0,
			posReady: false,
			dragging: false,
			snapAnim: false,
			_lock: false
		}
	},
	computed: {
		cfg() {
			return getPageGuide(this.page)
		},
		chipDockLeft() {
			if (this.dragging) return this.sideFromRightOffset(this.mascotRight) === 'left'
			return this.mascotSide === 'left'
		},
		mascotStyle() {
			const z = 'z-index:10050;pointer-events:auto;'
			if (!this.posReady) {
				return `position:fixed;right:0;bottom:120px;left:auto;top:auto;${z}`
			}
			const b = this.mascotBottom
			if (this.dragging) {
				return `position:fixed;right:${this.mascotRight}px;bottom:${b}px;left:auto;top:auto;${z}`
			}
			if (this.mascotSide === 'left') {
				return `position:fixed;left:0;bottom:${b}px;right:auto;top:auto;${z}`
			}
			return `position:fixed;right:0;bottom:${b}px;left:auto;top:auto;${z}`
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
			return `position:fixed;left:${h.left}px;top:${h.top}px;width:${h.width}px;height:${h.height}px;border:3px solid #18AF38;border-radius:12px;box-sizing:border-box;background:transparent;z-index:999998;pointer-events:none;`
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
	},
	beforeDestroy() {
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
			const { inset, h } = this.screenSize()
			const rpx = uni.getSystemInfoSync().windowWidth / 750
			const chipH = this.rpxToPx(72)
			const bMid = h * 0.46 - chipH / 2
			return {
				r: 0,
				b: Math.max(108 * rpx + inset + 12 * rpx, bMid)
			}
		},
		chipMetrics() {
			const chipW = this.rpxToPx(168)
			const chipH = this.rpxToPx(72)
			return { chipW, chipH }
		},
		maxMascotRight() {
			const { w } = this.screenSize()
			const { chipW } = this.chipMetrics()
			return Math.max(0, w - chipW)
		},
		clampBottom(b) {
			const { h } = this.screenSize()
			const { chipH } = this.chipMetrics()
			const maxB = Math.max(4, h - chipH - 4)
			return Math.max(4, Math.min(b, maxB))
		},
		clampRB(r, b) {
			const maxR = this.maxMascotRight()
			return {
				r: Math.max(0, Math.min(r, maxR)),
				b: this.clampBottom(b)
			}
		},
		sideFromRightOffset(r) {
			const { w } = this.screenSize()
			const { chipW } = this.chipMetrics()
			const centerX = w - chipW / 2 - r
			return centerX < w / 2 ? 'left' : 'right'
		},
		rightOffsetForSide(side) {
			return side === 'left' ? this.maxMascotRight() : 0
		},
		applyMascotPos() {
			const def = this.defaultRB()
			let side = 'right'
			let b = def.b
			try {
				const p = uni.getStorageSync('help_mascot_rb')
				if (p && (p.side === 'left' || p.side === 'right') && isFinite(Number(p.b))) {
					side = p.side
					b = Number(p.b)
				} else if (p && isFinite(Number(p.r)) && isFinite(Number(p.b))) {
					b = Number(p.b)
					side = this.sideFromRightOffset(Number(p.r))
				}
			} catch (e) {}
			this.mascotSide = side
			this.mascotBottom = this.clampBottom(b)
			this.mascotRight = this.rightOffsetForSide(side)
			this.posReady = true
		},
		saveMascotPos() {
			try {
				uni.setStorageSync('help_mascot_rb', {
					side: this.mascotSide,
					b: this.mascotBottom
				})
				uni.$emit('help_mascot_moved')
			} catch (e) {}
		},
		snapMascotToEdge() {
			const side = this.sideFromRightOffset(this.mascotRight)
			this.mascotSide = side
			this.mascotBottom = this.clampBottom(this.mascotBottom)
			this.mascotRight = this.rightOffsetForSide(side)
		},
		onPetTouchStart(e) {
			const t = (e.touches && e.touches[0]) || (e.changedTouches && e.changedTouches[0])
			if (!t) return
			this.dragging = true
			this._moved = false
			this._sx = t.clientX
			this._sy = t.clientY
			this._ox = this.rightOffsetForSide(this.mascotSide)
			this._oy = this.mascotBottom
			this.mascotRight = this._ox
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
			const moved = this._moved
			this.dragging = false
			if (moved) {
				this.snapAnim = true
				this.snapMascotToEdge()
				this.saveMascotPos()
				setTimeout(() => {
					this.snapAnim = false
				}, 280)
				return
			}
			this.onMascotTap()
		},
		onMascotTap() {
			this.startGuide()
		},
		startGuide() {
			if (this._lock || this.showGuide) return
			const steps = (this.cfg.steps || []).slice()
			if (!steps.length) {
				uni.showToast({ title: '本页暂无教程', icon: 'none' })
				return
			}
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
	right: 0;
	bottom: 120px;
	z-index: 10050;
	pointer-events: auto;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
}
.pg-mascot--left {
	align-items: flex-start;
}
.pg-mascot--snap {
	transition: left 0.26s ease-out, right 0.26s ease-out, bottom 0.26s ease-out;
}
.pg-mascot--drag .pg-chip {
	opacity: 0.88;
}
.pg-mascot--left .pg-chip {
	border-radius: 0 999rpx 999rpx 0;
	box-shadow: 6rpx 6rpx 24rpx rgba(16, 48, 24, 0.12);
	border: 1rpx solid rgba(24, 175, 56, 0.14);
	border-left: none;
	border-right: 1rpx solid rgba(24, 175, 56, 0.14);
}
.pg-chip {
	display: flex;
	flex-direction: row;
	align-items: center;
	padding: 14rpx 22rpx 14rpx 20rpx;
	background: rgba(255, 255, 255, 0.98);
	border-radius: 999rpx 0 0 999rpx;
	box-shadow: -6rpx 6rpx 24rpx rgba(16, 48, 24, 0.12);
	border: 1rpx solid rgba(24, 175, 56, 0.14);
	border-right: none;
}
.pg-chip-icon {
	display: flex;
	flex-direction: row;
	align-items: center;
	margin-right: 10rpx;
}
.pg-chip-dot {
	width: 10rpx;
	height: 10rpx;
	border-radius: 50%;
	background: #18af38;
}
.pg-chip-dot--alt {
	margin-left: 6rpx;
	background: #3974f2;
	opacity: 0.9;
}
.pg-chip-text {
	font-size: 26rpx;
	font-weight: 600;
	color: #18af38;
	letter-spacing: 2rpx;
	line-height: 1.2;
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
	pointer-events: none;
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
