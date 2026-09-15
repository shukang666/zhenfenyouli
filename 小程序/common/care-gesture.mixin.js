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
	// 页面原生滚动生命周期 - 自动被 uni-app 调用
	onPageScroll(e) {
		this._updateScrollTop(e)
	},
	onShow() {
		// 每次页面显示时重置为顶部状态
		this.careGesture.isInTop = true
	},
	methods: {
		// 供 z-paging @scroll 事件绑定使用
		// 使用方式：<z-paging @scroll="onZPagingScroll" ...>
		onZPagingScroll(e) {
			// z-paging 的 scroll 事件参数可能是 { scrollTop, scrollHeight, ... } 或 { detail: { scrollTop } }
			const scrollTop = (e && e.scrollTop !== undefined) 
				? e.scrollTop 
				: (e && e.detail && e.detail.scrollTop !== undefined ? e.detail.scrollTop : 0)
			this._updateScrollTop({ scrollTop })
		},

		// 内部方法：根据 scrollTop 更新 isInTop
		_updateScrollTop(e) {
			if (e && typeof e.scrollTop !== 'undefined') {
				this.careGesture.isInTop = e.scrollTop <= 5
			}
		},

		// 触摸开始
		onCareTouchStart(e) {
			if (!this.careGesture.isInTop) return
			this.careGesture.startY = e.touches[0].clientY
			this.careGesture.startTime = Date.now()
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

			if (isQuickSwipe || isLongPull) {
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
			uni.vibrateShort({ success: () => {} })
			this.$u.route('/pages/care/care')
		}
	}
}
