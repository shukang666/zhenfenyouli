function toast(title, icon, duration = 1500) {
	uni.showToast({
		title: title,
		icon: icon || 'none',
		duration: duration
	})
}

export default toast