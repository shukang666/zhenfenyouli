<template>
	<view class="container" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="index"></skeleton-screen>
		
		<!-- 关怀版下拉提示 -->
		<view class="care-pull-layer" :style="{ height: carePullHeight + 'px', opacity: careHintOpacity }" v-if="showCareHint">
			<view class="care-pull-content">
<text class="care-pull-text">{{ carePullHeight >= 90 ? '松开进入关怀版' : '下拉进入关怀版' }}</text>
			<u-icon :name="carePullHeight >= 90 ? 'arrow-up' : 'arrow-down'" size="28" color="#18AF38"></u-icon>
			</view>
		</view>
		<z-paging ref='paging' v-model='dataList' @query='queryList' @scroll="onZPagingScroll" :use-page-scroll="true" :fixed='true' :safe-area-inset-bottom="true" :auto="false" :empty-view-enabled="currentTab === 1" :loading-more-enabled="false" :refresher-enabled="false">
			<!-- 顶部导航栏：仅展示 logo，无返回按钮 -->
			<template slot="top">
				<u-navbar :is-back="false" title="" title-color="rgba(0,0,0,0)" :background="{background:'#ffffff'}" :border-bottom="false">
					<image src="https://admin.circular-economy.cn/static/images/logo.png" class="u-w-320 u-h-88 u-m-l-30" mode="aspectFill"></image>
				</u-navbar>
			</template>

			<!-- 活动搜索 + 扫码入口（点击进活动搜索页，右侧扫码直达活动详情） -->
			<view id="guide-search" class="home-search-wrap">
				<view class="home-search-box" @click="$u.route('/pages/activity/active-search')">
					<u-icon name="search" size="28" color="#b0b3bb"></u-icon>
					<text class="home-search-text">搜索活动名称 / 地点</text>
					<view class="home-search-scan" @click.stop="onScanQrcode">
						<u-icon name="scan" size="36" color="#8e8e93"></u-icon>
					</view>
				</view>
			</view>

			<!-- Banner 轮播图 -->
			<view class="banner-wrap">
				<u-swiper :list="bannerList" height="340" border-radius="24" @click="adLink" bg-color="rgba(0,0,0,0)"></u-swiper>
			</view>

			<!-- 走马灯通知栏（后台 home_notice 配置，留空则不显示） -->
			<view class="notice-bar-wrap" v-if="homeNotice">
				<u-notice-bar :list="[homeNotice]" :volume-icon="true" :more-icon="false" :close-icon="false" :speed="100"></u-notice-bar>
			</view>

			<!-- 问候 + 快捷打卡（浅色卡片，入口指向垃圾分类打卡页） -->
			<view id="guide-hello" class="hello-card" v-if="isLogin && todayTask.show">
				<view class="hello-card__main" @click="$u.route({type:'switchTab', url:'/pages/user/index'})">
					<view class="hello-card__avatar">
						<u-image :src="userInfo.avatar || 'https://admin.circular-economy.cn/static/images/default-avatar.png'" width="80" height="80" shape="circle" mode="aspectFill"></u-image>
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
			<!-- <view class="entry-grid">
				<view class="entry-item" v-for="item in entry_list" :key="item.key"
					hover-class="entry-item--hover" :hover-stay-time="80" @click="goEntry(item)">
					<view class="entry-icon">
						<image :src="item.svgIcon" class="entry-svg" mode="aspectFit"></image>
					</view>
					<view class="entry-name">{{ item.name }}</view>
				</view>
			</view> -->

			<!-- 内容区标签导航：把活动与积分商品合并到同一区块，通过 tab 切换 -->
			<view id="guide-tabs" class="content-tabs">
				<view class="tab-list">
					<view class="tab-item" :class="{'tab-item--active': currentTab === index}" v-for="(item, index) in tabList" :key="index" @click="switchContentTab(index)">
						<text class="tab-item__text">{{ item.name }}</text>
						<view class="tab-item__line" v-if="currentTab === index"></view>
					</view>
				</view>
				<view class="tab-more" @click="goMore">
					<text>更多</text>
					<u-icon name="arrow-right" size="22" color="#999"></u-icon>
				</view>
			</view>

			<!-- 热门活动横向滚动卡片 -->
			<scroll-view scroll-x class="active-scroll" :show-scrollbar="false" v-if="currentTab === 0">
				<view class="u-flex">
					<view class="active-card" v-for="item in active_list" :key="item.id"
						@click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)">
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

			<!-- 兑换商品列表：两行横向滚动（每列 2 个商品，横向滚动） -->
			<scroll-view scroll-x class="goods-scroll" :show-scrollbar="false" v-if="currentTab === 1">
				<view class="goods-cols">
					<view class="goods-col" v-for="(col, colIndex) in goodsColumns" :key="colIndex">
						<view class="goods-card" v-for="item in col" :key="item.id" @click="$u.route('/pages/shop/details?id='+item.id)">
							<u-image width="100%" height="220" :src="item.thumb" :lazy-load="true" border-radius="20rpx 20rpx 0 0"></u-image>
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
				</view>
			</scroll-view>
		</z-paging>

		<!-- 四块 cover-view 挖洞：框内是真实界面，框外才是灰罩 -->
		<block v-if="showGuide && guideHole">
			<cover-view :style="guideDimCss(0, 0, guideWinW, guideHole.top)"></cover-view>
			<cover-view :style="guideDimCss(0, guideHole.bottom, guideWinW, guideHole.bottomRest)"></cover-view>
			<cover-view :style="guideDimCss(0, guideHole.top, guideHole.left, guideHole.height)"></cover-view>
			<cover-view :style="guideDimCss(guideHole.right, guideHole.top, guideHole.rightRest, guideHole.height)"></cover-view>
			<cover-view :style="guideSpotCss"></cover-view>
		</block>
		<cover-view
			v-else-if="showGuide"
			style="position:fixed;left:0;top:0;right:0;bottom:0;width:100%;height:100%;background:rgba(0,0,0,0.72);z-index:999998;"
		></cover-view>
		<view
			v-if="showGuide"
			class="guide-root"
			style="position:fixed;left:0;top:0;right:0;bottom:0;width:100%;height:100%;z-index:999999;"
			@touchmove.stop.prevent="noop"
		>
			<new-user-guide
				:steps="playingGuideSteps"
				:target-rect="guideTargetRect"
				:card-top="guideCardTop"
				:place-below="guideCardPlaceBelow"
				:card-wrap-css="guideCardWrapCss"
				@close="onGuideClose"
				@query-target="onGuideQueryTarget"
			></new-user-guide>
		</view>

		<!-- 新手引导加载期间透明遮罩：防止等待首页数据时用户切页或点击 -->
		<view v-if="guideLoading" class="guide-loading-mask" @tap.stop.prevent="noop" @touchmove.stop.prevent="noop"></view>

		<!-- 对齐 custom-tab-bar 悬浮条（108rpx 高 + 左右 24rpx + 底部安全区），仅用于引导定位 -->
		<view id="guide-tabbar" class="guide-tabbar-area"></view>
	</view>
