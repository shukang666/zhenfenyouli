<template>

	<view

		class="glass-page glass-page--health hp-page"

		@touchstart="onCareTouchStart"

		@touchmove="onCareTouchMove"

		@touchend="onCareTouchEnd"

	>

		<view class="glass-page__ambient"></view>

		<view class="glass-page__content">

			<view

				class="care-pull-layer"

				:style="{ height: carePullHeight + 'px', opacity: careHintOpacity }"

				v-if="showCareHint"

			>

				<view class="care-pull-content">

					<text class="care-pull-text">{{

						carePullHeight >= 90 ? '松开进入关怀版' : '下拉进入关怀版'

					}}</text>

					<u-icon

						:name="carePullHeight >= 90 ? 'arrow-up' : 'arrow-down'"

						size="28"

						color="#1565D8"

					></u-icon>

				</view>

			</view>

			<skeleton-screen :show="isLoading" type="health"></skeleton-screen>



			<care-pull-hint :visible="!showCareHint"></care-pull-hint>

			<view class="hp-immersive" id="guide-health-hub">
				<view class="hp-immersive-bg"></view>
				<view class="hp-immersive-glow hp-immersive-glow--a"></view>
				<view class="hp-immersive-glow hp-immersive-glow--b"></view>

				<view class="hp-nav hp-nav--scene">
					<view class="hp-nav-brand">
						<text class="hp-nav-title">健康服务</text>
						<view class="hp-nav-tag"><text>数字健康</text></view>
					</view>
					<u-image
						width="200"
						height="56"
						:src="healthNavLogo"
						mode="aspectFit"
					></u-image>
				</view>

				<health-virtual-persona
					embedded
					hide-caption
					size="hero"
					dark-caption
					:speaking="personaSpeaking"
				/>

				<view class="hp-assistant-panel">
					<text class="hp-assistant-bubble">{{ personaCaption }}</text>

					<view class="hp-assistant-actions">
						<view
							class="hp-cta"
							hover-class="hp-cta--on"
							@tap="openAdvisorSurvey"
						>
							<text class="hp-cta-kicker">智能健康指导</text>
							<text class="hp-cta-title">开始健康问答</text>
							<text class="hp-cta-desc">梳理关注点，获取可执行参考建议（非诊疗）</text>
							<view class="hp-cta-row">
								<text class="hp-cta-go">立即开始</text>
								<u-icon name="arrow-right" size="24" color="#fff"></u-icon>
							</view>
						</view>

						<view
							class="hp-link"
							hover-class="hp-link--on"
							@tap="openGrassrootsCare"
						>
							<u-icon name="home-fill" size="34" color="#90caf9"></u-icon>
							<view class="hp-link-copy">
								<view class="hp-link-title-row">
									<text class="hp-link-title">基层医疗机构</text>
									<text class="hp-link-badge">重点</text>
								</view>
								<text class="hp-link-desc">社区卫生服务与公共卫生资源</text>
							</view>
							<u-icon name="arrow-right" size="24" color="rgba(255,255,255,0.55)"></u-icon>
						</view>

						<text class="hp-assistant-meta" v-if="lastCompletedHint">{{ lastCompletedHint }}</text>
					</view>
				</view>
			</view>

		</view>

		<view style="height: 172rpx; flex-shrink: 0;"></view>

		<page-guide page="health"></page-guide>

	</view>

</template>



