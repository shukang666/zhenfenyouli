<template>
	<view class="activity-center">
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="low_carbon"></skeleton-screen>

		<!-- 顶部玻璃导航区域 -->
		<view class="glass-header" v-if="!isLoading">
			<view class="header-bg"></view>
			<view class="header-content">
				<view class="greeting-section">
					<text class="greeting-text">{{greetingText}}</text>
					<text class="greeting-sub">今天想为地球做点什么？</text>
				</view>
				<view class="user-badge-mini" @click="goToUser">
					<image :src="userInfo.avatar || '/static/images/user-1.png'" class="avatar" mode="aspectFill"></image>
					<text class="level-tag">{{userLevel}}</text>
				</view>
			</view>
		</view>

		<view class="main-content" v-if="!isLoading">
			<!-- 今日环保任务卡片 -->
			<view class="section task-section" v-if="todayTask.show">
				<view class="glass-task-card" @click="navigator('/pages/flhd/ljfldk',1)">
					<view class="task-card-bg"></view>
					<view class="task-card-content">
						<view class="task-header">
							<view class="task-icon-wrap">
								<text class="task-emoji">🌍</text>
							</view>
							<view class="task-info">
								<text class="task-title">{{todayTask.title}}</text>
								<text class="task-subtitle">{{todayTask.subtitle}}</text>
							</view>
							<view class="task-points">
								<text class="points-value">+{{todayTask.points}}</text>
								<text class="points-label">积分</text>
							</view>
						</view>
						<view class="task-progress">
							<view class="progress-header">
								<text class="streak-text">🔥 连续{{todayTask.streak}}天</text>
								<text class="goal-text">还差{{todayTask.daysToGoal}}天</text>
							</view>
							<view class="progress-bar-wrap">
								<view class="progress-bar">
									<view class="progress-fill" :style="{width: todayTask.progress + '%'}"></view>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 我的成长区域 -->
			<view class="section growth-section">
				<view class="growth-card">
					<view class="growth-bg"></view>
					<view class="growth-content">
						<view class="growth-header">
							<text class="section-title">我的成长</text>
							<text class="growth-more" @click="goToUser">查看详情 ›</text>
						</view>
						<view class="growth-body">
							<!-- 等级进度 -->
							<view class="level-progress">
								<view class="level-item" :class="{current: growth.currentLevel === 0}">
									<text class="level-emoji">🌱</text>
									<text class="level-name">环保新人</text>
								</view>
								<view class="level-line" :class="{active: growth.currentLevel >= 1}"></view>
								<view class="level-item" :class="{current: growth.currentLevel === 1}">
									<text class="level-emoji">🌿</text>
									<text class="level-name">绿色达人</text>
								</view>
								<view class="level-line" :class="{active: growth.currentLevel >= 2}"></view>
								<view class="level-item" :class="{current: growth.currentLevel === 2}">
									<text class="level-emoji">🌳</text>
									<text class="level-name">社区先锋</text>
								</view>
							</view>
							<!-- 数据统计 -->
							<view class="stats-row">
								<view class="stat-item">
									<text class="stat-value">{{growth.points}}</text>
									<text class="stat-label">积分</text>
								</view>
								<view class="stat-divider"></view>
								<view class="stat-item">
									<text class="stat-value">{{growth.streak}}</text>
									<text class="stat-label">连续打卡</text>
								</view>
								<view class="stat-divider"></view>
								<view class="stat-item">
									<text class="stat-value">{{growth.activities}}</text>
									<text class="stat-label">完成活动</text>
								</view>
								<view class="stat-divider"></view>
								<view class="stat-item">
									<text class="stat-value">{{growth.badges}}</text>
									<text class="stat-label">徽章</text>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>

			<!-- 垃圾分类专区（今日真分） -->
			<view class="section zone-section">
				<view class="section-header">
					<text class="section-title">今日真分</text>
					<text class="section-sub">垃圾分类专区</text>
				</view>
				<view class="zone-grid">
					<view class="zone-card glass-card" @click="navigator('/pages/flhd/ljfldk',1)">
						<view class="zone-icon">🏠</view>
						<text class="zone-name">家庭分类打卡</text>
						<text class="zone-desc">上传照片+20积分</text>
					</view>
					<view class="zone-card glass-card" @click="$u.route('/pages/flhd/list')">
						<view class="zone-icon">📚</view>
						<text class="zone-name">分类知识</text>
						<text class="zone-desc">学习垃圾分类</text>
					</view>
					<view class="zone-card glass-card" @click="goToWasteGuide">
						<view class="zone-icon">♻️</view>
						<text class="zone-name">可回收行动</text>
						<text class="zone-desc">旧物回收积分</text>
					</view>
					<view class="zone-card glass-card" @click="goToChallenge">
						<view class="zone-icon">🎯</view>
						<text class="zone-name">环保挑战</text>
						<text class="zone-desc">7天/21天挑战</text>
					</view>
				</view>
			</view>

		<!-- 活动分类导航（用户语言） -->
		<view class="section category-section">
			<CategoryTabs 
				:value="currentCategory"
				:categories="displayCategories"
				:show-indicator="true"
				@input="currentCategory = $event"
				@change="switchCategory"
			/>
		</view>

			<!-- 推荐活动列表 -->
			<view class="section activities-section">
				<view class="activity-card glass-card" 
					v-for="item in activityList" 
					:key="item.id"
					@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)"
				>
					<view class="activity-image-wrap">
						<u-image :src="item.thumb" height="320" mode="aspectFill" border-radius="20 20 0 0"></u-image>
						<view class="activity-overlay" v-if="item.active_count">
							<view class="avatar-stack">
								<image 
									:src="img" 
									class="stack-avatar" 
									mode="aspectFill" 
									v-for="(img,i) in item.avatar_array.slice(0,3)" 
									:key="i"
									:style="{left: i * 16 + 'rpx'}"
								></image>
							</view>
							<text class="participants-count">{{item.active_count}}人参与</text>
						</view>
						<view class="point-badge" v-if="item.send_point">
							<text class="point-text">+{{item.send_point}}</text>
						</view>
					</view>
					<view class="activity-info">
						<text class="activity-name u-line-1">{{item.name}}</text>
						<view class="activity-meta">
							<view class="meta-item">
								<u-icon name="clock" size="22"></u-icon>
								<text class="meta-text">{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="meta-item" v-if="item.address && item.address.trim()">
								<u-icon name="map" size="22"></u-icon>
								<text class="meta-text u-line-1">{{item.address}}</text>
							</view>
						</view>
						<view class="activity-footer">
							<view class="challenge-tag" v-if="item.is_clock == 1">
								<text class="tag-text">挑战活动</text>
							</view>
							<view class="badge-preview" v-if="item.badge_image">
								<image :src="item.badge_image" class="badge-icon" mode="aspectFill"></image>
							</view>
						</view>
					</view>
				</view>
				
				<!-- 空状态 -->
				<view class="empty-state" v-if="activityList.length === 0 && !activeLoading">
					<text class="empty-emoji">🌱</text>
					<text class="empty-text">暂无活动，敬请期待</text>
				</view>
				
				<!-- 加载更多 -->
				<view class="load-more" v-if="activeLoading">
					<text class="load-text">加载中...</text>
				</view>
				<view class="load-more" v-if="!activeLoading && activityList.length > 0 && activityList.length >= activityTotal">
					<text class="load-text">没有更多了</text>
				</view>
			</view>

			<!-- 底部安全区 -->
			<view class="safe-bottom"></view>
		</view>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	import CategoryTabs from '@/components/category-tabs/category-tabs.vue'
	
	// 分类名称映射：后台分类ID -> 用户友好的显示名称
	const CATE_NAME_MAP = {
		// 可根据实际后台分类ID配置，以下为示例
		// 如后台分类名称为"低碳生活" -> 显示为"绿色生活"
	}

	// 分类图标映射
	const CATE_ICON_MAP = {}

	// 分类显示配置
	const DISPLAY_CATEGORIES = [
		{ field: 'all', name: '全部', icon: '✨', displayName: '全部' },
		{ field: 'green', name: '绿色生活', icon: '🌿', displayName: '绿色生活' },
		{ field: 'energy', name: '低碳生活', icon: '🌱', displayName: '低碳生活' },
		{ field: 'community', name: '社区生活', icon: '🏠', displayName: '社区活动' },
		{ field: 'health', name: '健康生活', icon: '💪', displayName: '健康生活' },
		{ field: 'study', name: '成长生活', icon: '📖', displayName: '知识学习' },
		{ field: 'challenge', name: '我的挑战', icon: '🎯', displayName: '我的挑战' }
	]
	
	export default {
	components: {
		SkeletonScreen,
		CategoryTabs
	},
	data() {
		return {
				isLoading: true,
				// 展示分类
				displayCategories: DISPLAY_CATEGORIES,
				currentCategory: 0,
				// 活动列表
				activityList: [],
				activityPage: 1,
				activityTotal: 0,
				activeLoading: false,
				// 成长数据
				growth: {
					currentLevel: 0,
					points: 0,
					streak: 0,
					activities: 0,
					badges: 0
				},
				// 今日任务
				todayTask: {
					show: true,
					title: '今日垃圾分类打卡',
					subtitle: '完成家庭分类，共建绿色社区',
					points: 20,
					streak: 0,
					daysToGoal: 2,
					progress: 0
				},
				// 分类数据
				cate_list: [],
				tab_list: [],
				current_tab: 0,
				// 本月推荐
				monthly_rec_list: [],
				monthly_rec_page: 1,
				monthly_rec_total: 0,
				monthly_rec_loading: false,
				// 自治活动
				shopInfo: {},
				// 轮播
				swiper_list: [],
				monthly_rec_banner: ''
			};
		},
		computed: {
			greetingText() {
				const hour = new Date().getHours()
				if (hour < 6) return '凌晨好'
				if (hour < 12) return '早上好'
				if (hour < 14) return '中午好'
				if (hour < 18) return '下午好'
				if (hour < 22) return '晚上好'
				return '夜深了'
			},
			userLevel() {
				const levels = ['环保新人', '绿色达人', '社区先锋']
				return levels[this.growth.currentLevel] || '环保新人'
			},
			// 当前分类下的活动
			currentCategoryData() {
				return this.displayCategories[this.currentCategory] || this.displayCategories[0]
			},
			is_daka_tab() {
				return this.current_tab === 1
			},
			is_backup_tab() {
				return this.tab_list.length > 0
					&& this.current_tab == this.tab_list.length - 1
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_backup === 1
			},
			is_zzhd_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'zzhd'
			},
			is_sjhd_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'sjhd'
			},
			is_monthly_rec_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'monthly_rec'
			}
		},
		onLoad() {
			this.loadAllData()
		},
		onShow() {
			this.updateTabBarSelected(1)
			let jumpMonthlyRec = uni.getStorageSync('jump_monthly_rec')
			if (jumpMonthlyRec) {
				if (this.tab_list.length > 0) {
					uni.removeStorageSync('jump_monthly_rec')
					this.current_tab = 0
					this.load_monthly_rec_list()
				}
				return
			}
			if (checkCommunity(this)) return
			if (!this.isLogin) return
			if (this._isChecking) return
			this._isChecking = true
			this.getUserInfo().then(() => {
				this._isChecking = false
				if (!this.isLogin) return
				if (!this.userInfo.is_learn) {
					this.$u.route('/pages/flhd/list')
				} else if (!this.userInfo.is_answer) {
					this.$u.route('/pages/answer/examine?id=1')
				}
			})
		},
		methods: {
			// 并行加载所有数据
			async loadAllData() {
				try {
					await Promise.all([
						this.find_slide(),
						this.get_monthly_rec_banner(),
						this.get_new_cate_active_list(),
						this.get_active_tab_list(),
						this.getUserInfoForGrowth(),
						this.get_new_badge_list()
					])
				} catch (e) {
					console.log('数据加载失败:', e)
				} finally {
					this.isLoading = false
				}
			},
			// 获取用户成长信息
			async getUserInfoForGrowth() {
				try {
					let res = await this.$u.api.getUserInfo()
					let info = res.data || {}
					this.growth.points = info.point || 0
					this.growth.currentLevel = this.calculateLevel(info.point || 0)
					this.growth.streak = info.clock_days || info.continuous_days || 0
					// 更新今日任务数据
					this.todayTask.streak = this.growth.streak
					this.todayTask.progress = Math.min((this.growth.streak % 7) / 7 * 100, 100)
					this.todayTask.daysToGoal = 7 - (this.growth.streak % 7)
					if (this.todayTask.daysToGoal === 7) this.todayTask.daysToGoal = 0
				} catch (e) {
					console.log('获取用户信息失败:', e)
				}
			},
			// 计算环保等级
		calculateLevel(points) {
				if (points >= 1000) return 2  // 社区先锋
				if (points >= 300) return 1   // 绿色达人
				return 0  // 环保新人
			},
			// 获取徽章列表
			async get_new_badge_list() {
				try {
					let res = await this.$u.api.get_new_badge_list({})
					this.growth.badges = (res.data || []).length
				} catch (e) {
					console.log('获取徽章失败:', e)
				}
			},
			// 获取用户信息
			getUserInfo() {
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				})
			},
			// 活动分类
			get_new_cate_active_list() {
				return this.$u.api.get_new_cate_active_list({
					page: 1,
				}).then(res => {
					this.cate_list = res.data || []
				}).catch(err => {
					console.log(err.msg)
				})
			},
			// 活动页标签
			get_active_tab_list() {
				return this.$u.api.get_active_tab_list().then(res => {
					let list = res.data || []
					list.unshift({ id: -99, name: '本月推荐', cate_id: 0, is_fixed: 2, _is_custom: 'monthly_rec' })
					list.push({ id: -2, name: '自治活动', cate_id: 0, is_fixed: 2, _is_custom: 'zzhd' })
					list.push({ id: -3, name: '社交活动', cate_id: 0, is_fixed: 2, _is_custom: 'sjhd' })
					this.tab_list = list
					this.load_monthly_rec_list()
				}).catch(err => {
					console.log(err.msg)
				})
			},
		// 切换分类
		switchCategory(index, category) {
			if (index === this.currentCategory) return
			this.currentCategory = index
			this.activityList = []
			this.activityPage = 1
			this.activityTotal = 0
			this.loadActivitiesByCategory()
		},
			// 根据分类加载活动
			loadActivitiesByCategory() {
				if (this.activeLoading) return
				this.activeLoading = true
				const cat = this.currentCategoryData
				let params = {
					page: this.activityPage,
					page_size: 10
				}
				// '我的挑战'分类只显示打卡活动
				if (cat.field === 'challenge') {
					params.is_put = 1
				}
				this.$u.api.get_new_active_list(params).then(res => {
					let data = res.data || {}
					let list = data.data || []
					this.activityTotal = data.total || 0
					if (this.activityPage <= 1) {
						this.activityList = list
					} else {
						this.activityList.push(...list)
					}
				}).catch(err => {
					this.$u.toast(err.msg)
				}).finally(() => {
					this.activeLoading = false
				})
			},
			// 加载本月推荐
			load_monthly_rec_list() {
				if (this.monthly_rec_loading) return
				this.monthly_rec_loading = true
				this.$u.api.get_new_active_list({
					page: this.monthly_rec_page,
					page_size: 10,
					is_month_recommend: 1
				}).then(res => {
					this.monthly_rec_loading = false
					let data = res.data || {}
					let list = data.data || []
					this.monthly_rec_total = data.total || 0
					this.monthly_rec_list = list
					// 合并到活动列表展示显示
					if (this.currentCategory === 0) {
						this.activityList = list
						this.activityTotal = data.total || 0
					}
				}).catch(err => {
					this.monthly_rec_loading = false
					this.$u.toast(err.msg)
				})
			},
			// 跳转页面
			navigator(url, type) {
				if (type == 1) {
					if (this.isLogin && !this.userInfo.is_answer) {
						var that = this
						uni.showModal({
							title: '提示',
							content: '请先学习垃圾分类指导，通过答题考试后可参与活动',
							showCancel: false,
							confirmText: '立即学习',
							confirmColor: '#39BF3E',
							success: function(res) {
								if (res.confirm) {
									that.$u.route('/pages/flhd/list')
								}
							}
						});
						return
					}
					this.$u.route(url)
					return
				}
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
			},
			// 垃圾分类指导
			goToWasteGuide() {
				this.$u.route('/pages/flhd/list')
			},
			// 环保挑战
			goToChallenge() {
				this.$u.route('/pages/flhd/ljfldk')
			},
			// 个人中心
			goToUser() {
				uni.switchTab({
					url: '/pages/user/user'
				})
			},
			// 轮播图
			find_slide() {
				return this.$u.api.find_slide({
					id: 5
				}).then(res => {
					this.swiper_list = res.data || []
				})
			},
			// banner图
			get_monthly_rec_banner() {
				return this.$u.api.find_slide({
					id: 8
				}).then(res => {
					if (res.data && res.data.length > 0) {
						this.monthly_rec_banner = res.data[0].image
					}
				})
			},
			// 更新tabbar
			updateTabBarSelected(index) {
				if (typeof this.$mp.page.getTabBar === 'function' && this.$mp.page.getTabBar()) {
					this.$mp.page.getTabBar().setSelected(index)
				}
			}
		},
		onReachBottom() {
			if (this.activeLoading) return
			if (this.activityList.length >= this.activityTotal) return
			this.activityPage++
			if (this.is_monthly_rec_tab) {
				this.load_monthly_rec_list()
			} else {
				this.loadActivitiesByCategory()
			}
		}
	}
