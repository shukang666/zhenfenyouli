/** 垃圾分类打卡：场景与垃圾种类匹配（basic_select type=2） */

export const SCENE_META = {
	home: { name: '家庭', sceneLabel: '家庭' },
	qinzi: { name: '亲子', sceneLabel: '亲子' },
	street: { name: '沿街', sceneLabel: '沿街' },
	zhenfenjiao: { name: '真分角', sceneLabel: '真分角' },
}

/** 各场景在 add 页展示的分类块（按名称匹配 cate_list） */
export const SECTION_KEYS = {
	home: null,
	qinzi: [
		{ key: 'recycle', match: '可回收', exclude: '不可' },
		{ key: 'harm', match: '有害' },
	],
	street: [{ key: 'food', match: '厨余' }],
	zhenfenjiao: null,
}

export function matchCate(list, rule) {
	if (!list || !list.length || !rule) return null
	for (let i = 0; i < list.length; i++) {
		const name = list[i].name || ''
		if (rule.match && name.indexOf(rule.match) < 0) continue
		if (rule.exclude && name.indexOf(rule.exclude) >= 0) continue
		return list[i]
	}
	return null
}

export function resolveCateByLabel(list, label) {
	const map = {
		可回收: { match: '可回收', exclude: '不可' },
		不可回收: { match: '不可回收' },
		有害: { match: '有害' },
		厨余垃圾: { match: '厨余' },
		厨余: { match: '厨余' },
	}
	return matchCate(list, map[label] || { match: label })
}

export function clockSceneLabel(sceneKey, sceneName) {
	if (sceneKey === 'zhenfenjiao') return '真分角'
	if (sceneName) return sceneName
	const m = SCENE_META[sceneKey]
	return m ? m.sceneLabel : ''
}

/** add 页文案与主题（按 scene_key；真分角为 legacy 自选种类） */
export const ADD_PAGE_META = {
	home: {
		theme: 'clock-add--home',
		title: '家庭垃圾分类打卡',
		subtitle: '选择垃圾种类并上传家中投放照片',
		placeholder: '可补充家中投放位置或注意事项',
	},
	qinzi: {
		theme: 'clock-add--qinzi',
		placeholder: '可写和孩子一起分类的小故事或提醒',
	},
	street: {
		theme: 'clock-add--street',
		placeholder: '可说明沿街投放点位置',
	},
	zhenfenjiao: {
		theme: 'clock-add--office',
		title: '真分角打卡',
		subtitle: '选择垃圾种类并上传投放照片（办公大厦真分角）',
		placeholder: '请输入垃圾分类描述',
	},
}

/** 打卡教程场景键（与 cmf_clock_tutorial.cate_key 存 scene 一致） */
export function resolveTutorialSceneKey(sceneKey) {
	const key = String(sceneKey || '').trim()
	if (!key) return 'zhenfenjiao'
	if (key === 'office') return 'zhenfenjiao'
	const allowed = ['home', 'qinzi', 'street', 'zhenfenjiao']
	if (allowed.indexOf(key) >= 0) return key
	return 'zhenfenjiao'
}

/** 根据后台种类名称解析面板样式等（非教程） */
export function resolveCateKind(cateName) {
	const name = cateName || ''
	if (name.indexOf('不可回收') >= 0 || name.indexOf('其他') >= 0) return 'other'
	if (name.indexOf('可回收') >= 0) return 'recycle'
	if (name.indexOf('有害') >= 0) return 'harm'
	if (name.indexOf('厨余') >= 0) return 'food'
	return ''
}

export function buildSceneCateTitle(sceneKey, sceneName, cateName) {
	const scene = clockSceneLabel(sceneKey, sceneName)
	const cate = cateName || '垃圾分类'
	if (scene) return scene + ' · ' + cate + '打卡'
	return cate + '打卡'
}

export function buildSceneCateSubtitle(sceneKey, cateName) {
	const kind = resolveCateKind(cateName)
	const map = {
		home: {
			recycle: '拍摄家中可回收物正确分类、准备投放的照片',
			other: '拍摄家中其他垃圾正确分类、准备投放的照片',
		},
		qinzi: {
			recycle: '和孩子一起完成可回收物分类并拍照记录',
			harm: '和孩子一起识别有害垃圾并安全投放',
		},
		street: {
			food: '拍摄沿街厨余垃圾正确投放到桶内的照片',
		},
	}
	const row = map[sceneKey]
	if (row && kind && row[kind]) return row[kind]
	return '请按下方要求拍摄并上传照片'
}

export const SECTION_HINTS = {
	recycle: '纸张、塑料瓶、金属罐、干净织物等',
	other: '受污染纸张、一次性餐具、破碎陶瓷等',
	harm: '电池、灯管、过期药品等',
	food: '剩菜剩饭、果皮菜叶等',
}
