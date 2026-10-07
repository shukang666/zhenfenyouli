<template>
	<view class="scene-clock-root">
	<!-- 原型 Screen 2：欢迎参与真分打卡 -->
	<view v-if="flowStep === 'theme_intro'" class="zf-intro">
		<view class="zf-intro-hero">
			<view class="zf-intro-sky"></view>
			<view class="zf-intro-hill zf-intro-hill--back"></view>
			<view class="zf-intro-hill zf-intro-hill--front"></view>
			<view class="zf-intro-people">
				<text class="zf-intro-people-ico">👨‍👩‍👧</text>
				<text class="zf-intro-people-ico">♻️</text>
				<text class="zf-intro-people-ico">🌳</text>
			</view>
			<view class="zf-intro-hero-copy">
				<text class="zf-intro-hero-l1">{{ themeHeroSlogan.line1 }}</text>
				<text class="zf-intro-hero-l2">{{ themeHeroSlogan.line2 }}</text>
			</view>
		</view>
		<view class="zf-intro-sheet">
			<text class="zf-intro-welcome">{{ themeHeadline }}</text>
			<text class="zf-intro-sub">{{ themeSubline }}</text>
			<view class="zf-intro-step" v-for="step in themeSteps" :key="step.num">
				<view class="zf-intro-step-no">{{ step.num }}</view>
				<view class="zf-intro-step-body">
					<view class="zf-intro-step-head">
						<text class="zf-intro-step-ico">{{ step.icon }}</text>
						<text class="zf-intro-step-title">{{ step.title }}</text>
					</view>
					<text class="zf-intro-step-desc">{{ step.desc }}</text>
				</view>
			</view>
		</view>
		<view class="zf-foot">
			<view class="zf-btn zf-btn--primary" hover-class="zf-btn--press" @tap="onThemeStart">{{ themeCta }}</view>
		</view>
	</view>

	<!-- 选场景首屏（欢迎 + 三场景卡片，配图 COS 可后续替换） -->
	<view v-else-if="flowStep === 'theme_scenes'" class="zf-scenes zf-scenes--pick">
		<view class="zf-pick-welcome">
			<image
				v-if="scenePickWelcomeImgUrl"
				class="zf-pick-welcome__img"
				:src="scenePickWelcomeImgUrl"
				mode="widthFix"
				@error="onScenePickWelcomeImgError"
			/>
			<view v-else class="zf-pick-ph zf-pick-ph--welcome">
				<text class="zf-pick-welcome__txt">{{ scenePickWelcomeBanner }}</text>
			</view>
		</view>

		<view class="zf-pick-title-block">
			<view class="zf-pick-title-art">
				<view class="zf-pick-title-deco zf-pick-title-deco--tl" aria-hidden="true"></view>
				<view class="zf-pick-title-deco zf-pick-title-deco--br" aria-hidden="true"></view>
				<view class="zf-pick-title-inner">
					<view class="zf-pick-title-row zf-pick-title-row--1">
						<text class="zf-pick-title-txt">选择你今日</text>
						<view class="zf-pick-title-badge" aria-hidden="true">
							<text class="zf-pick-title-badge-ico">✓</text>
						</view>
					</view>
					<view class="zf-pick-title-row zf-pick-title-row--2">
						<text class="zf-pick-title-txt">的真分场景吧！</text>
						<view class="zf-pick-title-scribble" aria-hidden="true"></view>
					</view>
				</view>
			</view>
			<text v-if="scenePickHasClock === false" class="zf-pick-first-hint">开始第一次真分吧！</text>
		</view>

		<view class="zf-pick-list">
			<view
				class="zf-pick-card"
				v-for="(item, sIdx) in sceneHubCardsForPick"
				:key="item.hub"
				hover-class="zf-pick-card--press"
				:hover-stay-time="80"
				@tap="pickThemeHubByIndex(sIdx)"
			>
				<view class="zf-pick-card__illus">
					<image
						v-if="item.illusSrc"
						class="zf-pick-card__img"
						:src="item.illusSrc"
						mode="aspectFit"
						:data-illus-key="item.illusKey"
						@error="onScenePickCardImgErrorEvt"
					/>
					<view v-else class="zf-pick-ph zf-pick-ph--card">
						<text class="zf-pick-ph-label">场景配图</text>
					</view>
				</view>
				<view class="zf-pick-card__body">
					<view class="zf-pick-card__copy">
						<text class="zf-pick-card__title">{{ item.title }}</text>
						<text class="zf-pick-card__desc">{{ item.desc }}</text>
					</view>
					<view class="zf-pick-card__go" aria-hidden="true">
						<text class="zf-pick-card__go-ico">›</text>
					</view>
				</view>
			</view>
		</view>
	</view>

	<!-- 原型 Screen 5：今日真分任务说明 -->
	<view v-else-if="flowStep === 'theme_brief'" class="zf-brief">
		<text class="zf-brief-tagline">{{ themeBriefTagline }}</text>
		<view class="zf-brief-card">
			<text class="zf-brief-card-title">{{ themeBriefTaskTitle }}</text>
			<view class="zf-brief-tasks">
				<view class="zf-brief-task" v-for="(t, i) in themeBriefTasks" :key="i">
					<view class="zf-brief-task-ico-wrap">
						<text class="zf-brief-task-ico">{{ t.icon }}</text>
					</view>
					<text class="zf-brief-task-label">{{ t.label }}</text>
					<text class="zf-brief-task-sub">{{ t.sub }}</text>
				</view>
			</view>
		</view>
		<view class="zf-brief-scene">
			<text class="zf-brief-scene-k">您选的场景</text>
			<text class="zf-brief-scene-v">{{ pendingHubLabel }}</text>
		</view>
		<view class="zf-foot zf-foot--inline">
			<view class="zf-btn zf-btn--primary" hover-class="zf-btn--press" @tap="startHubFlow">{{ themeBriefCta }}</view>
		</view>
	</view>
	</view>
