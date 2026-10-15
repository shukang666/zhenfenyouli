/**
 * 清除小程序本地登录态（与 vuex lifeData 一致）
 */
export function clearLocalSession(store) {
	if (store) {
		store.commit('$uStore', { name: 'userInfo', value: {} })
		store.commit('$uStore', { name: 'isLogin', value: false })
	}
	try {
		uni.removeStorageSync('vcode')
	} catch (e) {}
}
