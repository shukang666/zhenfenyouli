<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="提交整改" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>

		<!-- 任务信息 -->
		<view class="u-p-30 u-type-white-bg u-radius-6 u-m-t-30">
			<view class="u-font-30 u-font-bold">{{taskTitle}}</view>
			<image v-if="shotImage" :src="shotImage" mode="aspectFill" class="shot-thumb u-m-t-16" @click="previewShot"></image>
			<view class="u-font-26 u-type-info u-m-t-10">{{taskContent}}</view>
			<view class="u-font-26" style="color:#18AF38; margin-top: 16rpx;" v-if="taskPoint > 0">奖励积分 +{{taskPoint}}</view>
		</view>

		<!-- 提交内容：待提交 / 被拒绝可再交；待审核不允许进这里，双保险仍不展示表单 -->
		<view class="u-p-30 u-type-white-bg u-radius-6 u-m-t-30" v-if="status == 0 || status == 2">
			<view class="u-font-28 u-m-b-16">整改照片</view>
			<view class="upload-area" @click="chooseImage">
				<view v-if="images.length == 0" class="upload-placeholder">
					<u-icon name="camera" size="60" color="#ccc"></u-icon>
					<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
				</view>
				<view v-else class="u-flex u-flex-wrap">
					<view v-for="(img, idx) in images" :key="idx" class="img-wrap u-m-r-10 u-m-b-10">
						<image :src="img" mode="aspectFill" class="u-w-200 u-h-200" style="border-radius: 12rpx;"></image>
						<view class="img-del" @click.stop="removeImage(idx)">×</view>
					</view>
					<view v-if="images.length < 3" class="add-more u-flex u-col-center u-row-center u-w-200 u-h-200" @click="chooseImage">
						<u-icon name="plus" size="40" color="#ccc"></u-icon>
					</view>
				</view>
			</view>

			<view class="u-font-28 u-m-b-16 u-m-t-30">整改说明</view>
			<textarea
				class="remark-textarea"
				v-model="content"
				placeholder="请描述整改情况（必填）"
				placeholder-style="color:#ccc;font-size:24rpx;"
				maxlength="500"
			></textarea>

			<view class="u-m-t-40 u-m-b-40">
				<u-button
					type="primary"
					shape="circle"
					hover-class="none"
					:disabled="images.length == 0 && !content"
					@click="submit"
					:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '90rpx', fontSize: '32rpx' }"
				>提交整改</u-button>
			</view>
		</view>

		<view class="u-p-30 u-type-white-bg u-radius-6 u-m-t-30" v-if="status == 1">
			<view class="u-font-30 u-font-bold" style="color:#E6A23C;">审核中</view>
			<view class="u-font-26 u-type-info u-m-t-10">已提交整改，请等待后台审核，无需重复提交</view>
		</view>

		<!-- 已完成的任务显示结果 -->
		<view class="u-p-30 u-type-white-bg u-radius-6 u-m-t-30" v-if="status == 3">
			<view class="u-font-30 u-font-bold" style="color:#18AF38;">✓ 审核通过</view>
			<view class="u-font-26 u-type-info u-m-t-10">积分 +{{taskPoint}} 已下发</view>
		</view>
	</view>
</template>

<script>
	import { uploadUrl } from '@/common/settings'
	export default {
		data() {
			return {
				uploadUrl,
				logId: 0,
				taskTitle: '',
				taskContent: '',
				taskPoint: 0,
				shotImage: '',
				status: 0,          // log.status: 0=待提交, 1=待审核, 2=失败, 3=通过
				images: [],
				content: '',
			};
		},
		onLoad(option) {
			this.logId = parseInt(option.log_id) || 0;
			this.taskTitle = option.title || '';
			this.taskContent = option.content || '';
			this.taskPoint = parseInt(option.point) || 0;
			this.shotImage = option.shot_image ? decodeURIComponent(option.shot_image) : '';
			this.status = parseInt(option.status) || 0;
			if (option.images) {
				try {
					const arr = JSON.parse(option.images);
					if (Array.isArray(arr)) this.images = arr;
				} catch (e) {}
			}
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
			chooseImage() {
				const remain = 3 - this.images.length;
				if (remain <= 0) return;
				uni.chooseImage({
					count: remain,
					sourceType: ['camera', 'album'],
					success: (res) => {
						res.tempFilePaths.forEach(p => this.uploadImage(p));
					}
				});
			},
			uploadImage(filePath) {
				uni.showLoading({ title: '上传中...' });
				uni.uploadFile({
					url: this.uploadUrl + '/wxapp/public/upload_asset',
					filePath: filePath,
					name: 'file',
					formData: { filetype: 'image' },
					success: (uploadRes) => {
						uni.hideLoading();
						try {
							const data = JSON.parse(uploadRes.data);
							if (data.code == 1) {
								this.images.push(data.data.url);
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
			removeImage(idx) {
				this.images.splice(idx, 1);
			},
			previewShot() {
				if (!this.shotImage) return;
				uni.previewImage({ urls: [this.shotImage] });
			},
			submit() {
				if (this.status == 1 || this.status == 3) {
					uni.showToast({ title: this.status == 3 ? '任务已完成' : '审核中，请勿重复提交', icon: 'none' });
					return;
				}
				if (this.images.length == 0 && !this.content) {
					uni.showToast({ title: '请上传整改照片或填写说明', icon: 'none' });
					return;
				}
				uni.showLoading({ title: '提交中...' });
				this.$u.api.submitVolunteerTask({
					log_id: this.logId,
					images: this.images,
					content: this.content,
				}).then(res => {
					uni.hideLoading();
					uni.showToast({
						title: res.msg || '提交成功',
						icon: 'none',
						duration: 2000
					});
					setTimeout(() => {
						uni.navigateBack();
					}, 2000);
				}).catch(err => {
					uni.hideLoading();
					uni.showToast({ title: (err && err.msg) || '提交失败', icon: 'none' });
				});
			},
		}
	}
</script>

<style lang="scss">
.upload-area {
	min-height: 200rpx;
	padding: 20rpx;
	background: #f5f5f5;
	border-radius: 12rpx;
	border: 2rpx dashed #ddd;
	.upload-placeholder {
		min-height: 200rpx;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
	}
}
.img-wrap {
	position: relative;
	.img-del {
		position: absolute;
		top: -10rpx;
		right: -10rpx;
		width: 40rpx;
		height: 40rpx;
		background: rgba(0, 0, 0, 0.5);
		color: #fff;
		border-radius: 50%;
		text-align: center;
		line-height: 40rpx;
		font-size: 28rpx;
	}
}
.add-more {
	background: #f0f0f0;
	border-radius: 12rpx;
	border: 2rpx dashed #ddd;
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
	box-sizing: border-box;
}
.shot-thumb {
	width: 200rpx;
	height: 200rpx;
	border-radius: 12rpx;
	background: #eee;
}
</style>
