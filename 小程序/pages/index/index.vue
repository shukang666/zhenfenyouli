<template>
	<view class="container">
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="index"></skeleton-screen>
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true' :safe-area-inset-bottom="true">
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

			<!-- 问候 + 快捷打卡（浅色卡片，入口指向垃圾分类打卡页） -->
			<view class="hello-card" v-if="isLogin && todayTask.show">
				<view class="hello-card__main" @click="$u.route({type:'switchTab', url:'/pages/user/index'})">
					<view class="hello-card__avatar">
						<u-image :src="userInfo.avatar || '/static/images/default-avatar.png'" width="80" height="80" shape="circle" mode="aspectFill"></u-image>
					</view>
					<view class="hello-card__text">
						<view class="hello-card__greet-row">
							<text class="hello-card__greet">{{ greetingText }}</text>
							<view class="hello-card__sign" :class="{ 'hello-card__sign--done': signStatus.today_signed }"
								hover-class="hello-card__sign--on" @click.stop="handleSign">
								<u-icon :name="signStatus.today_signed ? 'checkbox-mark' : 'calendar'" size="28" color="#ffffff"></u-icon>
								<text class="hello-card__sign-text">{{ signStatus.today_signed ? '已签到' : '签到' }}</text>
								<text v-if="signStatus.continue_days > 0" class="hello-card__sign-days">{{ signStatus.continue_days }}天</text>
							</view>
						</view>
						<view class="hello-card__meta">
							<text class="hello-card__level">{{ userInfo.nickname || '环保新人' }}</text>
							<text class="hello-card__dot">·</text>
							<text>连续打卡 {{ todayTask.streak }} 天</text>
							<text v-if="todayTask.daysToGoal > 0">，再坚持 {{ todayTask.daysToGoal }} 天</text>
						</view>
					</view>
				</view>
				<view class="hello-card__btn" hover-class="hello-card__btn--on" @click.stop="goToClock">
					<text class="hello-card__btn-title">快捷打卡</text>
					<text class="hello-card__btn-sub">垃圾分类</text>
				</view>
			</view>

			<!-- 核心功能入口：一行四个图标（iOS毛玻璃风格） -->
			<view class="entry-grid">
				<view class="entry-item" v-for="item in entry_list" :key="item.key"
					hover-class="entry-item--hover" :hover-stay-time="80" @click="goEntry(item)">
					<view class="entry-icon">
						<image :src="item.svgIcon" class="entry-svg" mode="aspectFit"></image>
					</view>
					<view class="entry-name">{{ item.name }}</view>
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
				// 首页核心入口：一行四个SVG图标
				entry_list: [
					{ key: 'active', name: '社区活动', url: '/pages/low_carbon/low_carbon', type: 'switchTab', svgIcon: 'https://admin.circular-economy.cn/upload/image/1.svg' },
					{ key: 'health', name: '健康测试', url: '/pages/health/health', type: 'switchTab', svgIcon: 'https://admin.circular-economy.cn/upload/image/2.svg' },
					{ key: 'mall', name: '积分商城', url: '/pages/mall/mall', type: 'switchTab', svgIcon: 'https://admin.circular-economy.cn/upload/image/3.svg' },
					{ key: 'community', name: '社区互动', url: '/pages/sjhd/sjhd', type: 'navigateTo', svgIcon: 'https://admin.circular-economy.cn/upload/image/4.svg' }
				],
				// 签到状态
				signStatus: {
					today_signed: false,
					continue_days: 0,
				},
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
				this.getSignStatus()
			}
		},
		onShow() {
			// 更新自定义tabbar选中态
			this.updateTabBarSelected(0)
			// 已登录则刷新用户成长数据
			if (this.isLogin) {
				this.getUserInfoForGrowth()
				this.getSignStatus()
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
		// 并行加载所有数据，全部完成后隐藏骨架屏（最少显示 400ms 防止闪烁）
		async loadAllData() {
			const startTime = Date.now()
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
				// 确保骨架屏最少显示 400ms，防止加载过快导致闪烁
				const elapsed = Date.now() - startTime
				const minDelay = 400
				if (elapsed < minDelay) {
					setTimeout(() => {
						this.isLoading = false
					}, minDelay - elapsed)
				} else {
					this.isLoading = false
				}
			}
		},
		// 拉首页入口配置（后台 home_entries），保留默认 svgIcon（后台未配置图标时使用默认）
		async getHomeEntries() {
			try {
				let res = await this.$u.api.findSetting({ name: 'home_entries' })
				let list = res.data || []
				if (list.length === 0) return
				// 按 key 匹配合并，保留 fallback 的 svgIcon 等字段
				let merged = this.entry_list.map(fb => {
					let cfg = list.find(c => c.key === fb.key)
					if (cfg) {
						// 后台没配 svgIcon 则保留默认
						return Object.assign({}, fb, cfg, { svgIcon: cfg.svgIcon || fb.svgIcon })
					}
					return fb
				})
				// 如果后台配了4个，也要确保每个都有 svgIcon
				if (list.length === 4) {
					merged = list.map(item => {
						let fb = this.entry_list.find(f => f.key === item.key)
						return Object.assign({}, fb || {}, item, { svgIcon: item.svgIcon || (fb && fb.svgIcon) || '' })
					})
				}
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
					this.$u.vuex('userInfo', info)
					let streak = info.clock_days || info.continuous_days || info.clock_count || 0
					this.todayTask.show = true
					this.todayTask.streak = streak
					this.todayTask.progress = Math.min((streak % 7) / 7 * 100, 100)
					this.todayTask.daysToGoal = 7 - (streak % 7)
					if (this.todayTask.daysToGoal === 7) this.todayTask.daysToGoal = 0
				} catch (e) {
					console.log('获取用户信息失败:', e)
				}
			},
			// 快捷打卡：跳转垃圾分类打卡页（与活动页入口同一套学习/考试校验）
			goToClock() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.goClockCheck()
			},
			// 快捷打卡前置校验：学习→考试→打卡（学完垃圾分类指导才可考试，考过才可打卡）
			async goClockCheck() {
				// 先拉取最新用户信息，避免在 flhd/list 学完后返回首页时 is_learn/is_answer 状态过期
				let info = this.userInfo
				try {
					let res = await this.$u.api.getUserInfo()
					info = res.data || {}
					this.$u.vuex('userInfo', info)
				} catch (e) {
					console.log('刷新用户状态失败:', e)
				}
				// 1. 尚未学完全部垃圾分类指导 → 引导学习
				if (!info.is_learn) {
					uni.showModal({
						title: '提示',
						content: '请先学习垃圾分类指导，通过答题考试后可参与活动',
						showCancel: false,
						confirmText: '立即学习',
						confirmColor: '#39BF3E',
						success: (res) => {
							if (res.confirm) this.$u.route('/pages/flhd/list')
						}
					})
					return
				}
				// 2. 已学完但未通过考试 → 引导考试
				if (!info.is_answer) {
					uni.showModal({
						title: '提示',
						content: '学习已完成，请通过垃圾分类答题考试后即可打卡',
						showCancel: false,
						confirmText: '去考试',
						confirmColor: '#39BF3E',
						success: (res) => {
							if (res.confirm) this.$u.route('/pages/answer/examine?id=1')
						}
					})
					return
				}
				// 3. 已通过考试 → 直接打卡
				this.$u.route('/pages/flhd/ljfldk')
			},
			// 获取签到状态
			async getSignStatus() {
				try {
					let res = await this.$u.api.getSignStatus()
					this.signStatus = res.data || { today_signed: false, continue_days: 0 }
				} catch (e) {
					// 接口失败不处理
				}
			},
			// 点击签到
			async handleSign() {
				if (this.signStatus.today_signed) return
				try {
					let res = await this.$u.api.dailySign()
					this.signStatus.today_signed = true
					this.signStatus.continue_days = res.data?.continue_days || this.signStatus.continue_days + 1
					this.$u.toast(`签到成功，获得 ${res.data.point} 积分`)
				} catch (e) {
					this.$u.toast(e.msg || '签到失败')
				}
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

	/* 问候 + 快捷打卡 */
	.hello-card {
		display: flex;
		align-items: center;
		margin: 24rpx 30rpx 0;
		padding: 22rpx 20rpx 22rpx 22rpx;
		background: #ffffff;
		border-radius: 24rpx;
		box-shadow: 0 8rpx 28rpx rgba(24, 175, 56, 0.08);
		box-sizing: border-box;
	}
	.hello-card__main {
		display: flex;
		align-items: center;
		flex: 1;
		min-width: 0;
	}
	.hello-card__avatar {
		flex-shrink: 0;
		margin-right: 18rpx;
		border-radius: 50%;
		overflow: hidden;
		box-shadow: 0 0 0 3rpx rgba(24, 175, 56, 0.12);
	}
	.hello-card__text {
		flex: 1;
		min-width: 0;
	}
	.hello-card__greet-row {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.hello-card__greet {
		flex: 1;
		font-size: 30rpx;
		font-weight: 700;
		color: #1c1c1e;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		margin-right: 12rpx;
	}
	.hello-card__sign {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		padding: 8rpx 18rpx;
		background: linear-gradient(145deg, #18af38 0%, #2ec45a 100%);
		border-radius: 32rpx;
		box-shadow: 0 4rpx 12rpx rgba(24, 175, 56, 0.3);
	}
	.hello-card__sign--done {
		background: linear-gradient(145deg, #b8b8b8 0%, #999999 100%);
		box-shadow: 0 2rpx 8rpx rgba(153, 153, 153, 0.2);
	}
	.hello-card__sign--on {
		transform: scale(0.92);
		opacity: 0.85;
	}
	.hello-card__sign-text {
		margin-left: 6rpx;
		font-size: 22rpx;
		font-weight: 600;
		color: #ffffff;
		line-height: 1.2;
	}
	.hello-card__sign-days {
		margin-left: 6rpx;
		font-size: 20rpx;
		color: rgba(255, 255, 255, 0.85);
		line-height: 1.2;
	}
	.hello-card__meta {
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #8e8e93;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.hello-card__level {
		color: #18af38;
		font-weight: 600;
	}
	.hello-card__dot {
		margin: 0 6rpx;
		color: #c7c7cc;
	}
	.hello-card__btn {
		flex-shrink: 0;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		margin-left: 16rpx;
		padding: 14rpx 22rpx;
		min-width: 140rpx;
		background: linear-gradient(145deg, #2ec45a 0%, #18af38 100%);
		border-radius: 20rpx;
		box-shadow: 0 6rpx 16rpx rgba(24, 175, 56, 0.28);
	}
	.hello-card__btn--on {
		opacity: 0.88;
		transform: scale(0.97);
	}
	.hello-card__btn-title {
		font-size: 24rpx;
		font-weight: 700;
		color: #ffffff;
		line-height: 1.2;
	}
	.hello-card__btn-sub {
		margin-top: 4rpx;
		font-size: 18rpx;
		color: rgba(255, 255, 255, 0.82);
		line-height: 1.2;
	}

	/* 核心功能入口：一行四个图标（iOS毛玻璃风格） */
	.entry-grid {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 24rpx 30rpx 0;
	}
	.entry-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		flex: 1;
		padding: 24rpx 0;
		margin: 0 8rpx;
		background: rgba(255, 255, 255, 0.72);
		border-radius: 28rpx;
		/* iOS毛玻璃效果 */
		backdrop-filter: blur(20rpx);
		-webkit-backdrop-filter: blur(20rpx);
		box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.06), inset 0 1rpx 0 rgba(255, 255, 255, 0.8);
		box-sizing: border-box;
	}
	.entry-item:first-child {
		margin-left: 0;
	}
	.entry-item:last-child {
		margin-right: 0;
	}
	.entry-item--hover {
		background: rgba(255, 255, 255, 0.5);
		transform: scale(0.96);
	}
	.entry-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 88rpx;
		height: 88rpx;
		border-radius: 24rpx;
		background: rgba(255, 255, 255, 0.6);
		box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.04);
	}
	.entry-svg {
		width: 56rpx;
		height: 56rpx;
	}
	.entry-name {
		margin-top: 16rpx;
		font-size: 26rpx;
		font-weight: 600;
		color: #1a1a1a;
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
	.goods-grid {
		padding-bottom: 160rpx;
	}
</style>
