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
