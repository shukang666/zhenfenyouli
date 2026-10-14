<template>
	<view class="me-page" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
		<view class="care-pull-layer" :style="{ height: carePullHeight + 'px', opacity: careHintOpacity }" v-if="showCareHint">
			<view class="care-pull-content">
				<text class="care-pull-text">{{ carePullHeight >= 90 ? '松开进入关怀版' : '下拉进入关怀版' }}</text>
				<u-icon :name="carePullHeight >= 90 ? 'arrow-up' : 'arrow-down'" size="28" color="#18AF38"></u-icon>
			</view>
		</view>

		<care-pull-hint :visible="!showCareHint"></care-pull-hint>
		<skeleton-screen :show="isLoading" type="user"></skeleton-screen>

		<!-- 顶区：扁平个人信息 -->
		<view class="me-head" id="guide-me-hero">
			<view class="me-profile" @click="onProfileTap">
				<view class="me-profile__avatar">
					<u-avatar :src="isLogin ? userInfo.avatar : ''" size="120"></u-avatar>
				</view>
				<view class="me-profile__main" v-if="!isLogin">
					<text class="me-profile__title">登录 / 注册</text>
					<text class="me-profile__sub">登录后查看积分、活动与订单</text>
				</view>
				<view class="me-profile__main" v-else>
					<view class="me-profile__name-row">
						<text class="me-profile__title">{{ userInfo.nickname }}</text>
						<view class="me-profile__tag" v-if="userInfo.is_environmental">环保达人</view>
						<view class="me-profile__tag me-profile__tag--staff" v-if="userInfo.type == 2">员工</view>
					</view>
					<text class="me-profile__sub">{{ userInfo.mobile }}</text>
					<view class="me-profile__badges" v-if="badge_list.length">
						<image
							v-for="item in badge_list"
							:key="item.id"
							:src="item.image"
							class="me-profile__badge"
							mode="aspectFill"
						></image>
					</view>
				</view>
				<u-icon name="arrow-right" size="28" color="#C7C7CC"></u-icon>
			</view>

			<view class="me-metrics" v-if="isLogin">
				<view class="me-metrics__item" @click="navigator('/pages/points/points')">
					<text class="me-metrics__val">{{ userInfo.point || 0 }}</text>
					<text class="me-metrics__label">积分</text>
				</view>
				<view class="me-metrics__sep"></view>
				<view class="me-metrics__item" @click="navigator('/pages/activity/my_activity')">
					<text class="me-metrics__val">—</text>
					<text class="me-metrics__label">我的活动</text>
				</view>
				<view class="me-metrics__sep"></view>
				<view class="me-metrics__item" @click="navigator('/pages/member/badge')">
					<text class="me-metrics__val">{{ badge_list.length }}</text>
					<text class="me-metrics__label">徽章</text>
				</view>
			</view>
		</view>

		<view class="me-body">
			<!-- 运营 Banner 占位：meAssets.banner 填 COS 地址即可 -->
			<view class="me-banner" v-if="isLogin">
				<image v-if="meAssets.banner" class="me-banner__img" :src="meAssets.banner" mode="aspectFill"></image>
				<view v-else class="me-banner__ph">
					<text class="me-banner__ph-title">运营 Banner 占位</text>
					<text class="me-banner__ph-size">建议 750 × 280 px · 替换 meAssets.banner</text>
				</view>
			</view>

			<view class="me-grow flat-card" v-if="!userInfo.is_environmental && isLogin">
				<view class="me-grow__icon-wrap">
					<image
						v-if="meAssets.growBadge"
						class="me-grow__icon"
						:src="meAssets.growBadge"
						mode="aspectFill"
					></image>
					<view v-else class="me-grow__icon-ph">
						<text>110×110</text>
					</view>
				</view>
				<view class="me-grow__content">
					<view class="me-grow__head">
						<text class="me-grow__title">环保达人</text>
						<view class="me-grow__help" @click.stop="openDesc">
							<text>说明</text>
							<u-icon name="question-circle" size="24" color="#86868B"></u-icon>
						</view>
					</view>
					<u-line-progress
						:striped="true"
						:percent="growPercent"
						height="12"
						active-color="#18AF38"
						inactive-color="#E8E8ED"
						:show-percent="false"
					></u-line-progress>
					<text class="me-grow__desc">
						已连续真分 {{ userInfo.clock_count }} 天，还差 {{ userInfo.remaining_count }} 天
					</text>
				</view>
			</view>

			<!-- 常用功能 -->
			<view class="flat-section flat-card" id="guide-me-common">
				<text class="flat-section__title">常用</text>
				<view class="me-quick">
					<view
						class="me-quick__item"
						v-for="item in quickMenus"
						:key="item.key"
						@click="navigator(item.url)"
					>
						<view class="me-quick__icon me-quick__icon--img">
							<image :src="meAssets.quick[item.key]" mode="aspectFill" class="me-quick__img"></image>
						</view>
						<text class="me-quick__name">{{ item.name }}</text>
					</view>
				</view>
			</view>

			<!-- 我的服务（精简） -->
			<view class="flat-section flat-card" id="guide-me-service">
				<text class="flat-section__title">服务</text>
				<view
					class="flat-row"
					v-for="item in serviceMenus"
					:key="item.key"
					@click="navigator(item.url)"
				>
					<view class="flat-row__icon" :style="{ background: item.iconBg }">
						<image v-if="meAssets.service[item.key]" :src="meAssets.service[item.key]" mode="aspectFit" class="flat-row__img"></image>
						<u-icon v-else :name="item.icon" :size="38" :color="item.iconColor"></u-icon>
					</view>
					<text class="flat-row__label">{{ item.name }}</text>
					<text v-if="item.desc" class="flat-row__desc">{{ item.desc }}</text>
					<u-icon name="arrow-right" size="24" color="#C7C7CC"></u-icon>
				</view>
			</view>

			<!--
			以下入口暂时下线（2026-03）
			我的评论、我的测评记录、我的问卷、我的预约、
			垃圾分类真分、垃圾分类指导、答题考试
			<view class="me-grid__item" @click="navigator('pages/my_comment/my_comment')" ...
			-->

			<view class="flat-section flat-card">
				<text class="flat-section__title">更多</text>
				<view
					class="flat-row"
					v-for="item in moreMenus"
					:key="item.key"
					@click="onMoreTap(item)"
				>
					<view class="flat-row__icon" :style="{ background: item.iconBg }">
						<image v-if="meAssets.more[item.key]" :src="meAssets.more[item.key]" mode="aspectFit" class="flat-row__img"></image>
						<u-icon v-else :name="item.icon" :size="38" :color="item.iconColor"></u-icon>
					</view>
					<text class="flat-row__label">{{ item.name }}</text>
					<text v-if="item.desc" class="flat-row__desc">{{ item.desc }}</text>
					<u-icon name="arrow-right" size="24" color="#C7C7CC"></u-icon>
				</view>
			</view>
		</view>

		<u-mask :show="mask_show">
			<view class="warp">
				<view>
					<view class="kf-box">
						<view class="kf-title">联系客服</view>
						<view class="kf-code">
							<u-image width="420" height="420" :src="setting.wx_code"></u-image>
						</view>
						<view class="kf-tip">扫码添加客服微信</view>
						<view class="kf-btn">
							<u-button
								type="success"
								shape="circle"
								:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)' }"
								hover-class="none"
								open-type="contact"
							>在线客服</u-button>
						</view>
					</view>
					<view class="kf-close" @click="closeKf">
						<u-icon name="close-circle" color="#fff" size="60"></u-icon>
					</view>
				</view>
			</view>
		</u-mask>
		<u-mask :show="descShow">
			<view class="desc-box">
				<view class="desc-title">等级说明</view>
				<scroll-view scroll-y="true" class="desc-scroll">
					<view class="desc-text">{{ setting.level_description }}</view>
				</scroll-view>
				<view class="desc-ok" @click="closeDesc">我知道了</view>
			</view>
		</u-mask>

		<view class="me-tabbar-space"></view>
		<page-guide page="user"></page-guide>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	import careGesture from '@/common/care-gesture.mixin.js'
	import { shouldTabRefresh } from '@/common/tab-refresh.js'
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	import settings from '@/common/settings.js'

	const ME_QUICK_ICON_BASE = settings.staticAssetBase + 'user/quick/'

	export default {
		components: { SkeletonScreen },
		mixins: [careGesture],
		data() {
			return {
				isLoading: true,
				mask_show: false,
				descShow: false,
				badge_list: [],
				/** 视觉资源：填 COS 完整地址后占位自动隐藏 */
				meAssets: {
					banner: '',
					growBadge: '',
					quick: {
						/* 小程序 image 不支持远程 SVG，用 PNG（与 static/user/quick 同名） */
						points: ME_QUICK_ICON_BASE + 'jifenshangcheng.png',
						activity: ME_QUICK_ICON_BASE + 'wodehuodong.png',
						order: ME_QUICK_ICON_BASE + 'duihuandingdan.png',
						msg: ME_QUICK_ICON_BASE + 'xiaoxitongzhi.png',
					},
					service: {
						community: '',
						badge: '',
					},
					more: {
						coupon: '',
						address: '',
						kf: '',
						profile: '',
						about: '',
					},
				},
				quickMenus: [
					{ key: 'points', name: '积分明细', url: '/pages/points/points' },
					{ key: 'activity', name: '我的活动', url: '/pages/activity/my_activity' },
					{ key: 'order', name: '兑换订单', url: '/pages/order_list/order_list' },
					{ key: 'msg', name: '消息通知', url: '/pages/msg_list/msg_list' },
				],
				serviceMenus: [
					{
						key: 'community',
						name: '健康生活社区',
						url: '/pages/community/record',
						icon: 'heart-fill',
						iconColor: '#18AF38',
						iconBg: 'rgba(24, 175, 56, 0.12)',
					},
					{
						key: 'badge',
						name: '我的徽章',
						url: '/pages/member/badge',
						icon: 'star-fill',
						iconColor: '#D4A017',
						iconBg: 'rgba(255, 214, 106, 0.35)',
					},
				],
				moreMenus: [
					{
						key: 'coupon',
						name: '优惠券',
						url: '/pages/coupon/my_coupon',
						needLogin: true,
						icon: 'coupon-fill',
						iconColor: '#E85D4C',
						iconBg: 'rgba(232, 93, 76, 0.12)',
					},
					{
						key: 'address',
						name: '地址管理',
						url: '/pages/address/index',
						needLogin: true,
						icon: 'map-fill',
						iconColor: '#3974F2',
						iconBg: 'rgba(57, 116, 242, 0.12)',
					},
					{
						key: 'kf',
						name: '联系客服',
						action: 'kf',
						icon: 'chat-fill',
						iconColor: '#18AF38',
						iconBg: 'rgba(24, 175, 56, 0.12)',
					},
					{
						key: 'profile',
						name: '个人信息',
						desc: '小区设置在这里',
						url: '/pages/member/info',
						needLogin: true,
						icon: 'account-fill',
						iconColor: '#5856D6',
						iconBg: 'rgba(88, 86, 214, 0.12)',
					},
					{
						key: 'about',
						name: '关于数康',
						action: 'about',
						icon: 'bookmark-fill',
						iconColor: '#0F5C32',
						iconBg: 'rgba(15, 92, 50, 0.1)',
					},
				],
			}
		},
		computed: {
			growPercent() {
				const total = Number(this.userInfo.continuous_check) || 0
				const done = Number(this.userInfo.clock_count) || 0
				if (total <= 0) return 0
				return Math.min(100, (done / total) * 100)
			},
		},
		onLoad() {},
		onShow() {
			this.updateTabBarSelected(4)
			if (checkCommunity(this)) return
			if (this.isLogin) {
				if (shouldTabRefresh('user-profile')) {
					this.fetchUserTabHome()
				} else {
					this.isLoading = false
				}
			} else {
				this.isLoading = false
			}
		},
		methods: {
			fetchUserTabHome(opts = {}) {
				const params = opts.refresh ? { refresh: 1 } : {}
				return this.$u.api
					.get_user_tab_home(params)
					.then((res) => {
						const d = res.data || {}
						if (!d.logged_in) return
						this.$u.vuex('userInfo', d.user_info || {})
						this.badge_list = d.badge_list || []
					})
					.catch((e) => {
						console.log(e)
						if (e && e.msg) this.$u.toast(e.msg)
					})
					.finally(() => {
						this.isLoading = false
					})
			},
			updateTabBarSelected(index) {
				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()
				if (tabBar) {
					tabBar.setSelected(index)
				}
			},
			navigator(url) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
			},
			onProfileTap() {
				if (!this.isLogin) {
					this.$u.route('/pages/public/login')
					return
				}
				this.$u.route('/pages/member/info')
			},
			onMoreTap(item) {
				if (item.action === 'kf') {
					this.openKf()
					return
				}
				if (item.action === 'about') {
					this.goShukangAbout()
					return
				}
				if (item.needLogin && !this.isLogin) {
					this.$u.route('/pages/public/login')
					return
				}
				if (item.url) this.$u.route(item.url)
			},
			openKf() {
				this.mask_show = true
			},
			closeKf() {
				this.mask_show = false
			},
			openDesc() {
				this.descShow = true
			},
			closeDesc() {
				this.descShow = false
			},
			goShukangAbout() {
				this.$u.route('/pages/public/shukang-about')
			},
		},
	}
