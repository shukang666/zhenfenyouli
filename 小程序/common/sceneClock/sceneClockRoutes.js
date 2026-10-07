import { normalizeClockHubKey } from '@/common/sceneClockHub.js'

/** 各场景真分独立页面（原 scene-clock-flow 拆分） */
export const SCENE_CLOCK_PICK_PAGE = '/pages/activity/scene-clock-pick'
export const SCENE_CLOCK_LEGACY_PAGE = '/pages/activity/scene-clock-flow'

const HUB_PAGE = {
	home: '/pages/activity/scene-clock-home',
	office: '/pages/activity/scene-clock-office',
	street: '/pages/activity/scene-clock-street',
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
	const base = sceneClockHubPagePath(key)
	const parts = []
	if (key === 'home' && extraQuery && String(extraQuery.home_setup || '') === '1') {
		parts.push('home_setup=1')
	}
	const qs = parts.length ? '?' + parts.join('&') : ''
	return base + qs
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
