/** 居民发布 · 详情页自定义展示块 */

export const MEMBER_BLOCK_TYPES = [
	{
		type: 'text',
		name: '单行文本',
		desc: '标题 + 一行内容，适合口令、集合点补充',
		exampleLabel: '集合口令',
		exampleValue: '认准志愿者绿色马甲',
	},
	{
		type: 'textarea',
		name: '多行文本',
		desc: '段落说明，适合流程、注意事项',
		exampleLabel: '温馨提示',
		exampleValue: '请穿运动鞋，自备饮水；雨天活动照常，记得带伞。',
	},
	{
		type: 'images',
		name: '图片集',
		desc: '多张横图，适合现场环境、往期活动',
		exampleLabel: '现场参考',
		exampleImages: [],
	},
]

export function newBlockId() {
	return 'b' + Date.now().toString(36) + Math.random().toString(36).slice(2, 6)
}

export function createBlock(type, overrides = {}) {
	const meta = MEMBER_BLOCK_TYPES.find((t) => t.type === type) || MEMBER_BLOCK_TYPES[0]
	const base = {
		id: newBlockId(),
		type,
		label: overrides.label || meta.exampleLabel || '未命名字段',
		value: overrides.value != null ? overrides.value : (type === 'images' ? '' : meta.exampleValue || ''),
		images: [],
		imagePaths: [],
	}
	if (type === 'images') {
		base.images = overrides.images || []
		base.imagePaths = overrides.imagePaths || []
		base.value = ''
	}
	return base
}

/** 新用户默认带一条示例多行（可删改） */
export function defaultPublishBlocks() {
	return [
		createBlock('textarea', {
			label: '活动简介',
			value: '',
		}),
	]
}

/** 预览用示例块（展示「最终长什么样」） */
export function examplePreviewBlocks() {
	return [
		createBlock('text', { label: '集合口令', value: '认准志愿者绿色马甲' }),
		createBlock('textarea', {
			label: '温馨提示',
			value: '请穿运动鞋，自备饮水；雨天活动照常，记得带伞。',
		}),
		createBlock('images', { label: '现场参考', demoGallery: true }),
	]
}

export function moveBlock(list, index, dir) {
	const arr = list.slice()
	const j = index + dir
	if (j < 0 || j >= arr.length) return arr
	const t = arr[index]
	arr[index] = arr[j]
	arr[j] = t
	return arr
}

export function validateBlocks(blocks) {
	if (!Array.isArray(blocks)) return '自定义字段格式错误'
	if (blocks.length > 12) return '最多添加 12 个展示字段'
	for (let i = 0; i < blocks.length; i++) {
		const b = blocks[i]
		const label = String(b.label || '').trim()
		if (!label) return `第 ${i + 1} 项请填写标题`
		if (label.length > 20) return `「${label}」标题不超过 20 字`
		if (b.type === 'text' && String(b.value || '').length > 100) {
			return `「${label}」单行内容不超过 100 字`
		}
		if (b.type === 'textarea' && String(b.value || '').length > 800) {
			return `「${label}」多行内容不超过 800 字`
		}
		if (b.type === 'images') {
			const n = (b.images || []).length + (b.imagePaths || []).length
			if (n > 6) return `「${label}」最多 6 张图`
		}
	}
	return ''
}

/** 提交前：只传 type / label / value / imagePaths */
export function serializeBlocksForApi(blocks) {
	return (blocks || []).map((b) => {
		const row = {
			type: b.type,
			label: String(b.label || '').trim(),
		}
		if (b.type === 'images') {
			row.images = (b.imagePaths || []).slice()
		} else {
			row.value = String(b.value || '').trim()
		}
		return row
	})
}
