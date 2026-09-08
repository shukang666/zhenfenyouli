<template>
	<view class="container">
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="index"></skeleton-screen>
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true'>
			<!-- 顶部导航栏：仅展示 logo，无返回按钮 -->
			<template slot="top">
				<u-navbar :is-back="false" title="" title-color="rgba(0,0,0,0)" :background="{background:'#ffffff'}" :border-bottom="false">
					<image src="/static/images/logo.png" class="u-w-320 u-h-88 u-m-l-30" mode="aspectFill"></image>
				</u-navbar>
			</template>

			<!-- Banner 轮播图 -->
			<view class="banner-wrap">
				<u-swiper :list="bannerList" height="340" border-radius="24" @click="adLink" bg-color="rgba(0,0,0,0)"></u-swiper>
			</view>

			<!-- 走马灯通知栏（后台 home_notice 配置，留空则不显示） -->
			<view class="notice-bar-wrap" v-if="homeNotice">
				<u-notice-bar :list="[homeNotice]" :volume-icon="true" :more-icon="false" :close-icon="false" :speed="100"></u-notice-bar>
			</view>

			<!-- ========== 问候+打卡 一体化卡片（仅已登录用户可见·iOS风格） ========== -->
			<view class="hero-card" v-if="isLogin && todayTask.show">
				<!-- 渐变背景层 -->
				<view class="hero-card__bg"></view>
				<!-- 光晕装饰 -->
				<view class="hero-card__glow"></view>

				<view class="hero-card__content">
					<!-- 问候区：头像 + 问候语 + 等级 -->
					<view class="hero-greeting" @click="$u.route({type:'switchTab', url:'/pages/user/user'})">
						<view class="hero-greeting__avatar">
							<u-image :src="userInfo.avatar || '/static/images/default-avatar.png'" width="96" height="96" shape="circle" mode="aspectFill"></u-image>
							<view class="hero-greeting__level">{{ userLevelName }}</view>
						</view>
						<view class="hero-greeting__text">
							<text class="hero-greeting__title">{{ greetingText }}，{{ userInfo.nickname || '环保达人' }}</text>
							<text class="hero-greeting__subtitle">今天想为地球做点什么？</text>
						</view>
						<view class="hero-greeting__arrow">
							<u-icon name="arrow-right" size="24" color="rgba(255,255,255,0.7)"></u-icon>
						</view>
					</view>

					<!-- 细线分隔 -->
					<view class="hero-divider"></view>

					<!-- 打卡进度区 -->
					<view class="hero-progress">
						<view class="hero-progress__header">
							<view class="hero-progress__label">
								<text class="hero-progress__fire">🔥</text>
								<text>连续打卡</text>
							</view>
							<view class="hero-progress__badge">
								<text class="hero-progress__streak">{{ todayTask.streak }}</text>
								<text class="hero-progress__unit">天</text>
							</view>
						</view>
						<!-- 进度条 -->
						<view class="hero-progress__track">
							<view class="hero-progress__fill" :style="{ width: todayTask.progress + '%' }"></view>
							<view class="hero-progress__indicator" :style="{ left: todayTask.progress + '%' }"></view>
						</view>
						<view class="hero-progress__footer">
							<text v-if="todayTask.daysToGoal > 0">再坚持 <text class="hero-progress__highlight">{{ todayTask.daysToGoal }}</text> 天获取额外奖励</text>
							<text class="hero-progress__done" v-else>🎉 已达成周目标！</text>
						</view>
					</view>

					<!-- 打卡按钮 -->
					<view class="hero-btn" hover-class="hero-btn--active" @click="goToClock">
						<view class="hero-btn__inner">
							<u-icon name="checkbox-mark" size="32" color="#18AF38"></u-icon>
							<text>立即打卡</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 核心功能入口：左侧大卡 + 右侧上两小块 + 右下横条 -->
			<view class="entry-grid">
				<!-- 左侧大卡 -->
				<view class="entry-main" hover-class="entry-card--hover" :hover-stay-time="80" @click="goEntry(entry_list[0])">
					<template v-if="entry_list[0].image">
						<image :src="entry_list[0].image" class="entry-img entry-img--lg" mode="aspectFill"></image>
					</template>
					<template v-else>
						<view class="entry-icon entry-icon--lg" :style="{ background: entry_list[0].bg }">
							<u-icon :name="entry_list[0].icon" size="48" :color="entry_list[0].color"></u-icon>
						</view>
						<view class="entry-name entry-name--lg">{{ entry_list[0].name }}</view>
						<view class="entry-desc">{{ entry_list[0].desc }}</view>
					</template>
				</view>
				<!-- 右侧列：上两小块 + 下横条 -->
				<view class="entry-side">
					<view class="entry-side-top">
						<view class="entry-mini" v-for="item in entry_list.slice(1, 3)" :key="item.key"
							hover-class="entry-card--hover" :hover-stay-time="80" @click="goEntry(item)">
							<template v-if="item.image">
								<image :src="item.image" class="entry-img entry-img--mini" mode="aspectFill"></image>
							</template>
							<template v-else>
								<view class="entry-icon" :style="{ background: item.bg }">
									<u-icon :name="item.icon" size="36" :color="item.color"></u-icon>
								</view>
								<view class="entry-name entry-name--sm">{{ item.name }}</view>
							</template>
						</view>
					</view>
					<view class="entry-wide" hover-class="entry-card--hover" :hover-stay-time="80" @click="goEntry(entry_list[3])">
						<template v-if="entry_list[3].image">
							<image :src="entry_list[3].image" class="entry-img entry-img--wide" mode="aspectFill"></image>
						</template>
						<template v-else>
							<view class="entry-icon" :style="{ background: entry_list[3].bg }">
								<u-icon :name="entry_list[3].icon" size="36" :color="entry_list[3].color"></u-icon>
							</view>
							<view class="entry-body">
								<view class="entry-name">{{ entry_list[3].name }}</view>
								<view class="entry-desc">{{ entry_list[3].desc }}</view>
							</view>
						</template>
					</view>
				</view>
			</view>

			<!-- 热门活动模块标题 -->
			<view class="section-header" @click="$u.route({url:'/pages/low_carbon/active-list'})">
				<view class="u-flex u-flex-1">
					<image src="/static/index-5.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
					<view class="section-title">热门活动</view>
					<view class="section-divider">|</view>
					<view class="section-subtitle">共同创造美好生态环境</view>
				</view>
				<view class="section-more">
					<text>更多</text>
					<u-icon name="arrow-right" size="22"></u-icon>
				</view>
			</view>

			<!-- 热门活动横向滚动卡片 -->
			<scroll-view scroll-x class="active-scroll" :show-scrollbar="false">
				<view class="u-flex">
					<view class="active-card" v-for="item in active_list" :key="item.id"
						@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
						<u-image width="540" height="270" :src="item.thumb" border-radius="20rpx 20rpx 0 0"></u-image>
						<view class="active-card-body">
							<view class="active-card-title">{{item.name}}</view>
							<view class="active-card-info">
								<view class="u-flex u-flex-1">
									<u-icon name="clock" size="24"></u-icon>
									<text class="active-card-text">{{item.date | date('mm月dd日')}}</text>
								</view>
								<view class="u-flex u-flex-1 u-m-l-20">
									<u-icon name="map" size="24"></u-icon>
									<text class="active-card-text u-line-1">{{item.address}}</text>
								</view>
							</view>
							<view class="active-card-btn">活动报名</view>
						</view>
					</view>
				</view>
			</scroll-view>

			<!-- 最受欢迎兑换商品模块标题 -->
			<view class="section-header" @click="$u.route({type:'switchTab',url:'/pages/mall/mall'})">
				<view class="u-flex u-flex-1">
					<image src="/static/index-6.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
					<view class="section-title">最受欢迎兑换商品</view>
				</view>
				<view class="section-more">
					<text>更多</text>
					<u-icon name="arrow-right" size="22"></u-icon>
				</view>
			</view>

			<!-- 兑换商品列表：双列卡片网格，宽度与核心入口大卡对齐 -->
			<view class="goods-grid">
				<view class="goods-card" v-for="item in dataList" :key="item.id" @click="$u.route('/pages/mall/details?id='+item.id)">
					<u-image width="100%" height="250" :src="item.thumb" :lazy-load="true" border-radius="20rpx 20rpx 0 0"></u-image>
					<view class="goods-card-body">
						<view class="goods-card-title">{{item.goods_name}}</view>
						<view class="goods-card-sold">已兑换{{item.sell_count}}次</view>
						<view class="goods-card-footer">
							<view class="goods-card-price">
								<text class="price-point" v-if="item.point">{{item.point}}积分</text>
								<text class="price-plus" v-if="Number(item.price) && item.point">+</text>
								<text class="price-main" v-if="Number(item.price)">{{item.price}}元</text>
							</view>
							<view class="goods-card-btn">去兑换</view>
						</view>
					</view>
				</view>
			</view>
		</z-paging>
	</view>
