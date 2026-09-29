/**
 * 接口加载态。五个 Tab 页用骨架屏反馈，不出「加载中」挡住点击。
 */
let pending = 0
let shown = false

const SILENT = [
	'find_setting',
	'get_index_tab_home',
	'get_activity_tab_home',
	'get_health_tab_home',
	'get_mall_tab_home',
	'get_user_tab_home',
]

const TAB_ROUTES = [
	'pages/index/index',
	'pages/low_carbon/low_carbon',
	'pages/health/health',
	'pages/mall/mall',
	'pages/user/index'
]

function isTabPage() {
	try {
		const pages = getCurrentPages()
		if (!pages || !pages.length) return false
		const cur = pages[pages.length - 1]
		const route = (cur && (cur.route || cur.__route__)) || ''
		return TAB_ROUTES.indexOf(route) !== -1
	} catch (e) {
		return false
	}
}

export function shouldShowHttpLoading(config) {
	if (!config || config.showLoading === false) return false
	if (isTabPage()) return false
	const url = String(config.url || '')
	for (let i = 0; i < SILENT.length; i++) {
		if (url.indexOf(SILENT[i]) !== -1) return false
	}
	return true
}

export function beginHttpLoading(config) {
	if (!shouldShowHttpLoading(config)) {
		config._httpLoading = false
		return
	}
	config._httpLoading = true
	pending++
	if (!shown) {
		shown = true
		uni.showLoading({
			title: '加载中...',
			mask: true
		})
	}
}

export function endHttpLoading(config) {
	if (!config || !config._httpLoading) return
	config._httpLoading = false
	pending--
	if (pending > 0) return
	pending = 0
	if (shown) {
		shown = false
		uni.hideLoading()
	}
}
