/** 活动显示形式：与 cmf_active.display_mode 一致 */
export const ACTIVE_DISPLAY_CONFIG = 1
export const ACTIVE_DISPLAY_WEB = 2

/**
 * 为 Web 式活动 URL 追加 active_id（若尚未携带）
 * @param {string} webUrl
 * @param {number|string} activeId
 */
export function appendActiveIdToWebUrl(webUrl, activeId) {
	const url = String(webUrl || '').trim()
	if (!url) return ''
	const id = String(activeId || '')
	if (!id) return url
	if (/[?&]active_id=/.test(url)) return url
	return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'active_id=' + encodeURIComponent(id)
}

/**
 * 是否 Web 式活动（支持列表项或详情字段）
 * @param {object|number|string} itemOrId
 * @param {object} [extra] 仅传 id 时可附带 display_mode、web_url
 */
export function isWebDisplayActive(itemOrId, extra) {
	const item = typeof itemOrId === 'object' && itemOrId !== null ? itemOrId : { id: itemOrId, ...(extra || {}) }
	return Number(item.display_mode) === ACTIVE_DISPLAY_WEB && String(item.web_url || '').trim() !== ''
}

/**
 * 打开活动：配置式走 details-new，Web 式走 web-view 分包
 * @param {object|number|string} itemOrId 活动对象或 id
 * @param {object} [extra] 列表未带 display_mode 时由调用方传入
 */
export function buildActiveOpenPath(itemOrId, extra) {
	const item = typeof itemOrId === 'object' && itemOrId !== null ? itemOrId : { id: itemOrId, ...(extra || {}) }
	const id = item.id || itemOrId
	if (isWebDisplayActive(item)) {
		const pageUrl = appendActiveIdToWebUrl(item.web_url, id)
		return '/pages/h5/h5?url=' + encodeURIComponent(pageUrl)
	}
	return '/pages/activity/lsxf/details-new?id=' + id
}

export function openActiveDetail(itemOrId, extra) {
	const path = buildActiveOpenPath(itemOrId, extra)
	uni.navigateTo({ url: path })
}
