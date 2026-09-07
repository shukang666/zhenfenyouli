<template>
	<view class="custom-tab-bar">
		<view 
			v-for="(item, index) in list" 
			:key="index" 
			class="tab-item" 
			:class="{ 'tab-item--active': selected === index }"
			@click="switchTab(index)"
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
			]
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
}
</style>
