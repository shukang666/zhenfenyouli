<template>
	<view class="dk-hub dk-hub--page" v-if="!setting.app_test">
		<view class="dk-banner">
			<text class="dk-banner-title">已经打卡 {{ clockTotalDays }} 天</text>
			<text class="dk-banner-sub" v-if="!isLogin">登录后同步您的打卡天数</text>
		</view>

		<view class="dk-supervisor-banner" v-if="showSupervisorBanner">
			<view class="dk-supervisor-banner-inner">
				<text class="dk-supervisor-banner-t">累计打卡 {{ supervisorInvite.clock_days }} 天，欢迎申请督导员</text>
				<text class="dk-supervisor-banner-d">参与点位巡检、桶边督导，共建分类社区</text>
				<view class="dk-supervisor-banner-row">
					<view class="dk-supervisor-banner-btn dk-supervisor-banner-btn--go" @tap="goSupervisorApply">去申请</view>
					<view class="dk-supervisor-banner-btn dk-supervisor-banner-btn--later" @tap="dismissSupervisorBanner">稍后</view>
				</view>
			</view>
		</view>

		<!-- 新用户：学考一体（完整页 pre_learn） -->
		<view class="dk-block dk-block--intro" v-if="showLearnHub">
			<text class="dk-block-t">学考一体</text>
			<text class="dk-block-hint">学一点、考一点，通过后即可选择场景真分打卡</text>
			<view class="dk-learn-exam-card">
				<text class="dk-learn-exam-ico">📖</text>
				<text class="dk-learn-exam-desc">分类常识 + 5 道练一练，大约几分钟</text>
				<view
					class="dk-scene-btn dk-scene-btn--primary"
					hover-class="dk-scene-btn--press"
					:hover-stay-time="80"
					@tap="goPreLearn"
				>
					<text class="dk-scene-btn-t">开始学考 ›</text>
				</view>
			</view>
		</view>

		<view class="dk-block dk-block--scenes" v-if="showSceneHub">
			<text class="dk-block-t">去打卡</text>
			<text class="dk-block-hint">选择场景，设置或选择真分点后拍照打卡</text>
			<view class="dk-scene-list">
			<view
				class="dk-scene-card"
				v-for="item in scenes"
				:key="item.key"
				:class="'dk-scene-card--' + item.key"
				hover-class="dk-scene-card--press"
				:hover-stay-time="80"
				@click="navigateScene(item.key)"
			>
				<view class="dk-scene-cover-wrap">
					<view class="dk-scene-cover" :class="'dk-scene-cover--' + item.key">
						<u-icon :name="item.icon" :size="sceneIconSize" color="#ffffff"></u-icon>
					</view>
				</view>
				<view class="dk-scene-body">
					<text class="dk-scene-name">{{ item.name }}</text>
					<text class="dk-scene-desc">{{ item.desc }}</text>
					<view class="dk-scene-actions">
						<view class="dk-scene-btn dk-scene-btn--primary">
							<text class="dk-scene-btn-t">进入打卡 ›</text>
						</view>
					</view>
				</view>
			</view>
			</view>
		</view>

		<view class="dk-block dk-block--hist" v-if="showSceneHub">
			<text class="dk-block-t">看看别人怎么分</text>
			<view class="dk-hist-row">
				<view class="dk-hist-btn dk-hist-btn--pick" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goChoice">
					<text class="dk-hist-name">精选打卡</text>
					<text class="dk-hist-go">查看 ›</text>
				</view>
				<view class="dk-hist-btn dk-hist-btn--mine" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goMy">
					<text class="dk-hist-name">我的打卡</text>
					<text class="dk-hist-go">查看 ›</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import {
		hideSupervisorBanner,
		isSupervisorBannerHidden,
		shouldShowSupervisorInvite,
	} from '@/common/supervisorInvite.js'
	import { openSceneClockHub } from '@/common/sceneClockEntry.js'
	export default {
		data() {
			return {
				supervisorBannerLocalHide: false,
				introSections: [],
				introLoaded: false,
				introAcknowledged: false,
				scenes: [
					{
						key: 'home',
						name: '家庭',
						desc: '厨房、客厅、卫生间，全家日常投放',
						layout: 'entry',
						icon: 'home-fill',
					},
					{
						key: 'office',
						name: '办公',
						desc: '部分办公大厦设有真分角，可分类投放',
						layout: 'entry',
						icon: 'grid-fill',
					},
					{
						key: 'street',
						name: '沿街',
						desc: '路边、商铺门前的分类投放点',
						layout: 'entry',
						icon: 'map-fill',
					},
				],
			}
		},
		computed: {
			sceneIconSize() {
				return 76
			},
			clockTotalDays() {
				const u = this.userInfo || {}
				const n = Number(u.clock_total_days ?? u.junk_clock_days ?? 0)
				return n >= 0 ? n : 0
			},
			/** 学完且考过，展示打卡入口 */
			showSceneHub() {
				if (!this.isLogin) return false
				const u = this.userInfo || {}
				return Number(u.is_learn) === 1 && Number(u.is_answer) === 1
			},
			showLearnHub() {
				if (!this.isLogin) return false
				return !this.showSceneHub
			},
			needsExam() {
				return this.showLearnHub
			},
			supervisorInvite() {
				return (this.userInfo && this.userInfo.supervisor_invite) || {}
			},
			showSupervisorBanner() {
				if (this.supervisorBannerLocalHide || isSupervisorBannerHidden()) return false
				return shouldShowSupervisorInvite(this.supervisorInvite)
			},
		},
		onShow() {
			this.supervisorBannerLocalHide = isSupervisorBannerHidden()
			this.refreshUser().then(() => {
				if (!this.isLogin) return
				this.syncIntroAckFromUser()
			})
		},
		methods: {
			syncIntroAckFromUser() {
				const u = this.userInfo || {}
				this.introAcknowledged = Number(u.is_learn) === 1
			},
			loadIntroSections() {
				this.introLoaded = false
				return this.$u.api.get_clock_pre_learn().then(res => {
					const data = res.data || {}
					const list = data.sections || []
					this.introSections = list.map(sec => ({
						id: sec.id,
						name: sec.title || '',
						desc: '',
						contentHtml: this.$u.func.formatRichText(sec.content || ''),
					}))
					if (Number(data.done) === 1) {
						this.introAcknowledged = true
					}
					this.introLoaded = true
				}).catch(() => {
					this.introLoaded = true
				})
			},
			ackIntro() {
				if (!this.isLogin) {
					return this.$u.route('/pages/public/login')
				}
				if (this.introAcknowledged) return
				this.$u.api.complete_clock_pre_learn().then(() => {
					this.introAcknowledged = true
					return this.refreshUser()
				}).then(() => {
					this.syncIntroAckFromUser()
					this.$u.toast('学习已完成')
				}).catch(err => {
					this.$u.toast((err && err.msg) || '提交失败，请重试')
				})
			},
			onExamTap() {
				if (!this.introAcknowledged) {
					return this.$u.toast('请先阅读并确认以上内容')
				}
				this.goExam()
			},
			goPreLearn() {
				uni.navigateTo({ url: '/pages/flhd/pre_learn' })
			},
			goExam() {
				this.goPreLearn()
			},
			refreshUser() {
				if (!this.isLogin) return Promise.resolve()
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data || {})
				}).catch(() => {})
			},
			navigateScene(sceneKey) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.showSceneHub) {
					return this.$u.toast('请先完成打卡前学习并通过分类测验')
				}
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				const scene = this.scenes.find(s => s.key === sceneKey)
				if (!scene) return
				const hub = sceneKey === 'office' ? 'office' : sceneKey
				openSceneClockHub(this, hub)
			},
			goMy() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route('/pages/flhd/record')
			},
			goChoice() {
				this.$u.route('/pages/flhd/choice')
			},
			goSupervisorApply() {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				this.$u.route('/pages/zzhd/wmbb')
			},
			dismissSupervisorBanner() {
				this.supervisorBannerLocalHide = true
				hideSupervisorBanner()
			},
		}
	}
