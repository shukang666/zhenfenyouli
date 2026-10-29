/** 居民发布 · 文字封面样式（预览与 canvas 共用） */

export const TEXT_COVER_W = 750
export const TEXT_COVER_H = 1000

/** 预览 / 导出：标题折行，尽量展示满 30 字 */
export function splitTitleLines(title, maxLines = 5, maxCharsPerLine = 14) {
	const t = String(title || '').trim()
	if (!t) return ['活动标题']
	const lines = []
	let idx = 0
	while (idx < t.length && lines.length < maxLines) {
		lines.push(t.slice(idx, idx + maxCharsPerLine))
		idx += maxCharsPerLine
	}
	if (idx < t.length && lines.length > 0) {
		const last = lines.length - 1
		const tail = lines[last]
		lines[last] = tail.length > 1 ? tail.slice(0, -1) + '…' : tail + '…'
	}
	return lines
}

export function titleFontSizeForLength(len, forPreviewRpx = false) {
	let px
	if (len > 24) px = 44
	else if (len > 16) px = 52
	else if (len > 10) px = 58
	else px = 64
	if (forPreviewRpx) {
		if (len > 24) return 34
		if (len > 16) return 38
		if (len > 10) return 42
		return 46
	}
	return px
}

export const MEMBER_TEXT_COVER_STYLES = [
	{
		id: 'forest',
		name: '森林',
		layout: 'leaf',
		gradient: 'linear-gradient(145deg, #064e3b 0%, #059669 42%, #6ee7b7 100%)',
		canvas: { c0: '#064e3b', c1: '#059669', c2: '#6ee7b7', angle: 145, decor: 'leaf' },
		titleColor: '#ffffff',
		subColor: 'rgba(255,255,255,0.82)',
		align: 'left',
	},
	{
		id: 'sunset',
		name: '暖暮',
		layout: 'sun',
		gradient: 'linear-gradient(135deg, #ea580c 0%, #fb923c 40%, #fde047 100%)',
		canvas: { c0: '#ea580c', c1: '#fb923c', c2: '#fde047', angle: 135, decor: 'sun' },
		titleColor: '#ffffff',
		subColor: 'rgba(255,255,255,0.9)',
		align: 'center',
	},
	{
		id: 'ocean',
		name: '深海',
		layout: 'wave',
		gradient: 'linear-gradient(165deg, #0c4a6e 0%, #0369a1 45%, #22d3ee 100%)',
		canvas: { c0: '#0c4a6e', c1: '#0369a1', c2: '#22d3ee', angle: 165, decor: 'wave' },
		titleColor: '#ffffff',
		subColor: 'rgba(255,255,255,0.85)',
		align: 'left',
	},
	{
		id: 'lavender',
		name: '极光',
		layout: 'aurora',
		gradient: 'linear-gradient(140deg, #4c1d95 0%, #7c3aed 50%, #c084fc 100%)',
		canvas: { c0: '#4c1d95', c1: '#7c3aed', c2: '#c084fc', angle: 140, decor: 'aurora' },
		titleColor: '#ffffff',
		subColor: 'rgba(255,255,255,0.88)',
		align: 'center',
	},
	{
		id: 'ink',
		name: '墨金',
		layout: 'frame',
		gradient: 'linear-gradient(155deg, #171717 0%, #404040 55%, #737373 100%)',
		canvas: { c0: '#171717', c1: '#404040', c2: '#737373', angle: 155, decor: 'frame' },
		titleColor: '#fcd34d',
		subColor: 'rgba(252,211,77,0.75)',
		align: 'left',
	},
	{
		id: 'mint',
		name: '清新',
		layout: 'glass',
		gradient: 'linear-gradient(135deg, #d1fae5 0%, #6ee7b7 50%, #10b981 100%)',
		canvas: { c0: '#d1fae5', c1: '#6ee7b7', c2: '#10b981', angle: 135, decor: 'glass' },
		titleColor: '#064e3b',
		subColor: 'rgba(6,78,59,0.7)',
		align: 'left',
	},
	{
		id: 'coral',
		name: '笔记',
		layout: 'note',
		gradient: 'linear-gradient(118deg, #e11d48 0%, #f43f5e 45%, #fda4af 100%)',
		canvas: { c0: '#e11d48', c1: '#f43f5e', c2: '#fda4af', angle: 118, decor: 'note' },
		titleColor: '#ffffff',
		subColor: 'rgba(255,255,255,0.92)',
		align: 'center',
	},
	{
		id: 'night',
		name: '星夜',
		layout: 'stars',
		gradient: 'linear-gradient(165deg, #020617 0%, #1e3a8a 50%, #4338ca 100%)',
		canvas: { c0: '#020617', c1: '#1e3a8a', c2: '#4338ca', angle: 165, decor: 'stars' },
		titleColor: '#e0e7ff',
		subColor: 'rgba(224,231,255,0.72)',
		align: 'center',
	},
	{
		id: 'paper',
		name: '纸纹',
		layout: 'paper',
		gradient: 'linear-gradient(180deg, #fef3c7 0%, #fde68a 100%)',
		canvas: { c0: '#fef3c7', c1: '#fde68a', c2: '#fbbf24', angle: 180, decor: 'paper' },
		titleColor: '#78350f',
		subColor: 'rgba(120,53,15,0.65)',
		align: 'left',
	},
	{
		id: 'geo',
		name: '几何',
		layout: 'geo',
		gradient: 'linear-gradient(135deg, #0891b2 0%, #6366f1 100%)',
		canvas: { c0: '#0891b2', c1: '#6366f1', c2: '#a855f7', angle: 135, decor: 'geo' },
		titleColor: '#ffffff',
		subColor: 'rgba(255,255,255,0.85)',
		align: 'center',
	},
]

export function getTextCoverStyle(id) {
	return MEMBER_TEXT_COVER_STYLES.find((s) => s.id === id) || MEMBER_TEXT_COVER_STYLES[0]
}
