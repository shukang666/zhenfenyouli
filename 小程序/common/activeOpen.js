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



export function buildH5WrapperPath(pageUrl) {

	return '/pages/h5/h5?url=' + encodeURIComponent(String(pageUrl || ''))

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

 * 解析 Web 式最终打开的 H5 地址（不含 web-view 外壳路径）

 * @param {object} item 含 web_launch_url / web_page_url / web_url

 * @param {number|string} [fallbackId]

 */

export function resolveWebActivityPageUrl(item, fallbackId) {

	if (!item || typeof item !== 'object') return ''

	if (item.web_launch_url) return String(item.web_launch_url)

	if (item.web_page_url) return String(item.web_page_url)

	const id = item.id || fallbackId

	if (item.web_url && id) return appendActiveIdToWebUrl(item.web_url, id)

	return ''

}



/**

 * 打开活动：配置式走 details-new，Web 式走 web-view 分包

 * @param {object|number|string} itemOrId 活动对象或 id

 * @param {object} [extra] 列表未带 display_mode 时由调用方传入

 * @param {object} [opts]

 * @param {function} [opts.issueContext] async (activeId) => { web_launch_url? }

 * @param {function} [opts.onNeedLogin] 未登录打开 Web 式时回调

 */

export function buildActiveOpenPath(itemOrId, extra, opts) {

	const item = typeof itemOrId === 'object' && itemOrId !== null ? itemOrId : { id: itemOrId, ...(extra || {}) }

	const id = item.id || itemOrId

	if (isWebDisplayActive(item)) {

		const pageUrl = resolveWebActivityPageUrl(item, id)

		return buildH5WrapperPath(pageUrl)

	}

	return '/pages/activity/lsxf/details-new?id=' + id

}



/**

 * 同步打开（无 web_ctx 时仅带 active_id；完整登录上下文请用 common.js 注入的 openActiveDetail）

 */

export function openActiveDetail(itemOrId, extra) {

	const path = buildActiveOpenPath(itemOrId, extra)

	uni.navigateTo({ url: path })

}



/**

 * 带 web_ctx 的 Web 式打开（需传入 issueContext，通常由 vm.$u.post 实现）

 */

export function openActiveDetailWithContext(itemOrId, extra, issueContext, onNeedLogin) {

	const item = typeof itemOrId === 'object' && itemOrId !== null ? itemOrId : { id: itemOrId, ...(extra || {}) }

	const id = item.id || itemOrId

	if (!isWebDisplayActive(item)) {

		uni.navigateTo({ url: '/pages/activity/lsxf/details-new?id=' + id })

		return Promise.resolve()

	}

	const openWithUrl = (pageUrl) => {

		uni.navigateTo({ url: buildH5WrapperPath(pageUrl) })

	}

	let pageUrl = resolveWebActivityPageUrl(item, id)

	if (!pageUrl && item.web_url) {

		pageUrl = appendActiveIdToWebUrl(item.web_url, id)

	}

	if (typeof issueContext !== 'function') {

		openWithUrl(pageUrl)

		return Promise.resolve()

	}

	return issueContext(id)

		.then((ctx) => {

			const launch = (ctx && ctx.web_launch_url) ? ctx.web_launch_url : pageUrl

			openWithUrl(launch || pageUrl)

		})

		.catch(() => {

			if (typeof onNeedLogin === 'function') {

				onNeedLogin()

				return

			}

			openWithUrl(pageUrl)

		})

}