</script>

<style lang="scss" scoped>
.dk-hub {
	background: #e8f0ea;
}
.dk-hub--page {
	height: 100%;
	min-height: 100%;
	box-sizing: border-box;
	display: flex;
	flex-direction: column;
	padding: 0 28rpx;
	padding-bottom: calc(16rpx + env(safe-area-inset-bottom));
	overflow: hidden;
}
.dk-banner {
	margin: 0 -28rpx 0;
	flex-shrink: 0;
	padding: 28rpx 28rpx 48rpx;
	text-align: center;
	background: linear-gradient(
		180deg,
		#18af38 0%,
		#2bb84a 28%,
		#6ecf85 52%,
		#b8e4c4 72%,
		#e8f0ea 100%
	);
}
.dk-banner-title {
	display: block;
	font-size: 48rpx;
	font-weight: 800;
	color: #fff;
	letter-spacing: 4rpx;
	text-shadow: 0 4rpx 16rpx rgba(12, 90, 36, 0.22);
}
.dk-banner-sub {
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.88);
}
.dk-hero {
	padding: 36rpx 32rpx 32rpx;
	border-radius: 28rpx;
	background: linear-gradient(145deg, #6dca7c 0%, #18AF38 78%);
	box-shadow: 0 16rpx 36rpx rgba(24, 175, 56, 0.26);
}
.dk-hero-kicker {
	display: block;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.86);
}
.dk-hero-title {
	display: block;
	margin-top: 10rpx;
	font-size: 44rpx;
	font-weight: 800;
	color: #fff;
}
.dk-hero-desc {
	display: block;
	margin-top: 12rpx;
	font-size: 26rpx;
	line-height: 1.55;
	color: rgba(255, 255, 255, 0.92);
}
.dk-steps {
	margin-top: 28rpx;
	display: flex;
	align-items: center;
}
.dk-step {
	display: flex;
	align-items: center;
	opacity: 0.62;
}
.dk-step--on {
	opacity: 1;
}
.dk-step-n {
	width: 36rpx;
	height: 36rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.28);
	color: #fff;
	font-size: 22rpx;
	font-weight: 800;
	text-align: center;
	line-height: 36rpx;
}
.dk-step--on .dk-step-n {
	background: #fff;
	color: #148a2e;
}
.dk-step-t {
	margin-left: 8rpx;
	font-size: 22rpx;
	font-weight: 700;
	color: #fff;
}
.dk-step-line {
	flex: 1;
	height: 4rpx;
	margin: 0 12rpx;
	border-radius: 4rpx;
	background: rgba(255, 255, 255, 0.28);
}
.dk-step-line--on {
	background: #fff;
}
.dk-stats {
	position: relative;
	z-index: 1;
	flex-shrink: 0;
	margin-top: -22rpx;
	padding: 18rpx 12rpx;
	border-radius: 20rpx;
	background: #fff;
	display: flex;
	align-items: center;
	box-shadow: 0 8rpx 24rpx rgba(36, 48, 40, 0.06);
}
.dk-stat {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.dk-stat-num {
	font-size: 32rpx;
	font-weight: 800;
	color: #148a2e;
	line-height: 1.2;
}
.dk-stat-lab {
	margin-top: 4rpx;
	font-size: 22rpx;
	color: #8a958c;
	line-height: 1.25;
}
.dk-stat-line {
	width: 1rpx;
	height: 44rpx;
	background: #e8eee9;
}
.dk-supervisor-banner {
	flex-shrink: 0;
	margin-top: 12rpx;
}
.dk-supervisor-banner-inner {
	padding: 22rpx 24rpx;
	border-radius: 20rpx;
	background: linear-gradient(135deg, #e6fffb 0%, #ffffff 55%, #f0f9ff 100%);
	border: 1rpx solid rgba(8, 151, 156, 0.25);
	box-shadow: 0 8rpx 24rpx rgba(8, 151, 156, 0.08);
}
.dk-supervisor-banner-t {
	display: block;
	font-size: 28rpx;
	font-weight: 800;
	color: #006d75;
}
.dk-supervisor-banner-d {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	line-height: 1.45;
	color: #5c6b6d;
}
.dk-supervisor-banner-row {
	display: flex;
	margin-top: 18rpx;
}
.dk-supervisor-banner-btn {
	flex: 1;
	margin-left: 16rpx;
	padding: 14rpx 0;
	border-radius: 999rpx;
	text-align: center;
	font-size: 26rpx;
	font-weight: 700;
}
.dk-supervisor-banner-row .dk-supervisor-banner-btn:first-child {
	margin-left: 0;
}
.dk-supervisor-banner-btn--go {
	background: linear-gradient(141deg, #5cdbd3 0%, #08979c 100%);
	color: #fff;
}
.dk-supervisor-banner-btn--later {
	background: #eef2f1;
	color: #666;
}
.dk-block {
	margin-top: 16rpx;
}
.dk-block--scenes {
	flex: 1;
	min-height: 0;
	display: flex;
	flex-direction: column;
	margin-top: 12rpx;
}
.dk-block--intro {
	flex: 1;
	min-height: 0;
	display: flex;
	flex-direction: column;
	margin-top: 12rpx;
}
.dk-learn-exam-card {
	margin-top: 16rpx;
	padding: 32rpx 24rpx;
	border-radius: 18rpx;
	background: #fff;
	box-shadow: 0 6rpx 20rpx rgba(36, 48, 40, 0.06);
	text-align: center;
}
.dk-learn-exam-ico {
	font-size: 72rpx;
	line-height: 1;
}
.dk-learn-exam-desc {
	display: block;
	margin: 16rpx 0 28rpx;
	font-size: 28rpx;
	line-height: 1.55;
	color: #5a665e;
}
.dk-intro-scroll {
	flex: 1;
	min-height: 280rpx;
	max-height: 52vh;
}
.dk-intro-card {
	margin-bottom: 16rpx;
	padding: 22rpx;
	border-radius: 18rpx;
	background: #fff;
	box-shadow: 0 6rpx 20rpx rgba(36, 48, 40, 0.06);
}
.dk-intro-name {
	display: block;
	font-size: 30rpx;
	font-weight: 800;
	color: #148a2e;
}
.dk-intro-desc {
	display: block;
	margin-top: 8rpx;
	font-size: 24rpx;
	color: #8a958c;
}
.dk-intro-rich {
	margin-top: 12rpx;
	font-size: 26rpx;
	line-height: 1.6;
	color: #444;
}
.dk-intro-empty {
	padding: 32rpx;
	text-align: center;
	font-size: 26rpx;
	color: #999;
}
.dk-intro-ack {
	margin-top: 16rpx;
	padding: 18rpx;
	border-radius: 14rpx;
	text-align: center;
	font-size: 26rpx;
	font-weight: 700;
	color: #18af38;
	background: rgba(24, 175, 56, 0.1);
	border: 2rpx dashed rgba(24, 175, 56, 0.45);
}
.dk-intro-ack--done {
	background: rgba(24, 175, 56, 0.18);
	border-style: solid;
}
.dk-exam-block--inline {
	flex-shrink: 0;
}
.dk-learn-wrap {
	flex: 1;
	min-height: 0;
	display: flex;
	flex-direction: column;
}
.dk-learn-progress {
	flex-shrink: 0;
	margin-bottom: 16rpx;
	padding: 16rpx 18rpx;
	border-radius: 16rpx;
	background: #fff;
}
.dk-learn-progress-t {
	display: block;
	margin-bottom: 12rpx;
	font-size: 24rpx;
	font-weight: 600;
	color: #148a2e;
}
.dk-learn-scroll-wrap {
	flex: 1;
	min-height: 0;
	height: 0;
}
.dk-learn-scroll {
	width: 100%;
	box-sizing: border-box;
}
.dk-learn-item {
	display: flex;
	align-items: flex-start;
	margin-bottom: 12rpx;
	padding: 16rpx;
	border-radius: 16rpx;
	background: #fff;
	box-shadow: 0 4rpx 16rpx rgba(36, 48, 40, 0.05);
}
.dk-learn-item--press {
	opacity: 0.92;
	transform: scale(0.99);
}
.dk-learn-item-body {
	flex: 1;
	min-width: 0;
	margin-left: 16rpx;
	display: flex;
	flex-direction: column;
}
.dk-learn-item-name {
	display: block;
	font-size: 28rpx;
	font-weight: 700;
	color: #243028;
}
.dk-learn-item-desc {
	display: block;
	margin-top: 8rpx;
	font-size: 22rpx;
	line-height: 1.45;
	color: #8a958c;
}
.dk-learn-item-go {
	font-size: 24rpx;
	font-weight: 700;
	color: #18af38;
}
.dk-learn-item-act {
	margin-top: 12rpx;
	align-self: flex-end;
	padding: 8rpx 20rpx;
	border-radius: 999rpx;
	background: rgba(24, 175, 56, 0.12);
}
.dk-learn-empty {
	padding: 40rpx 20rpx;
	text-align: center;
	font-size: 26rpx;
	color: #8a958c;
}
.dk-exam-block {
	flex-shrink: 0;
	margin-top: 20rpx;
}
.dk-exam-block-t {
	margin-top: 4rpx;
}
.dk-exam-card {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	padding: 24rpx 22rpx;
	border-radius: 20rpx;
	background: #fff;
	box-shadow: 0 6rpx 20rpx rgba(36, 48, 40, 0.06);
}
.dk-exam-card--locked {
	opacity: 0.92;
	background: #f5f7f5;
}
.dk-exam-card--ready {
	border: 2rpx solid rgba(24, 175, 56, 0.45);
	box-shadow: 0 8rpx 24rpx rgba(24, 175, 56, 0.12);
}
.dk-exam-desc {
	font-size: 26rpx;
	line-height: 1.55;
	color: #555;
}
.dk-exam-card .dk-scene-btn {
	margin-top: 20rpx;
	align-self: center;
	min-width: 280rpx;
}
.dk-scene-btn--disabled {
	background: #c5ccc7 !important;
	box-shadow: none;
}
.dk-scene-btn--disabled .dk-scene-btn-t {
	color: #fff;
}
.dk-block--hist {
	flex-shrink: 0;
	margin-top: 10rpx;
}
.dk-block-t {
	display: block;
	margin-bottom: 6rpx;
	font-size: 30rpx;
	font-weight: 700;
	color: #243028;
}
.dk-block-hint {
	display: block;
	margin: 0 0 10rpx;
	font-size: 22rpx;
	color: #8a958c;
}
.dk-scene-list {
	flex: 1;
	min-height: 0;
	display: flex;
	flex-direction: column;
	gap: 12rpx;
}
.dk-card--press {
	transform: scale(0.97);
	opacity: 0.92;
}
.dk-scene-card {
	display: flex;
	align-items: stretch;
	flex: 1;
	min-height: 0;
	padding: 16rpx;
	border-radius: 20rpx;
	background: #fff;
	box-shadow: 0 6rpx 20rpx rgba(36, 48, 40, 0.06);
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-scene-card--press {
	transform: scale(0.99);
	opacity: 0.96;
}
.dk-scene-cover-wrap {
	flex-shrink: 0;
	align-self: stretch;
	height: 100%;
	width: auto;
	max-width: 40%;
	aspect-ratio: 1;
}
.dk-scene-cover {
	width: 100%;
	height: 100%;
	border-radius: 16rpx;
	overflow: hidden;
	background: #eef5f0;
	display: flex;
	align-items: center;
	justify-content: center;
}
.dk-scene-cover--home {
	background: linear-gradient(145deg, #f8ecd0 0%, #e8c56a 100%);
}
.dk-scene-cover--qinzi {
	background: linear-gradient(145deg, #fde8e6 0%, #f0a8b0 100%);
}
.dk-scene-cover--office {
	background: linear-gradient(145deg, #dceef8 0%, #7eb8d4 100%);
}
.dk-scene-cover--street {
	background: linear-gradient(145deg, #dff5e6 0%, #6fcf88 100%);
}
.dk-scene-body {
	flex: 1;
	min-width: 0;
	margin-left: 18rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	padding: 4rpx 0;
}
.dk-scene-name {
	display: block;
	font-size: 34rpx;
	font-weight: 800;
	color: #243028;
	line-height: 1.25;
}
.dk-scene-desc {
	display: -webkit-box;
	margin-top: 8rpx;
	font-size: 24rpx;
	line-height: 1.45;
	color: #8a958c;
	overflow: hidden;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	flex: 1;
}
.dk-scene-actions {
	display: flex;
	justify-content: flex-end;
	align-items: flex-end;
	margin-top: 10rpx;
}
.dk-scene-btn-row {
	display: flex;
	gap: 14rpx;
	margin-top: 10rpx;
}
.dk-scene-btn {
	flex: 1;
	padding: 14rpx 10rpx;
	border-radius: 999rpx;
	text-align: center;
	background: #f0f7f2;
	border: 2rpx solid #c8e6d0;
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-scene-btn--primary {
	flex: none;
	padding: 14rpx 28rpx;
	border: none;
	background: linear-gradient(141deg, #a4db88 0%, #18af38 100%);
	box-shadow: 0 6rpx 16rpx rgba(24, 175, 56, 0.28);
}
.dk-scene-btn--primary .dk-scene-btn-t {
	color: #fff;
	font-weight: 700;
}
.dk-scene-btn--press {
	transform: scale(0.96);
	opacity: 0.9;
}
.dk-scene-btn-t {
	font-size: 26rpx;
	font-weight: 700;
	color: #148a2e;
}
.dk-type-head {
	display: flex;
	align-items: center;
	margin-bottom: 8rpx;
}
.dk-back {
	margin-right: 16rpx;
	padding: 8rpx 16rpx;
	border-radius: 999rpx;
	background: #fff;
	font-size: 24rpx;
	font-weight: 700;
	color: #148a2e;
}
.dk-back--press {
	opacity: 0.8;
}
.dk-type-title {
	margin-bottom: 0;
	flex: 1;
}
.dk-types {
	display: flex;
	flex-wrap: wrap;
	justify-content: space-between;
}
.dk-type {
	position: relative;
	overflow: hidden;
	width: 48%;
	margin-bottom: 20rpx;
	padding: 28rpx 22rpx 24rpx;
	border-radius: 22rpx;
	min-height: 220rpx;
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-type::before {
	content: '';
	position: absolute;
	width: 120rpx;
	height: 120rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
	top: -40rpx;
	right: -28rpx;
	pointer-events: none;
}
.dk-type--recycle {
	background: linear-gradient(141deg, #2B8BB8 0%, #156A90 100%);
	box-shadow: 0 10rpx 22rpx rgba(21, 106, 144, 0.38);
}
.dk-type--food {
	background: linear-gradient(141deg, #C48A3A 0%, #8B5A14 100%);
	box-shadow: 0 10rpx 22rpx rgba(139, 90, 20, 0.38);
}
.dk-type--harm {
	background: linear-gradient(141deg, #E75E5E 0%, #B32626 100%);
	box-shadow: 0 10rpx 22rpx rgba(179, 38, 38, 0.38);
}
.dk-type--other {
	background: linear-gradient(141deg, #8A959C 0%, #5C656C 100%);
	box-shadow: 0 10rpx 22rpx rgba(92, 101, 108, 0.38);
}
.dk-type-name {
	position: relative;
	z-index: 1;
	display: block;
	font-size: 30rpx;
	font-weight: 800;
	color: #fff;
}
.dk-type-hint {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 10rpx;
	font-size: 22rpx;
	line-height: 1.45;
	color: rgba(255, 255, 255, 0.9);
}
.dk-type-go {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 22rpx;
	font-size: 22rpx;
	font-weight: 700;
	color: #fff;
}
.dk-hist-row {
	display: flex;
	margin: 0 -10rpx;
}
.dk-hist-btn {
	position: relative;
	overflow: hidden;
	flex: 1;
	margin: 0 10rpx;
	padding: 22rpx 18rpx 18rpx;
	border-radius: 20rpx;
	transition: transform 0.1s ease, opacity 0.1s ease;
}
.dk-hist-btn::before {
	content: '';
	position: absolute;
	width: 140rpx;
	height: 140rpx;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.22);
	top: -50rpx;
	right: -36rpx;
	pointer-events: none;
}
.dk-hist-btn::after {
	content: '';
	position: absolute;
	left: 0;
	bottom: 0;
	width: 100%;
	height: 8rpx;
	background: rgba(255, 255, 255, 0.32);
	pointer-events: none;
}
.dk-hist-btn--mine {
	background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
	box-shadow: 0 10rpx 24rpx rgba(24, 175, 56, 0.28);
}
.dk-hist-btn--pick {
	background: linear-gradient(141deg, #7dd3c7 0%, #2aa8a0 100%);
	box-shadow: 0 10rpx 24rpx rgba(42, 168, 160, 0.26);
}
.dk-hist-btn--press {
	transform: scale(0.96);
	opacity: 0.92;
}
.dk-hist-name {
	position: relative;
	z-index: 1;
	display: block;
	font-size: 32rpx;
	font-weight: 800;
	color: #fff;
}
.dk-hist-go {
	position: relative;
	z-index: 1;
	display: block;
	margin-top: 12rpx;
	font-size: 24rpx;
	font-weight: 700;
	color: #fff;
}
</style>

<style lang="scss">
page {
	height: 100%;
	background: #e8f0ea;
}
</style>
