<template>
	<view class="me-page glass-page" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
		<view class="glass-page__ambient"></view>

		<view class="care-pull-layer" :style="{ height: carePullHeight + 'px', opacity: careHintOpacity }" v-if="showCareHint">
			<view class="care-pull-content">
				<text class="care-pull-text">{{ carePullHeight >= 90 ? '松开进入关怀版' : '下拉进入关怀版' }}</text>
				<u-icon :name="carePullHeight >= 90 ? 'arrow-up' : 'arrow-down'" size="28" color="#18AF38"></u-icon>
			</view>
		</view>

		<care-pull-hint :visible="!showCareHint"></care-pull-hint>
		<skeleton-screen :show="isLoading" type="user"></skeleton-screen>

		<view class="me-hero" id="guide-me-hero">
			<image v-if="imgUrl" :src="`${imgUrl}user-bg.png`" class="me-hero__bg" mode="aspectFill"></image>
			<view class="me-hero__orb me-hero__orb--a"></view>
			<view class="me-hero__orb me-hero__orb--b"></view>
			<view class="me-hero__body">
				<view class="me-user">
					<view class="me-avatar">
						<u-avatar :src="userInfo.avatar" size="130"></u-avatar>
					</view>
					<view class="me-user__info" v-if="!isLogin">
						<view class="user-login-btn" @click="$u.route('/pages/public/login')">
							<text class="user-login-btn__text">立即登录</text>
						</view>
						<text class="user-login-sub">登录后解锁积分、活动与打卡</text>
					</view>
					<view class="me-user__info" v-else>
						<view class="me-name-row">
							<text class="me-name">{{userInfo.nickname}}</text>
							<u-tag v-if="userInfo.is_environmental" text="环保达人" bg-color="#FFD56A" color="#7A5A00" border-color="#FFD56A" size="mini" />
							<image :src="item.image" class="me-badge" mode="aspectFill" v-for="item in badge_list" :key="item.id"></image>
						</view>
						<text class="me-mobile">{{userInfo.mobile}}</text>
						<view class="me-staff" v-if="userInfo.type==2">
							<u-tag text="员工" bg-color="#fff" color="#B181CE" border-color="#fff" size="mini" shape="circle" />
						</view>
					</view>
				</view>
				<view class="me-stat" v-if="isLogin" @click="navigator('/pages/points/points')">
					<view class="me-stat__left">
						<text class="me-stat__label">账户积分</text>
						<text class="me-stat__num">{{userInfo.point || 0}}</text>
					</view>
					<view class="me-stat__go">
						<text class="me-stat__go-text">明细</text>
						<u-icon name="arrow-right" size="22" color="#18AF38"></u-icon>
					</view>
				</view>
			</view>
		</view>

		<view class="me-sheet glass-page__content">
			<view class="me-grow" v-if="!userInfo.is_environmental && isLogin">
				<image src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/jiankangdaren.png" class="me-grow__img" mode="aspectFill"></image>
				<view class="me-grow__body">
					<view class="me-grow__top">
						<text class="me-grow__title">环保达人</text>
						<view class="me-grow__help" @click="openDesc">
							<text>等级说明</text>
							<u-icon name="question-circle" size="26" color="#8a9b8d"></u-icon>
						</view>
					</view>
					<u-line-progress :striped="true" :percent="(userInfo.clock_count / userInfo.continuous_check) * 100" height="18" active-color="#18AF38" :show-percent="false"></u-line-progress>
					<text class="me-grow__desc">已连续打卡 <text class="me-grow__em">{{userInfo.clock_count}}</text> 天，还差 <text class="me-grow__warn">{{userInfo.remaining_count}}</text> 天</text>
				</view>
			</view>

			<view class="me-card glass-card" id="guide-me-common">
				<text class="me-card__title">常用功能</text>
				<view class="me-grid">
					<view class="me-grid__item" @click="navigator('/pages/points/points')">
						<view class="me-grid__icon me-grid__icon--a">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-1.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">积分明细</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/activity/my_activity')">
						<view class="me-grid__icon me-grid__icon--b">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-2.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">我的活动</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/order_list/order_list')">
						<view class="me-grid__icon me-grid__icon--c">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-3.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">兑换订单</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/msg_list/msg_list')">
						<view class="me-grid__icon me-grid__icon--d">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-4.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">消息通知</text>
					</view>
				</view>
			</view>
