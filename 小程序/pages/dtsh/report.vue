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
					<view class="location-picker" @click="toggleCommunityDropdown">
						<view class="u-flex u-flex-1 u-col-center">
							<u-icon name="home" size="28" color="#18AF38" class="u-m-r-10"></u-icon>
							<text v-if="form.community_name" class="location-text">{{ form.community_name }}</text>
							<text v-else class="location-placeholder">未绑定小区，请点击选择</text>
						</view>
						<u-icon name="arrow-right" size="24" color="#999"></u-icon>
					</view>
				</view>
				<view>
					<view class="u-font-24 u-type-info u-m-b-8">问题点位</view>
					<view class="location-picker" @click="openPointPicker">
						<text v-if="form.point_name" class="location-text">{{ form.point_name }}</text>
						<text v-else class="location-placeholder">选填，请选择点位</text>
						<view class="location-picker-tail">
							<text
								v-if="form.point_id"
								class="point-clear-link"
								@click.stop="clearPoint"
							>不选点位</text>
							<u-icon name="arrow-right" size="24" color="#999"></u-icon>
						</view>
					</view>
				</view>
				<view class="u-font-22 u-type-info u-m-t-12 point-field-hint">
					选填。若问题不在固定点位，可不选；请在下方「问题描述」中写明楼栋、单元或具体位置。
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

		<!-- 小区选择（搜索 + 列表，仅本次提交） -->
		<view v-if="showCommunityDropdown" class="community-dropdown-mask" @click="showCommunityDropdown = false">
			<view class="community-dropdown" @click.stop>
				<view class="u-font-28 u-font-bold u-m-b-16">选择小区</view>
				<view class="u-radius-5 u-p-lr-20" style="background-color: #f7f7f7;">
					<u-input v-model="communityKeyword" type="text" placeholder="输入小区名称搜索" :clearable="true" @input="onCommunitySearch" />
				</view>
				<scroll-view scroll-y class="community-dropdown-list" @scrolltolower="loadMoreCommunity" lower-threshold="50">
					<view v-if="!communityResults.length && !communityLoading" class="u-text-center u-p-30 u-font-24 u-type-info">
						{{ communityKeyword ? '未找到相关小区' : '输入名称搜索，或下滑加载更多' }}
					</view>
					<view
						v-for="item in communityResults"
						:key="item.id"
						class="u-p-lr-20 u-p-tb-15 community-dropdown-item"
						@click="selectReportCommunity(item)"
					>
						<view class="u-font-28">{{ item.name }}</view>
						<view class="u-font-24 u-type-info" v-if="item.parent_name">{{ item.parent_name }}</view>
					</view>
					<view v-if="communityLoading" class="u-text-center u-p-tb-20 u-font-24 u-type-info">加载中...</view>
					<view v-if="communityNoMore && communityResults.length" class="u-text-center u-p-tb-20 u-font-24 u-type-info">没有更多了</view>
				</scroll-view>
			</view>
		</view>
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
				userCommunityName: '',
				userCommunityId: 0,
				pointList: [],
				showPointPicker: false,
				showCommunityDropdown: false,
				communityKeyword: '',
				communityResults: [],
				communityPage: 1,
				communityLastPage: 1,
				communityLoading: false,
				communitySearchTimer: null,
				uploadUrl,
			};
		},
		computed: {
			communityNoMore() {
				return this.communityPage >= this.communityLastPage;
			},
			canSubmit() {
				if (!this.form.image || !this.form.remark) return false;
				if (this.form.location_type == 1) {
					return this.form.community_id > 0;
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
						this.userCommunityName = res.data.community || '';
						this.userCommunityId = res.data.community_id || 0;
						this.form.community_id = this.userCommunityId;
						this.form.community_name = this.userCommunityName;
						if (this.form.community_id > 0) {
							this.loadPointList(this.form.community_id);
						}
					}
				}).catch(() => {});
			},

			toggleCommunityDropdown() {
				this.showCommunityDropdown = !this.showCommunityDropdown;
				if (this.showCommunityDropdown) {
					this.communityKeyword = '';
					this.communityPage = 1;
					this.communityResults = [];
					this.loadCommunityList('', true);
				}
			},

			loadCommunityList(keyword, isReset = false) {
				if (this.communityLoading) return;
				this.communityLoading = true;
				const page = isReset ? 1 : this.communityPage;
				this.$u.api.search_community({ keyword, page, page_size: 20 }).then(res => {
					const data = res.data || {};
					const list = data.data || [];
					if (isReset) {
						this.communityResults = list;
						this.communityPage = 1;
					} else {
						this.communityResults = this.communityResults.concat(list);
					}
					this.communityLastPage = data.last_page || 1;
				}).catch(() => {}).finally(() => {
					this.communityLoading = false;
				});
			},

			loadMoreCommunity() {
				if (this.communityLoading || this.communityNoMore) return;
				this.communityPage++;
				this.loadCommunityList(this.communityKeyword.trim(), false);
			},

			onCommunitySearch(value) {
				if (this.communitySearchTimer) clearTimeout(this.communitySearchTimer);
				const keyword = (value || '').trim();
				this.communitySearchTimer = setTimeout(() => {
					this.loadCommunityList(keyword, true);
				}, 300);
			},

			selectReportCommunity(item) {
				if (!item || !item.id) return;
				this.form.community_id = item.id;
				this.form.community_name = item.name;
				this.form.point_id = 0;
				this.form.point_name = '';
				this.showCommunityDropdown = false;
				this.communityKeyword = '';
				this.communityResults = [];
				this.loadPointList(item.id);
			},

			loadPointList(communityId) {
				const cid = communityId || this.form.community_id;
				if (!cid) {
					this.pointList = [];
					return;
				}
				this.$u.api.get_community_cate_list({ community_id: cid }).then(res => {
					this.pointList = res.data || [];
				}).catch(() => {
					this.pointList = [];
				});
			},

			openPointPicker() {
				if (!this.form.community_id) {
					uni.showToast({ title: '请先选择所属小区', icon: 'none' });
					return;
				}
				this.showPointPicker = true;
			},
			clearPoint() {
				this.form.point_id = 0;
				this.form.point_name = '';
			},
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
					url: this.uploadUrl,
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
					if (!this.form.community_id) {
						uni.showToast({ title: '请选择所属小区', icon: 'none' });
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

	.location-picker-tail {
		display: flex;
		align-items: center;
		flex-shrink: 0;
		margin-left: 12rpx;
	}

	.point-clear-link {
		font-size: 24rpx;
		color: #8a958c;
		margin-right: 10rpx;
		padding: 6rpx 16rpx;
		line-height: 1.3;
		background: #ebebeb;
		border-radius: 999rpx;
	}

	.point-field-hint {
		line-height: 1.55;
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
	min-height: 80rpx;
	line-height: 40rpx;
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

	.community-dropdown-mask {
		position: fixed;
		left: 0;
		right: 0;
		top: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.35);
		z-index: 999;
		display: flex;
		align-items: flex-start;
		justify-content: center;
	}

	.community-dropdown {
		margin-top: 200rpx;
		width: 690rpx;
		background-color: #fff;
		border-radius: 16rpx;
		padding: 24rpx;
		box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);
	}

	.community-dropdown-list {
		max-height: 560rpx;
		margin-top: 16rpx;
	}

	.community-dropdown-item {
		border-bottom: 1rpx solid #f0f0f0;
		&:last-child {
			border-bottom: none;
		}
	}
</style>
