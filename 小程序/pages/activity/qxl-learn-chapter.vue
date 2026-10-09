<template>
	<view class="redirect-page"></view>
</template>

<script>
export default {
	onLoad(query) {
		let url = '/pages/activity/qxl-learn-exam'
		if (query && query.id) {
			url += '?scrollId=' + query.id
		}
		uni.redirectTo({ url })
	},
}
</script>
