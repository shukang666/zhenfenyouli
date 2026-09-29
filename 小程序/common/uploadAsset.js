import { staticUploadBase } from '@/common/settings.js'

/**
 * 解析小程序 upload_asset 返回值：库内存 filepath，界面展示 url
 */
export function parseUploadAssetResponse(payload) {
	const d = (payload && payload.data) ? payload.data : (payload || {})
	const filepath = (d.filepath || d.file_path || '').trim()
	const url = (d.url || d.preview_url || '').trim()
	let savePath = filepath
	if (!savePath && url) {
		savePath = urlToRelativePath(url)
	}
	const showUrl = url || filepath
	return { savePath, showUrl }
}

export function urlToRelativePath(url) {
	if (!url || typeof url !== 'string') return ''
	const u = url.trim()
	if (!/^https?:\/\//i.test(u)) {
		return u.replace(/^\/+/, '').replace(/^upload\//, '')
	}
	let path = u.replace(/^https?:\/\/[^/]+\//i, '')
	path = path.replace(/\?.*$/, '')
	path = path.replace(/^\/+/, '')
	if (path.indexOf('upload/') === 0) {
		path = path.slice(7)
	}
	return path
}

/** 相对路径 → 可展示的完整 URL（与后端 cmf_get_asset_url 一致走 COS） */
export function resolveAssetShowUrl(pathOrUrl) {
	const p = (pathOrUrl || '').trim()
	if (!p) return ''
	if (/^https?:\/\//i.test(p)) return p
	const base = (staticUploadBase || '').replace(/\/+$/, '')
	if (!base) return p
	return base + '/' + p.replace(/^\/+/, '')
}
