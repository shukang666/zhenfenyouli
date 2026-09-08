<template>
	<view class="category-tabs-wrapper">
		<scroll-view class="category-scroll" scroll-x :show-scrollbar="false" :scroll-into-view="`cat-${activeIndex}`" scroll-with-animation>
			<view class="category-list">
				<view 
					:id="`cat-${index}`"
					class="cat-item" 
					:class="{active: activeIndex === index}"
					v-for="(cat, index) in categories" 
					:key="index"
					@click="handleSelect(index)"
				>
					<view class="cat-icon-wrap">
						<text class="cat-icon">{{cat.icon}}</text>
					</view>
					<text class="cat-name">{{cat.displayName}}</text>
					<!-- 分类计数徽标 -->
					<view class="cat-badge" v-if="cat.count && cat.count > 0">
						<text class="cat-count">{{cat.count}}</text>
					</view>
				</view>
			</view>
		</scroll-view>
		<!-- 滚动指示器 -->
		<view class="scroll-indicator" v-if="showIndicator">
			<view class="indicator-bar"></view>
		</view>
	</view>
</template>

<script>
	// 分类显示名称映射：后台分类名称 -> 用户友好的显示名称
	const CATE_DISPLAY_MAP = {
		// 后台分类名称: 前端显示名称
		// 示例映射，可根据实际后台配置调整
	}
	
	// 分类图标映射
	const CATE_ICON_MAP = {
		// 后台分类名称: 图标
	}
	
	// 默认分类配置
	const DEFAULT_CATEGORIES = [
		{ field: 'all', name: '全部', icon: '✨', displayName: '全部' },
		{ field: 'green', name: '绿色生活', icon: '🌿', displayName: '绿色生活' },
		{ field: 'energy', name: '低碳生活', icon: '🌱', displayName: '低碳生活' },
		{ field: 'community', name: '社区生活', icon: '🏠', displayName: '社区活动' },
	{ field: 'health', name: '健康生活', icon: '💪', displayName: '健康生活' },
		{ field: 'study', name: '成长生活', icon: '📖', displayName: '知识学习' },
		{ field: 'challenge', name: '我的挑战', icon: '🎯', displayName: '我的挑战' }
	]
	
	export default {
		name: 'CategoryTabs',
		props: {
			// 分类数据（可选，不存在则使用默认配置）
			categories: {
				type: Array,
				default: () => DEFAULT_CATEGORIES
			},
			// 当前激活的分类索引
			value: {
				type: Number,
				default: 0
			},
			// 是否显示滚动指示器
			showIndicator: {
				type: Boolean,
				default: true
			},
			// 后台原始分类列表（用于映射显示名称）
			backCategories: {
				type: Array,
				default: () => []
			}
		},
		computed: {
			activeIndex() {
				return this.value
			}
		},
		methods: {
			handleSelect(index) {
				this.$emit('input', index)
				this.$emit('change', index, this.categories[index])
			},
			// 根据后台分类名称获取显示名称
			getDisplayName(name) {
				if (CATE_DISPLAY_MAP[name]) {
					return CATE_DISPLAY_MAP[name]
				}
				return name
			},
			// 根据后台分类名称获取图标
			getIcon(name) {
				if (CATE_ICON_MAP[name]) {
					return CATE_ICON_MAP[name]
				}
				return '📋'
			}
		}
	}
</script>

<style lang="scss" scoped>
	.category-tabs-wrapper {
		position: relative;
		.category-scroll {
			white-space: nowrap;
			padding: 4rpx 0;
			
			.category-list {
				display: inline-flex;
				gap: 16rpx;
				padding: 0;
				
				.cat-item {
					position: relative;
					display: inline-flex;
					flex-direction: column;
					align-items: center;
					gap: 8rpx;
					padding: 20rpx 32rpx;
					border-radius: 24rpx;
					background: rgba(255, 255, 255, 0.7);
					backdrop-filter: blur(10rpx);
					-webkit-backdrop-filter: blur(10rpx);
					transition: all 0.3s ease;
					min-width: 140rpx;
					
					&:active {
						transform: scale(0.95);
					}
					
					&.active {
						background: linear-gradient(135deg, #34C759 0%, #30D158 100%);
						box-shadow: 0 6rpx 24rpx rgba(52, 199, 89, 0.35);
						transform: translateY(-2rpx);
						
						.cat-icon-wrap {
							background: rgba(255, 255, 255, 0.25);
						}
						
						.cat-name {
							color: #FFFFFF;
							font-weight: 600;
						}
					}
					
					.cat-icon-wrap {
						width: 56rpx;
						height: 56rpx;
						border-radius: 16rpx;
						background: rgba(52, 199, 89, 0.1);
						display: flex;
						align-items: center;
						justify-content: center;
						transition: all 0.3s ease;
						
						.cat-icon {
							font-size: 32rpx;
							line-height: 1;
						}
					}
					
					.cat-name {
						font-size: 24rpx;
						color: #8E8E93;
						white-space: nowrap;
						transition: color 0.3s ease;
					}
					
					.cat-badge {
						position: absolute;
						top: 12rpx;
						right: 12rpx;
						min-width: 32rpx;
						height: 32rpx;
						padding: 0 8rpx;
						border-radius: 16rpx;
						background: #FF3B30;
						display: flex;
						align-items: center;
						justify-content: center;
						
						.cat-count {
							font-size: 18rpx;
							color: #FFFFFF;
							font-weight: 600;
						}
					}
				}
			}
		}
		
		.scroll-indicator {
			width: 60rpx;
			height: 4rpx;
			background: rgba(0, 0, 0, 0.06);
			border-radius: 2rpx;
			margin: 16rpx auto 0;
			
			.indicator-bar {
				width: 30rpx;
				height: 100%;
				background: #34C759;
				border-radius: 2rpx;
				animation: scrollIndicator 2s ease-in-out infinite;
			}
		}
	}
	
	@keyframes scrollIndicator {
		0%, 100% {
			transform: translateX(0);
		}
		50% {
			transform: translateX(30rpx);
		}
	}
</style>
