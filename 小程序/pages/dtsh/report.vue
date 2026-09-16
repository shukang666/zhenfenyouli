<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="随手发现问题" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30">
			<view class="u-text-center u-font-32 u-font-bold u-m-b-30">随手发现问题</view>
			<view class="u-font-28 u-type-info u-text-center">
				拍照记录小区、沿街商铺存在的问题
			</view>

			<!-- 拍照上传 -->
			<view class="u-m-t-40">
				<view class="u-font-28 u-m-b-16">问题照片</view>
				<view class="upload-area" @click="chooseImage">
					<view v-if="!form.image" class="upload-placeholder">
						<u-icon name="camera" size="60" color="#ccc"></u-icon>
						<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
					</view>
					<image v-else :src="form.image" mode="aspectFill" class="upload-image"></image>
				</view>
			</view>

			<!-- 备注 -->
			<view class="u-m-t-30">
				<view class="u-font-28 u-m-b-16">问题描述</view>
				<textarea
					class="remark-textarea"
					v-model="form.remark"
					placeholder="请描述发现的问题（必填）"
					placeholder-style="color:#ccc;font-size:24rpx;"
					maxlength="500"
				></textarea>
			</view>

			<!-- 提交按钮 -->
			<view class="u-m-t-40 u-m-b-40">
				<u-button
					type="primary"
					shape="circle"
					size="large"
					:disabled="!form.image || !form.remark"
					@click="submit"
					height="90"
				>提交</u-button>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				form: {
					image: '',
					remark: ''
				}
			};
		},
		methods: {
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({
							url: '/pages/index/index'
						})
					}
				})
			},

			// 选择图片并上传
			chooseImage() {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						const tempFilePath = res.tempFilePaths[0];
						this.uploadImage(tempFilePath);
					}
				});
			},

			// 上传图片到服务器
			uploadImage(filePath) {
				uni.showLoading({ title: '上传中...' });
				uni.uploadFile({
					url: this.$u.http.config.baseUrl + '/wxapp/public/upload_asset',
					filePath: filePath,
					name: 'file',
					formData: {
						filetype: 'image'
					},
					success: (uploadRes) => {
						uni.hideLoading();
						try {
							const data = JSON.parse(uploadRes.data);
							if (data.code == 1) {
								this.form.image = data.data.url;
								uni.showToast({ title: '上传成功', icon: 'success' });
							} else {
								uni.showToast({ title: data.msg || '上传失败', icon: 'none' });
							}
						} catch (e) {
							uni.showToast({ title: '上传失败', icon: 'none' });
						}
					},
					fail: () => {
						uni.hideLoading();
						uni.showToast({ title: '上传失败', icon: 'none' });
					}
				});
			},

			// 提交表单
			submit() {
				if (!this.form.image) {
					uni.showToast({ title: '请上传问题图片', icon: 'none' });
					return;
				}
				if (!this.form.remark) {
					uni.showToast({ title: '请填写问题描述', icon: 'none' });
					return;
				}

				uni.showLoading({ title: '提交中...' });
				this.$u.api.postShotReport({
					image: this.form.image,
					remark: this.form.remark
				}).then(res => {
					uni.hideLoading();
					uni.showToast({
						title: res.msg || '已提交，项目正在处理',
						icon: 'success',
						duration: 2000
					});
					setTimeout(() => {
						uni.navigateBack();
					}, 2000);
				}).catch(err => {
					uni.hideLoading();
					uni.showToast({ title: '提交失败，请重试', icon: 'none' });
				});
			}
		}
	}
</script>

<style lang="scss">
	.upload-area {
		width: 100%;
		height: 360rpx;
		background: #f5f5f5;
		border-radius: 12rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border: 2rpx dashed #ddd;
		position: relative;
		overflow: hidden;

		.upload-placeholder {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}

		.upload-image {
			width: 100%;
			height: 360rpx;
			border-radius: 12rpx;
		}
	}

	.remark-textarea {
		width: 100%;
		min-height: 200rpx;
		background: #f5f5f5;
		border-radius: 12rpx;
		padding: 20rpx;
		font-size: 26rpx;
		color: #333;
		border: 2rpx solid #eee;
	}
</style>
