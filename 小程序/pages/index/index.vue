<template>
	<view class="container glass-page" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
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
				<u-navbar :is-back="false" title="" title-color="rgba(0,0,0,0)" :background="{background:'rgba(238,242,239,0)'}" :border-bottom="false">
					<image src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/logo.png" class="home-logo" mode="aspectFill"></image>
				</u-navbar>
				<care-pull-hint :visible="!showCareHint"></care-pull-hint>
			</template>

			<view class="glass-page__ambient"></view>

			<!-- 活动搜索 + 扫码入口（点击进活动搜索页，右侧扫码直达活动详情） -->
			<view id="guide-search" class="home-search-wrap">
				<view class="home-search-box glass-search" @click="$u.route('/pages/activity/active-search')">
					<view class="home-search-ico">
						<u-icon name="search" size="28" color="#18AF38"></u-icon>
					</view>
					<text class="home-search-text">搜索活动名称 / 地点</text>
					<view class="home-search-scan" @click.stop="onScanQrcode">
						<u-icon name="scan" size="32" color="#ffffff"></u-icon>
					</view>
				</view>
			</view>

			<!-- Banner 轮播图 -->
			<view class="banner-wrap">
				<u-swiper :list="bannerList" height="320" border-radius="20" @click="adLink" bg-color="rgba(0,0,0,0)"></u-swiper>
			</view>

			<!-- 走马灯通知栏（后台 home_notice 配置，留空则不显示） -->
			<view class="notice-bar-wrap" v-if="homeNotice">
				<view class="notice-bar-inner">
					<u-notice-bar :list="[homeNotice]" :volume-icon="true" :more-icon="false" :close-icon="false" :speed="100" type="none" bg-color="transparent" color="#8A6D2A" :border-radius="0"></u-notice-bar>
				</view>
			</view>

			<!-- 问候 + 快捷打卡（浅色卡片，入口指向垃圾分类打卡页） -->
			<view id="guide-hello" class="hello-card" v-if="isLogin && todayTask.show">
				<view class="hello-card__orb hello-card__orb--a"></view>
				<view class="hello-card__orb hello-card__orb--b"></view>
				<view class="hello-card__main" @click="$u.route({type:'switchTab', url:'/pages/user/index'})">
					<view class="hello-card__avatar">
						<u-image :src="userInfo.avatar || 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/default-avatar.png'" width="80" height="80" shape="circle" mode="aspectFill"></u-image>
					</view>
					<view class="hello-card__text">
						<view class="hello-card__greet-row">
							<text class="hello-card__greet">{{ greetingText }}</text>
							<view class="hello-card__sign" :class="{ 'hello-card__sign--done': signStatus.today_signed }"
								hover-class="hello-card__sign--on" @click.stop="handleSign">
								<u-icon :name="signStatus.today_signed ? 'checkbox-mark' : 'calendar'" size="28" color="#18AF38"></u-icon>
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

			

			<!-- 内容区：活动 / 积分商品 -->
			<view class="content-panel">
			<view id="guide-tabs" class="content-tabs">
				<view class="tab-list">
					<view class="tab-item" :class="{'tab-item--active': currentTab === index}" v-for="(item, index) in tabList" :key="index" @click="switchContentTab(index)">
						<text class="tab-item__text">{{ item.name }}</text>
					</view>
				</view>
				<view class="tab-more" @click="goMore">
					<text class="tab-more__text">更多</text>
					<u-icon name="arrow-right" size="22" color="#18AF38"></u-icon>
				</view>
			</view>

			<!-- 热门活动横向滚动卡片 -->
			<scroll-view scroll-x class="active-scroll" :show-scrollbar="false" v-if="currentTab === 0">
				<view class="u-flex">
					<view class="active-card" v-for="item in active_list" :key="item.id"
						hover-class="press-scale" :hover-stay-time="80"
						@click="$u.func.openActiveDetail(item)">
						<view class="active-card-cover">
							<u-image width="500" height="260" :src="item.thumb" :lazy-load="true" border-radius="20rpx 20rpx 0 0"></u-image>
						</view>
						<view class="active-card-body">
							<view class="active-card-title u-line-2">{{item.name}}</view>
							<view class="active-card-info">
								<view class="active-chip">
									<u-icon name="clock" size="20" color="#18AF38"></u-icon>
									<text class="active-card-text">{{item.date | date('mm月dd日')}}</text>
								</view>
								<view class="active-chip active-chip--addr">
									<u-icon name="map" size="20" color="#18AF38"></u-icon>
									<text class="active-card-text u-line-1">{{item.address}}</text>
								</view>
							</view>
							<view class="active-card-foot">
								<text>了解详情</text>
								<u-icon name="arrow-right" size="20" color="#18AF38"></u-icon>
							</view>
						</view>
					</view>
				</view>
			</scroll-view>

			<!-- 兑换商品列表：两行横向滚动（每列 2 个商品，横向滚动） -->
			<scroll-view scroll-x class="goods-scroll" :show-scrollbar="false" v-if="currentTab === 1">
				<view class="goods-cols">
					<view class="goods-col" v-for="(col, colIndex) in goodsColumns" :key="colIndex">
						<view class="goods-card" v-for="item in col" :key="item.id" hover-class="press-scale" :hover-stay-time="80" @click="$u.route('/pages/shop/details?id='+item.id)">
							<u-image width="100%" height="200" :src="item.thumb" :lazy-load="true" border-radius="16rpx 16rpx 0 0"></u-image>
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
			</view>



			<!-- 活动风采：展示社区活动/宣讲现场照片 -->
			<view class="showcase-section" v-if="showcaseList.length > 0">
				<view class="section-head">
					<view class="section-head__main">
						<text class="section-head__title">活动风采</text>
						<text class="section-head__sub">记录每一次精彩瞬间</text>
					</view>
					<view class="section-head__more" @click="goShowcaseList">
						<text>全部</text>
						<u-icon name="arrow-right" size="20" color="#18AF38"></u-icon>
					</view>
				</view>
				<scroll-view scroll-x class="showcase-scroll" :show-scrollbar="false">
					<view class="showcase-row">
						<view class="showcase-card" v-for="item in showcaseList" :key="item.id"
							hover-class="press-scale" :hover-stay-time="80"
							@click="goShowcaseDetail(item.id)">
							<view class="showcase-card__cover">
								<u-image width="280" height="200" :src="item.cover" :lazy-load="true" border-radius="20rpx" mode="aspectFill"></u-image>
								<view class="showcase-card__badge" v-if="item.photo_count > 1">
									<u-icon name="photo" size="18" color="#fff"></u-icon>
									<text>{{ item.photo_count }}</text>
								</view>
							</view>
							<view class="showcase-card__body">
								<view class="showcase-card__title u-line-2">{{ item.title }}</view>
								<view class="showcase-card__date">{{ item.event_date_text }}</view>
							</view>
						</view>
						<view class="showcase-row__tail" aria-hidden="true"></view>
					</view>
				</scroll-view>
			</view>


			
			<view class="home-footer">
				<view class="sk-about">
					<view class="sk-about-mark"></view>
					<view class="sk-about-body">
						<text class="sk-about-kicker">SHUKANG · 数康科技</text>
						<text class="sk-about-title">把低碳做成社区日常</text>
						<text class="sk-about-desc">分类打卡、社区活动、积分兑换，一站式低碳服务。</text>
					</view>
				</view>
				<view class="sk-guide-cta" hover-class="sk-guide-cta--on" :hover-stay-time="80" @click="openUsageGuide">
					<view class="sk-guide-cta-ring"></view>
					<view class="sk-guide-cta-copy">
						<text class="sk-guide-cta-hint">第一次来也可以随时看</text>
						<text class="sk-guide-cta-title">点击查看使用教程</text>
					</view>
					<view class="sk-guide-cta-go">
						<text class="sk-guide-cta-arrow">›</text>
					</view>
				</view>
			</view>
		</z-paging>

		<!-- 蒙层只用 view：cover-view 是原生层，离开首页后仍会盖住扫码签到页，导致教程关不掉、也点不了签到 -->
		<block v-if="guideOnPage && showGuide && guideHole">
			<view :style="guideDimCss(0, 0, guideWinW, guideHole.top)"></view>
			<view :style="guideDimCss(0, guideHole.bottom, guideWinW, guideHole.bottomRest)"></view>
			<view :style="guideDimCss(0, guideHole.top, guideHole.left, guideHole.height)"></view>
			<view :style="guideDimCss(guideHole.right, guideHole.top, guideHole.rightRest, guideHole.height)"></view>
			<view :style="guideSpotCss"></view>
		</block>
		<view
			v-else-if="guideOnPage && showGuide"
			style="position:fixed;left:0;top:0;right:0;bottom:0;width:100%;height:100%;background:rgba(0,0,0,0.72);z-index:999998;"
		></view>
		<view
			v-if="guideOnPage && showGuide"
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
		<view v-if="guideOnPage && guideLoading" class="guide-loading-mask" @tap.stop.prevent="noop" @touchmove.stop.prevent="noop"></view>

		<!-- 对齐 custom-tab-bar 悬浮条（108rpx 高 + 左右 24rpx + 底 16rpx + 安全区），仅用于引导定位 -->
		<view id="guide-tabbar" class="guide-tabbar-area"></view>
	</view>