</script>

<style lang="scss" scoped>
// 颜色变量 - Apple风格环保绿
$primary-green: #34C759;
$light-green: #A8E6CF;
$bg-gray: #F2F2F7;
$card-bg: rgba(255, 255, 255, 0.72);
$text-primary: #1C1C1E;
$text-secondary: #8E8E93;
$glass-blur: 20rpx;

.activity-center {
	min-height: 100vh;
	background: $bg-gray;
}

// 玻璃头部
.glass-header {
	position: relative;
	padding: 80rpx 40rpx 40rpx;
	overflow: hidden;
	
	.header-bg {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		height: 100%;
		background: linear-gradient(135deg, #34C759 0%, #30B350 50%, #28A045 100%);
		z-index: 0;
	}
	
	.header-content {
		position: relative;
		z-index: 1;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	
	.greeting-section {
		display: flex;
		flex-direction: column;
		
		.greeting-text {
			font-size: 40rpx;
			font-weight: 700;
			color: #FFFFFF;
			letter-spacing: 0.5rpx;
		}
		
		.greeting-sub {
			font-size: 26rpx;
			color: rgba(255, 255, 255, 0.85);
			margin-top: 8rpx;
		}
	}
	
	.user-badge-mini {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 8rpx;
		
		.avatar {
			width: 72rpx;
			height: 72rpx;
			border-radius: 50%;
			border: 3rpx solid rgba(255, 255, 255, 0.6);
		}
		
		.level-tag {
			font-size: 20rpx;
			color: #FFFFFF;
			background: rgba(255, 255, 255, 0.25);
			padding: 2rpx 12rpx;
			border-radius: 10rpx;
			backdrop-filter: blur($glass-blur);
		}
	}
}

// 主内容区
.main-content {
	padding: 0 30rpx;
}

// 通用区块
.section {
	margin-top: 30rpx;
}

.section-header {
	display: flex;
	align-items: baseline;
	gap: 16rpx;
	margin-bottom: 20rpx;
	
	.section-title {
		font-size: 34rpx;
		font-weight: 700;
		color: $text-primary;
	}
	
	.section-sub {
		font-size: 24rpx;
		color: $text-secondary;
	}
}

// 玻璃任务卡片
.glass-task-card {
	position: relative;
	border-radius: 28rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 32rpx rgba(52, 199, 89, 0.12);
	
	.task-card-bg {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: linear-gradient(135deg, rgba(52, 199, 89, 0.95) 0%, rgba(48, 179, 80, 0.9) 100%);
		backdrop-filter: blur($glass-blur);
		z-index: 0;
	}
	
	.task-card-content {
		position: relative;
		z-index: 1;
		padding: 32rpx;
	}
	
	.task-header {
		display: flex;
		align-items: center;
		gap: 20rpx;
		
		.task-icon-wrap {
			width: 80rpx;
			height: 80rpx;
			background: rgba(255, 255, 255, 0.25);
			border-radius: 20rpx;
			display: flex;
			align-items: center;
			justify-content: center;
			
			.task-emoji {
				font-size: 40rpx;
			}
		}
		
		.task-info {
			flex: 1;
			
			.task-title {
				font-size: 30rpx;
				font-weight: 600;
				color: #FFFFFF;
				display: block;
			}
			
			.task-subtitle {
				font-size: 22rpx;
				color: rgba(255, 255, 255, 0.8);
				margin-top: 4rpx;
				display: block;
			}
		}
		
		.task-points {
			display: flex;
			flex-direction: column;
			align-items: center;
			background: rgba(255, 255, 255, 0.2);
			padding: 12rpx 20rpx;
			border-radius: 16rpx;
			
			.points-value {
				font-size: 36rpx;
				font-weight: 700;
				color: #FFFFFF;
			}
			
			.points-label {
				font-size: 20rpx;
				color: rgba(255, 255, 255, 0.8);
			}
		}
	}
	
	.task-progress {
		margin-top: 24rpx;
		padding-top: 20rpx;
		border-top: 1rpx solid rgba(255, 255, 255, 0.2);
		
		.progress-header {
			display: flex;
			justify-content: space-between;
			margin-bottom: 12rpx;
			
			.streak-text {
				font-size: 24rpx;
				color: #FFFFFF;
				font-weight: 500;
			}
			
			.goal-text {
				font-size: 22rpx;
				color: rgba(255, 255, 255, 0.8);
			}
		}
		
		.progress-bar-wrap {
			.progress-bar {
				height: 8rpx;
				background: rgba(255, 255, 255, 0.3);
				border-radius: 4rpx;
				overflow: hidden;
				
				.progress-fill {
					height: 100%;
					background: #FFFFFF;
					border-radius: 4rpx;
					transition: width 0.3s ease;
				}
			}
		}
	}
}

// 成长卡片
.growth-card {
	position: relative;
	border-radius: 28rpx;
	overflow: hidden;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
	
	.growth-bg {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: $card-bg;
		backdrop-filter: blur($glass-blur);
		z-index: 0;
	}
	
	.growth-content {
		position: relative;
		z-index: 1;
		padding: 30rpx;
	}
	
	.growth-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 24rpx;
		
		.section-title {
			font-size: 30rpx;
			font-weight: 700;
			color: $text-primary;
		}
		
		.growth-more {
			font-size: 24rpx;
			color: $primary-green;
		}
	}
	
	.growth-body {
		.level-progress {
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20rpx 0;
			
			.level-item {
				display: flex;
				flex-direction: column;
				align-items: center;
				gap: 8rpx;
				opacity: 0.4;
				transition: opacity 0.3s;
				
				&.current {
					opacity: 1;
				}
				
				.level-emoji {
					font-size: 48rpx;
				}
				
				.level-name {
					font-size: 22rpx;
					color: $text-secondary;
					white-space: nowrap;
				}
			}
			
			.level-line {
				flex: 1;
				max-width: 60rpx;
				height: 3rpx;
				background: #E5E5EA;
				margin: 0 10rpx;
				margin-bottom: 30rpx;
				
				&.active {
					background: $primary-green;
				}
			}
		}
		
		.stats-row {
			display: flex;
			align-items: center;
			justify-content: space-around;
			padding: 20rpx 0 10rpx;
			border-top: 1rpx solid rgba(0, 0, 0, 0.06);
			
			.stat-item {
				display: flex;
				flex-direction: column;
				align-items: center;
				flex: 1;
				
				.stat-value {
					font-size: 32rpx;
					font-weight: 700;
					color: $text-primary;
				}
				
				.stat-label {
					font-size: 22rpx;
					color: $text-secondary;
					margin-top: 4rpx;
				}
			}
			
			.stat-divider {
				width: 1rpx;
				height: 40rpx;
				background: #E5E5EA;
			}
		}
	}
}

// 今日真分专区
.zone-section {
	.zone-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 20rpx;
	}
	
	.zone-card {
		padding: 28rpx 24rpx;
		text-align: center;
		
		.zone-icon {
			font-size: 56rpx;
			display: block;
			margin-bottom: 12rpx;
		}
		
		.zone-name {
			font-size: 28rpx;
			font-weight: 600;
			color: $text-primary;
			display: block;
		}
		
		.zone-desc {
			font-size: 22rpx;
			color: $text-secondary;
			margin-top: 6rpx;
			display: block;
		}
	}
}

