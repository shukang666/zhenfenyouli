import { normalizeClockHubKey } from '@/common/sceneClockHub.js'

/** 各场景真分独立页面（原 scene-clock-flow 拆分） */
export const SCENE_CLOCK_PICK_PAGE = '/pages/activity/scene-clock-pick'
export const SCENE_CLOCK_LEGACY_PAGE = '/pages/activity/scene-clock-flow'

/** 家庭场景：今日真分 / 点位布置 分两个页面 */
export const SCENE_CLOCK_HOME_CLOCK_PAGE = '/pages/activity/scene-clock-home'
export const SCENE_CLOCK_HOME_SETUP_PAGE = '/pages/activity/scene-clock-home-setup'

const HUB_PAGE = {
	home: SCENE_CLOCK_HOME_CLOCK_PAGE,
	office: '/pages/activity/scene-clock-office',
	street: '/pages/activity/scene-clock-street',
}

const HOME_CLOCK_ROUTE = 'pages/activity/scene-clock-home'

export function openSceneClockHomeSetup({ replace = false } = {}) {
	const url = SCENE_CLOCK_HOME_SETUP_PAGE
	if (replace) {
		uni.redirectTo({ url })
		return
	}
	uni.navigateTo({
		url,
		fail: () => {
			uni.redirectTo({ url })
		},
	})
}

export function openSceneClockHomeClock({ replace = false } = {}) {
	const url = SCENE_CLOCK_HOME_CLOCK_PAGE
	if (replace) {
		uni.redirectTo({ url })
		return
	}
	uni.navigateTo({
		url,
		fail: () => {
			uni.redirectTo({ url })
		},
	})
}

/** 布置完成：从今日真分进入则返回上一页，否则进入今日真分页 */
export function finishSceneClockHomeSetupNavigation() {
	const pages = getCurrentPages()
	if (pages.length >= 2) {
		const prev = pages[pages.length - 2]
		if (prev && prev.route === HOME_CLOCK_ROUTE) {
			uni.navigateBack()
			return
		}
	}
	uni.redirectTo({ url: SCENE_CLOCK_HOME_CLOCK_PAGE })
}

export function sceneClockHubPagePath(hub) {
	const key = normalizeClockHubKey(hub)
	return HUB_PAGE[key] || SCENE_CLOCK_LEGACY_PAGE
}

export function buildSceneClockPickUrl(query) {
	const q = query && typeof query === 'object' ? query : {}
	const parts = []
	if (q.mode) parts.push('mode=' + encodeURIComponent(String(q.mode)))
	if (q.entry) parts.push('entry=' + encodeURIComponent(String(q.entry)))
	const qs = parts.length ? '?' + parts.join('&') : ''
	return SCENE_CLOCK_PICK_PAGE + qs
}

export function buildSceneClockHubUrl(hub, extraQuery) {
	const key = normalizeClockHubKey(hub)
	if (key === 'home' && extraQuery && String(extraQuery.home_setup || '') === '1') {
		return SCENE_CLOCK_HOME_SETUP_PAGE
	}
	const base = sceneClockHubPagePath(key)
	return base
}

/** 旧链接 scene-clock-flow?hub= → 新页面 */
export function resolveLegacySceneClockFlowRedirect(option) {
	const opt = option || {}
	const mode = String(opt.mode || opt.entry || '').trim()
	if (mode === 'theme' || mode === 'intro') {
		return buildSceneClockPickUrl({ mode: 'theme' })
	}
	const hubRaw = opt.hub != null ? String(opt.hub).trim() : ''
	if (!hubRaw) {
		return buildSceneClockPickUrl({})
	}
	return buildSceneClockHubUrl(hubRaw, opt)
}