<!-- 下面暂时注释，应对审核 -->
			<!-- <view class="me-shop" v-if="userInfo.shop_status==0">
				<u-image width="100%" height="130" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250610/5457d5a5128588468ce6ff8019b57ae2.png"
					@click="navigator('/pages/settled/settled')"></u-image>
			</view>
			<view class="me-shop" v-if="userInfo.shop_status==1||userInfo.shop_status==3">
				<u-image width="100%" height="130" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250610/5457d5a5128588468ce6ff8019b57ae2.png"
					@click="navigator('/pages/settled/state')"></u-image>
			</view>
			<view class="me-shop" v-if="userInfo.shop_status==2">
				<u-image width="100%" height="130" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250610/3d7904797608c5d73cf85b06a2043fda.png"
					@click="navigator('/pages/store/store')"></u-image>
			</view> -->

			<view class="me-card" id="guide-me-service">
				<text class="me-card__title">我的服务</text>
				<view class="me-grid">
					<view class="me-grid__item" @click="navigator('pages/my_comment/my_comment')" v-if="!setting.app_test">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-5.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">我的评论</text>
					</view>
					<view class="me-grid__item" @click="navigator('pages/my_evaluation/my_evaluation')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-6.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">我的测评记录</text>
					</view>
					<view class="me-grid__item" @click="navigator('pages/my_questionnaire/my_questionnaire')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-7.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">我的问卷</text>
					</view>
					<view class="me-grid__item" @click="navigator('pages/my_reservation/my_reservation')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-8.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">我的预约</text>
					</view>
					<view class="me-grid__item" @click="navigator('pages/community/record')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-9.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">健康生活社区</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/flhd/record')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-10.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">垃圾分类打卡</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/flhd/list')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/40bfaab5aeaffdd0457cf369eca0e567.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">垃圾分类指导</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/answer/examine?id=1')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250512/ee564dd59c747c37e5b5b01eb2db7c54.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">答题考试</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/member/badge')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-9.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">我的徽章</text>
					</view>
				</view>
			</view>

			<view class="me-card">
				<text class="me-card__title">其他功能</text>
				<view class="me-grid">
					<view class="me-grid__item" @click="navigator('/pages/coupon/my_coupon')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-11.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">优惠券</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/address/index')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-12.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">地址管理</text>
					</view>
					<view class="me-grid__item" @click="openKf">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-13.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">联系客服</text>
					</view>
					<view class="me-grid__item" @click="navigator('/pages/member/info')">
						<view class="me-grid__icon">
							<u-icon name="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/user-14.png" :size="50"></u-icon>
						</view>
						<text class="me-grid__name">个人信息</text>
					</view>
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
							<u-button type="success" shape="circle" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}" hover-class="none"
								open-type="contact">在线客服</u-button>
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
					<view class="desc-text">{{setting.level_description}}</view>
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
	export default {
	components: { SkeletonScreen },
	mixins: [careGesture],
	data() {
		return {
			isLoading: true,
			mask_show: false,
			descShow: false,
			badge_list: [],
		}
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
				return this.$u.api.get_user_tab_home(params).then(res => {
					const d = res.data || {}
					if (!d.logged_in) return
					this.$u.vuex('userInfo', d.user_info || {})
					this.badge_list = d.badge_list || []
				}).catch(e => {
					console.log(e)
					if (e && e.msg) this.$u.toast(e.msg)
				}).finally(() => {
					this.isLoading = false
				})
			},
			updateTabBarSelected(index) {
				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()
				if (tabBar) {
					tabBar.setSelected(index)
				}
			},
			async get_new_badge_list() {
				try {
					let params = {}
					let res = await this.$u.api.get_new_badge_list(params)
					this.badge_list = res.data
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			navigator(url) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
			},
			getUserInfo() {
				this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				}).finally(() => {
					this.isLoading = false
				})
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
		}
	}
</script>

