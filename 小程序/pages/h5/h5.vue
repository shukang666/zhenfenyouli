<template>
	<view>
		<web-view :src="url"></web-view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				url: ''
			};
		},
		onLoad(option) {
			this.url = decodeURIComponent(option.url);
		}
	}
</script>

<style lang="scss">

</style>
