<template>
	<view class="custom-tab-bar">
		<view 
			v-for="(item, index) in list" 
			:key="index" 
			class="tab-item" 
			:class="{ 'tab-item--active': selected === index }"
			@touchstart="handleTouchStart(index)"
			@touchend="handleTouchEnd(index)"
			@touchcancel="handleTouchCancel"
		>
			<image 
				:src="getIconPath(item, index)" 
				class="tab-icon" 
				mode="aspectFit"
			></image>
			<text 
				class="tab-text" 
				:style="{ color: selected === index ? selectedColor : color }"
			>{{ item.text }}</text>
		</view>
		
		<!-- 长按提示 -->
		<view class="long-press-tip" v-if="showLongPressTip">
			<text class="long-press-tip__text">继续按住2秒进入开发者模式</text>
			<view class="long-press-tip__arrow"></view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			selected: 0,
			color: '#7A7E83',
			selectedColor: '#18AF38',
			safeAreaBottom: 0,
			list: [
				{
					pagePath: 'pages/index/index',
					iconPath: '/static/tabbar/tab1.png',
					selectedIconPath: '/static/tabbar/tab1-active.png',
					text: '首页'
				},
				{
					pagePath: 'pages/low_carbon/low_carbon',
					iconPath: '/static/tabbar/tab2.png',
					selectedIconPath: '/static/tabbar/tab2-active.png',
					text: '活动'
				},
				{
					pagePath: 'pages/health/health',
					iconPath: '/static/tabbar/tab3.png',
					selectedIconPath: '/static/tabbar/tab3-active.png',
					text: '健康'
				},
				{
					pagePath: 'pages/mall/mall',
					iconPath: '/static/tabbar/tab4.png',
					selectedIconPath: '/static/tabbar/tab4-active.png',
					text: '积分权益'
				},
				{
					pagePath: 'pages/user/index',
					iconPath: '/static/tabbar/tab5.png',
					selectedIconPath: '/static/tabbar/tab5-active.png',
					text: '我的'
				}
			],
			// 长按相关
			longPressTimer: null,
			isLongPress: false,
			longPressTriggered: false,
			showLongPressTip: false,
		}
	},
	created() {
		// 获取底部安全区高度
		try {
			const systemInfo = uni.getSystemInfoSync()
			this.safeAreaBottom = systemInfo.safeAreaInsets?.bottom || 10
		} catch (e) {
			this.safeAreaBottom = 10
		}
	},
	methods: {
		getIconPath(item, index) {
			if (this.selected === index) {
				return item.selectedIconPath
			}
			return item.iconPath
		},
		handleTouchStart(index) {
			// 只有"我的"tab（index=4）支持长按检测
			if (index !== 4) {
				this.switchTab(index)
				return
			}
			
			this.isLongPress = true
			this.longPressTriggered = false
			this.showLongPressTip = true
			
			this.longPressTimer = setTimeout(() => {
				if (this.isLongPress) {
					this.longPressTriggered = true
					this.showLongPressTip = false
					// 触发震动反馈
					uni.vibrateLong()
					// 打开开发者密码弹窗
					this.openDevPasswordDialog()
				}
			}, 2000)
		},
		handleTouchEnd(index) {
			if (index !== 4) return
			
			this.isLongPress = false
			this.showLongPressTip = false
			clearTimeout(this.longPressTimer)
			
			// 如果不是长按触发的，执行正常切换
			if (!this.longPressTriggered) {
				this.switchTab(index)
			}
			this.longPressTriggered = false
		},
		handleTouchCancel() {
			this.isLongPress = false
			this.longPressTriggered = false
			this.showLongPressTip = false
			clearTimeout(this.longPressTimer)
		},
		switchTab(index) {
			if (index === this.selected) return
			const item = this.list[index]
			const url = '/' + item.pagePath
			uni.switchTab({ 
				url,
				success: () => {
					this.selected = index
				}
			})
		},
		openDevPasswordDialog() {
			console.log('[DevMode] openDevPasswordDialog 被调用')
			
			// 使用 wx.showModal 的 editable 模式（原生弹窗，不受键盘影响）
			// #ifdef MP-WEIXIN
			console.log('[DevMode] wx.canIUse 检查:', wx.canIUse && wx.canIUse('showModal.editable'))
			
			if (wx.canIUse && wx.canIUse('showModal.editable')) {
				console.log('[DevMode] 开始调用 wx.showModal editable')
				wx.showModal({
					title: '开发者验证',
					content: '请输入开发者密码',
					editable: true,
					placeholderText: '请输入密码',
					confirmText: '确认',
					cancelText: '取消',
					success: (res) => {
						console.log('[DevMode] showModal success 回调:', JSON.stringify(res))
						if (res.confirm) {
							console.log('[DevMode] 用户确认，输入内容:', res.content)
							if (res.content === '990110') {
								uni.navigateTo({
									url: '/pages/dev/index'
								})
							} else {
								uni.showToast({
									title: '密码错误',
									icon: 'none'
								})
							}
						}
					},
					fail: (err) => {
						console.log('[DevMode] showModal fail 回调:', JSON.stringify(err))
					}
				})
				console.log('[DevMode] wx.showModal 已调用')
				return
			}
			// #endif
			
			console.log('[DevMode] 无法使用 showModal.editable，尝试降级方案')
			
			// 降级方案：使用 uView 的弹窗（如果可用）
			console.log('[DevMode] this.$u 存在:', !!this.$u)
			if (this.$u && this.$u.modal) {
				console.log('[DevMode] 使用 uView modal')
				this.$u.modal({
					title: '开发者验证',
					placeholder: '请输入密码',
					showCancelButton: true,
					confirmText: '确认',
					cancelText: '取消',
					onConfirm: (value) => {
						console.log('[DevMode] uView modal 确认，输入:', value)
						if (value === '990110') {
							uni.navigateTo({
								url: '/pages/dev/index'
							})
						} else {
							uni.showToast({
								title: '密码错误',
								icon: 'none'
							})
						}
					}
				})
				return
			}
			
			console.log('[DevMode] 所有弹窗方案都不可用')
		},
		// 外部调用：更新选中状态
		setSelected(index) {
			this.selected = index
		}
	}
}
</script>

<style lang="scss" scoped>
.custom-tab-bar {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	height: 50px;
	padding-bottom: env(safe-area-inset-bottom);
	padding-bottom: constant(safe-area-inset-bottom);
	background-color: #ffffff;
	border-top: 0.5px solid rgba(0, 0, 0, 0.3);
	display: flex;
	z-index: 999;
	
	.tab-item {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		position: relative;
		height: 50px;
		
		.tab-icon {
			width: 24px;
			height: 24px;
			margin-bottom: 2px;
		}
		
		.tab-text {
			font-size: 10px;
			line-height: 1;
			text-align: center;
			font-family: -apple-system, BlinkMacSystemFont, 'Helvetica Neue', Helvetica, Segoe UI, Arial, Roboto, 'PingFang SC', 'miui', 'Hiragino Sans GB', 'Microsoft Yahei', sans-serif;
		}
	}
	
	.long-press-tip {
		position: absolute;
		bottom: calc(50px + 10px);
		right: 10px;
		z-index: 1000;
		
		&__text {
			display: block;
			background-color: rgba(0, 0, 0, 0.75);
			color: #fff;
			font-size: 12px;
			padding: 8px 12px;
			border-radius: 6px;
			white-space: nowrap;
		}
		
		&__arrow {
			position: absolute;
			top: 100%;
			right: 20px;
			border: 6px solid transparent;
			border-top-color: rgba(0, 0, 0, 0.75);
		}
	}
}
</style>
