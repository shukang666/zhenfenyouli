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



			<view class="hp-nav">

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

			<care-pull-hint :visible="!showCareHint"></care-pull-hint>



			<view class="hp-hero" id="guide-health-hub">

				<view class="hp-hero-glow"></view>

				<view class="hp-hero-accent"></view>

				<view class="hp-hero-inner">

					<text class="hp-hero-kicker">智能健康指导</text>

					<text class="hp-hero-title">了解身体状况，获取可执行建议</text>

					<text class="hp-hero-desc"

						>通过简短问答梳理您的健康关注点，结合基层医疗服务资源，为您提供清晰、可跟进的指导方向（非诊疗）。</text

					>

					<view

						class="hp-hero-cta"

						hover-class="hp-hero-cta--on"

						@tap="openAdvisorSurvey"

					>

						<text>开始健康问答</text>

						<u-icon name="arrow-right" size="26" color="#fff"></u-icon>

					</view>

					<text class="hp-hero-meta" v-if="lastCompletedHint">{{ lastCompletedHint }}</text>

				</view>

			</view>



			<view

				class="hp-grass"

				hover-class="hp-grass--on"

				@tap="openGrassrootsCare"

			>

				<view class="hp-grass-icon">

					<u-icon name="home-fill" size="40" color="#fff"></u-icon>

				</view>

				<view class="hp-grass-copy">

					<view class="hp-grass-title-row">

						<text class="hp-grass-title">基层医疗机构</text>

						<view class="hp-grass-badge"><text>重点入口</text></view>

					</view>

					<text class="hp-grass-desc">社区卫生服务中心、站点信息与可预约公共卫生服务</text>

				</view>

				<u-icon name="arrow-right" size="28" color="#1565D8"></u-icon>

			</view>

		</view>

		<view style="height: 172rpx; flex-shrink: 0;"></view>

		<page-guide page="health"></page-guide>

	</view>

</template>



<script>

	import { checkCommunity } from '@/common/communityCheck.mixin.js'

	import SkeletonScreen from '@/components/skeleton-screen/index.vue'

	import careGesture from '@/common/care-gesture.mixin.js'



	export default {

		components: { SkeletonScreen },

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

		},

		methods: {

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



.hp-hero {

	position: relative;

	margin-top: 12rpx;

	border-radius: 28rpx;

	overflow: hidden;

	box-shadow: 0 20rpx 48rpx rgba(13, 71, 161, 0.22);

}

.hp-hero-glow {

	position: absolute;

	inset: 0;

	background: linear-gradient(135deg, #1565d8 0%, #0d47a1 58%, #083a8a 100%);

}

.hp-hero-accent {

	position: absolute;

	top: 0;

	right: 0;

	width: 180rpx;

	height: 8rpx;

	background: linear-gradient(90deg, transparent, #ff5252 40%, #e53935 100%);

}

.hp-hero-inner {

	position: relative;

	z-index: 1;

	padding: 36rpx 32rpx 32rpx;

}

.hp-hero-kicker {

	display: inline-block;

	padding: 6rpx 16rpx;

	margin-bottom: 16rpx;

	font-size: 22rpx;

	font-weight: 700;

	color: #fff;

	background: rgba(229, 57, 53, 0.92);

	border-radius: 8rpx;

	letter-spacing: 1rpx;

}

.hp-hero-title {

	display: block;

	font-size: 36rpx;

	font-weight: 800;

	color: #fff;

	line-height: 1.35;

}

.hp-hero-desc {

	display: block;

	margin-top: 16rpx;

	font-size: 24rpx;

	color: rgba(255, 255, 255, 0.88);

	line-height: 1.55;

}

.hp-hero-cta {

	margin-top: 28rpx;

	display: inline-flex;

	align-items: center;

	gap: 10rpx;

	padding: 18rpx 32rpx;

	border-radius: 999rpx;

	background: #fff;

	color: #1565d8;

	font-size: 28rpx;

	font-weight: 700;

	box-shadow: 0 12rpx 28rpx rgba(0, 0, 0, 0.12);

}

.hp-hero-cta--on {

	opacity: 0.9;

	transform: scale(0.98);

}

.hp-hero-meta {

	display: block;

	margin-top: 16rpx;

	font-size: 22rpx;

	color: rgba(255, 255, 255, 0.75);

}



.hp-grass {

	display: flex;

	align-items: center;

	gap: 20rpx;

	margin-top: 24rpx;

	padding: 28rpx 24rpx;

	border-radius: 24rpx;

	background: #fff;

	border: 2rpx solid rgba(21, 101, 216, 0.18);

	box-shadow: 0 12rpx 32rpx rgba(21, 101, 216, 0.1);

}

.hp-grass--on {

	opacity: 0.92;

}

.hp-grass-icon {

	flex-shrink: 0;

	width: 88rpx;

	height: 88rpx;

	border-radius: 20rpx;

	display: flex;

	align-items: center;

	justify-content: center;

	background: linear-gradient(145deg, #1565d8, #0d47a1);

	box-shadow: inset 0 -4rpx 0 rgba(0, 0, 0, 0.08);

}

.hp-grass-copy {

	flex: 1;

	min-width: 0;

}

.hp-grass-title-row {

	display: flex;

	align-items: center;

	flex-wrap: wrap;

	gap: 10rpx;

	margin-bottom: 8rpx;

}

.hp-grass-title {

	font-size: 30rpx;

	font-weight: 800;

	color: #0d47a1;

}

.hp-grass-badge {

	padding: 2rpx 12rpx;

	border-radius: 6rpx;

	background: #ffebee;

	border: 1rpx solid rgba(229, 57, 53, 0.35);

}

.hp-grass-badge text {

	font-size: 18rpx;

	font-weight: 700;

	color: #e53935;

}

.hp-grass-desc {

	font-size: 22rpx;

	color: #64748b;

	line-height: 1.45;

}



</style>


