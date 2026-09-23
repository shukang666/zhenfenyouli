/** 积分商城 · 地理产区与中国地图大区映射 */

export const MAP_REGIONS = [
	{ key: 'northeast', name: '东北', aliases: ['东北', '辽东', '辽西'] },
	{ key: 'north', name: '华北', aliases: ['黄淮海', '沂蒙'] },
	{ key: 'northwest', name: '西北', aliases: ['黄土', '河套', '河西', '哈密', '草原', '内蒙古', '新疆'] },
	{ key: 'central', name: '华中', aliases: ['秦岭'] },
	{ key: 'east', name: '华东', aliases: ['江南'] },
	{ key: 'southwest', name: '西南', aliases: ['四川', '云贵', '青藏'] },
	{ key: 'south', name: '华南', aliases: ['闽粤', '海南'] },
]

export function matchZoneToRegion(cateName) {
	const n = cateName || ''
	if (n.indexOf('综合') >= 0) return 'creative'
	for (let i = 0; i < MAP_REGIONS.length; i++) {
		const r = MAP_REGIONS[i]
		for (let j = 0; j < r.aliases.length; j++) {
			if (n.indexOf(r.aliases[j]) >= 0) return r.key
		}
	}
	return ''
}

export function regionLabel(key) {
	const r = MAP_REGIONS.find(item => item.key === key)
	return r ? r.name : ''
}

/**
 * @param {Array<{id:number,cate_name:string}>} cateList 含首项「全部」
 * @param {string} regionKey
 * @returns {Array<{index:number,id:number,cate_name:string}>}
 */
export function zonesInRegion(cateList, regionKey) {
	if (!regionKey || regionKey === 'all') return []
	const out = []
	for (let i = 0; i < cateList.length; i++) {
		if (i === 0) continue
		const c = cateList[i]
		if (regionKey === 'creative') {
			if (matchZoneToRegion(c.cate_name) === 'creative' || (c.cate_name || '').indexOf('综合') >= 0) {
				out.push({ index: i, id: c.id, cate_name: c.cate_name })
			}
			continue
		}
		if (matchZoneToRegion(c.cate_name) === regionKey) {
			out.push({ index: i, id: c.id, cate_name: c.cate_name })
		}
	}
	return out
}

export function zoneShortName(name) {
	const map = {
		全部: '全部',
		东北山地林区: '东北山地',
		东北平原黑土农区: '东北平原',
		辽西粗粮干果区: '辽西粗粮',
		辽东半岛冷水海鲜区: '辽东海鲜',
		黄淮海平原农区: '黄淮海',
		沂蒙丘陵农林区: '沂蒙丘陵',
		黄土高原粗粮区: '黄土高原',
		秦岭果蔬区: '秦岭果蔬',
		'河套-河西走廊农区': '河西走廊',
		哈密盆地果蔬区: '哈密盆地',
		'内蒙古、新疆草原牧区': '草原牧区',
		江南水产茶果区: '江南茶果',
		四川天府农区: '四川天府',
		云贵高原山珍区: '云贵山珍',
		闽粤海鲜区: '闽粤海鲜',
		海南热带水产鲜果区: '海南热带',
		青藏高原农区: '青藏高原',
		综合文创: '综合文创',
	}
	return map[name] || name
}
