/**
 * 各页新手引导配置（统一维护 selector / 文案）
 *
 * page key 与 <page-guide page="xxx"> 一致。
 * selector 用页面里的 id（推荐 #guide-xxx），空字符串表示只出说明卡片、不挖洞。
 * type: 'tabbar' 不查 DOM，按自定义胶囊 Tab 的尺寸计算高亮（查固定层会偏）。
 * 小助手入口（右侧吸附胶囊）按 fixed 定位；拖动位置存 help_mascot_rb，各 Tab 共用。
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
				desc: '报名社区活动、垃圾真分打卡、进七贤岭专题和工作台。下面按从上到下认一认。'
			},
			{
				selector: '#guide-fb-search',
				prefer: 'below',
				title: '搜索活动',
				desc: '按活动名称或地点查找，不用在列表里慢慢翻。'
			},
			{
				selector: '#guide-fb-zhenfen-hub',
				prefer: 'below',
				title: '垃圾真分',
				desc: '顶部海报和下面家庭、办公、沿街三个入口是一整套：看说明、选场景、拍照打卡。'
			},
			{
				selector: '#guide-fb-dtsh-hub',
				prefer: 'below',
				title: '垃圾分类工作台',
				desc: '学习、打卡、督导监测、积分排行等集中在这里，点这张卡片进入工作台。'
			},
			{
				selector: '#guide-fb-qxl',
				prefer: 'below',
				title: '社区专题',
				desc: '我和七贤岭、步行山海净美家园、绿色二手集市、垃圾真分小课堂，点卡片进入；后三个再选具体地点。'
			},
			{
				selector: '#guide-fb-theme',
				prefer: 'below',
				title: '主题活动',
				desc: '健康运动、学习成长等五大类，点圆钮进入对应分类活动列表。'
			},
			{
				selector: '#guide-fb-weekly',
				prefer: 'above',
				title: '本周推荐',
				desc: '横滑海报与首页热门一致：垃圾真分 + 四个社区专题，点卡片直达。'
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
		hideMascot: true,
		steps: [
			{
				selector: '#guide-health-hub',
				prefer: 'below',
				title: '健康服务首页',
				desc: '点「开始健康问答」进入评估；基层医疗机构入口在下方卡片。'
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
				desc: '用打卡和活动攒的积分兑商品；顶部有券时可领取，再选产区或搜商品。'
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
				title: '地理产区',
				desc: '展开后点地图大区，再选具体产区，下方会筛当地好物。'
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
				desc: '看积分、改资料、查活动与订单，打卡和徽章也在这里进。'
			},
			{
				selector: '#guide-me-hero',
				prefer: 'below',
				title: '头像和积分',
				desc: '登录后显示昵称；绿色卡片是账户积分，点「明细」查流水。'
			},
			{
				selector: '#guide-me-common',
				prefer: 'above',
				title: '常用功能',
				desc: '积分明细、我的活动、兑换订单、消息通知，日常最常用。'
			},
			{
				selector: '#guide-me-service',
				prefer: 'above',
				title: '服务',
				desc: '健康生活社区、我的徽章等，点右侧箭头进入。'
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
				title: '垃圾分类工作台',
				desc: '督导员在这里做桶边督导、巡查检查和监测登记。点下一步认一认。'
			},
			{
				selector: '#guide-dtsh-vol',
				prefer: 'above',
				title: '督导员工作台',
				desc: '通过审核的督导员从这里进桶边督导、监测、巡查和商户共治。'
			}
		]
	}
}

export function getPageGuide(pageKey) {
	return PAGE_GUIDES[pageKey] || { steps: [] }
}
