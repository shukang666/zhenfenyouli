/**
 * @copyright
 * @description 全局变量配置
 */
module.exports = {
	// 应用名
	name: '甄分友礼',
	// 版本号
	version: '1.0.0',
	// 开发环境接口Url
	devUrl: 'https://admin.circular-economy.cn/api',
	// 线上环境接口Url
	prodUrl: 'https://admin.circular-economy.cn/api',
	// 站点 public 静态资源（Lottie 等，不走 COS）
	sitePublicUrl: 'https://admin.circular-economy.cn',
	healthAdvisorLottiePath: '/static/lottie/health-advisor-idle.json',
	// 腾讯云 COS 公网根（无尾斜杠）
	cosPublicBase: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com',
	// 业务上传图（xcxkf052/...，无 /upload/ 段）
	staticUploadBase: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/',
	// 原 public/static 资源在 COS 上的前缀（需同步 public/static → 桶内 static/）
	staticAssetBase: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/',
	// H5授权登陆Url
	// loginUrl: 'https://xcxkf052.aubye.com/api/common/public/h5_login',
	// 上传接口完整地址（uni.uploadFile / u-upload 直接用，勿再拼 /wxapp/public/upload_asset）
	uploadUrl: 'https://admin.circular-economy.cn/api/wxapp/public/upload_asset',
	// 微信分享卡片封面（5:4）
	shareCoverUrl: 'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/share.png',
	// 配后端数据的接收方式application/json;charset=UTF-8或者application/x-www-form-urlencoded;charset=UTF-8
	contentType: 'application/json;charset=UTF-8',
	// 后端返回状态码，如code,status
	codeName:'code',
	// 是否显示请求中loading（实际由 http.interceptor 出「加载中...」，此处仅作兜底说明）
	showLoading: true,
	// 请求loading的文字提示
	loadingText: '加载中...',
	// 请求头中身份验证字段名称
	headerTokenName: 'openid',
	// 用户信息中身份验证字段名称
	userTokenName: 'openid',
	// 拦截器已改为立刻出加载中，此值不再驱动 uView 延迟 loading
	loadingTime: 0,
	// 操作正常code
	successCode: 1,
	// 登录失效code
	invalidCode: 100,
	// 公众号appid
	wechatAppId: '',
	// 公众号授权方式snsapi_base或snsapi_userinfo
	wechatScope: 'snsapi_userinfo',
	//腾讯/高德地图开发者KEY
	lbsKEY: ''
}