</template>

<script>
import {
	getThemeIntroHeadline,
	getThemeIntroSubline,
	getThemeIntroSteps,
	getThemeIntroCta,
	getThemeIntroHeroSlogan,
	getScenePickNavTitle,
	getScenePickWelcomeBanner,
	getSceneHubCards,
	getThemeBriefTagline,
	getThemeBriefTaskTitle,
	getThemeBriefTasks,
	getThemeBriefCta,
} from '@/common/sceneClockUserCopy.js'
import { getHub, normalizeClockHubKey } from '@/common/sceneClockHub.js'
import { openSceneClockHub } from '@/common/sceneClockEntry.js'
import settings from '@/common/settings'

export default {
	data() {
		return {
			flowStep: 'theme_scenes',
			themeHeadline: getThemeIntroHeadline(),
			themeSubline: getThemeIntroSubline(),
			themeSteps: getThemeIntroSteps(),
			themeCta: getThemeIntroCta(),
			themeHeroSlogan: getThemeIntroHeroSlogan(),
			scenePickHasClock: null,
			scenePickImgOk: { welcomeNew: true, welcomeBack: true, home: true, office: true, street: true },
			sceneHubCards: getSceneHubCards(),
			themeBriefTagline: getThemeBriefTagline(),
			themeBriefTaskTitle: getThemeBriefTaskTitle(),
			themeBriefTasks: getThemeBriefTasks(),
			themeBriefCta: getThemeBriefCta(),
			pendingHubKey: '',
		}
	},
	computed: {
		scenePickCosBase() {
			const base = settings.staticAssetBase || ''
			return base + (base.endsWith('/') ? '' : '/') + 'scene-pick/'
		},
		scenePickWelcomeBanner() {
			return getScenePickWelcomeBanner(this.scenePickHasClock !== false)
		},
		scenePickWelcomeImgUrl() {
			const ok = this.scenePickImgOk || {}
			const base = this.scenePickCosBase
			if (this.scenePickHasClock === false) {
				return ok.welcomeNew ? base + 'welcome-new.png' : ''
			}
			return ok.welcomeBack ? base + 'welcome-back.png' : ''
		},
		sceneHubCardsForPick() {
			const ok = this.scenePickImgOk || {}
			const base = this.scenePickCosBase
			return (this.sceneHubCards || []).map(c => ({
				...c,
				illusSrc: ok[c.illusKey] ? base + c.illusKey + '.png' : '',
			}))
		},
		pendingHubLabel() {
			const h = getHub(this.pendingHubKey)
			return (h && h.title) || ''
		},
	},
	onLoad(option) {
		const opt = option || {}
		const mode = String(opt.mode || opt.entry || '').trim()
		if (mode === 'intro') this.flowStep = 'theme_intro'
		else this.flowStep = 'theme_scenes'
		uni.setNavigationBarTitle({ title: getScenePickNavTitle() })
	},
	onShow() {
		if (this.flowStep === 'theme_scenes') {
			this.loadScenePickWelcomeState()
			this.ensureThemeSceneGate()
		}
	},
	methods: {
		loadScenePickWelcomeState() {
			if (!this.isLogin) {
				this.scenePickHasClock = false
				return Promise.resolve()
			}
			return this.$u.api.get_scene_clock_pick_state().then(res => {
				this.scenePickHasClock = Number((res.data || {}).has_scene_clock) === 1
			}).catch(() => {})
		},
		ensureThemeSceneGate() {
			if (!this.isLogin) return
			this.$u.api.getUserInfo().then(res => {
				const info = res.data || {}
				this.$u.vuex('userInfo', info)
				if (!info.is_learn || !info.is_answer) {
					uni.showModal({
						title: '还差一步',
						content: '第一次打卡前，请先完成学习与考试（大约1分钟）。',
						showCancel: false,
						confirmText: '去学考',
						confirmColor: '#38B061',
						success: r => { if (r.confirm) uni.navigateTo({ url: '/pages/flhd/pre_learn' }) },
					})
				}
			}).catch(() => {})
		},
		onThemeStart() {
			if (!this.isLogin) return this.$u.route('/pages/public/login')
			this.$u.api.getUserInfo().then(res => {
				const info = res.data || {}
				this.$u.vuex('userInfo', info)
				if (!info.is_learn || !info.is_answer) {
					uni.showModal({
						title: '还差一步',
						content: '第一次打卡前，请先完成学习与考试（大约1分钟）。',
						showCancel: false,
						confirmText: '去学考',
						success: r => { if (r.confirm) uni.navigateTo({ url: '/pages/flhd/pre_learn' }) },
					})
					return
				}
				this.flowStep = 'theme_scenes'
			}).catch(() => {})
		},
		onScenePickWelcomeImgError() {
			const key = this.scenePickHasClock === false ? 'welcomeNew' : 'welcomeBack'
			if (this.scenePickImgOk) this.$set(this.scenePickImgOk, key, false)
		},
		onScenePickCardImgErrorEvt(e) {
			const ds = (e && e.currentTarget && e.currentTarget.dataset) || {}
			const key = ds.illusKey || ds['illus-key'] || ''
			if (key && this.scenePickImgOk) this.$set(this.scenePickImgOk, key, false)
		},
		pickThemeHubByIndex(sIdx) {
			const card = (this.sceneHubCards || [])[sIdx]
			if (!card || !card.hub) return
			openSceneClockHub(this, card.hub)
		},
		startHubFlow() {
			if (!this.pendingHubKey) {
				this.$u.toast('请先选一个场景')
				this.flowStep = 'theme_scenes'
				return
			}
			openSceneClockHub(this, this.pendingHubKey)
		},
	},
}
</script>

<style lang="scss" scoped>
@import '@/common/sceneClock/scene-clock-styles.scss';
</style>
