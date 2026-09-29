const home = {
	// 获取我的徽章列表
	get_new_badge_list: (params = {}) => uni.$u.post('/wxapp/active/get_new_badge_list', params),
	// 活动分类
	get_new_cate_active_list: (params = {}) => uni.$u.post('/wxapp/active/get_new_cate_active_list', params),
	// 社区活动分类列表（七贤岭 4 个 + 常规 5 个）
	get_community_cate_list: (params = {}) => uni.$u.get('/wxapp/active/get_community_cate_list', params),
	get_xsdld_cate_list: (params = {}) => uni.$u.get('/wxapp/active/get_xsdld_cate_list', params),
	// 活动页标签列表
	get_active_tab_list: (params = {}) => uni.$u.post('/wxapp/active/get_active_tab_list', params),
	// 活动所有标签（去重，用于底部标签栏）
	get_active_tags_list: (params = {}) => uni.$u.post('/wxapp/active/get_active_tags_list', params),
	// 活动列表
	get_new_active_list: (params = {}) => uni.$u.post('/wxapp/active/get_new_active_list', params),
	// 活动 Tab 首页聚合（Banner + 标签 + 推荐区 + 活动列表）
	get_activity_tab_home: (params = {}) => uni.$u.post('/wxapp/active/get_activity_tab_home', params),
	// 获取活动详情 
	get_new_active_detail: (params = {}) => uni.$u.post('/wxapp/active/get_new_active_detail', params),
	// 活动签到排行榜 
	get_new_active_ranking: (params = {}) => uni.$u.post('/wxapp/active/get_new_active_ranking', params),
	// 活动报名 
	post_new_active_sign: (params = {}) => uni.$u.post('/wxapp/active/post_new_active_sign', params),
	// 报名打卡 
	post_new_sign_clock: (params = {}) => uni.$u.post('/wxapp/active/post_new_sign_clock', params),
	// 线下活动现场签到
	post_offline_event_sign: (params = {}) => uni.$u.post('/wxapp/active/post_offline_event_sign', params),
	// 商家申请 
	add_shop_new: (params = {}) => uni.$u.post('/wxapp/check/add_shop', params),
	// 商家申请详情  
	get_shop_new: (params = {}) => uni.$u.post('/wxapp/check/get_shop', params),
	// 商家打卡 /
	add_handle_shop: (params = {}) => uni.$u.post('/wxapp/check/add_handle', params),
	// 商家打卡记录
	get_handle_list_shop: (params = {}) => uni.$u.post('/wxapp/check/get_handle_list', params),
	// 获取合同内容
	add_check_html: (params = {}) => uni.$u.post('/wxapp/check/add_check_html', params),
	// 提交打卡动作表单（即时表单通知）
	post_clock_action_form: (params = {}) => uni.$u.post('/wxapp/active/post_clock_action_form', params),
	// 记录打卡动作订阅授权状态（滞后推送用）
	post_clock_action_subscribe: (params = {}) => uni.$u.post('/wxapp/active/post_clock_action_subscribe', params),
	// 商户共治 - 提交
	postMerchantInspect: (params = {}) => uni.$u.post('/wxapp/check/merchant_inspect', params),
	// 商户共治 - 我的提交记录
	getMerchantInspectList: (params = {}) => uni.$u.get('/wxapp/check/merchant_inspect_list', params),
}
export default home