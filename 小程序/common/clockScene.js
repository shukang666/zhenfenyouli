/** 垃圾分类打卡：场景与垃圾种类匹配（basic_select type=2） */

export const SCENE_META = {
	home: { name: '家庭', sceneLabel: '家庭' },
	qinzi: { name: '亲子', sceneLabel: '亲子' },
	street: { name: '沿街', sceneLabel: '沿街' },
	zhenfenjiao: { name: '真分角', sceneLabel: '真分角' },
}

/** 各场景在 add 页展示的分类块（按名称匹配 cate_list） */
export const SECTION_KEYS = {
	home: [
		{ key: 'recycle', match: '可回收', exclude: '不可' },
		{ key: 'other', match: '不可回收' },
	],
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

/** 四类垃圾：提交按钮下方的教程与拍照要求 */
export const CATE_GUIDE = {
	recycle: {
		title: '可回收物 · 教程与要求',
		intro: '干净、干燥、可再利用的废弃物，请单独装袋或捆扎后投放。',
		examples: '常见：废纸、塑料瓶、金属罐、玻璃瓶、干净旧衣物等。',
		photo: ['垃圾内容清晰可见，能辨认是可回收物', '尽量拍投放前分类好的状态', '避免只拍空袋子或模糊远景'],
		notice: ['污染严重的纸张、外卖餐盒请投其他垃圾', '分类错误可能被扣积分'],
	},
	other: {
		title: '其他垃圾 · 教程与要求',
		intro: '除可回收、厨余、有害以外的日常生活废弃物。',
		examples: '常见：污损纸张、一次性餐具、烟蒂、尘土、破碎陶瓷等。',
		photo: ['展示实际要丢弃的其他垃圾', '照片对焦清晰、光线充足'],
		notice: ['可回收、厨余、有害请勿混入', '大件垃圾请按小区规定投放'],
	},
	harm: {
		title: '有害垃圾 · 教程与要求',
		intro: '对人体或环境有毒有害，需单独密封存放并投放到有害收集容器。',
		examples: '常见：废电池、废灯管、过期药品、油漆桶等。',
		photo: ['拍清有害垃圾种类与包装', '如有破损请说明，勿徒手接触'],
		notice: ['勿与其他垃圾混投', '亲子打卡请由家长操作、孩子旁观学习'],
	},
	food: {
		title: '厨余垃圾 · 教程与要求',
		intro: '易腐烂的生物质废弃物，请沥水后投放。',
		examples: '常见：剩菜剩饭、果皮、菜叶、茶叶渣等。',
		photo: ['展示厨余内容及投放方式', '沿街点位请拍桶身或标识'],
		notice: ['塑料袋、餐巾纸请取出后另投', '汤汁请沥干，避免滴漏'],
	},
}

/** 根据后台种类名称解析 guide 键 */
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
