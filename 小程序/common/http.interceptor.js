import {
	devUrl,
	prodUrl,
	contentType,
	headerTokenName,
	userTokenName,
	codeName,
	successCode,
	invalidCode,
} from '@/common/settings'
import { beginHttpLoading } from '@/common/http.loading.js'
import { deepResolveAssetUrls } from '@/utils/common.js'


// 这里的vm，就是我们在vue文件里面的this，所以我们能在这里获取vuex的变量，比如存放在里面的token变量
const install = (Vue, vm) => {
	// 此为自定义配置参数，具体参数见上方说明
	Vue.prototype.$u.http.setConfig({
		baseUrl: process.env.NODE_ENV === 'development' ? devUrl : prodUrl, // 请求的本域名
		method: 'GET',
		// 设置为json，返回后会对数据进行一次JSON.parse()
		dataType: 'json',
		// 关掉 uView 自带的延迟「请求中」，改由下面拦截器立刻出「加载中...」
		showLoading: false,
		originalData: false, // 是否在拦截器中返回服务端的原始数据
		loadingMask: true,
		// 配置请求头信息
		header: {
			'content-type': contentType
		},
	});

	// 请求拦截：带 token，等网络时立刻出加载中
	Vue.prototype.$u.http.interceptor.request = (config) => {
		config.header[headerTokenName] = vm.userInfo?. [userTokenName] || '';
		beginHttpLoading(config)
		return config;
	}

	// 响应拦截，判断状态码是否通过
	Vue.prototype.$u.http.interceptor.response = (res) => {
		if (res[codeName] == successCode) {
			if (res.data != null) {
				res.data = deepResolveAssetUrls(res.data)
			}
			return res
		} else if (res[codeName] == invalidCode) {
			// token失效，这里跳转登录
			vm.$u.func.showToast({
				title: '验证失败，请重新登录',
				success: () => {
					// 此为uView的方法，详见路由相关文档
					vm.$u.route('/pages/user/login/login')
				}
			})
			return false
		} else {
			// 如果返回false，则会调用Promise的reject回调，
			// 并将进入this.$u.post(url).then().catch(res=>{})的catch回调中，res为服务端的返回值
			// vm.$u.toast(res.msg)
			return false;
		}
	}
}

export default {
	install
}
