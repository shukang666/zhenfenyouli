const order = {
    // 轮播图
    find_slide: (params = {}) => uni.$u.post('/wxapp/basic/find_slide', params),
    // 环保活动
    get_active_cate_list: (params = {}) => uni.$u.post('/wxapp/active/get_active_cate_list', params),
    
	// 帖子分类
    get_cate_list: (params = {}) => uni.$u.post('/wxapp/forum/get_cate_list', params),
    // 帖子列表/详情
    get_forum_list: (params = {}) => uni.$u.post('/wxapp/forum/get_forum_list', params),
    // 发布
    add_forum: (params = {}) => uni.$u.post('/wxapp/forum/add_forum', params),
    get_clock_tutorial: (params = {}) => uni.$u.post('/wxapp/forum/get_clock_tutorial', params),
    get_clock_scene_guide: (params = {}) => uni.$u.post('/wxapp/forum/get_clock_scene_guide', params),
    submit_clock_scene_survey: (params = {}) => uni.$u.post('/wxapp/forum/submit_clock_scene_survey', params),
    // 删除发布
    del_forum: (params = {}) => uni.$u.post('/wxapp/forum/del_forum', params),
    // 评论列表
    get_comment: (params = {}) => uni.$u.post('/wxapp/forum/get_comment', params),
    // 添加评论
    add_comment: (params = {}) => uni.$u.post('/wxapp/forum/add_comment', params),
    // 点赞
    add_like: (params = {}) => uni.$u.post('/wxapp/forum/add_like', params),
    // 精选打卡列表
    get_choice_list: (params = {}) => uni.$u.post('/wxapp/forum/get_choice_list', params),
	
    // 健康管理
    get_health_home_list: (params = {}) => uni.$u.post('/wxapp/health/get_health_home_list', params),
    // 问卷列表
    find_exam_topic_list: (params = {}) => uni.$u.post('/wxapp/exam/find_exam_topic_list', params),
    // 身心健康
    get_disease_list: (params = {}) => uni.$u.post('/wxapp/health/get_disease_list', params),
    // 答题分类
    find_exam_list: (params = {}) => uni.$u.post('/wxapp/exam/find_exam_list', params),
    // 答题列表
    find_question_list: (params = {}) => uni.$u.post('/wxapp/exam/find_question_list', params),
    // 提交答题
    add_exam_record: (params = {}) => uni.$u.post('/wxapp/exam/add_exam_record', params),
    // 提交预约
    add_reservation: (params = {}) => uni.$u.post('/wxapp/health/add_reservation', params),
    // 机构列表
    get_select_list: (params = {}) => uni.$u.post('/wxapp/select/get_select_list', params),
    // 优惠券列表
    get_coupon_list: (params = {}) => uni.$u.post('/wxapp/coupon/get_coupon_list', params),
    // 领取优惠券
    select_coupon: (params = {}) => uni.$u.post('/wxapp/coupon/select_coupon', params),
    // 督导员详情
    get_check: (params = {}) => uni.$u.post('/wxapp/check/get_check', params),
    // 申请督导员
    add_check: (params = {}) => uni.$u.post('/wxapp/check/add_check', params),
    // 文章列表
    get_news: (params = {}) => uni.$u.post('/wxapp/news/get_news', params),
    // 低碳活动分类
    get_active_cate_list: (params = {}) => uni.$u.post('/wxapp/active/get_active_cate_list', params),
    // 低碳活动列表
    get_active_list: (params = {}) => uni.$u.post('/wxapp/active/get_active_list', params),
    // 参加低碳活动
    add_active_user: (params = {}) => uni.$u.post('/wxapp/active/add_active_user', params),
    // 参加低碳活动记录
    get_active_user_list: (params = {}) => uni.$u.post('/wxapp/active/get_active_user_list', params),
	
    // 社区列表
    get_community_parent_list: (params = {}) => uni.$u.post('/wxapp/forum/get_community_parent_list', params),
    // 小区列表
    get_community_list: (params = {}) => uni.$u.post('/wxapp/forum/get_community_list', params),
    // 搜索小区(带社区名)
    search_community: (params = {}) => uni.$u.post('/wxapp/forum/search_community', params),
    // 点位列表
    get_community_cate_list: (params = {}) => uni.$u.post('/wxapp/forum/get_community_cate_list', params),
	
    // 点位巡检处理
    add_handle: (params = {}) => uni.$u.post('/wxapp/forum/add_handle', params),
    // 督导员排行
    get_ranking: (params = {}) => uni.$u.post('/wxapp/check/get_ranking', params),
    // 垃圾分类指导
    get_waste_list: (params = {}) => uni.$u.post('/wxapp/active/get_waste_list', params),
    // 答题分类列表
    get_problem_list: (params = {}) => uni.$u.post('/wxapp/answer/get_problem_list', params),
    // 答题列表
    get_question_one: (params = {}) => uni.$u.post('/wxapp/answer/get_question_one', params),
    // 提交答题
    add_answer_one: (params = {}) => uni.$u.post('/wxapp/answer/add_answer_one', params),
    // 知识竞赛排行榜
    get_answer_ranking: (params = {}) => uni.$u.post('/wxapp/answer/get_answer_ranking', params),
    // 申请入驻
    add_shop: (params = {}) => uni.$u.post('/wxapp/shop/add_shop', params),
    // 商家信息
    get_shop: (params = {}) => uni.$u.post('/wxapp/shop/get_shop', params),
    // 我的活动
    my_active: (params = {}) => uni.$u.post('/wxapp/active/my_active', params),
    // 我添加的优惠卷
    my_shop_coupon_list: (params = {}) => uni.$u.post('/wxapp/shop/my_shop_coupon_list', params),
    // 添加优惠卷
    add_coupon: (params = {}) => uni.$u.post('/wxapp/shop/add_coupon', params),
    // 商家优惠卷
    get_shop_coupon_list: (params = {}) => uni.$u.post('/wxapp/shop/get_shop_coupon_list', params),
    // 商家统计
    get_shop_goods_total: (params = {}) => uni.$u.post('/wxapp/shop/get_shop_goods_total', params), 
    // 消息通知
    get_system_msg_list: (params = {}) => uni.$u.post('/wxapp/message/get_system_msg_list', params),
    // 消息通知数量
    my_msg_num: (params = {}) => uni.$u.post('wxapp/message/my_msg_num', params),
    // 互动消息列表
    get_msg_list: (params = {}) => uni.$u.post('/wxapp/message/get_msg_list', params),
    // 我的评论
    my_comment_list: (params = {}) => uni.$u.post('/wxapp/forum/my_comment_list', params),
    // 我的预约
    get_reservation_list: (params = {}) => uni.$u.post('/wxapp/health/get_reservation_list', params),
    // 我的问卷
    my_exam_list: (params = {}) => uni.$u.post('/wxapp/exam/my_exam_list', params),
}
export default order