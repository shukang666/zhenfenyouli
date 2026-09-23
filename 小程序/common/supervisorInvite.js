const BANNER_HIDE_KEY = 'supervisor_invite_banner_hide'

export function isSupervisorBannerHidden() {
	try {
		return !!uni.getStorageSync(BANNER_HIDE_KEY)
	} catch (e) {
		return false
	}
}

export function hideSupervisorBanner() {
	try {
		uni.setStorageSync(BANNER_HIDE_KEY, 1)
	} catch (e) {}
}

export function shouldShowSupervisorInvite(invite) {
	if (!invite || Number(invite.show_invite) !== 1) return false
	return true
}
