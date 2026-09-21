/**
 * 各页新手引导配置（统一维护 selector / 文案）
 *
 * page key 与 <page-guide page="xxx"> 一致。
 * selector 用页面里的 id（推荐 #guide-xxx），空字符串表示只出说明卡片、不挖洞。
 * type: 'tabbar' 不查 DOM，按自定义胶囊 Tab 的尺寸计算高亮（查固定层会偏）。
 * 吉祥物按屏幕右下角定位（相对窗口，不是页面文档）。拖动距离存 help_mascot_rb，各 Tab 共用。
 * prefer: 'above' | 'below' 卡片相对高亮的偏好。
 *
 * 新增页面：1）模板加 id  2）在此加一段 steps  3）页面底部挂 <page-guide page="key" />
 */
export const PAGE_GUIDES = {
	low_carbon: {
		steps: [
			{
				selector: '',
				title: '活动页能做什么',
				desc: '这里报名小区活动、去垃圾分类打卡，也能进「我和七贤岭」。点下一步认一认。'
			},
			{
				selector: '#guide-fb-search',
				prefer: 'below',
				title: '搜索活动',
				desc: '按活动名称或地点查找，不用在列表里慢慢翻。'
			},
			{
				selector: '#guide-fb-entry',
				prefer: 'below',
				title: '两个快捷入口',
				desc: '左边去垃圾分类学习打卡，右边进「我和七贤岭」。'
			},
			{
				selector: '#guide-fb-theme',
				prefer: 'above',
				title: '主题活动',
				desc: '健康、学习、兴趣等分类，点进去看对应活动。'
			},
			{
				selector: '#pg-tabbar',
				type: 'tabbar',
				prefer: 'above',
				title: '底部五个按钮',
				desc: '首页、活动、健康、积分权益、我的，随时切换。'
			}
		]
	},
	health: {
		steps: [
			{
				selector: '',
				title: '健康页能做什么',
				desc: '身心健康测评、健康档案都在这里。点下一步认一认入口。'
			},
			{
				selector: '#guide-health-diag',
				prefer: 'below',
				title: '健康诊断',
				desc: '上面两块是测评入口，下面可以建健康档案、看直播。'
			},
			{
				selector: '#guide-health-life',
				prefer: 'above',
				title: '健康生活社区',
				desc: '邻居分享的健康帖，可以看、赞、评论。没有这块时跳过即可。'
			},
			{
				selector: '#pg-tabbar',
				type: 'tabbar',
				prefer: 'above',
				title: '底部五个按钮',
				desc: '回首页、去活动或积分权益，点下面一排就行。'
			}
		]
	},
	mall: {
		steps: [
			{
				selector: '',
				title: '积分商城',
				desc: '用打卡和活动攒的积分，在这里兑商品、领优惠券。'
			},
			{
				selector: '#guide-mall-search',
				prefer: 'below',
				title: '搜索商品',
				desc: '记得商品名就直接搜，更快找到想兑的。'
			},
			{
				selector: '#guide-mall-cate',
				prefer: 'below',
				title: '商品分类',
				desc: '左右滑选分类，下面列表会跟着变。'
			},
			{
				selector: '#guide-mall-goods',
				prefer: 'above',
				title: '兑换商品',
				desc: '点卡片看详情，积分够就能兑。'
			},
			{
				selector: '#pg-tabbar',
				type: 'tabbar',
				prefer: 'above',
				title: '底部五个按钮',
				desc: '兑完回首页或去「我的」看订单。'
			}
		]
	},
	user: {
		steps: [
			{
				selector: '',
				title: '这是「我的」',
				desc: '看积分、改资料、查打卡和订单，都从这里进。'
			},
			{
				selector: '#guide-me-hero',
				prefer: 'below',
				title: '头像和积分',
				desc: '登录后这里是昵称。绿色卡片是账户积分，点进去看明细。'
			},
			{
				selector: '#guide-me-common',
				prefer: 'above',
				title: '常用功能',
				desc: '积分明细、我的活动、兑换订单、消息通知。'
			},
			{
				selector: '#guide-me-service',
				prefer: 'above',
				title: '我的服务',
				desc: '打卡记录、答题、徽章等，按名字点进去就行。'
			},
			{
				selector: '#pg-tabbar',
				type: 'tabbar',
				prefer: 'above',
				title: '底部五个按钮',
				desc: '随时回首页或去活动、健康、积分权益。'
			}
		]
	},
	dtsh: {
		steps: [
			{
				selector: '',
				title: '垃圾分类打卡',
				desc: '这里学分类、答题、拍照打卡攒积分，也可以报问题和领整改任务。点下一步认一认。'
			},
			{
				selector: '#guide-dtsh-clock',
				prefer: 'below',
				title: '去拍照打卡',
				desc: '点这张图进入打卡。先学会分类、通过考试，才能拍照交作业。'
			},
			{
				selector: '#guide-dtsh-entry',
				prefer: 'below',
				title: '三个常用入口',
				desc: '随手拍发现问题、分类知识挑战答题、看分类知识文章。'
			},
			{
				selector: '#guide-dtsh-vol',
				prefer: 'above',
				title: '督导员工作台',
				desc: '通过审核的督导员从这里进监测、巡查和治理。'
			},
			{
				selector: '#guide-dtsh-task',
				prefer: 'above',
				title: '整改任务',
				desc: '可领取邻居随手拍转来的任务。每人每天最多领 1 条，做完交照片审核。'
			}
		]
	}
}

export function getPageGuide(pageKey) {
	return PAGE_GUIDES[pageKey] || { steps: [] }
}
