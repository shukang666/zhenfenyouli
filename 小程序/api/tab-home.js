/** 五个 TabBar 页各一个聚合接口 */
const tabHome = {
	get_index_tab_home: (params = {}) => uni.$u.post('/wxapp/basic/get_index_tab_home', params),
	get_activity_tab_home: (params = {}) => uni.$u.post('/wxapp/active/get_activity_tab_home', params),
	get_health_tab_home: (params = {}) => uni.$u.post('/wxapp/forum/get_health_tab_home', params),
	get_mall_tab_home: (params = {}) => uni.$u.post('/wxapp/goods/get_mall_tab_home', params),
	get_user_tab_home: (params = {}) => uni.$u.post('/wxapp/user/get_user_tab_home', params),
}
export default tabHome
