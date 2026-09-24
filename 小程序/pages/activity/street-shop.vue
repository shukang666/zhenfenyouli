<template>
	<view class="scene-redirect"></view>
</template>

<script>
export default {
	onLoad() {
		uni.redirectTo({ url: '/pages/activity/scene-clock-flow?hub=street' })
	},
}
</script>

<style lang="scss" scoped>
.scene-redirect {
	min-height: 100vh;
	background: #f4f7f5;
}
</style>