</script>

<style lang="scss" scoped>
	$flat-bg: #f2f3f5;
	$flat-card: #ffffff;
	$flat-text: #1d1d1f;
	$flat-sub: #86868b;
	$flat-line: rgba(0, 0, 0, 0.06);
	$flat-brand: #18af38;

	.me-page {
		min-height: 100vh;
		background: $flat-bg;
		padding-bottom: env(safe-area-inset-bottom);
	}

	.me-head {
		background: $flat-card;
		padding: 100rpx 32rpx 0;
		border-bottom: 1rpx solid $flat-line;
	}

	.me-profile {
		display: flex;
		flex-direction: row;
		align-items: center;
		padding-bottom: 32rpx;
	}
	.me-profile__avatar {
		flex-shrink: 0;
	}
	.me-profile__main {
		flex: 1;
		min-width: 0;
		margin: 0 20rpx;
	}
	.me-profile__name-row {
		display: flex;
		flex-direction: row;
		align-items: center;
		flex-wrap: wrap;
	}
	.me-profile__title {
		font-size: 36rpx;
		font-weight: 600;
		color: $flat-text;
		line-height: 1.3;
	}
	.me-profile__sub {
		display: block;
		margin-top: 8rpx;
		font-size: 26rpx;
		color: $flat-sub;
	}
	.me-profile__tag {
		margin-left: 12rpx;
		margin-top: 4rpx;
		padding: 4rpx 14rpx;
		font-size: 20rpx;
		color: #7a5a00;
		background: #fff4cc;
		border-radius: 8rpx;
	}
	.me-profile__tag--staff {
		color: #6b4f99;
		background: #f0ebfa;
	}
	.me-profile__badges {
		display: flex;
		flex-direction: row;
		margin-top: 12rpx;
	}
	.me-profile__badge {
		width: 40rpx;
		height: 40rpx;
		margin-right: 8rpx;
		border-radius: 8rpx;
	}

	.me-metrics {
		display: flex;
		flex-direction: row;
		align-items: center;
		padding: 28rpx 0 32rpx;
		border-top: 1rpx solid $flat-line;
	}
	.me-metrics__item {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
	}
	.me-metrics__val {
		font-size: 36rpx;
		font-weight: 600;
		color: $flat-text;
		font-variant-numeric: tabular-nums;
	}
	.me-metrics__label {
		margin-top: 6rpx;
		font-size: 22rpx;
		color: $flat-sub;
	}
	.me-metrics__sep {
		width: 1rpx;
		height: 48rpx;
		background: $flat-line;
	}

	.me-body {
		padding: 24rpx 24rpx 0;
	}

	.flat-card {
		background: $flat-card;
		border-radius: 24rpx;
		margin-bottom: 20rpx;
		overflow: hidden;
		box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.03);
	}

	.me-banner {
		border-radius: 24rpx;
		overflow: hidden;
		margin-bottom: 20rpx;
		height: 280rpx;
		background: $flat-card;
	}
	.me-banner__img {
		width: 100%;
		height: 280rpx;
		display: block;
	}
	.me-banner__ph {
		height: 280rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		background: linear-gradient(135deg, #eef0f3 0%, #e4e8ec 100%);
		border: 2rpx dashed rgba(0, 0, 0, 0.08);
		box-sizing: border-box;
		margin: 0;
		border-radius: 24rpx;
	}
	.me-banner__ph-title {
		font-size: 28rpx;
		font-weight: 600;
		color: $flat-sub;
	}
	.me-banner__ph-size {
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #aeaeb2;
	}

	.me-grow {
		display: flex;
		flex-direction: row;
		align-items: center;
		padding: 28rpx;
	}
	.me-grow__icon-wrap {
		flex-shrink: 0;
		margin-right: 24rpx;
	}
	.me-grow__icon {
		width: 110rpx;
		height: 110rpx;
		border-radius: 20rpx;
	}
	.me-grow__icon-ph {
		width: 110rpx;
		height: 110rpx;
		border-radius: 20rpx;
		background: #eef0f3;
		border: 2rpx dashed rgba(0, 0, 0, 0.08);
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 20rpx;
		color: #aeaeb2;
	}
	.me-grow__content {
		flex: 1;
		min-width: 0;
	}
	.me-grow__head {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 16rpx;
	}
	.me-grow__title {
		font-size: 30rpx;
		font-weight: 600;
		color: $flat-text;
	}
	.me-grow__help {
		display: flex;
		flex-direction: row;
		align-items: center;
		font-size: 24rpx;
		color: $flat-sub;
	}
	.me-grow__desc {
		display: block;
		margin-top: 12rpx;
		font-size: 24rpx;
		color: $flat-sub;
		line-height: 1.45;
	}

	.flat-section__title {
		display: block;
		padding: 28rpx 28rpx 8rpx;
		font-size: 26rpx;
		font-weight: 600;
		color: $flat-sub;
		letter-spacing: 1rpx;
	}

	.me-quick {
		display: flex;
		flex-direction: row;
		flex-wrap: wrap;
		padding: 8rpx 12rpx 24rpx;
	}
	.me-quick__item {
		width: 25%;
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 16rpx 0;
		box-sizing: border-box;
	}
	.me-quick__icon {
		width: 88rpx;
		height: 88rpx;
		border-radius: 22rpx;
		background: #f2f3f5;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 12rpx;
		overflow: hidden;
	}
	.me-quick__icon--img {
		background: #f7f8fa;
		padding: 0;
	}
	.me-quick__img {
		width: 100%;
		height: 100%;
		border-radius: 22rpx;
	}
	.me-quick__name {
		font-size: 24rpx;
		color: $flat-text;
		text-align: center;
	}

	.flat-row {
		display: flex;
		flex-direction: row;
		align-items: center;
		padding: 28rpx;
		border-top: 1rpx solid $flat-line;
	}
	.flat-row:first-of-type {
		border-top: none;
	}
	.flat-row__icon {
		width: 72rpx;
		height: 72rpx;
		border-radius: 18rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-right: 24rpx;
		flex-shrink: 0;
		overflow: hidden;
	}
	.flat-row__img {
		width: 40rpx;
		height: 40rpx;
	}
	.flat-row__label {
		flex: 1;
		font-size: 30rpx;
		color: $flat-text;
		font-weight: 400;
	}
	.flat-row__desc {
		flex-shrink: 0;
		max-width: 280rpx;
		margin-right: 8rpx;
		font-size: 24rpx;
		color: $flat-sub;
		text-align: right;
	}

	.warp {
		display: flex;
		align-items: center;
		justify-content: center;
		height: 100%;
	}
	.kf-box {
		width: 560rpx;
		border-radius: 24rpx;
		background: #fff;
		padding: 36rpx 30rpx;
		text-align: center;
	}
	.kf-title {
		font-size: 32rpx;
		font-weight: 700;
		color: $flat-text;
	}
	.kf-code {
		margin-top: 20rpx;
		display: flex;
		justify-content: center;
	}
	.kf-tip {
		margin-top: 10rpx;
		font-size: 24rpx;
		color: $flat-sub;
	}
	.kf-btn {
		margin-top: 30rpx;
		padding: 0 30rpx;
	}
	.kf-close {
		margin-top: 30rpx;
		text-align: center;
	}

	.desc-box {
		position: absolute;
		left: 50%;
		top: 50%;
		transform: translate(-50%, -50%);
		width: 640rpx;
		border-radius: 24rpx;
		background: #fff;
		overflow: hidden;
	}
	.desc-title {
		font-size: 30rpx;
		font-weight: 700;
		padding: 30rpx;
		text-align: center;
		color: $flat-text;
	}
	.desc-scroll {
		height: 700rpx;
		padding: 0 30rpx;
	}
	.desc-text {
		font-size: 26rpx;
		color: #444;
		white-space: pre-wrap;
	}
	.desc-ok {
		padding: 28rpx 30rpx;
		margin-top: 20rpx;
		border-top: 1rpx solid $flat-line;
		text-align: center;
		font-size: 32rpx;
		color: $flat-brand;
		font-weight: 600;
	}

	.me-tabbar-space {
		height: 172rpx;
	}
</style>
