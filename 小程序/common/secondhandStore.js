const STORAGE_KEY = 'SECONDHAND_LISTINGS_V1'

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

const SEED = [
	{
		id: 'seed-1',
		title: '儿童绘本一套（8本）',
		description: '孩子升年级用不到了，无缺页，适合 3-6 岁亲子阅读。',
		images: [],
		point_price: 80,
		category: 'books',
		condition: 'like_new',
		area_text: '七贤岭街道 · 附近自提',
		seller_nickname: '邻居小王',
		seller_avatar: '',
		status: 'on_sale',
		create_time: Date.now() - 86400000 * 2,
	},
	{
		id: 'seed-2',
		title: '闲置小烤箱',
		description: '功能正常，仅支持自提，送烤盘。',
		images: [],
		point_price: 200,
		category: 'home',
		condition: 'used',
		area_text: '龙王塘附近',
		seller_nickname: '环保达人',
		seller_avatar: '',
		status: 'on_sale',
		create_time: Date.now() - 86400000 * 5,
	},
]

function readAll() {
	try {
		const raw = uni.getStorageSync(STORAGE_KEY)
		if (raw && Array.isArray(raw) && raw.length) {
			return raw
		}
	} catch (e) {}
	uni.setStorageSync(STORAGE_KEY, SEED.slice())
	return SEED.slice()
}

function writeAll(list) {
	uni.setStorageSync(STORAGE_KEY, list)
}

function genId() {
	return 'sh-' + Date.now() + '-' + Math.floor(Math.random() * 10000)
}

export function getCategories() {
	return CATEGORIES
}

export function getConditions() {
	return CONDITIONS
}

export function conditionLabel(key) {
	return CONDITIONS.find(c => c.key === key)?.label || ''
}

export function categoryLabel(key) {
	return CATEGORIES.find(c => c.key === key)?.label || ''
}

export function listSecondhand({ category = 'all', keyword = '', status = 'on_sale' } = {}) {
	let list = readAll().filter(item => item.status === status)
	if (category && category !== 'all') {
		list = list.filter(item => item.category === category)
	}
	const kw = String(keyword || '').trim()
	if (kw) {
		list = list.filter(item => {
			const t = (item.title || '') + (item.description || '')
			return t.indexOf(kw) >= 0
		})
	}
	return list.sort((a, b) => (b.create_time || 0) - (a.create_time || 0))
}

export function getSecondhandById(id) {
	return readAll().find(item => String(item.id) === String(id)) || null
}

export function listMySecondhand(userId, nickname) {
	const list = readAll()
	return list
		.filter(item => {
			if (item.seller_user_id && userId) {
				return String(item.seller_user_id) === String(userId)
			}
			if (nickname && item.seller_nickname) {
				return item.seller_nickname === nickname
			}
			return false
		})
		.sort((a, b) => (b.create_time || 0) - (a.create_time || 0))
}

export function addSecondhand(payload) {
	const list = readAll()
	const row = {
		id: genId(),
		title: String(payload.title || '').trim(),
		description: String(payload.description || '').trim(),
		images: Array.isArray(payload.images) ? payload.images.slice(0, 6) : [],
		point_price: Math.max(1, parseInt(payload.point_price, 10) || 0),
		category: payload.category || 'other',
		condition: payload.condition || 'used',
		area_text: String(payload.area_text || '').trim(),
		seller_nickname: payload.seller_nickname || '社区用户',
		seller_avatar: payload.seller_avatar || '',
		seller_user_id: payload.seller_user_id || '',
		status: 'on_sale',
		create_time: Date.now(),
	}
	if (!row.title) {
		throw new Error('请填写物品名称')
	}
	if (!row.point_price) {
		throw new Error('请填写积分价格')
	}
	if (!row.images.length) {
		throw new Error('请至少上传一张图片')
	}
	list.unshift(row)
	writeAll(list)
	return row
}

export function updateSecondhandStatus(id, status) {
	const list = readAll()
	const idx = list.findIndex(item => String(item.id) === String(id))
	if (idx < 0) return false
	list[idx].status = status
	writeAll(list)
	return true
}
