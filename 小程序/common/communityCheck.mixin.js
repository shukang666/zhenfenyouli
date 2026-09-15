// 强制完善信息检查工具函数
// 已登录但未设置小区(community_id 为 0/空)的用户，进入页面时同步强制跳转到完善信息页
// 在各 tabbar 页面的 onShow 中调用，防止用户快速切 tab 绕过首页检查
let isRedirecting = false

export function checkCommunity(vm) {
	if (isRedirecting) return false
	if (!vm.isLogin || !vm.userInfo || vm.userInfo.community_id) return false
	isRedirecting = true
	uni.navigateTo({
		url: '/pages/member/info',
		complete: () => {
			isRedirecting = false
		}
	})
	return true
}