</template>

<script>
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	export default {
		components: {
			SkeletonScreen
		},
		data() {
			return {
				isLoading: true,
				bannerList: [],
				dataList: [],
				active_list: [],
				homeNotice: '',
				// 问候模块数据
				greetingHour: new Date().getHours(),
				userLevelName: '环保新人',
				// 今日打卡任务数据
				todayTask: {
					show: false,
					title: '今日垃圾分类打卡',
					subtitle: '完成家庭分类，共建绿色社区',
					points: 20,
					streak: 0,
					daysToGoal: 2,
					progress: 0
				},
				// 首页核心入口：默认 fallback 数据（后台 home_entries 没配图片时用），接口拉到后会覆盖
				entry_list: [
					{ key: 'active', icon: 'calendar', name: '社区活动', desc: '参加低碳环保活动', color: '#18AF38', bg: 'rgba(24,175,56,0.10)', url: '/pages/low_carbon/low_carbon', type: 'switchTab' },
					{ key: 'health', icon: 'heart', name: '健康测试', desc: '关注身体健康', color: '#2E7CF6', bg: 'rgba(46,124,246,0.10)', url: '/pages/health/health', type: 'switchTab' },
					{ key: 'mall', icon: 'integral', name: '积分商城', desc: '环保好礼兑换', color: '#F0546C', bg: 'rgba(240,84,108,0.10)', url: '/pages/mall/mall', type: 'switchTab' },
					{ key: 'community', icon: 'chat', name: '社区互动', desc: '分享绿色生活', color: '#FF9040', bg: 'rgba(255,144,64,0.12)', url: '/pages/sjhd/sjhd', type: 'navigateTo' }
				],
			}
		},
		computed: {
			// 问候文本计算属性
			greetingText() {
				const hour = this.greetingHour
				if (hour < 6) return '凌晨好'
				if (hour < 12) return '早上好'
				if (hour < 14) return '中午好'
				if (hour < 18) return '下午好'
				if (hour < 22) return '晚上好'
				return '夜深了'
			}
		},
		onShareAppMessage() {
			return {
				path: `/pages/index/index?scene=${this.userInfo.id}`
			}
		},
		onShareTimeline() {
			return {
				path: `/pages/index/index?scene=${this.userInfo.id}`
			}
		},
		onLoad(op) {
			if (op.scene) {
				uni.setStorageSync('vcode', op.scene)
			}
			this.loadAllData()
			// 已登录则拉取用户成长信息（问候模块+打卡卡片）
			if (this.isLogin) {
				this.getUserInfoForGrowth()
			}
		},
		onShow() {
			// 更新自定义tabbar选中态
			this.updateTabBarSelected(0)
			// 已登录则刷新用户成长数据
			if (this.isLogin) {
				this.getUserInfoForGrowth()
			}
			// 已登录但未设置小区 → 强制跳完善信息页（nextTick 避免 z-paging 白屏）
			this.$nextTick(() => {
				if (this.isLogin && !this.userInfo.community_id) {
					uni.navigateTo({
						url: '/pages/user/info',
						fail: () => {}
					})
				}
			})
		},
		methods: {
			// 并行加载所有数据，全部完成后隐藏骨架屏
			async loadAllData() {
				try {
					await Promise.all([
						this.getBannerList(),
						this.get_new_active_list(),
						this.getHomeEntries(),
						this.getHomeNotice()
					])
				} catch (e) {
					console.log('数据加载失败:', e)
				} finally {
					this.isLoading = false
				}
			},
			// 拉首页入口配置（后台 home_entries），有 image 则用图片，没图片保持 fallback icon 样式
			async getHomeEntries() {
				try {
					let res = await this.$u.api.findSetting({ name: 'home_entries' })
					let list = res.data || []
					if (list.length === 0) return
					// 按 key 匹配合并，保留 fallback 的 url/type 等字段
					let merged = this.entry_list.map(fb => {
						let cfg = list.find(c => c.key === fb.key)
						return cfg ? Object.assign({}, fb, cfg) : fb
					})
					// 如果后台配的数量不对（比如没配全4个），还是用接口返回的
					if (list.length === 4) merged = list
					this.entry_list = merged
				} catch (e) {
					// 接口失败不影响，保留 fallback
				}
			},
			// 拉首页走马灯通知（后台 home_notice 配置，留空则不显示）
			async getHomeNotice() {
				try {
					let res = await this.$u.api.findSetting({ name: 'home_notice' })
					let data = res.data || {}
					this.homeNotice = (data.text || '').toString()
				} catch (e) {
					// 接口失败不显示通知栏
				}
			},
			// 核心入口跳转
			goEntry(item) {
				// 社区互动：功能开发中
				if (item.key === 'community') {
					this.$u.toast('该功能开发中')
					return
				}
				// 社区活动：跳转活动页"本月推荐"标签（同 banner 跳转逻辑）
				if (item.key === 'active' && item.url.indexOf('low_carbon') !== -1) {
					uni.setStorageSync('jump_monthly_rec', 1)
					uni.switchTab({ url: item.url.split('?')[0] })
					return
				}
				this.$u.route({ type: item.type, url: item.url })
			},
			// 获取活动列表
			async get_new_active_list() {
				try {
					let params = {
						is_recommendation: 1
					}
					let res = await this.$u.api.get_new_active_list(params)
					this.active_list = res.data?.data || []
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			switchTab(url) {
				uni.switchTab({
					url: url
				})
			},
			// 获取商品列表
			async queryList(page) {
				try {
					let params = {
						page,
						keyword: this.keyword,
						is_recommend: 1
					}
					let res = await this.$u.api.get_goods_list(params)
					this.$refs.paging.complete(res.data.data);
				} catch (e) {
					console.log(e);
					this.$refs.paging.complete([]);
				}
			},
			// 获取轮播图
			async getBannerList() {
				try {
					let params = {
						id: 1
					}
					let res = await this.$u.api.find_slide(params)
					this.bannerList = res.data || []
				} catch (e) {
					console.log(e);
				}
			},
			// 轮播图点击跳转
			adLink(e) {
				let data = this.bannerList[e];
				if (!data.url) return
				let url = data.url
				// 只要是跳转到活动页（low_carbon），就设置标记，让活动页切到"本月推荐"标签
				// switchTab 不支持 query 参数，所以用 storage 传标记
				if (url.indexOf('low_carbon') !== -1) {
					let tabUrl = url.split('?')[0]
					uni.setStorageSync('jump_monthly_rec', 1)
					uni.switchTab({ url: tabUrl })
					return
				}
				uni.navigateTo({
					url: url,
					fail() {
						uni.switchTab({
							url: url
						})
					}
				})
			},
			// 更新自定义tabbar选中索引
			updateTabBarSelected(index) {
				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()
				if (tabBar) {
					tabBar.setSelected(index)
				}
			},
			// 获取用户成长信息（问候模块+打卡卡片用）
			async getUserInfoForGrowth() {
				try {
					let res = await this.$u.api.getUserInfo()
					let info = res.data || {}
					let points = info.point || 0
					// 计算环保等级
					this.userLevelName = this.calculateLevelName(points)
					// 更新今日任务数据
					let streak = info.clock_days || info.continuous_days || 0
					this.todayTask.show = true
					this.todayTask.streak = streak
					this.todayTask.progress = Math.min((streak % 7) / 7 * 100, 100)
					this.todayTask.daysToGoal = 7 - (streak % 7)
					if (this.todayTask.daysToGoal === 7) this.todayTask.daysToGoal = 0
				} catch (e) {
					console.log('获取用户信息失败:', e)
				}
			},
			// 计算环保等级名称
			calculateLevelName(points) {
				if (points >= 1000) return '社区先锋'
				if (points >= 300) return '绿色达人'
				return '环保新人'
			},
			// 跳转到垃圾分类打卡页
			goToClock() {
				uni.navigateTo({
					url: '/pages/low_carbon/clock/clock',
					fail: () => {
						this.$u.toast('打卡功能暂未开放')
					}
				})
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F6F8;
	}

	/* Banner 轮播图容器：左右留白，顶部间距 */
	.banner-wrap {
		padding: 30rpx 30rpx 0;
	}

	/* 走马灯通知栏：左右留白与上下区块对齐 */
	.notice-bar-wrap {
		padding: 20rpx 30rpx 0;
	}

	/* ========== iOS 风格 问候+打卡一体化卡片 ========== */
	.hero-card {
		position: relative;
		margin: 24rpx 30rpx 0;
		border-radius: 32rpx;
		overflow: hidden;
		/* 多层阴影营造 iOS 立体感 */
		box-shadow:
			0 8rpx 32rpx rgba(24, 175, 56, 0.18),
			0 2rpx 8rpx rgba(0, 0, 0, 0.06),
			inset 0 1rpx 0 rgba(255, 255, 255, 0.2);

		&__bg {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			/* iOS 风格柔和渐变 */
			background: linear-gradient(145deg, #0D9F30 0%, #18AF38 35%, #2DC853 70%, #4DCA6B 100%);
			z-index: 0;
		}

		/* 右上角光晕装饰 */
		&__glow {
			position: absolute;
			top: -60rpx;
			right: -40rpx;
			width: 240rpx;
			height: 240rpx;
			background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
			z-index: 1;
			pointer-events: none;
		}

		&__content {
			position: relative;
			z-index: 2;
			padding: 36rpx 32rpx 32rpx;
		}

		/* 问候区 */
		.hero-greeting {
			display: flex;
			align-items: center;

			&__avatar {
				position: relative;
				flex-shrink: 0;
				margin-right: 24rpx;

				::v-deep .u-image {
					border: 3rpx solid rgba(255, 255, 255, 0.35);
					box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.1);
				}
			}

			/* 等级徽章贴在头像右下角 */
			&__level {
				position: absolute;
				bottom: -4rpx;
				left: 50%;
				transform: translateX(-50%);
				padding: 2rpx 12rpx;
				font-size: 18rpx;
				font-weight: 600;
				color: #ffffff;
				background: rgba(255, 255, 255, 0.25);
				backdrop-filter: blur(8px);
				border-radius: 10rpx;
				white-space: nowrap;
				border: 1rpx solid rgba(255, 255, 255, 0.2);
			}

			&__text {
				flex: 1;
				min-width: 0;
			}

			&__title {
				font-size: 34rpx;
				font-weight: 700;
				color: #ffffff;
				letter-spacing: 0.5rpx;
				text-shadow: 0 1rpx 3rpx rgba(0, 0, 0, 0.1);
			}

			&__subtitle {
				display: block;
				margin-top: 6rpx;
				font-size: 24rpx;
				color: rgba(255, 255, 255, 0.82);
				letter-spacing: 0.3rpx;
			}

			&__arrow {
				flex-shrink: 0;
				margin-left: 16rpx;
				opacity: 0.7;
			}
		}

		/* 分隔线 */
		.hero-divider {
			height: 1rpx;
			margin: 28rpx 0;
			background: linear-gradient(
				90deg,
				transparent 0%,
				rgba(255, 255, 255, 0.2) 20%,
				rgba(255, 255, 255, 0.3) 50%,
				rgba(255, 255, 255, 0.2) 80%,
				transparent 100%
			);
		}

		/* 打卡进度区 */
		.hero-progress {
			padding: 0 4rpx;

			&__header {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}

			&__label {
				display: flex;
				align-items: center;
				font-size: 24rpx;
				color: rgba(255, 255, 255, 0.9);
				font-weight: 500;
			}

			&__fire {
				font-size: 24rpx;
				margin-right: 8rpx;
			}

			&__badge {
				display: flex;
				align-items: baseline;
				padding: 4rpx 16rpx;
				background: rgba(255, 255, 255, 0.15);
				border-radius: 20rpx;
				border: 1rpx solid rgba(255, 255, 255, 0.15);
			}

			&__streak {
				font-size: 32rpx;
				font-weight: 700;
				color: #ffffff;
				line-height: 1;
			}

			&__unit {
				margin-left: 4rpx;
				font-size: 20rpx;
				color: rgba(255, 255, 255, 0.7);
			}

			/* 进度条轨道 */
			&__track {
				position: relative;
				margin-top: 16rpx;
				height: 8rpx;
				background: rgba(255, 255, 255, 0.2);
				border-radius: 4rpx;
				overflow: visible;
			}

			/* 进度条填充 */
			&__fill {
				height: 100%;
				background: linear-gradient(90deg, rgba(255, 255, 255, 0.8) 0%, #ffffff 100%);
				border-radius: 4rpx;
				transition: width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
				box-shadow: 0 0 8rpx rgba(255, 255, 255, 0.5);
			}

			/* 进度指示器圆点 */
			&__indicator {
				position: absolute;
				top: 50%;
				transform: translate(-50%, -50%);
				width: 16rpx;
				height: 16rpx;
				background: #ffffff;
				border-radius: 50%;
				box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.15);
				transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
			}

			&__footer {
				margin-top: 14rpx;
				font-size: 22rpx;
				color: rgba(255, 255, 255, 0.75);
				text-align: center;
			}

			&__highlight {
				color: #ffffff;
				font-weight: 700;
				font-size: 26rpx;
			}

			&__done {
				display: block;
				color: #ffffff;
				font-weight: 600;
				font-size: 24rpx;
			}
		}

		/* 打卡按钮 - iOS 风格胶囊按钮 */
		.hero-btn {
			margin-top: 28rpx;
			background: rgba(255, 255, 255, 0.95);
			border-radius: 50rpx;
			box-shadow:
				0 4rpx 16rpx rgba(0, 0, 0, 0.08),
				inset 0 1rpx 0 rgba(255, 255, 255, 0.5);
			transition: all 0.2s ease;

			&:hover,
			&--active {
				transform: scale(0.98);
				background: rgba(255, 255, 255, 0.85);
				box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
			}

			&__inner {
				display: flex;
				align-items: center;
				justify-content: center;
				height: 88rpx;
				font-size: 30rpx;
				font-weight: 600;
				color: #18AF38;
				letter-spacing: 0.5rpx;

				.u-icon {
					margin-right: 8rpx;
				}
			}
		}
	}

	/* 核心功能入口：左侧大卡 + 右侧上两小块 + 右下横条 */
	.entry-grid {
		display: flex;
		align-items: stretch;
		padding: 24rpx 30rpx 0;
	}
	/* 卡片内图片：铺满整张卡，圆角跟随卡片 */
	.entry-img {
		width: 100%;
		height: 100%;
		border-radius: inherit;
	}
	.entry-img--lg, .entry-img--mini, .entry-img--wide {
		border-radius: 20rpx;
	}
	.entry-main {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
		width: 334rpx;
		height: 348rpx;
		padding: 0 20rpx;
		background: #fff;
		border-radius: 20rpx;
		box-sizing: border-box;
	}
	.entry-side {
		flex: 1;
		min-width: 0;
		margin-left: 20rpx;
	}
	.entry-side-top {
		display: flex;
		justify-content: space-between;
	}
	.entry-mini {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		width: 158rpx;
		height: 158rpx;
		background: #fff;
		border-radius: 20rpx;
		box-sizing: border-box;
	}
	.entry-wide {
		display: flex;
		align-items: center;
		height: 170rpx;
		margin-top: 20rpx;
		padding: 0 24rpx;
		background: #fff;
		border-radius: 20rpx;
		box-sizing: border-box;
	}
	.entry-card--hover {
		background: #f7f9fa;
	}
	.entry-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 76rpx;
		height: 76rpx;
		border-radius: 24rpx;
		flex-shrink: 0;
	}
	.entry-icon--lg {
		width: 96rpx;
		height: 96rpx;
		border-radius: 28rpx;
	}
	.entry-body {
		flex: 1;
		min-width: 0;
		margin-left: 18rpx;
	}
	.entry-name {
		font-size: 29rpx;
		font-weight: 600;
		color: #1a1a1a;
	}
	.entry-name--lg {
		margin-top: 20rpx;
		font-size: 32rpx;
	}
	.entry-name--sm {
		margin-top: 14rpx;
		font-size: 26rpx;
	}
	.entry-desc {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: #9aa0a8;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
	}

	/* 模块标题：图标 + 标题 + 分隔线 + 副标题 + 更多 */
	.section-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 40rpx 30rpx 24rpx;
	}
	.section-title {
		margin-left: 16rpx;
		font-size: 32rpx;
		font-weight: bold;
		color: #1a1a1a;
	}
	.section-divider {
		margin: 0 20rpx;
		color: #ddd;
	}
	.section-subtitle {
		font-size: 22rpx;
		color: #999;
	}
	.section-more {
		display: flex;
		align-items: center;
		font-size: 24rpx;
		color: #999;
	}

	/* 热门活动横向滚动卡片 */
	.active-scroll {
		white-space: nowrap;
		padding-left: 30rpx;
	}
	.active-card {
		flex-shrink: 0;
		width: 540rpx;
		margin-right: 24rpx;
		background: #fff;
		border-radius: 20rpx;
		overflow: hidden;
		box-shadow: 0 6rpx 28rpx rgba(0, 0, 0, 0.07);
	}
	.active-card-body {
		padding: 24rpx;
	}
	.active-card-title {
		font-size: 30rpx;
		font-weight: bold;
		color: #1a1a1a;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.active-card-info {
		display: flex;
		align-items: center;
		margin-top: 16rpx;
		font-size: 24rpx;
		color: #999;
	}
	.active-card-text {
		margin-left: 8rpx;
	}
	/* 活动报名按钮：渐变绿色，呼应品牌色 */
	.active-card-btn {
		margin-top: 20rpx;
		background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
		color: #fff;
		text-align: center;
		padding: 18rpx 0;
		border-radius: 12rpx;
		font-size: 26rpx;
		font-weight: bold;
	}

	/* 兑换商品列表：双列卡片网格 */
	.goods-grid {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		padding: 0 30rpx;
	}
	.goods-card {
		width: 334rpx;
		margin-bottom: 24rpx;
		background: #fff;
		border-radius: 20rpx;
		overflow: hidden;
	}
	.goods-card-body {
		padding: 20rpx 20rpx 24rpx;
	}
	/* 商品标题：固定两行高度，保证卡片底部对齐 */
	.goods-card-title {
		height: 76rpx;
		font-size: 28rpx;
		font-weight: 600;
		line-height: 38rpx;
		color: #1a1a1a;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
	}
	.goods-card-sold {
		margin-top: 10rpx;
		font-size: 22rpx;
		color: #b3b9c2;
	}
	.goods-card-footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-top: 16rpx;
	}
	/* 商品价格：积分为主、金额为辅，绿色突出 */
	.goods-card-price {
		display: flex;
		align-items: baseline;
		min-width: 0;
		white-space: nowrap;
		overflow: hidden;
	}
	.price-point {
		font-size: 30rpx;
		font-weight: bold;
		color: #18AF38;
	}
	.price-plus {
		margin: 0 4rpx;
		font-size: 24rpx;
		font-weight: bold;
		color: #18AF38;
	}
	.price-main {
		font-size: 24rpx;
		font-weight: bold;
		color: #18AF38;
	}
	/* 去兑换按钮：渐变绿胶囊，呼应活动报名按钮 */
	.goods-card-btn {
		flex-shrink: 0;
		height: 52rpx;
		line-height: 52rpx;
		padding: 0 22rpx;
		margin-left: 12rpx;
		background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
		color: #fff;
		font-size: 24rpx;
		font-weight: 600;
		text-align: center;
		border-radius: 26rpx;
	}
</style>
