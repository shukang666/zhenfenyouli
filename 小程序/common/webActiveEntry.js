import { PENDING_KEY } from '@/common/scanSignEntry.js'
import settings from '@/common/settings.js'

/** 分享 / 扫码进入 Web 式活动的中转页（带 active_id，登录后签发 web_ctx） */
export function buildWebActiveEntryPath(activeId, extraQuery) {
	const id = String(activeId || '').trim()
	if (!id) return '/pages/activity/web-active-entry'
	let path = '/pages/activity/web-active-entry?active_id=' + encodeURIComponent(id)
	if (extraQuery && typeof extraQuery === 'object') {
		Object.keys(extraQuery).forEach((key) => {
			const v = extraQuery[key]
			if (v !== undefined && v !== null && String(v) !== '') {
				path += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(String(v))
			}
		})
	}
	return path
}

export function savePendingWebActiveEntry(activeId) {
	const path = buildWebActiveEntryPath(activeId)
	if (!path) return
	uni.setStorageSync(PENDING_KEY, path)
}

function decodeB64UrlToUtf8(b64url) {
	let s = String(b64url || '').replace(/-/g, '+').replace(/_/g, '/')
	while (s.length % 4) s += '='
	if (typeof atob !== 'function') return ''
	try {
		const binary = atob(s)
		const bytes = binary.split('').map((ch) => ch.charCodeAt(0))
		if (typeof TextDecoder !== 'undefined') {
			return new TextDecoder('utf-8').decode(new Uint8Array(bytes))
		}
		return decodeURIComponent(
			bytes.map((b) => '%' + ('00' + b.toString(16)).slice(-2)).join('')
		)
	} catch (e) {
		return ''
	}
}

function parseAidFromWebCtxToken(rawUrl) {
	const m = String(rawUrl || '').match(/[?&]web_ctx=([^&#]+)/i)
	if (!m) return ''
	let token = m[1]
	try {
		token = decodeURIComponent(token)
	} catch (e) {}
	const b64 = token.split('.')[0]
	if (!b64) return ''
	const json = decodeB64UrlToUtf8(b64)
	if (!json) return ''
	try {
		const payload = JSON.parse(json)
		const aid = payload && (payload.aid ?? payload.active_id)
		return aid ? String(aid) : ''
	} catch (e) {
		return ''
	}
}

/** 从 H5 地址 / web_launch_url 解析 active_id（query 或 web_ctx 票据 aid） */
export function parseActiveIdFromWebLaunchUrl(pageUrl) {
	let raw = String(pageUrl || '').trim()
	if (!raw) return ''
	try {
		raw = decodeURIComponent(raw)
	} catch (e) {}
	const m = raw.match(/[?&]active_id=(\d+)/i)
	if (m) return m[1]
	return parseAidFromWebCtxToken(raw)
}

/** @deprecated 使用 parseActiveIdFromWebLaunchUrl */
export function parseActiveIdFromWebViewPageUrl(pageUrl) {
	return parseActiveIdFromWebLaunchUrl(pageUrl)
}

export function syncWebActiveMpShare(vm, activeId, title) {
	if (!vm || !activeId) return
	const shareTitle = String(title || '社区低碳活动').trim() || '社区低碳活动'
	const path = buildWebActiveEntryPath(activeId, { title: shareTitle })
	if (!vm.$u) vm.$u = {}
	vm.$u.mpShare = {
		title: shareTitle,
		path,
		imageUrl: settings.shareCoverUrl,
	}
}

export function isOnePlusOneWebUrl(pageUrl) {
	let raw = String(pageUrl || '')
	try {
		raw = decodeURIComponent(raw)
	} catch (e) {}
	return /one-plus-one/i.test(raw)
}
