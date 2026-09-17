<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="志愿督导" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>

		<!-- 今日状态卡片 -->
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30">
			<view class="u-text-center u-font-32 u-font-bold u-m-b-20">志愿督导签到</view>
			<view class="u-font-24 u-type-info u-text-center">
				当前时段：{{peakText}}
			</view>
			<view class="u-m-t-20 u-text-center" v-if="todaySigned">
				<view class="u-font-28" style="color:#18AF38;">今日已签到 ✓</view>
				<view class="u-font-24 u-type-info u-m-t-10" v-if="todayRecord">积分 +{{todayRecord.point || 0}}</view>
			</view>
			<view class="u-m-t-20 u-text-center" v-else-if="!inPeakHour">
				<view class="u-font-28 u-type-error">当前不在高峰时段</view>
			</view>
			<view class="u-m-t-20 u-text-center" v-else>
				<view class="u-font-28" style="color:#18AF38;">可签到</view>
			</view>
		</view>

		<!-- 签到表单 -->
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30" v-if="!todaySigned && inPeakHour">
			<view class="u-font-28 u-m-b-16">督导小区</view>
			<view class="select-row" @click="showCommunity = true">
				<text v-if="form.community_name" class="u-font-28">{{form.community_name}}</text>
				<text v-else class="u-font-28 u-type-info">请选择督导小区</text>
				<u-icon name="arrow-right" color="#ccc"></u-icon>
			</view>

			<view class="u-font-28 u-m-b-16 u-m-t-30">督导点位</view>
			<view class="select-row" @click="showPoint = true">
				<text v-if="form.point_name" class="u-font-28">{{form.point_name}}</text>
				<text v-else class="u-font-28 u-type-info">请选择督导点位（可选）</text>
				<u-icon name="arrow-right" color="#ccc"></u-icon>
			</view>

			<view class="u-font-28 u-m-b-16 u-m-t-30">督导照片</view>
			<view class="upload-area" @click="chooseImage">
				<view v-if="!form.image" class="upload-placeholder">
					<u-icon name="camera" size="60" color="#ccc"></u-icon>
					<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
				</view>
				<image v-else :src="form.image" mode="aspectFill" class="upload-image"></image>
			</view>

			<view class="u-font-28 u-m-b-16 u-m-t-30">督导内容</view>
			<textarea
				class="remark-textarea"
				v-model="form.content"
				placeholder="请描述本次督导内容（必填）"
				placeholder-style="color:#ccc;font-size:24rpx;"
				maxlength="500"
			></textarea>

			<view class="u-m-t-40 u-m-b-40">
				<u-button
					type="primary"
					shape="circle"
					hover-class="none"
					:disabled="!form.community_id || !form.image || !form.content"
					@click="submit"
					:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '90rpx', fontSize: '32rpx' }"
				>提交签到</u-button>
			</view>
		</view>

		<!-- 我的历史记录 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-30 u-p-30">
			<view class="u-font-30 u-font-bold u-m-b-20">我的督导记录</view>
			<view v-if="historyList.length == 0" class="u-p-30">
				<u-empty text="暂无记录" mode="list"></u-empty>
			</view>
			<view v-for="(item, idx) in historyList" :key="idx" class="history-item u-m-b-20">
				<view class="u-flex u-row-between">
					<view class="u-font-28">{{item.community_name}}</view>
					<view class="u-font-24 u-type-info">{{item.supervision_date}}</view>
				</view>
				<view class="u-font-24 u-type-info u-m-t-10" v-if="item.point > 0">积分 +{{item.point}}</view>
			</view>
		</view>

		<!-- 小区选择 -->
		<u-select v-model="showCommunity" label-name="label" value-name="value" :list="communityList" @confirm="confirmCommunity"></u-select>
		<!-- 点位选择 -->
		<u-select v-model="showPoint" label-name="label" value-name="value" :list="pointList" @confirm="confirmPoint"></u-select>

		<u-gap height="40"></u-gap>
	</view>
</template>

