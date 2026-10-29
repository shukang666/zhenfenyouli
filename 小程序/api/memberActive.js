const memberActive = {
	memberActiveMeta: (params = {}) => uni.$u.post('/wxapp/member_active/meta', params),
	memberActiveList: (params = {}) => uni.$u.post('/wxapp/member_active/list', params),
	memberActivePublish: (params = {}) => uni.$u.post('/wxapp/member_active/publish', params),
	memberActiveMyList: (params = {}) => uni.$u.post('/wxapp/member_active/my_list', params),
}
export default memberActive
