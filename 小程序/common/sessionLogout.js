/**
 * 清除小程序本地登录态（与 vuex lifeData 一致）
 * @param {object} [vm] 当前页 this，用于 $u.vuex
 */
export function clearLocalSession(vm) {
	try {
		const tmp = uni.getStorageSync('lifeData') || {}
		tmp.userInfo = {}
		tmp.isLogin = false
		uni.setStorageSync('lifeData', tmp)
	} catch (e) {}
	try {
		uni.removeStorageSync('vcode')
	} catch (e) {}
	if (vm && vm.$u && vm.$u.vuex) {
		vm.$u.vuex('userInfo', {})
		vm.$u.vuex('isLogin', false)
	} else if (vm && vm.$store) {
		vm.$store.commit('$uStore', { name: 'userInfo', value: {} })
		vm.$store.commit('$uStore', { name: 'isLogin', value: false })
	}
}
