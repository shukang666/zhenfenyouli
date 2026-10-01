/**
 * 活动 Tab / Banner / 低碳工作台 → 真分场景打卡流（scene-clock-flow）统一路由
 * 链路说明见 .cursor/skills/mp-home-activity/scene-clock-entry-routes.md
 */

import { normalizeClockHubKey } from '@/common/sceneClockHub.js'

const SCENE_CLOCK_PAGE = '/pages/activity/scene-clock-flow'

export function buildSceneClockThemeUrl() {
	return `${SCENE_CLOCK_PAGE}?mode=theme`
}

export function buildSceneClockHubUrl(hub) {
	const key = normalizeClockHubKey(hub)
	return `${SCENE_CLOCK_PAGE}?hub=${encodeURIComponent(key)}`
}

/**
 * 解析 Banner / 运营配置的 url 字段
 * @returns {null | { kind: 'theme' } | { kind: 'hub', hub: string }}
 */
export function parseBannerSceneClockEntry(bannerUrl) {
	const url = String(bannerUrl || '').trim()
	if (!url) return null

	const hubAlias = url.match(/^(?:scene-clock-hub|zhenfen-hub):(\w+)$/i)
	if (hubAlias) {
		return { kind: 'hub', hub: normalizeClockHubKey(hubAlias[1]) }
	}

	if (
		url === 'scene-clock-intro' ||
		url === 'zhenfen' ||
		url.indexOf('scene-clock-intro') >= 0
	) {
		return { kind: 'theme' }
	}

	if (url.indexOf('scene-clock-flow') >= 0) {
		const hubQ = url.match(/[?&]hub=([^&]+)/)
		if (hubQ) {
			return { kind: 'hub', hub: normalizeClockHubKey(decodeURIComponent(hubQ[1])) }
		}
		return { kind: 'theme' }
	}

	return null
}

/** 总入口：选场景页（theme_scenes），等同 Banner / 本周推荐置顶卡 */
export function openSceneClockTheme(vm) {
	return vm.$u.route(buildSceneClockThemeUrl())
}

/**
 * 场景深链：等同在选场景页点对应卡片后的 bootstrapHub(hub)
 * 与 pickThemeHub 一致：仅校验登录，学考门禁在 scene-clock-flow 内 ensureExamGate
 */
export function openSceneClockHub(vm, hub) {
	if (!vm.isLogin) {
		return vm.$u.route('/pages/public/login')
	}
	return vm.$u.route(buildSceneClockHubUrl(hub))
}

export function openSceneClockFromBannerEntry(vm, entry) {
	if (!entry) return false
	if (entry.kind === 'theme') {
		openSceneClockTheme(vm)
		return true
	}
	if (entry.kind === 'hub') {
		openSceneClockHub(vm, entry.hub)
		return true
	}
	return false
}
