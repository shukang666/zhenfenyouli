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
