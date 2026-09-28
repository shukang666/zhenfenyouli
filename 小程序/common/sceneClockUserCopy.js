/**
 * 场景打卡流（scene-clock-flow）用户向文案
 * 避免「真分点」等内部术语，面向普通居民
 */

/** 家庭/亲子：用户自建的固定投放位置 */
export const HOME_POINT_LABEL = '分类打卡点'
export const HOME_POINT_LABEL_SHORT = '打卡点'

/** 办公/沿街：社区配置的集中投放位置 */
export const COMMUNITY_POINT_LABEL = '集中分类点'

export function getStepTitles() {
	return {
		hub_empty: '等待配置',
		setup: '设置' + HOME_POINT_LABEL,
		points: '选择' + HOME_POINT_LABEL_SHORT,
		manage: '管理' + HOME_POINT_LABEL_SHORT,
		cate: '选择垃圾种类',
		guide: '分类指引',
		survey: '分享分类技巧',
		learn: '拍照打卡',
	}
}

export function getIntroLead(hubKey) {
	if (hubKey === 'qinzi') {
		return '和孩子一起：在家分好类，再投对桶'
	}
	return '在家分好类，再投对桶'
}

export function getIntroSummary(hubKey) {
	if (hubKey === 'qinzi') {
		return '先在家里设几个固定「打卡点」（如厨房厨余桶、客厅可回收箱），每次投放拍照片记录，习惯更容易坚持。'
	}
	return '先在家里设几个固定「打卡点」（如厨房厨余桶、玄关可回收箱），每次投放拍照片记录，方便养成习惯。'
}

export function getIntroBlocks(hubKey) {
	const qinziExtra =
		hubKey === 'qinzi'
			? '亲子场景会侧重与孩子一起认识可回收、有害等类别，打卡也是记录陪伴过程。'
			: ''

	return [
		{
			id: 'what',
			title: '先把垃圾分对了',
			body:
				'「践行真分」是社区里的简称，意思就是：垃圾在产生的地方就按可回收、厨余、有害、其他分开，装对袋子或桶，再投到对应收集容器。不是额外折腾，而是少混投、少返工，环境更干净。',
		},
		{
			id: 'policy',
			title: '国家政策',
			body:
				'2019年起，国家推动地级及以上城市全面开展生活垃圾分类（《关于在全国地级及以上城市全面开展生活垃圾分类工作的通知》等）。生活垃圾一般按可回收物、厨余垃圾、有害垃圾、其他垃圾四类管理。居民准确分类，是法规要求，也是绿色生活的一部分。',
		},
		{
			id: 'local',
			title: '大连高新区 · 七贤岭',
			body:
				'七贤岭街道结合辖区实际，用数字化方式把分类做进日常：居民通过本小程序学习分类知识、通过测验后再打卡；按家庭、办公、商铺等场景拍照记录投放；参与低碳活动、志愿督导与问题反馈，可获积分激励。社区与物业协同配置集中投放点，逐步推动「会分、愿分、准确分」。' +
				(qinziExtra ? ' ' + qinziExtra : ''),
		},
		{
			id: 'vision',
			title: '这个小程序想做什么',
			body:
				'「真分」小程序希望分类不再难懂、不再孤单：学得会、做得到、有反馈。用打卡记录真实投放，用活动和积分鼓励坚持，用督导与随手拍推动共治，共建干净、低碳的七贤岭社区。',
		},
	]
}

export function getSetupActionHint(hubKey) {
	if (hubKey === 'qinzi') {
		return '请至少添加 1 个打卡点：填写名称、选择垃圾类型，并拍一张能看清桶/袋/标签的照片。保存后点下方卡片即可开始打卡。'
	}
	return '请至少添加 1 个打卡点：填写名称、选择垃圾类型，并拍一张能看清桶/袋/标签的照片。保存后点下方卡片即可开始打卡。'
}

export function getCommunityEmptyTitle(isStreetHub, isOfficeHub) {
	if (isStreetHub) return '暂未配置商铺集中分类点'
	if (isOfficeHub) return '暂未配置办公集中分类点'
	return '暂未配置回收点'
}

export function getCommunityPointsTitle(isStreetHub) {
	if (isStreetHub) return '请到集中分类点打卡'
	return '请到大楼集中分类点打卡'
}

export function getCommunityPointsDesc(isStreetHub) {
	if (isStreetHub) {
		return '以下为社区根据商铺情况配置的集中分类点，每个点位对应一种垃圾类型，选择后按指引拍照打卡即可。'
	}
	return '以下为社区在办公区域配置的集中分类点，无需自行设置，选择后按指引拍照打卡即可。'
}
