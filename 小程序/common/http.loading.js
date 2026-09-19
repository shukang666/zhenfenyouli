/**
 * 接口加载态：只服务网络请求。
 * 并发计数，全部结束才关掉，避免第一个接口返回就把「加载中」掐掉。
 */
let pending = 0
let shown = false

const SILENT = [
	'find_setting'
]

export function shouldShowHttpLoading(config) {
	if (!config || config.showLoading === false) return false
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
	if (pending <= 0) {
		pending = 0
		if (shown) {
			shown = false
			uni.hideLoading()
		}
	}
}
