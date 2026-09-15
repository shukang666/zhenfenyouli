// 关怀版下拉手势 Mixin
// 仅在页面处于顶部时，下拉才触发进入关怀版
export default {
	data() {
		return {
			// 下拉手势相关
			careGesture: {
				startY: 0,
				startTime: 0,
				pullDistance: 0,
				isInTop: true,
				triggerHeight: 120, // 触发距离(px)
				currentPage: null
			},
			// 下拉提示显示状态
			showCareHint: false,
			careHintOpacity: 0,
			carePullHeight: 0
		}
	},
	onLoad() {
		// 始终包装 onPageScroll 以确保检测页面滚动位置
		const originalOnPageScroll = this.$options.onPageScroll
		this.$options.onPageScroll = (e) => {
			if (originalOnPageScroll) {
				originalOnPageScroll.call(this, e)
			}
			this.checkScrollTop(e)
		}
		console.log('[care-gesture] onLoad - onPageScroll 已挂载')
	},
	onShow() {
		// 每次页面显示时重置为顶部状态
		this.careGesture.isInTop = true
		console.log('[care-gesture] onShow - isInTop 重置为 true')
	},
	methods: {
		// 检查是否在页面顶部
		checkScrollTop(e) {
			if (e && typeof e.scrollTop !== 'undefined') {
				const wasInTop = this.careGesture.isInTop
				this.careGesture.isInTop = e.scrollTop <= 5
				console.log('[care-gesture] scroll - scrollTop:', e.scrollTop, 'isInTop:', this.careGesture.isInTop)
			} else {
				console.log('[care-gesture] scroll - 事件无 scrollTop:', JSON.stringify(e))
			}
		},

		// 触摸开始
		onCareTouchStart(e) {
			console.log('[care-gesture] touchstart - isInTop:', this.careGesture.isInTop)
			if (!this.careGesture.isInTop) {
				console.log('[care-gesture] touchstart - 不在顶部，忽略')
				return
			}
			this.careGesture.startY = e.touches[0].clientY
			this.careGesture.startTime = Date.now()
			console.log('[care-gesture] touchstart - 记录起点Y:', this.careGesture.startY)
		},

		// 触摸移动
		onCareTouchMove(e) {
			if (!this.careGesture.isInTop) return
			
			const currentY = e.touches[0].clientY
			const diff = currentY - this.careGesture.startY
			
			// 只在下拉时触发
			if (diff > 10) {
				this.careGesture.pullDistance = diff
				this.carePullHeight = Math.min(diff * 0.5, 200)
				this.careHintOpacity = Math.min(this.carePullHeight / 100, 1)
				this.showCareHint = true
				console.log('[care-gesture] touchmove - 下拉距离:', diff.toFixed(0), 'carePullHeight:', this.carePullHeight.toFixed(0), 'opacity:', this.careHintOpacity.toFixed(2))
			} else {
				this.resetCarePull()
			}
		},

		// 触摸结束
		onCareTouchEnd(e) {
			if (!this.showCareHint) return
			
			// 快速滑动或下拉超过阈值
			const endTime = Date.now()
			const duration = endTime - this.careGesture.startTime
			const isQuickSwipe = duration < 300 && this.careGesture.pullDistance > 50
			const isLongPull = this.careGesture.pullDistance > this.careGesture.triggerHeight ||
							   this.carePullHeight > 100

			console.log('[care-gesture] touchend - pullDistance:', this.careGesture.pullDistance.toFixed(0), 'duration:', duration, 'isQuickSwipe:', isQuickSwipe, 'isLongPull:', isLongPull)

			if (isQuickSwipe || isLongPull) {
				console.log('[care-gesture] touchend - 触发关怀版！')
				this.goToCareMode()
			}

			this.resetCarePull()
		},

		// 重置下拉状态
		resetCarePull() {
			this.carePullHeight = 0
			this.careHintOpacity = 0
			this.careGesture.pullDistance = 0
			setTimeout(() => {
				this.showCareHint = false
			}, 200)
		},

		// 进入关怀版
		goToCareMode() {
			console.log('[care-gesture] goToCareMode - 跳转关怀版')
			uni.vibrateShort({ success: () => {} })
			this.$u.route('/pages/care/care')
		}
	}
}
