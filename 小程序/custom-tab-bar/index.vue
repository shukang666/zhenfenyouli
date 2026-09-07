<template>
	<view class="custom-tab-bar" :style="{ paddingBottom: safeAreaInsetBottom + 'px' }">
		<view 
			v-for="(item, index) in list" 
			:key="index" 
			class="tab-item" 
			:class="{ 'tab-item--active': selected === index }"
			@touchstart="handleTouchStart(index, $event)"
			@touchend="handleTouchEnd(index, $event)"
			@touchcancel="handleTouchCancel"
			@click="handleClick(index)"
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
		
		<!-- 开发者密码弹窗 -->
		<u-mask :show="devPasswordShow">
			<view class="u-abso-center u-w-600 u-radius-20 u-type-white-bg u-p-40">
				<view class="u-font-32 u-font-bold u-text-center u-m-b-30">🔐 开发者验证</view>
				<view class="u-font-26 u-type-info u-m-b-20">请输入开发者密码</view>
				<u-input v-model="devPassword" type="password" placeholder="请输入密码" border />
				<view class="u-flex u-row-between u-m-t-40">
					<u-button @click="closeDevPasswordDialog">取消</u-button>
					<u-button type="primary" @click="checkDevPassword">确认</u-button>
				</view>
			</view>
		</u-mask>
		
		<!-- 长按提示 -->
		<view class="long-press-tip" v-if="showLongPressTip">
			<view class="long-press-tip__content">
				<text>继续按住2秒进入开发者模式</text>
			</view>
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
			safeAreaInsetBottom: 0,
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
			// 开发者模式相关
			devPasswordShow: false,
			devPassword: '',
		}
	},
	created() {
		// 获取底部安全区高度
		const systemInfo = uni.getSystemInfoSync()
		this.safeAreaInsetBottom = systemInfo.safeAreaInsets?.bottom || 0
		if (this.safeAreaInsetBottom === 0) {
			this.safeAreaInsetBottom = 10
		}
	},
	methods: {
		getIconPath(item, index) {
			if (this.selected === index) {
				return item.selectedIconPath
			}
			return item.iconPath
		},
		handleTouchStart(index, e) {
			// 只有"我的"tab（index=4）支持长按检测
			if (index !== 4) return
			
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
		handleTouchEnd(index, e) {
			if (index !== 4) return
			
			this.isLongPress = false
			this.showLongPressTip = false
			clearTimeout(this.longPressTimer)
			
			// 如果不是长按触发的，执行正常切换
			if (!this.longPressTriggered) {
				this.switchTab(index)
			}
		},
		handleTouchCancel() {
			this.isLongPress = false
			this.longPressTriggered = false
			this.showLongPressTip = false
			clearTimeout(this.longPressTimer)
		},
		handleClick(index) {
			if (index === 4 && this.isLongPress) {
				// 不处理点击，长按检测进行中
				return
			}
			this.switchTab(index)
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
			this.devPasswordShow = true
			this.devPassword = ''
		},
		closeDevPasswordDialog() {
			this.devPasswordShow = false
			this.devPassword = ''
		},
		checkDevPassword() {
			if (this.devPassword === '990110') {
				this.devPasswordShow = false
				this.devPassword = ''
				uni.navigateTo({
					url: '/pages/dev/index'
				})
			} else {
				this.$u.toast('密码错误')
				this.devPassword = ''
			}
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
	/* #ifndef H5 */
	height: 50px;
	/* #endif */
	/* #ifdef H5 */
	height: 60px;
	/* #endif */
	background-color: #fff;
	border-top: 1rpx solid rgba(0, 0, 0, 0.1);
	display: flex;
	z-index: 998;
	
	.tab-item {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		position: relative;
		transition: all 0.1s;
		
		&:active {
			opacity: 0.7;
		}
		
		.tab-icon {
			width: 24px;
			height: 24px;
			margin-bottom: 2px;
		}
		
		.tab-text {
			font-size: 10px;
			line-height: 1.2;
		}
	}
	
	.long-press-tip {
		position: absolute;
		bottom: 100%;
		left: 50%;
		transform: translateX(-50%);
		margin-bottom: 10px;
		z-index: 1000;
		
		&__content {
			background-color: rgba(0, 0, 0, 0.75);
			color: #fff;
			font-size: 12px;
			padding: 8px 16px;
			border-radius: 6px;
			white-space: nowrap;
			
			&::after {
				content: '';
				position: absolute;
				top: 100%;
				left: 50%;
				transform: translateX(-50%);
				border: 6px solid transparent;
				border-top-color: rgba(0, 0, 0, 0.75);
			}
		}
	}
}
</style>
