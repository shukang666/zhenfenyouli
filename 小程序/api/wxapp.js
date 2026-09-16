const wxapp = {
	// 小程序授权登录
    wxappLogin: (params = {}) => uni.$u.post('/Index/wxappLogin', params),
	// 小程序授权手机号登录
    decryptPhone: (params = {}) => uni.$u.post('/wxapp/public/decrypt_phone', params),
	
	// 公众号获取签名
    getSignPackage: (params = {}) => uni.$u.post('/WechatScript/getSignPackage', params),
	
	// 配置信息
	findSetting: (params = {}) => uni.$u.post('/wxapp/basic/find_setting', params),
	// 获取页面图片配置（七贤岭背景图等）
	getPageConfig: () => uni.$u.post('/wxapp/basic/find_setting', {}),

	// 获取用户信息
	getUserInfo: (params = {}) => uni.$u.post('/wxapp/user/get_user_info', params),

	// 每日签到
	dailySign: (params = {}) => uni.$u.post('/wxapp/user/daily_sign', params),
	// 签到状态
	getSignStatus: (params = {}) => uni.$u.post('/wxapp/user/sign_status', params),
		// 更新用户信息
	updateMember: (params = {}) => uni.$u.post('/wxapp/user/update_user', params),

	// 随手拍提交
	postShotReport: (params = {}) => uni.$u.post('/wxapp/shot_report/post', params),

	// 分类知识挑战 - 获取题目（随机组卷）
	getQuiz: (params = {}) => uni.$u.get('/wxapp/quiz/get_quiz', params),
	// 分类知识挑战 - 提交答案
	submitQuiz: (params = {}) => uni.$u.post('/wxapp/quiz/submit_quiz', params),
	// 分类知识挑战 - 检查今日是否已答
	checkTodayQuiz: (params = {}) => uni.$u.get('/wxapp/quiz/check_today', params),

}
export default wxapp