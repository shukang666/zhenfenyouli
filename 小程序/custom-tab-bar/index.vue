<template>
	<view class="custom-tab-bar" :style="safeAreaStyle">
		<view class="tab-bar-bg"></view>
		<view class="tab-bar-content">
			<view
				class="tab-item"
				v-for="(item, index) in list"
				:key="item.pagePath"
				@click="switchTab(index, item)"
			>
				<view class="tab-icon-wrap" :class="{ 'tab-icon-wrap--active': selected === index }">
					<image
						class="tab-icon"
						:src="selected === index ? item.selectedIconPath : item.iconPath"
						mode="aspectFit"
					></image>
				</view>
				<text class="tab-text" :class="{ 'tab-text--active': selected === index }">
					{{ item.text }}
				</text>
			</view>
		</view>
		<!-- 底部安全区占位 -->
		<view class="safe-area-bottom" v-if="safeAreaBottom > 0"></view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				selected: 0,
				safeAreaBottom: 0,
				list: [
					{
						pagePath: '/pages/index/index',
						iconPath: '/static/tabbar/tab1.png',
						selectedIconPath: '/static/tabbar/tab1-active.png',
						text: '首页'
					},
					{
						pagePath: '/pages/low_carbon/low_carbon',
						iconPath: '/static/tabbar/tab2.png',
						selectedIconPath: '/static/tabbar/tab2-active.png',
						text: '活动'
					},
					{
						pagePath: '/pages/health/health',
						iconPath: '/static/tabbar/tab3.png',
						selectedIconPath: '/static/tabbar/tab3-active.png',
						text: '健康'
					},
					{
						pagePath: '/pages/mall/mall',
						iconPath: '/static/tabbar/tab4.png',
						selectedIconPath: '/static/tabbar/tab4-active.png',
						text: '积分权益'
					},
					{
						pagePath: '/pages/user/index',
						iconPath: '/static/tabbar/tab5.png',
						selectedIconPath: '/static/tabbar/tab5-active.png',
						text: '我的'
					}
				]
			}
		},
		computed: {
			safeAreaStyle() {
				return this.safeAreaBottom > 0
					? `padding-bottom: ${this.safeAreaBottom}px;`
					: ''
			}
		},
		created() {
			// 获取底部安全区高度
			const systemInfo = uni.getSystemInfoSync()
			this.safeAreaBottom = systemInfo.safeAreaInsets?.bottom || 0
		},
		methods: {
			setSelected(index) {
				this.selected = index
			},
			switchTab(index, item) {
				if (this.selected === index) return
				uni.switchTab({
					url: item.pagePath
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.custom-tab-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		z-index: 999;
		/* 防止底部出现缝隙 */
		background: #ffffff;
	}

	/* 背景层：瑞幸风格 - 纯白底 + 顶部细线 */
	.tab-bar-bg {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: #ffffff;
		/* 顶部 0.5px 细线，iOS 风格 */
		&::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 1rpx;
			background: rgba(0, 0, 0, 0.06);
			transform: scaleY(0.5);
			transform-origin: top;
		}
	}

	/* 内容区 */
	.tab-bar-content {
		position: relative;
		display: flex;
		align-items: flex-start;
		justify-content: space-around;
		padding-top: 12rpx;
		height: calc(100rpx + env(safe-area-inset-bottom));
		height: calc(100rpx + constant(safe-area-inset-bottom));
	}

	/* 单个 tab 项 */
	.tab-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: flex-start;
		flex: 1;
		padding-top: 4rpx;
	}

	/* 图标容器 */
	.tab-icon-wrap {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 52rpx;
		height: 52rpx;
		transition: transform 0.2s ease;

		&--active {
			transform: scale(1.1);
		}
	}

	.tab-icon {
		width: 44rpx;
		height: 44rpx;
	}

	/* 文字 */
	.tab-text {
		margin-top: 6rpx;
		font-size: 20rpx;
		color: #999999;
		line-height: 1;
		transition: all 0.2s ease;

		&--active {
			color: #18AF38;
			font-weight: 600;
		}
	}

	/* 底部安全区占位 */
	.safe-area-bottom {
		height: env(safe-area-inset-bottom);
		height: constant(safe-area-inset-bottom);
	}
</style>
