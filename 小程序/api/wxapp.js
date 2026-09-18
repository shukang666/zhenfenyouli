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

	// 督导监测提交
	postSupervisionMonitor: (params = {}) => uni.$u.post('/wxapp/supervision_monitor/post', params),

	// 分类知识挑战 - 获取题目（随机组卷）
	getQuiz: (params = {}) => uni.$u.get('/wxapp/quiz/get_quiz', params),
	// 分类知识挑战 - 提交答案
	submitQuiz: (params = {}) => uni.$u.post('/wxapp/quiz/submit_quiz', params),
	// 分类知识挑战 - 检查今日是否已答
	checkTodayQuiz: (params = {}) => uni.$u.get('/wxapp/quiz/check_today', params),

	// 分类知识 - 列表
	getKnowledgeList: (params = {}) => uni.$u.get('/wxapp/knowledge/get_list', params),
	// 分类知识 - 详情
	getKnowledgeDetail: (params = {}) => uni.$u.get('/wxapp/knowledge/get_detail', params),

	// 七贤岭学习 - 列表
	getQxlKnowledgeList: (params = {}) => uni.$u.get('/wxapp/qxl_knowledge/get_list', params),
	// 七贤岭学习 - 详情
	getQxlKnowledgeDetail: (params = {}) => uni.$u.get('/wxapp/qxl_knowledge/get_detail', params),

	// 七贤岭考试 - 获取题目
	getQxlQuiz: (params = {}) => uni.$u.get('/wxapp/qxl_quiz/get_quiz', params),
	// 七贤岭考试 - 提交答案
	submitQxlQuiz: (params = {}) => uni.$u.post('/wxapp/qxl_quiz/submit_quiz', params),
	// 七贤岭考试 - 检查今日是否已答
	checkTodayQxlQuiz: (params = {}) => uni.$u.get('/wxapp/qxl_quiz/check_today', params),

	// 志愿者申请 - 角色类型申请状态
	getCheckRole: (params = {}) => uni.$u.post('/wxapp/check/get_check', params),
	// 志愿者申请 - 提交申请（含 role_type）
	addCheckRole: (params = {}) => uni.$u.post('/wxapp/check/add_check', params),

	// 工作账号登录（APP端外勤人员）
	workLogin: (params = {}) => uni.$u.post('/wxapp/public/work_login', params),

	// 志愿督导 - 今日签到状态
	getVolunteerToday: (params = {}) => uni.$u.get('/wxapp/volunteer_supervision/today', params),
	// 志愿督导 - 提交签到
	postVolunteerSignIn: (params = {}) => uni.$u.post('/wxapp/volunteer_supervision/sign_in', params),
	// 志愿督导 - 我的历史记录
	getVolunteerMyList: (params = {}) => uni.$u.get('/wxapp/volunteer_supervision/my_list', params),

	// 整改任务 - 可领取列表
	getVolunteerTaskList: (params = {}) => uni.$u.get('/wxapp/volunteer_task/task_list', params),
	// 整改任务 - 领取
	acceptVolunteerTask: (params = {}) => uni.$u.post('/wxapp/volunteer_task/accept', params),
	// 整改任务 - 我的任务列表
	getMyVolunteerTasks: (params = {}) => uni.$u.get('/wxapp/volunteer_task/my_tasks', params),
	// 整改任务 - 提交
	submitVolunteerTask: (params = {}) => uni.$u.post('/wxapp/volunteer_task/submit', params),

}
export default wxapp