<template>
	<view class="container">
		<u-image v-if="info.invoice" width="750" mode="widthFix" :src="info.invoice"></u-image>

		<view class="submit-btn" style="padding: 30rpx 60rpx;" v-if="info.invoice">
			<u-button @click="saveImg(info.invoice)">下载保存</u-button>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				info: '',
			};
		},
		onLoad(op) {
			this.getInvoice(op.order_num)
		},
		methods: {
			saveImg(url) {
				uni.downloadFile({
					url,
					success: (res) => {
						if (res.statusCode === 200) {
							//鉴权
							uni.authorize({
								scope: 'scope.writePhotosAlbum',
								success: () => {
									//保存图片到相册
									uni.saveImageToPhotosAlbum({
										filePath: res.tempFilePath,
										success: () => {
											uni.showToast({
												title: '保存成功'
											})
										},
										fail: (err) => {
											//取消不为错，h5端可能有这个问题
											if (err.errMsg
												.includes(
													'fail cancel')
											) {
												uni.hideLoading()
												return
											}
											uni.showToast({
												title: '保存失败',
												icon: "error"
											})
										}
									});
								},
								fail: () => {
									// 鉴权失败手动打开
									uni.hideLoading();
									uni.showModal({
										title: '提示',
										content: '需要保存图片权限，请在设置中打开',
										showCancel: false,
										success: uni.openSetting
									});
								}
							});

						}
					},
					fail: (err) => {
						uni.showModal({
							content: JSON.stringify(err)
						})
						// 地址有误处理
						uni.showToast({
							title: '图片解析失败',
							icon: 'error'
						})
					}
				});
			},
			// 获取详情 
			async getInvoice(order_num) {
				try {
					let res = await this.$u.api.getInvoice({order_num})
					this.info = res.data
				} catch (e) {
					console.log(e);
				}
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		padding-bottom: 160rpx;
	}
</style>