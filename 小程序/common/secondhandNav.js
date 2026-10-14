/** 活动 Tab · 七贤岭绿色二手集市（线下场次） */
export const FLEA_SPOT_KEY = 'flea_market'
export const FLEA_HUB_TITLE = '七贤岭绿色二手集市'

export function fleaMarketHubUrl() {
	return `/pages/activity/activity-spot-hub?spot=${FLEA_SPOT_KEY}&name=${encodeURIComponent(FLEA_HUB_TITLE)}`
}

/** 积分权益 · 闲置说明页（规则 + 入口聚合） */
export function secondhandIntroUrl() {
	return '/pages/points/secondhand-intro'
}

export function secondhandListUrl() {
	return '/pages/points/secondhand-list'
}

export function secondhandPublishUrl() {
	return '/pages/points/secondhand-publish'
}

export function secondhandMyUrl() {
	return '/pages/points/secondhand-my'
}
