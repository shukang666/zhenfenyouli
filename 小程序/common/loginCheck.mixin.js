// 强制登录检查工具函数
// 未登录(isLogin=false)的用户进入页面时，强制跳转到登录页
// 在各 tabbar 页面 onShow 首行调用，防止用户快速切 tab 绕过
let isRedirecting = false

// 排除列表：这些页面自己就是登录/完善信息页，不要拦截否则死循环
const EXCLUDE_PAGES = [
	'/pages/public/login',
	'/pages/user/info',
]

export function checkLogin(vm) {
	if (isRedirecting) return false

	// 获取当前页面路径，排除自身
	let pages = getCurrentPages()
	if (pages.length > 0) {
		let curPath = '/' + pages[pages.length - 1].route
		if (EXCLUDE_PAGES.includes(curPath)) return false
	}

	if (vm.isLogin) return false

	isRedirecting = true
	uni.reLaunch({
		url: '/pages/public/login',
		complete: () => {
			isRedirecting = false
		}
	})
	return true
}
