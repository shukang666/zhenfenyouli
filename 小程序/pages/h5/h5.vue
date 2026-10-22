<template>
	<view>
		<web-view :src="url"></web-view>
	</view>
</template>

<script>
import settings from '@/common/settings.js'
import {
	buildWebActiveEntryPath,
	parseActiveIdFromWebLaunchUrl,
	isOnePlusOneWebUrl,
	syncWebActiveMpShare,
} from '@/common/webActiveEntry.js'

export default {
	data() {
		return {
			url: '',
			shareActiveId: '',
			shareTitle: '社区低碳活动',
		}
	},
	onLoad(option) {
		this.url = decodeURIComponent(option.url || '')
		this.shareActiveId = String(option.active_id || '').trim()
			|| parseActiveIdFromWebLaunchUrl(this.url)
		if (isOnePlusOneWebUrl(this.url)) {
			this.shareTitle = '1+1 七日挑战'
		}
		this.refreshShareConfig()
	},
	onShareAppMessage() {
		this.refreshShareConfig()
		if (this.shareActiveId) {
			return {
				title: this.shareTitle,
				path: buildWebActiveEntryPath(this.shareActiveId, {
					title: this.shareTitle,
				}),
				imageUrl: settings.shareCoverUrl,
			}
		}
		// 勿回落到首页；仍分享当前 web-view 页，避免好友误进 Tab 首页
		return {
			title: this.shareTitle,
			path: '/pages/h5/h5?url=' + encodeURIComponent(this.url || ''),
			imageUrl: settings.shareCoverUrl,
		}
	},
	methods: {
		refreshShareConfig() {
			if (!this.shareActiveId) return
			syncWebActiveMpShare(this, this.shareActiveId, this.shareTitle)
			if (this.$u && this.$u.mpShare) {
				this.$u.mpShare.imageUrl = settings.shareCoverUrl
			}
		},
	},
}
</script>

<style lang="scss">

</style>