</template>

<script>
import SkeletonScreen from '@/components/skeleton-screen/index.vue'
import NewUserGuide from '@/components/new-user-guide/new-user-guide.vue'
import careGesture from '@/common/care-gesture.mixin.js'
import { shouldTabRefresh, markTabRefreshed } from '@/common/tab-refresh.js'
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
				showcaseList: [],
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
					{ key: 'active', name: '社区活动', url: '/pages/low_carbon/low_carbon', type: 'switchTab', svgIcon: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/image/1.svg' },
					{ key: 'health', name: '健康测试', url: '/pages/health/health', type: 'switchTab', svgIcon: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/image/2.svg' },
					{ key: 'mall', name: '积分商城', url: '/pages/mall/mall', type: 'switchTab', svgIcon: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/image/3.svg' },
					{ key: 'community', name: '社区互动', url: '/pages/sjhd/sjhd', type: 'navigateTo', svgIcon: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/image/4.svg' }
				],
				// 签到状态
				signStatus: {
					today_signed: false,
					continue_days: 0,
				},
				// 新手引导：登录成功后首页显示，引导用户了解核心功能
				showGuide: false,
				guideOnPage: true,
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
				const tabbar = (108 + 16) * sys.windowWidth / 750
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
				path: `/pages/index/index?scene=${this.userInfo.id}`,
				imageUrl: this.$u.mpShare.imageUrl
			}
		},
		onShareTimeline() {
			return {
				path: `/pages/index/index?scene=${this.userInfo.id}`,
				imageUrl: this.$u.mpShare.imageUrl
			}
		},
		onLoad(op) {
			if (op.scene) {
				uni.setStorageSync('vcode', op.scene)
			}
			this._indexHomeBootstrapping = true
			this.loadAllData().finally(() => {
				this._indexHomeBootstrapping = false
				if (this.isLogin) markTabRefreshed('index-user-growth')
			})
		},
		onShow() {
			this.guideOnPage = true
			uni.$off('pause-new-user-guide', this.pauseGuideOverlay)
			uni.$on('pause-new-user-guide', this.pauseGuideOverlay)
			// 更新自定义tabbar选中态
			this.updateTabBarSelected(0)
			// 已登录：从其它页回到首页时刷新用户成长（首屏 loadAllData 已拉过，跳过）
			if (
				this.isLogin &&
				!this._indexHomeBootstrapping &&
				shouldTabRefresh('index-user-growth')
			) {
				this.fetchIndexTabHome({ refresh: 1 })
			}
		},
		onHide() {
			this.pauseGuideOverlay()
			if (this._guideOpenTimer) {
				clearTimeout(this._guideOpenTimer)
				this._guideOpenTimer = null
			}
		},
		onUnload() {
			uni.$off('pause-new-user-guide', this.pauseGuideOverlay)
			this.pauseGuideOverlay()
			if (this._guideWaitTimer) {
				clearTimeout(this._guideWaitTimer)
				this._guideWaitTimer = null
			}
			if (this._guideOpenTimer) {
				clearTimeout(this._guideOpenTimer)
				this._guideOpenTimer = null
			}
		},
		methods: {
		// 新手引导：只由首页底部「点击查看使用教程」调用，不要在 onShow / 登录里自动弹。
		pauseGuideOverlay() {
			this.guideOnPage = false
		},
		openUsageGuide() {
			uni.pageScrollTo({ scrollTop: 0, duration: 220 })
			if (this._guideOpenTimer) clearTimeout(this._guideOpenTimer)
			this._guideOpenTimer = setTimeout(() => {
				this._guideOpenTimer = null
				this.startNewUserGuide()
			}, 260)
		},
		startNewUserGuide() {
			if (this._guideLock || this.showGuide || this.guideLoading) return
			this.guideOnPage = true
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
			this._guideLock = false
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
				await this.fetchIndexTabHome({ refresh: this.isLogin ? 1 : 0 })
			} catch (e) {
				console.log('数据加载失败:', e)
			} finally {
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
		fetchIndexTabHome(opts = {}) {
			if (this._indexTabHomeInflight) {
				return this._indexTabHomeInflight
			}
			const params = opts.refresh ? { refresh: 1 } : {}
			const req = this.$u.api.get_index_tab_home(params).then(res => {
				const d = res.data || {}
				this.bannerList = d.banner_list || []
				this.active_list = d.active_list || []
				this.showcaseList = d.showcase_list || []
				this.homeNotice = (d.home_notice || '').toString()
				this.mergeHomeEntries(d.home_entries || [])
				if (d.sign_status) {
					this.signStatus = d.sign_status
				}
				if (d.user_info && Object.keys(d.user_info).length) {
					this.$u.vuex('userInfo', Object.assign({}, this.userInfo, d.user_info))
					this.applyIndexUserGrowth(d.user_info)
				}
			}).finally(() => {
				if (this._indexTabHomeInflight === req) {
					this._indexTabHomeInflight = null
				}
			})
			this._indexTabHomeInflight = req
			return req
		},
		mergeHomeEntries(list) {
			if (!list || list.length === 0) return
			let merged = this.entry_list.map(fb => {
				let cfg = list.find(c => c.key === fb.key)
				if (cfg) {
					return Object.assign({}, fb, cfg, { svgIcon: cfg.svgIcon || fb.svgIcon })
				}
				return fb
			})
			if (list.length === 4) {
				merged = list.map(item => {
					let fb = this.entry_list.find(f => f.key === item.key)
					return Object.assign({}, fb || {}, item, { svgIcon: item.svgIcon || (fb && fb.svgIcon) || '' })
				})
			}
			this.entry_list = merged
		},
		applyIndexUserGrowth(info) {
			const streak = info.clock_days || info.clock_count || info.continuous_days || 0
			this.todayTask.show = true
			this.todayTask.streak = streak
			this.todayTask.progress = Math.min((streak % 7) / 7 * 100, 100)
			this.todayTask.daysToGoal = 7 - (streak % 7)
			if (this.todayTask.daysToGoal === 7) this.todayTask.daysToGoal = 0
		},
			goShowcaseList() {
				this.$u.route('/pages/activity/showcase-list?tab=manual');
			},
			goShowcaseDetail(id) {
				this.$u.route('/pages/activity/showcase-detail?id=' + id);
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
				// 1. 尚未完成打卡前学习 → 引导学习
				if (!info.is_learn) {
					uni.showModal({
						title: '提示',
						content: '请先完成打卡前学习，通过答题考试后可参与垃圾分类打卡',
						showCancel: false,
						confirmText: '去学习',
						confirmColor: '#39BF3E',
						success: (res) => {
							if (res.confirm) uni.navigateTo({ url: '/pages/flhd/pre_learn' })
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
	@import '@/static/styles/glass-ui.scss';

	$brand: #18AF38;
	$brand-dark: #12962e;
	$brand-soft: #e8f7ec;
	$surface: #ffffff;
	$text: #1a2e22;
	$text-sub: #5f7166;
	$text-muted: #95a59b;
	$radius-md: 20rpx;
	$radius-lg: 24rpx;
	$shadow-card: 0 8rpx 32rpx rgba(22, 53, 36, 0.06);
	$shadow-float: 0 16rpx 40rpx rgba(22, 53, 36, 0.1);

	.container {
		position: relative;
	}

	.home-logo {
		width: 320rpx;
		height: 88rpx;
		margin-left: 30rpx;
	}

	.press-scale {
		opacity: 0.94;
		transform: scale(0.985);
	}

	.home-search-wrap {
		position: relative;
		z-index: 1;
		padding: 16rpx 30rpx 0;
	}

	.home-search-box {
		display: flex;
		align-items: center;
		height: 84rpx;
		padding: 0 10rpx 0 20rpx;
	}

	.home-search-ico {
		width: 52rpx;
		height: 52rpx;
		border-radius: 50%;
		background: rgba(24, 175, 56, 0.08);
		display: flex;
		align-items: center;
		justify-content: center;
		flex-shrink: 0;
	}

	.home-search-text {
		flex: 1;
		margin-left: 14rpx;
		font-size: 26rpx;
		color: $text-muted;
	}

	.home-search-scan {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 56rpx;
		height: 56rpx;
		margin-left: 10rpx;
		border-radius: 50%;
		background: $brand;
		box-shadow: 0 4rpx 12rpx rgba(24, 175, 56, 0.22);
	}

	.banner-wrap {
		position: relative;
		z-index: 1;
		margin: 24rpx 32rpx 0;
		border-radius: $glass-radius-lg;
		overflow: hidden;
		@include glass-surface(0.35, 32px);
		padding: 8rpx;
		box-sizing: border-box;
	}

	.notice-bar-wrap {
		position: relative;
		z-index: 1;
		padding: 16rpx 30rpx 0;
	}

	.notice-bar-inner {
		overflow: hidden;
		border-radius: $glass-radius-md;
		@include glass-surface(0.55, 36px);
	}

	.hello-card {
		@include glass-accent-card(rgba(24, 175, 56, 0.05));
		position: relative;
		z-index: 1;
		overflow: hidden;
		display: flex;
		align-items: center;
		margin: 24rpx 32rpx 0;
		padding: 28rpx 24rpx;
		box-sizing: border-box;
	}
	.hello-card__orb {
		display: none;
	}
	.hello-card__main {
		position: relative;
		z-index: 1;
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
		box-shadow: 0 0 0 4rpx rgba(255, 255, 255, 0.35);
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
		font-weight: 600;
		color: $glass-text;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		margin-right: 12rpx;
	}
	.hello-card__sign {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		padding: 8rpx 16rpx;
		background: $glass-brand-soft;
		border-radius: 999rpx;
		border: 1rpx solid rgba(24, 175, 56, 0.12);
	}
	.hello-card__sign--done {
		background: rgba(0, 0, 0, 0.04);
		border-color: $glass-hairline;
	}
	.hello-card__sign--on {
		transform: scale(0.92);
		opacity: 0.85;
	}
	.hello-card__sign-text {
		margin-left: 6rpx;
		font-size: 22rpx;
		font-weight: 500;
		color: $glass-brand-dark;
		line-height: 1.2;
	}
	.hello-card__sign-days {
		margin-left: 6rpx;
		font-size: 20rpx;
		color: $glass-text-tertiary;
		line-height: 1.2;
	}
	.hello-card__meta {
		margin-top: 10rpx;
		font-size: 22rpx;
		color: $glass-text-secondary;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.hello-card__level {
		color: $glass-text;
		font-weight: 600;
	}
	.hello-card__dot {
		margin: 0 6rpx;
		color: $glass-text-tertiary;
	}
	.hello-card__btn {
		position: relative;
		z-index: 1;
		flex-shrink: 0;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		margin-left: 16rpx;
		padding: 18rpx 22rpx;
		min-width: 128rpx;
		background: $brand;
		border-radius: $glass-radius-md;
		box-shadow: 0 8rpx 20rpx rgba(24, 175, 56, 0.25);
	}
	.hello-card__btn--on {
		opacity: 0.88;
		transform: scale(0.97);
	}
	.hello-card__btn-title {
		font-size: 24rpx;
		font-weight: 600;
		color: #fff;
		line-height: 1.2;
	}
	.hello-card__btn-sub {
		margin-top: 4rpx;
		font-size: 18rpx;
		color: rgba(255, 255, 255, 0.82);
		line-height: 1.2;
	}

	.entry-grid {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 24rpx 30rpx 0;
	}
	.entry-item {
		@include glass-surface(0.58, 40px);
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		flex: 1;
		padding: 24rpx 0;
		margin: 0 8rpx;
		border-radius: 28rpx;
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

	.section-head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 0 24rpx 18rpx;
	}
	.section-head__title {
		display: block;
		font-size: 30rpx;
		font-weight: 700;
		color: $text;
		line-height: 1.3;
	}
	.section-head__sub {
		display: block;
		margin-top: 4rpx;
		font-size: 22rpx;
		color: $text-muted;
	}
	.section-head__more {
		display: flex;
		align-items: center;
		gap: 4rpx;
		padding: 10rpx 16rpx;
		font-size: 24rpx;
		color: $brand;
		background: $brand-soft;
		border-radius: 999rpx;
	}

	.showcase-section {
		@include glass-sheet;
		position: relative;
		z-index: 1;
		margin: 24rpx 32rpx 0;
		padding: 28rpx 0 16rpx;
		overflow: hidden;
	}
	.showcase-scroll {
		width: 100%;
		white-space: nowrap;
		box-sizing: border-box;
	}
	.showcase-row {
		display: inline-flex;
		flex-direction: row;
		align-items: flex-start;
		padding-left: 24rpx;
		box-sizing: border-box;
	}
	.showcase-row__tail {
		flex-shrink: 0;
		width: 24rpx;
		height: 1rpx;
	}
	.showcase-card {
		flex-shrink: 0;
		width: 280rpx;
		margin-right: 16rpx;
	}
	.showcase-card__cover {
		position: relative;
		width: 280rpx;
		height: 200rpx;
		border-radius: $radius-md;
		overflow: hidden;
	}
	.showcase-card__badge {
		position: absolute;
		right: 10rpx;
		bottom: 10rpx;
		display: flex;
		align-items: center;
		gap: 4rpx;
		padding: 4rpx 10rpx;
		font-size: 20rpx;
		color: #fff;
		background: rgba(0, 0, 0, 0.42);
		border-radius: 999rpx;
	}
	.showcase-card__body {
		padding: 14rpx 4rpx 6rpx;
	}
	.showcase-card__title {
		font-size: 26rpx;
		font-weight: 600;
		color: $text;
		line-height: 1.45;
		min-height: 74rpx;
	}
	.showcase-card__date {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: $text-muted;
	}

	.content-panel {
		@include glass-sheet;
		position: relative;
		z-index: 1;
		margin: 24rpx 32rpx 0;
		padding: 24rpx 0 12rpx;
		overflow: hidden;
	}

	.content-tabs {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin: 0 24rpx 16rpx;
		@include glass-segment-track;
	}
	.tab-list {
		display: flex;
		align-items: center;
		flex: 1;
	}
	.tab-item {
		flex: 1;
		text-align: center;
		padding: 16rpx 0;
		border-radius: 12rpx;
		transition: all 0.2s ease;
	}
	.tab-item__text {
		font-size: 28rpx;
		color: $text-sub;
		font-weight: 500;
	}
	.tab-item--active {
		@include glass-segment-item-active;
	}
	.tab-item--active .tab-item__text {
		color: $glass-text;
		font-weight: 600;
	}
	.tab-more {
		display: flex;
		align-items: center;
		flex-shrink: 0;
		padding: 12rpx 14rpx 12rpx 8rpx;
	}
	.tab-more__text {
		font-size: 24rpx;
		color: $brand;
		font-weight: 600;
		margin-right: 2rpx;
	}

	.active-scroll {
		white-space: nowrap;
		padding: 8rpx 0 20rpx 20rpx;
	}
	.active-card {
		@include glass-surface(0.65, 36px);
		flex-shrink: 0;
		width: 500rpx;
		margin-right: 16rpx;
		border-radius: $glass-radius-lg;
		overflow: hidden;
	}
	.active-card-cover {
		width: 500rpx;
		height: 260rpx;
		overflow: hidden;
	}
	.active-card-body {
		padding: 20rpx 22rpx 22rpx;
	}
	.active-card-title {
		font-size: 28rpx;
		font-weight: 600;
		color: $glass-text;
		line-height: 1.45;
		min-height: 86rpx;
	}
	.active-card-info {
		display: flex;
		flex-direction: column;
		gap: 10rpx;
		margin-top: 8rpx;
	}
	.active-chip {
		display: flex;
		align-items: center;
		min-width: 0;
		padding: 8rpx 14rpx;
		background: $brand-soft;
		border-radius: 999rpx;
	}
	.active-chip--addr {
		background: #f3f6f4;
	}
	.active-card-text {
		margin-left: 8rpx;
		font-size: 22rpx;
		color: $text-sub;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.active-card-foot {
		display: flex;
		align-items: center;
		justify-content: flex-end;
		gap: 4rpx;
		margin-top: 16rpx;
		padding-top: 16rpx;
		border-top: 1rpx solid #edf2ee;
		font-size: 24rpx;
		font-weight: 600;
		color: $brand;
	}

	.goods-scroll {
		white-space: nowrap;
		padding: 8rpx 0 20rpx 20rpx;
	}
	.goods-cols {
		display: flex;
		padding-right: 30rpx;
	}
	.goods-col {
		flex-shrink: 0;
		width: 300rpx;
		margin-right: 16rpx;
		display: flex;
		flex-direction: column;
	}
	.goods-col:last-child {
		margin-right: 0;
	}
	.goods-card {
		@include glass-surface(0.62, 36px);
		width: 100%;
		margin-bottom: 16rpx;
		border-radius: $glass-radius-lg;
		overflow: hidden;
		white-space: normal;
	}
	.goods-card-body {
		padding: 16rpx 18rpx 20rpx;
	}
	.goods-card-title {
		height: 72rpx;
		font-size: 26rpx;
		font-weight: 600;
		line-height: 36rpx;
		color: $text;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
	}
	.goods-card-sold {
		display: inline-block;
		margin-top: 8rpx;
		padding: 4rpx 12rpx;
		font-size: 20rpx;
		color: $text-muted;
		background: #f0f4f1;
		border-radius: 999rpx;
	}
	.goods-card-footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-top: 14rpx;
	}
	.goods-card-price {
		display: flex;
		align-items: baseline;
		min-width: 0;
		white-space: nowrap;
		overflow: hidden;
	}
	.price-point {
		font-size: 28rpx;
		font-weight: 700;
		color: $brand;
	}
	.price-plus {
		margin: 0 4rpx;
		font-size: 22rpx;
		font-weight: 700;
		color: $brand;
	}
	.price-main {
		font-size: 22rpx;
		font-weight: 700;
		color: $brand;
	}
	.goods-card-btn {
		flex-shrink: 0;
		height: 48rpx;
		line-height: 48rpx;
		padding: 0 18rpx;
		margin-left: 10rpx;
		background: $brand;
		color: #fff;
		font-size: 22rpx;
		font-weight: 600;
		text-align: center;
		border-radius: 24rpx;
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

	.home-footer {
		position: relative;
		z-index: 1;
		padding: 24rpx 30rpx calc(172rpx + env(safe-area-inset-bottom));
	}
	.sk-about {
		@include glass-sheet;
		position: relative;
		overflow: hidden;
		padding: 32rpx 32rpx 32rpx 36rpx;
		border-radius: $glass-radius-xl $glass-radius-xl 0 0;
	}
	.sk-about-mark {
		position: absolute;
		left: 0;
		top: 24rpx;
		bottom: 24rpx;
		width: 6rpx;
		border-radius: 0 6rpx 6rpx 0;
		background: linear-gradient(180deg, #86efac 0%, $brand 100%);
	}
	.sk-about-body {
		display: flex;
		flex-direction: column;
	}
	.sk-about-kicker,
	.sk-about-title,
	.sk-about-desc,
	.sk-guide-cta-hint,
	.sk-guide-cta-title {
		display: block;
	}
	.sk-about-kicker {
		font-size: 20rpx;
		letter-spacing: 0.12em;
		color: $glass-text-tertiary;
		font-weight: 500;
	}
	.sk-about-title {
		margin-top: 8rpx;
		font-size: 30rpx;
		font-weight: 600;
		color: $glass-text;
		line-height: 1.35;
	}
	.sk-about-desc {
		margin-top: 8rpx;
		font-size: 24rpx;
		line-height: 1.6;
		color: $glass-text-secondary;
	}
	.sk-guide-cta {
		position: relative;
		display: flex;
		align-items: center;
		margin-top: 0;
		padding: 24rpx 24rpx 24rpx 28rpx;
		background: rgba(24, 175, 56, 0.92);
		border-radius: 0 0 $glass-radius-xl $glass-radius-xl;
		box-shadow: none;
	}
	.sk-guide-cta--on {
		opacity: 0.94;
	}
	.sk-guide-cta-ring {
		flex-shrink: 0;
		width: 52rpx;
		height: 52rpx;
		margin-right: 18rpx;
		border: 3rpx solid rgba(255, 255, 255, 0.8);
		border-top-color: transparent;
		border-radius: 50%;
		animation: skGuideSpin 1.6s linear infinite;
	}
	@keyframes skGuideSpin {
		to { transform: rotate(360deg); }
	}
	.sk-guide-cta-copy {
		flex: 1;
		min-width: 0;
		display: flex;
		flex-direction: column;
	}
	.sk-guide-cta-hint {
		font-size: 20rpx;
		color: rgba(255, 255, 255, 0.8);
	}
	.sk-guide-cta-title {
		margin-top: 4rpx;
		font-size: 30rpx;
		font-weight: 700;
		color: #fff;
	}
	.sk-guide-cta-go {
		flex-shrink: 0;
		width: 52rpx;
		height: 52rpx;
		margin-left: 12rpx;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.22);
		display: flex;
		align-items: center;
		justify-content: center;
	}
	.sk-guide-cta-arrow {
		font-size: 40rpx;
		line-height: 1;
		color: #fff;
		font-weight: 300;
		margin-top: -4rpx;
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
		bottom: calc(16rpx + constant(safe-area-inset-bottom));
		bottom: calc(16rpx + env(safe-area-inset-bottom));
		height: 108rpx;
		pointer-events: none;
		z-index: 0;
	}
</style>