<script>

	import { checkCommunity } from '@/common/communityCheck.mixin.js'

	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	import HealthVirtualPersona from '@/components/health-virtual-persona/index.vue'
	import careGesture from '@/common/care-gesture.mixin.js'

	export default {
		components: { SkeletonScreen, HealthVirtualPersona },

		mixins: [careGesture],

		computed: {

			healthNavLogo() {

				const logo = this.setting && this.setting.min_logo

				if (logo) return logo

				return 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/logo.png'

			},

			lastCompletedHint() {

				if (!this.lastAdvisorCompletedAt) return ''

				return '您已完成最近一次健康问答，可随时重新评估'

			}

		},

		data() {

			return {

				isLoading: true,
				lastAdvisorCompletedAt: 0,
				personaSpeaking: false,
				personaCaption: '',
				personaTimer: null,
				healthHub: {

					banner: {

						title: '',

						subtitle: '',

						image: '',

						link_mode: 'advisor',

						survey_id: 0,

						url: '',

						route_type: 'navigateTo'

					}

				}

			}

		},

		onLoad() {

			this.loadAllData()

			this.readAdvisorCompleted()

		},

		onShow() {

			this.updateTabBarSelected(2)

			if (checkCommunity(this)) return

			this.readAdvisorCompleted()
			this.playHomePersonaIntro()
		},
		onHide() {
			this.stopPersonaSpeak()
		},
		methods: {
			stopPersonaSpeak() {
				if (this.personaTimer) {
					clearTimeout(this.personaTimer)
					this.personaTimer = null
				}
				this.personaSpeaking = false
			},
			playHomePersonaIntro() {
				this.stopPersonaSpeak()
				this.personaCaption =
					'您好，我是数字健康助手，可为您提供健康指导与身体状况梳理参考。'
				this.personaSpeaking = true
				this.personaTimer = setTimeout(() => {
					this.personaSpeaking = false
					this.personaTimer = null
				}, 4200)
			},
			readAdvisorCompleted() {

				try {

					this.lastAdvisorCompletedAt =

						Number(uni.getStorageSync('health_advisor_completed_at')) || 0

				} catch (e) {

					this.lastAdvisorCompletedAt = 0

				}

			},

			resolveBannerSurveyId() {

				const b = this.healthHub && this.healthHub.banner ? this.healthHub.banner : {}

				let id = parseInt(b.survey_id, 10) || 0

				if (id <= 0) {

					id = parseInt(b.exam_id, 10) || 0

				}

				return id

			},

			openAdvisorSurvey() {

				const surveyId = this.resolveBannerSurveyId()

				let url = '/pages/health/advisor-survey'

				if (surveyId > 0) {

					url += '?id=' + surveyId

				}

				this.openHealthRoute(url, 'navigateTo', { needLogin: false })

			},

			openGrassrootsCare() {

				this.openHealthRoute('/pages/healthcare/participate?id=6', 'navigateTo')

			},

			openHealthRoute(url, routeType, opts = {}) {

				const needLogin = opts.needLogin !== false

				if (needLogin && !this.isLogin) {

					return this.$u.route('/pages/public/login')

				}

				const type = routeType || 'navigateTo'

				if (type === 'switchTab') {

					return uni.switchTab({ url })

				}

				if (type === 'redirectTo') {

					return uni.redirectTo({ url })

				}

				uni.navigateTo({

					url,

					fail: () => {

						this.$u.toast('页面打开失败，请重新编译上传小程序')

					}

				})

			},

			async loadAllData() {

				const startTime = Date.now()

				try {

					await this.fetchHealthTabHome({ page: 1 })

				} catch (e) {

					console.log('数据加载失败:', e)

				} finally {

					const elapsed = Date.now() - startTime

					if (elapsed < 400) {

						setTimeout(() => {

							this.isLoading = false

						}, 400 - elapsed)

					} else {

						this.isLoading = false

					}

				}

			},

			fetchHealthTabHome({ page = 1 } = {}) {

				return this.$u.api

					.get_health_tab_home({ page })

					.then((res) => {

						const d = res.data || {}

						if (d.page_setting && d.page_setting.min_logo) {

							this.$u.vuex(

								'setting',

								Object.assign({}, this.setting || {}, {

									min_logo: d.page_setting.min_logo

								})

							)

						}

						if (d.health_hub) {

							const hub = d.health_hub

							this.healthHub = Object.assign({}, this.healthHub, hub, {

								banner: Object.assign({}, this.healthHub.banner, hub.banner || {})

							})

						}
					})

					.catch((err) => {

						this.$u.toast(err.msg)

					})

			},

			updateTabBarSelected(index) {

				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()

				if (tabBar) {

					tabBar.setSelected(index)

				}

			}

		}

	}

</script>



<style lang="scss">

@import '@/static/styles/glass-ui.scss';



.hp-page {

	padding: 0 28rpx 24rpx;

	box-sizing: border-box;

}



.hp-nav {

	display: flex;

	align-items: center;

	justify-content: space-between;

	padding: calc(env(safe-area-inset-top) + 12rpx) 4rpx 8rpx;

}

.hp-nav-brand {

	display: flex;

	align-items: center;

	gap: 12rpx;

}

.hp-nav-title {

	font-size: 40rpx;

	font-weight: 800;

	color: #0d47a1;

	letter-spacing: 1rpx;

}

.hp-nav--scene {
	position: relative;
	z-index: 2;
	padding-top: calc(env(safe-area-inset-top) + 8rpx);
}

