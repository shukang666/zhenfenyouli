<template>
	<view class="redirect-page"></view>
</template>

<script>
export default {
	onLoad() {
		uni.redirectTo({ url: '/pages/activity/qxl-learn-exam' })
	},
}
</script>
