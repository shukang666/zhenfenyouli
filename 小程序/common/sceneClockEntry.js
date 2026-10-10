/**
 * 活动 Tab / Banner / 低碳工作台 → 真分场景打卡流统一路由
 */

import { normalizeClockHubKey } from '@/common/sceneClockHub.js'
import {
	buildSceneClockPickUrl,
	buildSceneClockHubUrl,
	SCENE_CLOCK_PICK_PAGE,
	SCENE_CLOCK_LEGACY_PAGE,
	SCENE_CLOCK_HOME_CLOCK_PAGE,
	SCENE_CLOCK_HOME_SETUP_PAGE,
} from '@/common/sceneClock/sceneClockRoutes.js'

const DBG = '[SCF-DEBUG] sceneClockEntry'

function dbg(...args) {
	console.log(DBG, ...args)
}

export function buildSceneClockThemeUrl() {
	return buildSceneClockPickUrl({ mode: 'theme' })
}

export { buildSceneClockHubUrl }

/**
 * 解析 Banner / 运营配置的 url 字段
 * @returns {null | { kind: 'theme' } | { kind: 'hub', hub: string }}
 */
export function parseBannerSceneClockEntry(bannerUrl) {
	const url = String(bannerUrl || '').trim()
	dbg('parseBannerSceneClockEntry in', url)
	if (!url) return null

	const hubAlias = url.match(/^(?:scene-clock-hub|zhenfen-hub):(\w+)$/i)
	if (hubAlias) {
		const out = { kind: 'hub', hub: normalizeClockHubKey(hubAlias[1]) }
		dbg('parseBannerSceneClockEntry →', out)
		return out
	}

	if (
		url === 'scene-clock-intro' ||
		url === 'zhenfen' ||
		url.indexOf('scene-clock-intro') >= 0
	) {
		dbg('parseBannerSceneClockEntry → theme (alias)')
		return { kind: 'theme' }
	}

	if (url.indexOf('scene-clock-flow') >= 0 || url.indexOf('scene-clock-pick') >= 0) {
		const hubQ = url.match(/[?&]hub=([^&]+)/)
		if (hubQ) {
			const out = { kind: 'hub', hub: normalizeClockHubKey(decodeURIComponent(hubQ[1])) }
			dbg('parseBannerSceneClockEntry →', out)
			return out
		}
		dbg('parseBannerSceneClockEntry → theme (default pick)')
		return { kind: 'theme' }
	}

	dbg('parseBannerSceneClockEntry → null')
	return null
}

/** 总入口：选场景页 */
export function openSceneClockTheme(vm) {
	const url = buildSceneClockThemeUrl()
	dbg('openSceneClockTheme', { url, isLogin: !!(vm && vm.isLogin) })
	return vm.$u.route(url)
}

/** 考试通过等场景：replace 到选场景页，避免回到旧 hub */
export function redirectSceneClockTheme() {
	const url = buildSceneClockThemeUrl()
	uni.redirectTo({ url })
}

/**
 * 学考门禁后进入三场景选场景页（首页快捷真分、关怀版去真分等共用）
 * @returns {Promise<boolean>} 是否已进入选场景页
 */
export async function runSceneClockLearnExamGate(vm) {
	if (!vm) return false
	if (!vm.isLogin) {
		vm.$u.route('/pages/public/login')
		return false
	}
	let info = vm.userInfo || {}
	try {
		const res = await vm.$u.api.getUserInfo()
		info = res.data || {}
		vm.$u.vuex('userInfo', info)
	} catch (e) {
		console.log('[sceneClockEntry] 刷新用户状态失败:', e)
	}
	if (!info.is_learn) {
		uni.showModal({
			title: '提示',
			content: '请先完成真分前学习，通过答题考试后可参与垃圾分类真分',
			showCancel: false,
			confirmText: '去学习',
			confirmColor: '#39BF3E',
			success: (res) => {
				if (res.confirm) uni.navigateTo({ url: '/pages/flhd/pre_learn' })
			},
		})
		return false
	}
	if (!info.is_answer) {
		uni.showModal({
			title: '提示',
			content: '学习已完成，请通过垃圾分类答题考试后即可真分',
			showCancel: false,
			confirmText: '去考试',
			confirmColor: '#39BF3E',
			success: (res) => {
				if (res.confirm) vm.$u.route('/pages/answer/examine?id=1')
			},
		})
		return false
	}
	openSceneClockTheme(vm)
	return true
}

/** 场景深链：家庭 / 办公 / 沿街独立页 */
export function openSceneClockHub(vm, hub) {
	const url = buildSceneClockHubUrl(hub)
	dbg('openSceneClockHub', {
		hubIn: hub,
		url,
		isLogin: !!(vm && vm.isLogin),
		community_id: vm && vm.userInfo && vm.userInfo.community_id,
	})
	if (!vm.isLogin) {
		dbg('openSceneClockHub → login')
		return vm.$u.route('/pages/public/login')
	}
	return vm.$u.route(url)
}

export function openSceneClockFromBannerEntry(vm, entry) {
	dbg('openSceneClockFromBannerEntry', entry)
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

export {
	SCENE_CLOCK_PICK_PAGE,
	SCENE_CLOCK_LEGACY_PAGE,
	SCENE_CLOCK_HOME_CLOCK_PAGE,
	SCENE_CLOCK_HOME_SETUP_PAGE,
}
