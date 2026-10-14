/** 分类/成色文案（与后端 SecondhandService 一致） */
const CATEGORIES = [
	{ key: 'all', label: '全部' },
	{ key: 'books', label: '书籍文具' },
	{ key: 'home', label: '家居日用' },
	{ key: 'baby', label: '母婴儿童' },
	{ key: 'digital', label: '数码小物' },
	{ key: 'other', label: '其他' },
]

const CONDITIONS = [
	{ key: 'new', label: '全新' },
	{ key: 'like_new', label: '几乎全新' },
	{ key: 'used', label: '正常使用' },
]

export function getCategories() {
	return CATEGORIES.slice()
}

export function getConditions() {
	return CONDITIONS.slice()
}

export function conditionLabel(key) {
	return CONDITIONS.find(c => c.key === key)?.label || ''
}

export function categoryLabel(key) {
	return CATEGORIES.find(c => c.key === key)?.label || ''
}

/** 列表/详情 status_key → 我的页文案 */
export function statusDisplayText(item) {
	const key = item && (item.status_key || item.status)
	if (key === 'sold' || key === 3) return '已关闭'
	if (key === 'reserved' || key === 2) return '已预订'
	if (key === 'pending' || key === 0) return '审核中'
	if (key === 'rejected' || key === 4) return '未通过'
	return '出售中'
}