</template>

<script>
import SkeletonScreen from '@/components/skeleton-screen/index.vue'
import NewUserGuide from '@/components/new-user-guide/new-user-guide.vue'
import careGesture from '@/common/care-gesture.mixin.js'
export default {
	components: {
		SkeletonScreen,
		NewUserGuide
	},
		mixins: [careGesture],
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
				// 内容区 tab 导航：活动 / 积分商品
				currentTab: 0,
				tabList: [
					{ name: '热门活动', moreUrl: '/pages/activity/active-list', moreType: 'navigateTo' },
					{ name: '积分商品', moreUrl: '/pages/mall/mall', moreType: 'switchTab' }
				],
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
				// 新手引导：登录成功后首页显示，引导用户了解核心功能
				showGuide: false,
				guideLoading: false,
				playingGuideSteps: [],
				guideTargetRect: null,
				guideSteps: [
					{
						selector: '',
						title: '欢迎来到绿色社区',
						desc: '这里可以学分类、每日打卡攒积分、报名小区活动。下面用几步带您认一认。'
					},
					{
						selector: '#guide-search',
						prefer: 'below',
						title: '找活动和扫码',
						desc: '点搜索框能找小区活动。右边扫一扫，可以参加现场活动。'
					},
					{
						selector: '#guide-hello',
						prefer: 'below',
						title: '签到和分类打卡',
						desc: '每天先点「签到」。再点「快捷打卡」，拍一张分类照片就能得积分。'
					},
					{
						selector: '#guide-tabs',
						prefer: 'above',
						title: '活动和积分商品',
						desc: '点「热门活动」看报名，点「积分商品」用积分兑换。字大、好认。'
					},
					{
						selector: '#guide-tabbar',
						prefer: 'above',
						title: '底部五个按钮',
						desc: '首页、活动、健康、积分权益、我的。随时点，不用记路径。'
					}
				]
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
			},
			// 商品按每列 2 个分组，用于两行横向滚动
			goodsColumns() {
				const cols = []
				const list = this.dataList || []
				for (let i = 0; i < list.length; i += 2) {
					cols.push(list.slice(i, i + 2))
				}
				return cols
			},
			guideWinW() {
				return uni.getSystemInfoSync().windowWidth
			},
			guideHole() {
				const rect = this.guideTargetRect
				if (!rect) return null
				const sys = uni.getSystemInfoSync()
				const winW = sys.windowWidth
				const winH = sys.windowHeight
				const pad = 8
				const left = Math.max(0, rect.left - pad)
				const top = Math.max(0, rect.top - pad)
				const right = Math.min(winW, rect.left + rect.width + pad)
				const bottom = Math.min(winH, rect.top + rect.height + pad)
				const width = right - left
				const height = bottom - top
				if (width < 20 || height < 16 || height > winH * 0.5) return null
				return {
					left,
					top,
					right,
					bottom,
					width,
					height,
					rightRest: Math.max(0, winW - right),
					bottomRest: Math.max(0, winH - bottom)
				}
			},
			guideSpotCss() {
				const h = this.guideHole
				if (!h) return ''
				return `position:fixed;left:${h.left}px;top:${h.top}px;width:${h.width}px;height:${h.height}px;border:3px solid #18AF38;border-radius:12px;box-sizing:border-box;background:transparent;z-index:999998;`
			},
			guideSafeTop() {
				const sys = uni.getSystemInfoSync()
				// 自定义导航栏：状态栏 + 导航内容约 44px，再留空隙，避免盖住 logo / 胶囊
				let top = (sys.statusBarHeight || 20) + 44 + 16
				try {
					const menu = uni.getMenuButtonBoundingClientRect()
					if (menu && menu.bottom) top = Math.max(top, Math.ceil(menu.bottom) + 16)
				} catch (e) {}
				return top
			},
			guideSafeBottom() {
				const sys = uni.getSystemInfoSync()
				const tabbar = 108 * sys.windowWidth / 750 + 8
				const inset = (sys.safeAreaInsets && sys.safeAreaInsets.bottom) || 0
				return Math.ceil(tabbar + inset + 20)
			},
			guideCardTop() {
				const sys = uni.getSystemInfoSync()
				const winH = sys.windowHeight
				const cardH = 230
				const gap = 18
				const minTop = this.guideSafeTop
				const maxTop = winH - this.guideSafeBottom - cardH
				const hole = this.guideHole
				if (!hole || maxTop < minTop) {
					return Math.max(minTop, Math.min(maxTop, Math.round((winH - cardH) / 2)))
				}
				const belowTop = hole.bottom + gap
				const aboveTop = hole.top - gap - cardH
				const belowOk = belowTop <= maxTop
				const aboveOk = aboveTop >= minTop
				// 高亮在上半屏：文案优先放下面，避开自定义导航；在下半屏：优先放上面，避开 tabbar
				const holeCenter = (hole.top + hole.bottom) / 2
				const preferBelow = holeCenter < winH * 0.58
				if (preferBelow && belowOk) return belowTop
				if (!preferBelow && aboveOk) return aboveTop
				if (belowOk) return belowTop
				if (aboveOk) return aboveTop
				return Math.max(minTop, Math.min(maxTop, preferBelow ? belowTop : aboveTop))
			},
			guideCardPlaceBelow() {
				const hole = this.guideHole
				if (!hole) return true
				return this.guideCardTop >= hole.bottom - 8
			},
			guideCardWrapCss() {
				return `position:absolute;left:40rpx;right:40rpx;top:${this.guideCardTop}px;z-index:100054;transform:none;`
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
			// 检测新手引导标记（登录后由 login 页 setStorageSync 设置），检测到后启动引导并清除标记
			this.checkAndStartGuide()
			this.$nextTick(() => {
				if (this.isLogin && !this.userInfo.community_id) {
					uni.navigateTo({
						url: '/pages/member/info',
						fail: () => {}
					})
				}
			})
		},
		onUnload() {
			if (this._guideWaitTimer) {
				clearTimeout(this._guideWaitTimer)
				this._guideWaitTimer = null
			}
		},
		methods: {
		// 检测并启动新手引导（仅未完成过、已绑定小区；标记在关闭时再清，避免等渲染时被打断丢失）
		checkAndStartGuide() {
			if (this._guideLock || this.showGuide || this.guideLoading) return
			if (uni.getStorageSync('has_finished_guide')) return
			const guideFlag = uni.getStorageSync('new_user_guide')
			if (!guideFlag || !this.userInfo.community_id) return
			this._guideLock = true
			this.guideLoading = true
			this.waitAndStartGuide()
		},
		waitAndStartGuide() {
			const started = Date.now()
			const poll = () => {
				if (!this.guideLoading || this.showGuide) return
				const pageReady = !this.isLoading
				const helloReady = this.todayTask.show || Date.now() - started > 2200
				if ((pageReady && helloReady) || Date.now() - started > 4000) {
					this.playingGuideSteps = (this.guideSteps || []).filter(step => {
						if (step.selector === '#guide-hello' && !this.todayTask.show) return false
						return true
					})
					this.showGuide = true
					this.guideLoading = false
					return
				}
				this._guideWaitTimer = setTimeout(poll, 120)
			}
			this.$nextTick(poll)
		},
		onGuideClose() {
			this.guideLoading = false
			this.showGuide = false
			this.guideTargetRect = null
			uni.setStorageSync('has_finished_guide', '1')
			uni.removeStorageSync('new_user_guide')
		},
		onGuideQueryTarget(selector) {
			if (!selector) {
				this.guideTargetRect = null
				return
			}
			this.$nextTick(() => {
				uni.createSelectorQuery().in(this).select(selector).boundingClientRect(rect => {
					if (rect && rect.width > 20 && rect.height > 16) {
						this.guideTargetRect = {
							left: rect.left,
							top: rect.top,
							width: rect.width,
							height: rect.height
						}
					} else {
						this.guideTargetRect = null
					}
				}).exec()
			})
		},
		guideDimCss(left, top, width, height) {
			const w = Math.max(0, width)
			const h = Math.max(0, height)
			return `position:fixed;left:${left}px;top:${top}px;width:${w}px;height:${h}px;background:rgba(0,0,0,0.72);z-index:999998;`
		},
		noop() {},
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
			// 切换内容区 tab
			switchContentTab(index) {
				if (this.currentTab === index) return
				this.currentTab = index
				// 首次切换到积分商品时触发分页加载
				if (index === 1 && this.dataList.length === 0) {
					this.$nextTick(() => {
						this.$refs.paging.reload()
					})
				}
			},
			// 当前 tab 的"更多"跳转
			goMore() {
				const tab = this.tabList[this.currentTab]
				this.$u.route({ type: tab.moreType, url: tab.moreUrl })
			},
			// 核心入口跳转
			goEntry(item) {
				// 社区互动：功能开发中
				if (item.key === 'community') {
					this.$u.toast('该功能开发中')
					return
				}
				// 社区活动：跳转活动页（同 banner 跳转逻辑）
				if (item.key === 'active' && item.url.indexOf('low_carbon') !== -1) {
					uni.setStorageSync('jump_monthly_rec', 1)
					uni.switchTab({ url: item.url.split('?')[0] })
					return
				}
				this.$u.route({ type: item.type, url: item.url })
			},
			// 获取活动列表（首页"热门活动"取后台"首页展示"is_put，最多 12 条）
			async get_new_active_list() {
				try {
					let params = {
						is_put: 1,
						page_size: 12
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
			// 获取商品列表（首页"积分商品"不限制推荐，加载 12 条）
			async queryList(page) {
				try {
					let params = {
						page,
						page_size: 12,
						keyword: this.keyword
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
				// 只要是跳转到活动页（low_carbon），就设置标记
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
			// 点击搜索框右侧扫码图标：拉起系统扫码，识别活动小程序码/二维码后直达活动详情
			onScanQrcode() {
				// #ifdef H5
				// 浏览器环境没有系统扫码能力，扫码仅支持在微信小程序端调用
				this.$u.toast('扫码需在微信小程序内使用')
				// #endif
				// #ifndef H5
				uni.scanCode({
					onlyFromCamera: false,
					success: (res) => {
					// 扫码成功。后台生成的活动码是"小程序码"(getwxacodeunlimit)，
					// 扫它时活动id不在 res.result，而在 res.path(页面路径?scene=活动id)；
					// 普通二维码(内容为活动id/含scene或id参数)则在 res.result。
					const scanType = (res && res.scanType) ? String(res.scanType) : ''
					const qPath = (res && res.path) ? String(res.path).trim() : ''
					const raw = (res && res.result != null) ? String(res.result).trim() : ''
					console.log('scanCode =>', { scanType: scanType, path: qPath, result: raw })

					// 1) 小程序码：兼容新旧两种路径
					//    新路径 pages/activity/lsxf/details-new?scene=活动id（当前 pages.json 注册路径）
					//    旧路径 pages/low_carbon/lsxf/details-new?scene=活动id（历史生成的活动码）
					//    只接受本项目活动详情页路径，防止误扫到其它页面/其它小程序的码
					const pathMatch = qPath.match(/^pages\/(?:activity|low_carbon)\/lsxf\/details-new/)
					if (pathMatch) {
						// 优先从 path 里解析 scene 参数，统一跳转到新路径
						const sceneMatch = qPath.match(/[?&]scene=(\d+)/)
						if (sceneMatch) {
							this.$u.route('/pages/activity/lsxf/details-new?id=' + sceneMatch[1])
							return
						}
						// 没有 scene 参数则直接跳转（兜底，理论上不会到这里）
						const full = qPath.indexOf('/') === 0 ? qPath : '/' + qPath
						this.$u.route(full)
						return
					}
					// 2) 兜底解析二维码内容里的活动id：纯数字 / scene=xx / id=xx
					const m = raw.match(/(?:scene|id)=(\d+)/)
					const activeId = /^\d+$/.test(raw) ? raw : (m ? m[1] : '')
					if (activeId) {
						this.$u.route('/pages/activity/lsxf/details-new?id=' + activeId)
						return
					}
					this.$u.toast('未识别到活动二维码')
					},
					fail: (err) => {
						// 用户手动取消扫码时静默处理，其它异常打印便于排查
						const msg = (err && err.errMsg) || ''
						if (msg.indexOf('cancel') === -1) console.log('scanCode fail =>', msg)
					}
				})
				// #endif
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

	/* 关怀版下拉提示层 */
	.care-pull-layer {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		background: linear-gradient(180deg, #E8F5EE 0%, rgba(232, 245, 238, 0.9) 100%);
		display: flex;
		align-items: flex-end;
		justify-content: center;
		padding-bottom: 16rpx;
		z-index: 9998;
		pointer-events: none;
		
		.care-pull-content {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 8rpx;
			
			.care-pull-text {
				font-size: 28rpx;
				color: #18AF38;
				font-weight: 500;
			}
		}
	}

	/* 活动搜索 + 扫码入口：与下方 Banner 同宽（左右 30rpx 留白）、同圆角(24rpx) */
	.home-search-wrap {
		padding: 24rpx 30rpx 0;
	}

	.home-search-box {
		display: flex;
		align-items: center;
		height: 72rpx;
		padding: 0 24rpx;
		background: #ffffff;
		border-radius: 24rpx; /* 与 banner 圆角一致 */
		box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
	}

	.home-search-text {
		flex: 1;
		margin-left: 12rpx;
		font-size: 26rpx;
		color: #b0b3bb;
	}

	.home-search-scan {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 60rpx;
		height: 60rpx;
		margin-left: 8rpx;
		padding-left: 20rpx;
		border-left: 1rpx solid #eeeeee;
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

	/* 内容区标签导航：活动 / 积分商品 */
	.content-tabs {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 40rpx 30rpx 24rpx;
	}
	.tab-list {
		display: flex;
		align-items: center;
	}
	.tab-item {
		position: relative;
		margin-right: 40rpx;
		padding-bottom: 12rpx;
	}
	.tab-item__text {
		font-size: 30rpx;
		color: #666;
		font-weight: 500;
	}
	.tab-item--active .tab-item__text {
		color: #1a1a1a;
		font-weight: bold;
	}
	.tab-item__line {
		position: absolute;
		bottom: 0;
		left: 50%;
		transform: translateX(-50%);
		width: 40rpx;
		height: 6rpx;
		background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
		border-radius: 3rpx;
	}
	.tab-more {
		display: flex;
		align-items: center;
		font-size: 24rpx;
		color: #999;
	}

	/* 热门活动横向滚动卡片 */
	.active-scroll {
		white-space: nowrap;
		padding-left: 30rpx;
		padding-bottom: 160rpx; /* 底部留白，避免被 tabbar 遮挡 */
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

	/* 兑换商品列表：两行横向滚动（每列 2 个商品） */
	.goods-scroll {
		white-space: nowrap;
		padding-left: 30rpx;
	}
	.goods-cols {
		display: flex;
		padding-right: 30rpx; /* 滚动到最右侧时留出与左侧一致的间隔 */
	}
	.goods-col {
		flex-shrink: 0;
		width: 320rpx;
		margin-right: 20rpx;
		display: flex;
		flex-direction: column;
	}
	.goods-col:last-child {
		margin-right: 0;
	}
	.goods-card {
		width: 100%;
		margin-bottom: 20rpx;
		background: #fff;
		border-radius: 20rpx;
		overflow: hidden;
		white-space: normal; /* 重置 scroll-view 的 nowrap，保证标题正常换行 */
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
	.goods-scroll {
		padding-bottom: 160rpx;
	}

	/* 页面全屏引导层，蒙版用内联 backgroundColor，避免 class 不生效 */
	.guide-root {
		position: fixed !important;
		left: 0;
		top: 0;
		right: 0;
		bottom: 0;
		width: 100%;
		height: 100%;
		z-index: 999999;
		background: transparent;
	}

	/* 新手引导加载期间透明遮罩：屏蔽所有点击与切页 */
	.guide-loading-mask {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 100001;
		background: rgba(0, 0, 0, 0);
	}

	/* 与 custom-tab-bar 悬浮胶囊对齐，供引导高亮查询 */
	.guide-tabbar-area {
		position: fixed;
		left: 24rpx;
		right: 24rpx;
		bottom: calc(4rpx + constant(safe-area-inset-bottom));
		bottom: calc(4rpx + env(safe-area-inset-bottom));
		height: 108rpx;
		pointer-events: none;
		z-index: 0;
	}
</style>
