export default {
	secondhandMeta: (params = {}) => uni.$u.post('/wxapp/secondhand/meta', params),
	secondhandList: (params = {}) => uni.$u.post('/wxapp/secondhand/list', params),
	secondhandDetail: (params = {}) => uni.$u.post('/wxapp/secondhand/detail', params),
	secondhandPublish: (params = {}) => uni.$u.post('/wxapp/secondhand/publish', params),
	secondhandMyList: (params = {}) => uni.$u.post('/wxapp/secondhand/my_list', params),
	secondhandOffShelf: (params = {}) => uni.$u.post('/wxapp/secondhand/off_shelf', params),
	secondhandCreateIntent: (params = {}) => uni.$u.post('/wxapp/secondhand/create_intent', params),
}