.hp-nav--scene .hp-nav-title {
	color: #fff;
}

.hp-immersive {
	position: relative;
	margin: 0 -28rpx;
	min-height: calc(100vh - 180rpx);
	padding-bottom: 24rpx;
	overflow: hidden;
}

.hp-immersive-bg {
	position: absolute;
	inset: 0;
	background: linear-gradient(
		180deg,
		#020810 0%,
		#071a33 38%,
		#0d47a1 72%,
		#e8f1ff 100%
	);
}

.hp-immersive-glow {
	position: absolute;
	border-radius: 50%;
	pointer-events: none;
	filter: blur(40rpx);
}

.hp-immersive-glow--a {
	width: 520rpx;
	height: 520rpx;
	left: -120rpx;
	top: 120rpx;
	background: rgba(21, 101, 216, 0.35);
}

.hp-immersive-glow--b {
	width: 400rpx;
	height: 400rpx;
	right: -80rpx;
	top: 280rpx;
	background: rgba(229, 57, 53, 0.18);
}

.hp-assistant-panel {
	position: relative;
	z-index: 2;
	margin-top: -24rpx;
	padding: 0 28rpx;
}

.hp-assistant-bubble {
	display: block;
	padding: 20rpx 24rpx;
	margin: 0 8rpx 20rpx;
	font-size: 26rpx;
	line-height: 1.55;
	color: rgba(255, 255, 255, 0.92);
	text-align: center;
	background: rgba(255, 255, 255, 0.08);
	border: 1rpx solid rgba(255, 255, 255, 0.14);
	border-radius: 20rpx;
	backdrop-filter: blur(12rpx);
}

.hp-assistant-actions {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.hp-cta {
	padding: 28rpx 28rpx 24rpx;
	border-radius: 24rpx;
	background: rgba(255, 255, 255, 0.14);
	border: 1rpx solid rgba(255, 255, 255, 0.22);
	backdrop-filter: blur(16rpx);
}

.hp-cta--on {
	opacity: 0.92;
	transform: scale(0.99);
}

.hp-cta-kicker {
	display: inline-block;
	padding: 4rpx 12rpx;
	margin-bottom: 12rpx;
	font-size: 20rpx;
	font-weight: 700;
	color: #fff;
	background: rgba(229, 57, 53, 0.9);
	border-radius: 6rpx;
}

.hp-cta-title {
	display: block;
	font-size: 34rpx;
	font-weight: 800;
	color: #fff;
}

.hp-cta-desc {
	display: block;
	margin-top: 10rpx;
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.78);
	line-height: 1.45;
}

.hp-cta-row {
	display: inline-flex;
	align-items: center;
	gap: 8rpx;
	margin-top: 20rpx;
	padding: 12rpx 22rpx;
	border-radius: 999rpx;
	background: #fff;
}

.hp-cta-go {
	font-size: 26rpx;
	font-weight: 700;
	color: #1565d8;
}

.hp-link {
	display: flex;
	align-items: center;
	gap: 16rpx;
	padding: 22rpx 24rpx;
	border-radius: 20rpx;
	background: rgba(0, 0, 0, 0.18);
	border: 1rpx solid rgba(255, 255, 255, 0.12);
}

.hp-link--on {
	opacity: 0.9;
}

.hp-link-copy {
	flex: 1;
	min-width: 0;
}

.hp-link-title-row {
	display: flex;
	align-items: center;
	gap: 10rpx;
	margin-bottom: 6rpx;
}

.hp-link-title {
	font-size: 28rpx;
	font-weight: 700;
	color: #fff;
}

.hp-link-badge {
	padding: 2rpx 10rpx;
	font-size: 18rpx;
	font-weight: 700;
	color: #ffcdd2;
	border: 1rpx solid rgba(229, 57, 53, 0.5);
	border-radius: 6rpx;
}

.hp-link-desc {
	font-size: 22rpx;
	color: rgba(255, 255, 255, 0.65);
}

.hp-assistant-meta {
	display: block;
	padding: 8rpx 12rpx 0;
	font-size: 22rpx;
	color: rgba(13, 71, 161, 0.75);
	text-align: center;
}

.hp-nav-tag {

	padding: 4rpx 14rpx;

	border-radius: 8rpx;

	background: #e53935;

}

.hp-nav-tag text {

	font-size: 20rpx;

	font-weight: 700;

	color: #fff;

	letter-spacing: 2rpx;

}



</style>


