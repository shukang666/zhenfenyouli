/** Tab 页 onShow 接口节流，避免切 Tab / 从后台回前台时重复打满接口 */
const lastAt = Object.create(null)

export function shouldTabRefresh(key, intervalMs = 30000) {
	const now = Date.now()
	const prev = lastAt[key]
	if (prev != null && now - prev < intervalMs) return false
	lastAt[key] = now
	return true
}

export function markTabRefreshed(key) {
	lastAt[key] = Date.now()
}
