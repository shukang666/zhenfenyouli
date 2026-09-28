/** 践行真分 hub 页内嵌介绍（对应 cmf_waste 标题，按此顺序展示） */
export const ZHENFEN_INTRO_NAMES = ['真分说明', '为什么做真分', '做了真分会收获什么']

export const INTRO_ACK_KEY = 'zhenfen_intro_ack_v1'

export function isIntroAcknowledged() {
	try {
		return !!uni.getStorageSync(INTRO_ACK_KEY)
	} catch (e) {
		return false
	}
}

export function setIntroAcknowledged() {
	try {
		uni.setStorageSync(INTRO_ACK_KEY, 1)
	} catch (e) {}
}
