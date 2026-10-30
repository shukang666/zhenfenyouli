<template>
	<view>
		<fSignatureVue @getImg="getImg"></fSignatureVue>
	</view>
</template>

<script>
	import fSignatureVue from '@/pages/zzhd/components/f-signature/f-signature.vue';
	import {
		uploadUrl
	} from '@/common/settings';
	export default {
		data() {
			return {
				uploadUrl
			};
		},
		components: {
			fSignatureVue
		},
		methods: {
			getImg(e) {
				console.log(e);
				this.uploadImg(e)
			},
			uploadImg(e) {
				uni.uploadFile({
					url: uploadUrl,
					filePath: e,
					name: 'file',
					fileType: 'image',
					success: (uploadFileRes) => {
						let imgData = JSON.parse(uploadFileRes.data)
						let img = imgData.data.url
						uni.$emit('getNameImg', img)
						uni.navigateBack()
					}
				});
			}


		}
	}
</script>

<style lang="scss">

</style>