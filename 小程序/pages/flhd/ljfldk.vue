<template>
	<view class="dk-hub dk-hub--page" v-if="!setting.app_test">
		<view class="dk-banner">
			<text class="dk-banner-title">践行真分</text>
		</view>

		<view class="dk-stats" v-if="isLogin">
			<view class="dk-stat">
				<text class="dk-stat-num">{{ userInfo.point || 0 }}</text>
				<text class="dk-stat-lab">我的积分</text>
			</view>
			<view class="dk-stat-line"></view>
			<view class="dk-stat">
				<text class="dk-stat-num">{{ streak }}</text>
				<text class="dk-stat-lab">连续打卡天数</text>
			</view>
			<view class="dk-stat-line"></view>
			<view class="dk-stat">
				<text class="dk-stat-num">{{ remainText }}</text>
				<text class="dk-stat-lab">距环保达人</text>
			</view>
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

		<!-- 新用户：先学指导 → 再考试，通过后才显示场景选择 -->
		<view class="dk-block dk-block--learn" v-if="showLearnHub">
			<text class="dk-block-t">分类学习</text>
			<text class="dk-block-hint">学完全部分类指导后，方可参加下方考试</text>

			<view class="dk-learn-wrap">
				<view class="dk-learn-progress" v-if="waste.waste_count">
					<text class="dk-learn-progress-t">学习进度 {{ waste.user_count || 0 }}/{{ waste.waste_count }}</text>
					<u-line-progress
						active-color="#18AF38"
						inactive-color="#e8eee9"
						:percent="learnPercent"
						:show-percent="false"
						height="12"
					></u-line-progress>
				</view>
				<view class="dk-learn-scroll-wrap">
					<scroll-view
						scroll-y
						class="dk-learn-scroll"
						:style="{ height: learnScrollPx + 'px' }"
						:show-scrollbar="false"
						@scrolltolower="onLearnScrollBottom"
					>
						<view
							class="dk-learn-item"
							v-for="(item, index) in waste_list"
							:key="'waste-' + index"
							hover-class="dk-learn-item--press"
							@tap="goLearnDetail(index)"
						>
							<u-image width="120" height="120" :src="item.thumb" border-radius="12"></u-image>
							<view class="dk-learn-item-body">
								<text class="dk-learn-item-name">{{ item.name }}</text>
								<text class="dk-learn-item-desc u-line-2">{{ item.desc }}</text>
								<view class="dk-learn-item-act" @tap.stop="goLearnDetail(index)">
									<text class="dk-learn-item-go">{{ item.is_study == 1 ? '继续学习' : '开始学习' }}</text>
								</view>
							</view>
						</view>
						<view class="dk-learn-empty" v-if="tutorialLoaded && !waste_list.length">暂无分类指导，请联系社区管理员</view>
					</scroll-view>
				</view>
			</view>

			<view class="dk-exam-block">
				<text class="dk-block-t dk-exam-block-t">分类考试</text>
				<view
					class="dk-exam-card"
					:class="{ 'dk-exam-card--locked': needsLearn, 'dk-exam-card--ready': !needsLearn && needsExam }"
				>
					<text class="dk-exam-desc" v-if="needsLearn">请先完成上方全部分类学习，再参加答题考试。</text>
					<text class="dk-exam-desc" v-else>已通过学习，参加答题考试通过后即可选择场景打卡；也可暂不考试，稍后再来。</text>
					<view
						class="dk-scene-btn"
						:class="needsLearn ? 'dk-scene-btn--disabled' : 'dk-scene-btn--primary'"
						:hover-class="needsLearn ? '' : 'dk-scene-btn--press'"
						:hover-stay-time="80"
						@tap="onExamTap"
					>
						<text class="dk-scene-btn-t">{{ needsLearn ? '请先完成学习' : '去答题考试' }}</text>
					</view>
				</view>
			</view>
		</view>

		<view class="dk-block dk-block--scenes" v-if="showSceneHub">
			<text class="dk-block-t">你在哪投放</text>
			<text class="dk-block-hint">点选场景进入，再选择垃圾类型并拍照打卡</text>
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

		<view class="dk-block dk-block--hist">
			<text class="dk-block-t">过往记录</text>
			<view class="dk-hist-row">
				<view class="dk-hist-btn dk-hist-btn--mine" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goMy">
					<text class="dk-hist-name">我的打卡</text>
					<text class="dk-hist-go">查看 ›</text>
				</view>
				<view class="dk-hist-btn dk-hist-btn--pick" hover-class="dk-hist-btn--press" :hover-stay-time="80" @click="goChoice">
					<text class="dk-hist-name">精选打卡</text>
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

	export default {
		data() {
			return {
				supervisorBannerLocalHide: false,
				waste_list: [],
				waste: {},
				wastePage: 1,
				wasteTotal: 0,
				tutorialLoaded: false,
				learnScrollPx: 280,
				scenes: [
					{
						key: 'home',
						name: '家庭',
						desc: '厨房、客厅、卫生间里的日常投放',
						layout: 'entry',
						icon: 'home-fill',
					},
					{
						key: 'qinzi',
						name: '亲子',
						desc: '家长带孩子一起学分类、动手投放',
						layout: 'entry',
						icon: 'plus-people-fill',
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
			streak() {
				const u = this.userInfo || {}
				return u.clock_days || u.continuous_days || u.clock_count || 0
			},
			remainText() {
				const n = (this.userInfo && this.userInfo.remaining_count) || 0
				return n > 0 ? n + '天' : '已达成'
			},
			/** 已通过学习 + 考试，展示家庭/亲子等场景 */
			showSceneHub() {
				if (!this.isLogin) return true
				const u = this.userInfo || {}
				return Number(u.is_learn) === 1 && Number(u.is_answer) === 1
			},
			showLearnHub() {
				if (!this.isLogin) return false
				return !this.showSceneHub
			},
			needsLearn() {
				if (this.learnAllFromWaste) return false
				const u = this.userInfo || {}
				return Number(u.is_learn) !== 1
			},
			learnAllFromWaste() {
				const total = Number(this.waste.waste_count) || 0
				const done = Number(this.waste.user_count) || 0
				return total > 0 && done >= total
			},
			needsExam() {
				const u = this.userInfo || {}
				return Number(u.is_learn) === 1 && Number(u.is_answer) !== 1
			},
			learnPercent() {
				const total = Number(this.waste.waste_count) || 0
				const done = Number(this.waste.user_count) || 0
				if (total <= 0) return 0
				return Math.min(100, Math.round((done / total) * 100))
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
			if (!this.isLogin) {
				this.refreshUser()
				return
			}
			this.refreshUser().then(() => {
				if (!this.showLearnHub) return
				return this.reloadWasteList()
			}).then(() => {
				this.measureLearnScroll()
			})
		},
		onReady() {
			this.measureLearnScroll()
		},
		methods: {
			measureLearnScroll() {
				if (!this.showLearnHub) return
				this.$nextTick(() => {
					uni.createSelectorQuery()
						.in(this)
						.select('.dk-learn-scroll-wrap')
						.boundingClientRect(rect => {
							if (rect && rect.height > 80) {
								this.learnScrollPx = Math.floor(rect.height)
							}
						})
						.exec()
				})
			},
			resetWasteList() {
				this.waste_list = []
				this.wastePage = 1
				this.wasteTotal = 0
				this.waste = {}
				this.tutorialLoaded = false
			},
			reloadWasteList() {
				this.wastePage = 1
				return this.loadWasteList(true)
			},
			loadWasteList(replaceFirstPage) {
				const page = this.wastePage
				return this.$u.api.get_waste_list({ page }).then(res => {
					this.tutorialLoaded = true
					const data = res.data || {}
					this.waste = data
					const list = (data.list && data.list.data) || []
					if (page === 1 || replaceFirstPage) {
						this.waste_list = list
					} else {
						this.waste_list = this.waste_list.concat(list)
					}
					this.wasteTotal = (data.list && data.list.total) || 0
					if (this.learnAllFromWaste) {
						this.syncLearnComplete()
					}
					this.measureLearnScroll()
				}).catch(() => {
					this.tutorialLoaded = true
				})
			},
			syncLearnComplete() {
				return this.refreshUser()
			},
			onLearnScrollBottom() {
				if (this.waste_list.length >= this.wasteTotal) return
				this.wastePage++
				this.loadWasteList()
			},
			goLearnDetail(index) {
				const item = this.waste_list[index]
				const id = item && (item.id || item.waste_id)
				if (!id) {
					return this.$u.toast('无法打开该指导')
				}
				this.$u.route('/pages/flhd/details?id=' + id)
			},
			onExamTap() {
				if (this.needsLearn) {
					return this.$u.toast('请先学完全部分类指导')
				}
				this.goExam()
			},
			goExam() {
				uni.navigateTo({ url: '/pages/answer/examine?id=1' })
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
					return this.$u.toast(this.needsExam ? '请先通过分类考试' : '请先学完分类指导')
				}
				if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
				const scene = this.scenes.find(s => s.key === sceneKey)
				if (!scene) return
				const apiKey = sceneKey === 'office' ? 'zhenfenjiao' : sceneKey
				this.$u.route(
					'/pages/flhd/add?scene_key=' +
						encodeURIComponent(apiKey) +
						'&scene=' +
						encodeURIComponent(scene.name),
				)
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
	font-size: 56rpx;
	font-weight: 800;
	color: #fff;
	letter-spacing: 8rpx;
	text-shadow: 0 4rpx 16rpx rgba(12, 90, 36, 0.22);
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
.dk-block--learn {
	flex: 1;
	min-height: 0;
	display: flex;
	flex-direction: column;
	margin-top: 12rpx;
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