// 分类导航
.category-section {
	margin-top: 30rpx;
	
	.category-scroll {
		white-space: nowrap;
		
		.category-tabs {
			display: inline-flex;
			gap: 16rpx;
			padding: 4rpx 0;
		}
		
		.cat-tab {
			display: inline-flex;
			flex-direction: column;
			align-items: center;
			gap: 8rpx;
			padding: 16rpx 28rpx;
			border-radius: 20rpx;
			background: rgba(255, 255, 255, 0.7);
			backdrop-filter: blur(10rpx);
			transition: all 0.2s;
			
			&.active {
				background: $primary-green;
				box-shadow: 0 4rpx 16rpx rgba(52, 199, 89, 0.3);
				
				.cat-name {
					color: #FFFFFF;
				}
			}
			
			.cat-icon {
				font-size: 36rpx;
			}
			
			.cat-name {
				font-size: 24rpx;
				color: $text-secondary;
				white-space: nowrap;
			}
		}
	}
}

// 活动卡片
.activities-section {
	.activity-card {
		margin-bottom: 24rpx;
		border-radius: 24rpx;
		overflow: hidden;
		
		.activity-image-wrap {
			position: relative;
			
			.activity-overlay {
				position: absolute;
				left: 24rpx;
				bottom: 24rpx;
				display: flex;
				align-items: center;
				gap: 12rpx;
				background: rgba(0, 0, 0, 0.4);
				backdrop-filter: blur(8rpx);
				padding: 8rpx 16rpx 8rpx 8rpx;
				border-radius: 20rpx;
				
				.avatar-stack {
					position: relative;
					width: 60rpx;
					height: 36rpx;
					
					.stack-avatar {
						position: absolute;
						width: 36rpx;
						height: 36rpx;
						border-radius: 50%;
						border: 2rpx solid #FFFFFF;
					}
				}
				
				.participants-count {
					font-size: 22rpx;
					color: #FFFFFF;
				}
			}
			
			.point-badge {
				position: absolute;
				top: 20rpx;
				right: 20rpx;
				background: linear-gradient(135deg, #FF9500 0%, #FF6B00 100%);
				padding: 6rpx 16rpx;
				border-radius: 12rpx;
				box-shadow: 0 2rpx 8rpx rgba(255, 149, 0, 0.4);
				
				.point-text {
					font-size: 22rpx;
					font-weight: 600;
					color: #FFFFFF;
				}
			}
		}
		
		.activity-info {
			padding: 24rpx;
			
			.activity-name {
				font-size: 30rpx;
				font-weight: 600;
				color: $text-primary;
				display: block;
			}
			
			.activity-meta {
				display: flex;
				gap: 24rpx;
				margin-top: 12rpx;
				
				.meta-item {
					display: flex;
					align-items: center;
					gap: 6rpx;
					
					.meta-text {
						font-size: 22rpx;
						color: $text-secondary;
					}
				}
			}
			
			.activity-footer {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-top: 16rpx;
				
				.challenge-tag {
					background: rgba(52, 199, 89, 0.12);
					padding: 4rpx 14rpx;
					border-radius: 10rpx;
					
					.tag-text {
						font-size: 20rpx;
						color: $primary-green;
					}
				}
				
				.badge-preview {
					.badge-icon {
						width: 40rpx;
						height: 40rpx;
						border-radius: 50%;
					}
				}
			}
		}
	}
}

// 空状态
.empty-state {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 80rpx 0;
	
	.empty-emoji {
		font-size: 80rpx;
		margin-bottom: 20rpx;
	}
	
	.empty-text {
		font-size: 26rpx;
		color: $text-secondary;
	}
}

// 加载更多
.load-more {
	text-align: center;
	padding: 30rpx 0;
	
	.load-text {
		font-size: 24rpx;
		color: $text-secondary;
	}
}

// 安全区
.safe-bottom {
	height: 60rpx;
}

// 通用玻璃卡片
.glass-card {
	background: $card-bg;
	backdrop-filter: blur($glass-blur);
	border-radius: 24rpx;
	box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
}
</style>
