/**
 * 「认识七贤岭」视频 + 3 道小互动（与 QxlIntroExperienceService 保持一致，接口未升级时前端兜底）
 */
import settings from '@/common/settings.js'

export const QXL_INTRO_CATEGORY = '认识七贤岭'

export const QXL_INTRO_VIDEO_URL =
	settings.staticAssetBase + 'video/qxl-intro-v3.mp4'

/** @type {Array<{id:number,title:string,option_a:string,option_b:string,option_c:string,option_d:string}>} */
export const QXL_INTRO_QUESTIONS = [
	{
		id: 100001,
		title: '七贤岭平时出行有哪些组合方式？',
		option_a: '只能步行，没有公共交通',
		option_b: '可结合地铁 1 号线七贤岭站、公交等，出发前用地图软件确认',
		option_c: '只能私家车，禁止公交',
		option_d: '无需规划，随意出行即可',
	},
	{
		id: 100002,
		title: '家里需要了解就业、社保或养老服务时，可以先向谁咨询？',
		option_a: '仅问小区物业',
		option_b: '七贤岭街道（含社会事务、就业社保等相关服务渠道）',
		option_c: '任意邻居代答即可',
		option_d: '只能自行上网搜索，不能咨询官方',
	},
	{
		id: 100003,
		title: '想确认办事材料和时间，应该优先查看哪里？',
		option_a: '未经核实的网络传言',
		option_b: '街道等政府部门正式公布的办公时间、地址、电话及当年办事指南',
		option_c: '随意猜测，不必核对',
		option_d: '只看他人口述，不看正式通知',
	},
]

export function isLegacyLearnFeed(data) {
	if (!data || typeof data !== 'object') return false
	const hasUnits = Array.isArray(data.units) && data.units.length > 0
	const hasIntro = data.intro_mode === 1 || data.intro_mode === true
	return hasUnits && !hasIntro && !data.video_url
}
