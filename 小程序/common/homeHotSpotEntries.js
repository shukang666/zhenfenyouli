/**
 * 首页「热门活动」固定五项 + 活动 Tab「本周推荐」首项（垃圾真分）
 */
import settings from '@/common/settings.js'
import { resolveAssetShowUrl } from '@/common/uploadAsset.js'
import { openSceneClockTheme } from '@/common/sceneClockEntry.js'

/** 已有运营图：场景打卡 / 七贤岭学考封面 / 各专题主活动缩略图 */
const THUMB_ZHENFEN_CLOCK = settings.staticAssetBase + 'scene-pick/home.png'
const THUMB_QXL = settings.staticAssetBase + 'img/qxl-learn-cover.png'
const THUMB_HIKING = 'xcxkf052/admin/20261002/9969252bfe5296f70fa911d6f4990fe6.png'
const THUMB_FLEA = 'xcxkf052/admin/20261002/99254fa35d6f5cd3ded6e611f9985eef.png'
const THUMB_ZHENFEN_CLASS = 'xcxkf052/admin/20261002/eda5f775876ba16251dfb02145f4934b.png'

/** 活动 Tab 本周推荐横滑第一项（不走后台 is_month_recommend） */
export const WEEKLY_ZHENFEN_PIN = {
	isWeeklyPin: true,
	name: '垃圾真分',
	active_count: 0,
	thumb: THUMB_ZHENFEN_CLOCK,
}

/** 相对路径或完整 URL → 小程序可展示地址 */
export function resolveHomeHotThumb(thumb) {
	const p = (thumb || '').trim()
	if (!p) return ''
	if (/^https?:\/\//i.test(p)) return p
	return resolveAssetShowUrl(p)
}

/** 首页热门活动横滑：垃圾真分 + 四大社区专题（顺序固定） */
export const HOME_HOT_SPOT_ENTRIES = [
	{
		key: 'zhenfen_clock',
		name: '垃圾真分',
		emoji: '♻️',
		thumb: THUMB_ZHENFEN_CLOCK,
		entry_type: 'scene_clock',
		meta: '随时参与',
		hint: '选场景 · 摆桶 · 拍照',
	},
	{
		key: 'qxl',
		name: '我和七贤岭',
		emoji: '⛰️',
		thumb: THUMB_QXL,
		entry_type: 'qxl_page',
		meta: '社区专题',
		hint: '认识 · 发现',
	},
	{
		key: 'hiking',
		name: '步行山海，净美家园',
		emoji: '🥾',
		thumb: THUMB_HIKING,
		entry_type: 'spot_hub',
		meta: '社区专题',
		hint: '边走边捡 · 洁净山野',
	},
	{
		key: 'flea_market',
		name: '七贤岭绿色二手集市',
		emoji: '♻️',
		thumb: THUMB_FLEA,
		entry_type: 'spot_hub',
		meta: '社区专题',
		hint: '闲置循环 · 绿色生活',
	},
	{
		key: 'zhenfen_class',
		name: '垃圾真分小课堂',
		emoji: '📗',
		thumb: THUMB_ZHENFEN_CLASS,
		entry_type: 'spot_hub',
		meta: '社区专题',
		hint: '学分类 · 真投放',
	},
]

export function navigateHomeHotSpotEntry(vm, item) {
	if (!item || !vm) return
	if (item.entry_type === 'scene_clock') {
		openSceneClockTheme(vm)
		return
	}
	if (item.entry_type === 'qxl_page' || item.key === 'qxl') {
		vm.$u.route('/pages/activity/love-qixianling')
		return
	}
	if (item.entry_type === 'spot_hub' && item.key) {
		vm.$u.route(
			`/pages/activity/activity-spot-hub?spot=${item.key}&name=${encodeURIComponent(item.name || '')}`,
		)
	}
}