<script>
	import { uploadUrl } from '@/common/settings'
	export default {
		data() {
			return {
				uploadUrl,
				todaySigned: false,
				todayRecord: null,
				inPeakHour: false,
				peakPeriods: { am: ['07:30', '09:30'], pm: ['16:30', '18:30'] },
				peakText: '',
				form: {
					community_id: 0,
					community_name: '',
					point_id: 0,
					point_name: '',
					image: '',
					content: '',
				},
				showCommunity: false,
				showPoint: false,
				communityList: [],
				pointList: [],
				historyList: [],
			};
		},
		onLoad() {
			this.loadToday();
			this.loadCommunityList();
			this.loadHistory();
		},
		onShow() {
			this.loadToday();
			this.loadHistory();
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
			loadToday() {
				// 使用 isLogin 状态判断（不自动跳转登录页），让用户可以看到页面内容，提交签到时才检查登录
				if (!this.isLogin) return;
				this.$u.api.getVolunteerToday().then(res => {
					this.todaySigned = res.data.today_signed;
					this.todayRecord = res.data.record;
					this.inPeakHour = res.data.in_peak_hour;
					this.peakPeriods = res.data.peak_periods || this.peakPeriods;
					this.peakText = this.formatPeakText(this.peakPeriods);
				}).catch(() => {});
			},
			formatPeakText(periods) {
				let parts = [];
				if (periods.am && periods.am.length >= 2) parts.push(`${periods.am[0]}-${periods.am[1]}`);
				if (periods.pm && periods.pm.length >= 2) parts.push(`${periods.pm[0]}-${periods.pm[1]}`);
				return parts.length > 0 ? parts.join('、') : '未配置';
			},
			loadCommunityList() {
				this.$u.api.get_community_list().then(res => {
					this.communityList = (res.data || []).map(item => ({
						label: item.name,
						value: item.id,
					}));
				}).catch(() => {});
			},
			loadPointList(communityId) {
				this.$u.api.get_community_cate_list({ community_id: communityId }).then(res => {
					this.pointList = (res.data || []).map(item => ({
						label: item.name,
						value: item.id,
					}));
				}).catch(() => {});
			},
		loadHistory() {
				// 使用 isLogin 状态判断（不自动跳转登录页）
				if (!this.isLogin) return;
				this.$u.api.getVolunteerMyList({ page: 1, page_size: 10 }).then(res => {
					this.historyList = res.data.data || [];
				}).catch(() => {});
			},
			confirmCommunity(e) {
				const item = e[0];
				this.form.community_id = item.value;
				this.form.community_name = item.label;
				// 切换小区时加载点位
				this.form.point_id = 0;
				this.form.point_name = '';
				this.loadPointList(item.value);
			},
			confirmPoint(e) {
				const item = e[0];
				this.form.point_id = item.value;
				this.form.point_name = item.label;
			},
			chooseImage() {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						this.uploadImage(res.tempFilePaths[0]);
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
			submit() {
				// 点击提交签到时才检查登录
				if (!this.$u.func.checkLogin()) return;
				if (!this.form.community_id) {
					uni.showToast({ title: '请选择督导小区', icon: 'none' });
					return;
				}
				if (!this.form.image) {
					uni.showToast({ title: '请上传督导照片', icon: 'none' });
					return;
				}
				if (!this.form.content) {
					uni.showToast({ title: '请填写督导内容', icon: 'none' });
					return;
				}
				uni.showLoading({ title: '提交中...' });
				this.$u.api.postVolunteerSignIn({
					community_id: this.form.community_id,
					community_name: this.form.community_name,
					point_id: this.form.point_id,
					point_name: this.form.point_name,
					image: this.form.image,
					content: this.form.content,
				}).then(res => {
					uni.hideLoading();
					uni.showToast({
						title: res.msg || '督导签到成功',
						icon: 'none',
						duration: 2000
					});
					setTimeout(() => {
						this.loadToday();
						this.loadHistory();
						this.resetForm();
					}, 1500);
				}).catch(err => {
					uni.hideLoading();
					uni.showToast({ title: (err && err.msg) || '提交失败', icon: 'none' });
				});
			},
			resetForm() {
				this.form = {
					community_id: 0,
					community_name: '',
					point_id: 0,
					point_name: '',
					image: '',
					content: '',
				};
			},
		}
	}
</script>

<style lang="scss">
.select-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 0;
	border-bottom: 1rpx solid #eee;
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
.history-item {
	padding: 20rpx;
	background: #f9f9f9;
	border-radius: 12rpx;
}
</style>
