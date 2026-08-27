const wxapp = {
	// 小程序授权登录
    wxappLogin: (params = {}) => uni.$u.post('/Index/wxappLogin', params),
	// 小程序授权手机号登录
    decryptPhone: (params = {}) => uni.$u.post('/wxapp/public/decrypt_phone', params),
	
	// 公众号获取签名
    getSignPackage: (params = {}) => uni.$u.post('/WechatScript/getSignPackage', params),
	
	// 配置信息
	findSetting: (params = {}) => uni.$u.post('/wxapp/basic/find_setting', params),
	
	// 获取用户信息
	getUserInfo: (params = {}) => uni.$u.post('/wxapp/user/get_user_info', params),
	// 更新用户信息
	updateMember: (params = {}) => uni.$u.post('/wxapp/user/update_user', params),
}
export default wxapp