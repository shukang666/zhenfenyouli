export const PENDING_KEY = 'pending_after_login'

export function savePendingActiveDetail(id) {
	if (!id && id !== 0) return
	let sid = String(id)
	try {
		sid = decodeURIComponent(sid)
	} catch (e) {}
	if (!sid) return
	uni.setStorageSync(PENDING_KEY, '/pages/activity/lsxf/details-new?id=' + sid)
}

export function consumePendingAfterLogin() {
	const url = uni.getStorageSync(PENDING_KEY) || ''
	if (url) uni.removeStorageSync(PENDING_KEY)
	return url
}

export function clearPendingAfterLogin() {
	uni.removeStorageSync(PENDING_KEY)
}

export function isActiveScanEntry() {
	try {
		const enter = (typeof wx !== 'undefined' && wx.getEnterOptionsSync)
			? wx.getEnterOptionsSync()
			: (typeof wx !== 'undefined' && wx.getLaunchOptionsSync ? wx.getLaunchOptionsSync() : null)
		const path = (enter && enter.path) || ''
		if (/details-new/.test(path)) return true
	} catch (e) {}
	try {
		const pages = getCurrentPages()
		const top = pages && pages.length ? pages[pages.length - 1] : null
		const route = (top && top.route) || ''
		if (/details-new/.test(route)) return true
	} catch (e) {}
	return !!uni.getStorageSync(PENDING_KEY)
}