<style lang="scss" scoped>
	@import '@/static/styles/glass-ui.scss';

	.me-page {
		position: relative;
	}

	.me-hero {
		position: relative;
		overflow: hidden;
		background: linear-gradient(165deg, #18af38 0%, #12962e 58%, #0e7f26 100%);
		padding: 120rpx 32rpx 88rpx;
	}
	.me-hero__bg {
		position: absolute;
		left: 0;
		top: 0;
		width: 750rpx;
		height: 448rpx;
		opacity: 0.28;
		pointer-events: none;
	}
	.me-hero__orb {
		position: absolute;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.12);
		pointer-events: none;
	}
	.me-hero__orb--a {
		width: 220rpx;
		height: 220rpx;
		top: -60rpx;
		right: -40rpx;
	}
	.me-hero__orb--b {
		width: 120rpx;
		height: 120rpx;
		bottom: 40rpx;
		left: -30rpx;
		background: rgba(255, 220, 80, 0.16);
	}
	.me-hero__body {
		position: relative;
		z-index: 1;
	}

	.me-user {
		display: flex;
		flex-direction: row;
		align-items: center;
	}
	.me-avatar {
		flex-shrink: 0;
		border-radius: 50%;
		box-shadow: 0 0 0 6rpx rgba(255, 255, 255, 0.35);
		overflow: hidden;
	}
	.me-user__info {
		flex: 1;
		min-width: 0;
		margin-left: 28rpx;
	}
	.me-name-row {
		display: flex;
		flex-direction: row;
		align-items: center;
		flex-wrap: wrap;
	}
	.me-name {
		font-size: 36rpx;
		font-weight: 700;
		color: #fff;
		margin-right: 12rpx;
	}
	.me-badge {
		width: 36rpx;
		height: 36rpx;
		margin-left: 10rpx;
	}
	.me-mobile {
		display: block;
		margin-top: 12rpx;
		font-size: 24rpx;
		color: rgba(255, 255, 255, 0.86);
	}
	.me-staff {
		margin-top: 12rpx;
	}

	.me-stat {
		@include glass-surface-strong(22px);
		margin-top: 28rpx;
		border-radius: 24rpx;
		padding: 22rpx 28rpx;
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
	}
	.me-stat__left {
		display: flex;
		flex-direction: row;
		align-items: baseline;
	}
	.me-stat__label {
		font-size: 24rpx;
		color: #6a7b6e;
		margin-right: 12rpx;
	}
	.me-stat__num {
		font-size: 40rpx;
		font-weight: 700;
		color: #18af38;
		line-height: 1;
	}
	.me-stat__go {
		display: flex;
		flex-direction: row;
		align-items: center;
	}
	.me-stat__go-text {
		font-size: 24rpx;
		color: #18af38;
		font-weight: 600;
		margin-right: 4rpx;
	}

	.user-login-btn {
		display: inline-flex;
		flex-direction: row;
		align-items: center;
		justify-content: center;
		padding: 16rpx 40rpx;
		border-radius: 40rpx;
		background: #fff;
	}
	.user-login-btn__text {
		margin-left: 8rpx;
		color: #18af38;
		font-size: 30rpx;
		font-weight: 700;
	}
	.user-login-sub {
		display: block;
		margin-top: 14rpx;
		font-size: 22rpx;
		color: rgba(255, 255, 255, 0.82);
	}

	.me-sheet {
		position: relative;
		margin-top: -40rpx;
		padding: 0 24rpx 8rpx;
	}

	.me-grow {
		@include glass-surface(0.8, 20px);
		display: flex;
		flex-direction: row;
		align-items: center;
		border-radius: 28rpx;
		padding: 24rpx;
		margin-bottom: 20rpx;
	}
	.me-grow__img {
		width: 110rpx;
		height: 110rpx;
		flex-shrink: 0;
		margin-right: 20rpx;
	}
	.me-grow__body {
		flex: 1;
		min-width: 0;
	}
	.me-grow__top {
		display: flex;
		flex-direction: row;
		align-items: center;
		justify-content: space-between;
		margin-bottom: 16rpx;
	}
	.me-grow__title {
		font-size: 30rpx;
		font-weight: 700;
		color: #18af38;
	}
	.me-grow__help {
		display: flex;
		flex-direction: row;
		align-items: center;
		font-size: 22rpx;
		color: #8a9b8d;
	}
	.me-grow__desc {
		display: block;
		margin-top: 12rpx;
		font-size: 22rpx;
		color: #6a7b6e;
	}
	.me-grow__em {
		color: #18af38;
		font-weight: 700;
	}
	.me-grow__warn {
		color: #e67a22;
		font-weight: 700;
	}

	.me-card {
		border-radius: 28rpx;
		margin-bottom: 20rpx;
		overflow: hidden;
	}
	.me-card__title {
		display: block;
		padding: 28rpx 28rpx 8rpx;
		font-size: 30rpx;
		font-weight: 700;
		color: #1c2b1e;
	}

	.me-grid {
		display: flex;
		flex-wrap: wrap;
		padding: 8rpx 8rpx 20rpx;
	}
	.me-grid__item {
		width: 25%;
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 20rpx 8rpx 12rpx;
		box-sizing: border-box;
	}
	.me-grid__icon {
		width: 88rpx;
		height: 88rpx;
		border-radius: 28rpx;
		background: #f3f8f4;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-bottom: 12rpx;
	}
	.me-grid__icon--a { background: #e8f8ec; }
	.me-grid__icon--b { background: #e8f2ff; }
	.me-grid__icon--c { background: #fff1e6; }
	.me-grid__icon--d { background: #f1ebff; }
	.me-grid__name {
		font-size: 22rpx;
		color: #2c3a2f;
		text-align: center;
		line-height: 1.3;
	}

	.me-shop {
		margin-bottom: 20rpx;
		border-radius: 24rpx;
		overflow: hidden;
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
		color: #1c2b1e;
	}
	.kf-code {
		margin-top: 20rpx;
		display: flex;
		justify-content: center;
	}
	.kf-tip {
		margin-top: 10rpx;
		font-size: 24rpx;
		color: #6a7b6e;
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
		color: #1c2b1e;
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
		border-top: 1rpx solid #eef2ef;
		text-align: center;
		font-size: 32rpx;
		color: #18af38;
		font-weight: 700;
	}

	.me-tabbar-space {
		height: 172rpx;
	}
</style>
