/**
 * 小程序 Canvas / 本地路径：http(s) 先 downloadFile；data URI 写入 USER_DATA_PATH。
 * @param {string} src
 * @returns {Promise<string>}
 */
export function resolveMpImageSource(src) {
	const raw = String(src || '')
	if (!raw) return Promise.reject(new Error('empty image src'))
	if (/^data:image\//.test(raw)) return resolveMpImageDataUri(raw)
	// #ifdef MP-WEIXIN
	if (/^https?:\/\//.test(raw)) {
		return new Promise((resolve, reject) => {
			uni.downloadFile({
				url: raw,
				success: (res) => {
					if (res.statusCode === 200 && res.tempFilePath) resolve(res.tempFilePath)
					else reject(new Error('downloadFile fail status ' + res.statusCode))
				},
				fail: (err) => reject(err || new Error('downloadFile fail')),
			})
		})
	}
	// #endif
	return Promise.resolve(raw)
}

/**
 * 微信小程序无法稳定渲染 data:image/webp;base64，需写入 USER_DATA_PATH 后用本地路径。
 * @param {string} dataUri
 * @returns {Promise<string>}
 */
export function resolveMpImageDataUri(dataUri) {
	const raw = String(dataUri || '')
	if (!raw) return Promise.reject(new Error('empty data uri'))
	// #ifdef MP-WEIXIN
	const match = /^data:image\/(\w+);base64,(.+)$/.exec(raw)
	if (!match) return Promise.reject(new Error('invalid data uri'))
	const format = match[1]
	const body = match[2]
	const fsm = wx.getFileSystemManager()
	const filePath = `${wx.env.USER_DATA_PATH}/home_prd_navigator.${format}`
	const buffer = wx.base64ToArrayBuffer(body)
	return new Promise((resolve, reject) => {
		fsm.writeFile({
			filePath,
			data: buffer,
			encoding: 'binary',
			success: () => resolve(filePath),
			fail: (err) => reject(err || new Error('writeFile fail')),
		})
	})
	// #endif
	// #ifndef MP-WEIXIN
	return Promise.resolve(raw)
	// #endif
}
