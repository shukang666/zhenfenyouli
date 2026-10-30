const memberActive = {
	memberActiveMeta: (params = {}) => uni.$u.post('/wxapp/member_active/meta', params),
	memberActiveList: (params = {}) => uni.$u.post('/wxapp/member_active/list', params),
	memberActivePublish: (params = {}) => uni.$u.post('/wxapp/member_active/publish', params),
	memberActiveMyList: (params = {}) => uni.$u.post('/wxapp/member_active/my_list', params),
	memberActiveMyPublish: (params = {}) => uni.$u.post('/wxapp/member_active/my_publish', params),
	memberActiveWithdraw: (params = {}) => uni.$u.post('/wxapp/member_active/withdraw_publish', params),
	memberActivePublishSignList: (params = {}) =>
		uni.$u.post('/wxapp/member_active/publish_sign_list', params),
	memberActivePublishCheckinList: (params = {}) =>
		uni.$u.post('/wxapp/member_active/publish_checkin_list', params),
}
export default memberActive
