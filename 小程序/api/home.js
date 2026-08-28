const home = {
	// 获取我的徽章列表 
	get_new_badge_list: (params = {}) => uni.$u.post('/wxapp/active/get_new_badge_list', params),
	// 活动分类
	get_new_cate_active_list: (params = {}) => uni.$u.post('/wxapp/active/get_new_cate_active_list', params),
	// 活动页标签列表
	get_active_tab_list: (params = {}) => uni.$u.post('/wxapp/active/get_active_tab_list', params),
	// 活动列表
	get_new_active_list: (params = {}) => uni.$u.post('/wxapp/active/get_new_active_list', params),
	// 获取活动详情 
	get_new_active_detail: (params = {}) => uni.$u.post('/wxapp/active/get_new_active_detail', params),
	// 活动签到排行榜 
	get_new_active_ranking: (params = {}) => uni.$u.post('/wxapp/active/get_new_active_ranking', params),
	// 活动报名 
	post_new_active_sign: (params = {}) => uni.$u.post('/wxapp/active/post_new_active_sign', params),
	// 报名打卡 
	post_new_sign_clock: (params = {}) => uni.$u.post('/wxapp/active/post_new_sign_clock', params),
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
}
export default home