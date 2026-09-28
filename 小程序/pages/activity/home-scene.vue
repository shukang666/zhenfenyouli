<template>
	<view class="scene-redirect"></view>
</template>

<script>
export default {
	onLoad() {
		uni.redirectTo({ url: '/pages/flhd/ljfldk' })
	},
}
</script>

<style lang="scss" scoped>
.scene-redirect {
	min-height: 100vh;
	background: #f4f7f5;
}
</style>
