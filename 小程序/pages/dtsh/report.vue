<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="随手发现问题" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30">
			<view class="u-text-center u-font-32 u-font-bold u-m-b-30">随手发现问题</view>
			<view class="u-font-28 u-type-info u-text-center">
				拍摄小区、非小区（例如沿街商铺）存在的问题
			</view>

			<!-- 问题类型 -->
			<view class="u-m-t-40">
				<view class="u-font-28 u-m-b-16">问题类型</view>
				<view class="u-flex">
					<view class="u-m-r-20" @click="form.location_type = 1">
						<u-tag text="小区内" :mode="form.location_type==1?'dark':'plain'" :type="form.location_type==1?'success':'info'" />
					</view>
					<view @click="form.location_type = 2">
						<u-tag text="非小区" :mode="form.location_type==2?'dark':'plain'" :type="form.location_type==2?'success':'info'" />
					</view>
				</view>
			</view>

			<!-- 小区内：选小区 + 点位 -->
			<view class="u-m-t-40" v-if="form.location_type == 1">
				<view class="u-font-28 u-m-b-16">小区与点位</view>
				<view class="u-m-b-20">
					<view class="u-font-24 u-type-info u-m-b-8">所属小区</view>
					<view class="location-display">
						<u-icon name="home" size="28" color="#18AF38"></u-icon>
						<text class="location-text">{{ communityName || '未绑定小区' }}</text>
					</view>
				</view>
				<view>
					<view class="u-font-24 u-type-info u-m-b-8">问题点位</view>
					<view class="location-picker" @click="showPointPicker = true">
						<text v-if="form.point_name" class="location-text">{{ form.point_name }}</text>
						<text v-else class="location-placeholder">请选择点位</text>
						<u-icon name="arrow-right" size="24" color="#999"></u-icon>
					</view>
				</view>
			</view>

			<!-- 非小区：商户名称 + 地址 -->
			<view class="u-m-t-40" v-if="form.location_type == 2">
				<view class="u-font-28 u-m-b-16">商户信息</view>
				<view class="u-m-b-20">
					<view class="u-font-24 u-type-info u-m-b-8">商户名称</view>
					<input
						class="merchant-input"
						v-model="form.merchant_name"
						placeholder="请输入商户名称"
						placeholder-style="color:#ccc;font-size:26rpx;"
						maxlength="50"
					/>
				</view>
				<view>
					<view class="u-font-24 u-type-info u-m-b-8">详细地址</view>
					<input
						class="merchant-input"
						v-model="form.address"
						placeholder="请输入详细地址"
						placeholder-style="color:#ccc;font-size:26rpx;"
						maxlength="100"
					/>
				</view>
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
					hover-class="none"
					:disabled="!canSubmit"
					@click="submit"
					:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '90rpx', fontSize: '32rpx' }"
				>提交</u-button>
			</view>
		</view>

		<!-- 点位选择器 -->
		<u-select
			v-model="showPointPicker"
			label-name="name"
			value-name="id"
			:list="pointList"
			@confirm="onPointConfirm"
		></u-select>
	</view>
</template>

<script>
	import {
		uploadUrl
	} from '@/common/settings'
	export default {
		data() {
			return {
				form: {
					image: '',
					remark: '',
					location_type: 1,   // 默认小区内
					// 小区内
					community_id: 0,
					community_name: '',
					point_id: 0,
					point_name: '',
					// 非小区
					merchant_name: '',
					address: '',
				},
				communityName: '',   // 用户绑定的小区名称
				communityId: 0,     // 用户绑定的小区ID
				pointList: [],      // 点位列表
				showPointPicker: false,
				uploadUrl,
			};
		},
		computed: {
			canSubmit() {
				if (!this.form.image || !this.form.remark) return false;
				if (this.form.location_type == 1) {
					// 小区内：需要小区+点位
					return this.communityId > 0 && this.form.point_id > 0;
				} else {
					// 非小区：需要商户名称+地址
					return this.form.merchant_name.trim() && this.form.address.trim();
				}
			}
		},
		onLoad(option) {
			if (option.location_type) {
				const lt = parseInt(option.location_type);
				if ([1, 2].includes(lt)) this.form.location_type = lt;
			}
			this.loadUserCommunity();
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

			// 加载用户绑定的小区信息
			loadUserCommunity() {
				this.$u.api.getUserInfo().then(res => {
					if (res.data) {
						this.communityName = res.data.community || '';
						this.communityId = res.data.community_id || 0;
						this.form.community_id = this.communityId;
						this.form.community_name = this.communityName;
						// 加载点位列表
						if (this.communityId > 0) {
							this.loadPointList();
						}
					}
				}).catch(() => {});
			},

			// 加载点位列表
			loadPointList() {
				this.$u.api.get_community_cate_list().then(res => {
					this.pointList = res.data || [];
				}).catch(() => {
					this.pointList = [];
				});
			},

			// 点位选择确认
			onPointConfirm(e) {
				if (e && e.length) {
					this.form.point_id = e[0].value;
					this.form.point_name = e[0].label;
				}
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
					url: this.uploadUrl + '/wxapp/public/upload_asset',
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

				// 根据位置类型校验
				if (this.form.location_type == 1) {
					if (this.communityId <= 0) {
						uni.showToast({ title: '请先绑定小区', icon: 'none' });
						return;
					}
					if (!this.form.point_id) {
						uni.showToast({ title: '请选择问题点位', icon: 'none' });
						return;
					}
				} else {
					if (!this.form.merchant_name.trim()) {
						uni.showToast({ title: '请填写商户名称', icon: 'none' });
						return;
					}
					if (!this.form.address.trim()) {
						uni.showToast({ title: '请填写详细地址', icon: 'none' });
						return;
					}
				}

				uni.showLoading({ title: '提交中...' });
				this.$u.api.postShotReport({
					image: this.form.image,
					remark: this.form.remark,
					location_type: this.form.location_type || 1,
					community_id: this.form.community_id,
					community_name: this.form.community_name,
					point_id: this.form.point_id,
					point_name: this.form.point_name,
					merchant_name: this.form.merchant_name,
					address: this.form.address,
				}).then(res => {
					uni.hideLoading();
					uni.showToast({
						title: res.msg || '已提交',
						icon: 'none',
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
	/* ===== 小区内位置展示 ===== */
	.location-display {
		display: flex;
		align-items: center;
		gap: 10rpx;
		padding: 20rpx 24rpx;
		background: #F0F2F5;
		border-radius: 12rpx;
	}

	.location-picker {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 20rpx 24rpx;
		background: #F5F5F5;
		border-radius: 12rpx;
		border: 2rpx solid #eee;
	}

	.location-text {
		font-size: 28rpx;
		color: #333;
		flex: 1;
	}

	.location-placeholder {
		font-size: 28rpx;
		color: #ccc;
		flex: 1;
	}

	/* ===== 非小区商户输入 ===== */
	.merchant-input {
		width: 100%;
		padding: 20rpx 24rpx;
		background: #F5F5F5;
		border-radius: 12rpx;
		font-size: 28rpx;
		color: #333;
		border: 2rpx solid #eee;
		box-sizing: border-box;
	}

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
		box-sizing: border-box;
	}
</style>
