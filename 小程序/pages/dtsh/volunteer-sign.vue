<template>
	<view class="page-wrap u-p-30">
		<u-navbar title-color="#fff" title="桶边督导" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="page-bg u-abso"></view>

		<!-- 顶栏：随时督导 + 先小区后点位 -->
		<view class="header-card u-m-t-20">
			<view class="header-top u-flex u-row-between u-col-center u-m-b-8">
				<view class="time-badge">随时督导</view>
				<view class="supervisor-name u-line-1">{{ supervisorLabel }}</view>
			</view>
			<view class="select-row" @click="openCommunityPicker">
				<text class="select-label">督导小区</text>
				<view class="select-value u-flex u-col-center u-flex-1 u-row-right">
					<text v-if="form.community_name" class="select-text u-line-1">{{ form.community_name }}</text>
					<text v-else class="select-placeholder">请选择小区</text>
					<u-icon name="arrow-right" size="24" color="#ccc" class="u-m-l-8"></u-icon>
				</view>
			</view>
			<view class="select-row select-row-last" :class="{ 'select-row-disabled': !form.community_id }" @click="openPointPicker">
				<text class="select-label">督导点位</text>
				<view class="select-value u-flex u-col-center u-flex-1 u-row-right">
					<u-icon name="map-fill" size="28" color="#18AF38" class="u-m-r-8"></u-icon>
					<text v-if="form.point_name" class="select-text u-line-1">{{ form.point_name }}</text>
					<text v-else class="select-placeholder">{{ form.community_id ? '请选择点位' : '请先选择小区' }}</text>
					<u-icon name="arrow-right" size="24" color="#ccc" class="u-m-l-8"></u-icon>
				</view>
			</view>
		</view>

		<!-- 今日已完成 -->
		<view class="card u-m-t-24" v-if="todaySigned">
			<view class="u-text-center u-font-32 u-font-bold">今日督导已完成</view>
			<view class="u-font-28 u-m-t-20 u-text-center" style="color:#18AF38;">感谢你的桶边督导 ✓</view>
			<view class="u-font-24 u-type-info u-m-t-10 u-text-center" v-if="todayRecord && todayRecord.point > 0">审核通过后可获 {{ todayRecord.point }} 积分</view>
		</view>

		<block v-else>
			<!-- 标准动作（展示） -->
			<view class="card u-m-t-24">
				<view class="section-title u-m-b-24">标准动作</view>
				<view class="action-row u-flex u-row-between">
					<view class="action-item" v-for="(item, idx) in standardActions" :key="idx">
						<view class="action-icon u-flex u-col-center u-row-center">
							<u-icon :name="item.icon" size="36" color="#18AF38"></u-icon>
						</view>
						<text class="action-label">{{ item.label }}</text>
					</view>
				</view>
			</view>

			<!-- 督导记录 -->
			<view class="card u-m-t-24">
				<view class="section-title u-m-b-24">督导记录</view>

				<view class="form-row u-flex u-row-between u-col-center">
					<text class="form-label">引导居民人次</text>
					<view class="stepper u-flex u-col-center">
						<view class="stepper-btn" @click="adjustCount('guide_count', -1)">−</view>
						<text class="stepper-num">{{ form.guide_count }}</text>
						<view class="stepper-btn" @click="adjustCount('guide_count', 1)">+</view>
					</view>
				</view>

				<view class="form-row u-flex u-row-between u-col-center">
					<text class="form-label">纠正错投次数</text>
					<view class="stepper u-flex u-col-center">
						<view class="stepper-btn" @click="adjustCount('correct_count', -1)">−</view>
						<text class="stepper-num">{{ form.correct_count }}</text>
						<view class="stepper-btn" @click="adjustCount('correct_count', 1)">+</view>
					</view>
				</view>

				<view class="form-row">
					<text class="form-label u-m-b-16">桶内纯净度</text>
					<view class="seg-group u-flex">
						<view
							v-for="opt in purityOptions"
							:key="opt.value"
							class="seg-item"
							:class="{ active: form.bin_purity === opt.value }"
							@click="form.bin_purity = opt.value"
						>{{ opt.label }}</view>
					</view>
				</view>

				<view class="form-row">
					<text class="form-label u-m-b-16">设施状态</text>
					<view class="seg-group u-flex">
						<view
							class="seg-item"
							:class="{ active: form.facility_status === 'normal' }"
							@click="setFacilityStatus('normal')"
						>正常</view>
						<view
							class="seg-item seg-item-warn"
							:class="{ active: form.facility_status === 'abnormal' }"
							@click="setFacilityStatus('abnormal')"
						>异常</view>
					</view>
					<view class="field-hint" v-if="form.facility_status === 'abnormal'">
						设施异常可
						<text class="link-text" @click="goProblemReport">前往问题反馈</text>
						上报
					</view>
				</view>

				<view class="form-row">
					<text class="form-label u-m-b-16">桶位卫生</text>
					<view class="seg-group u-flex">
						<view
							v-for="opt in hygieneOptions"
							:key="opt.value"
							class="seg-item"
							:class="{ active: form.bin_hygiene === opt.value }"
							@click="form.bin_hygiene = opt.value"
						>{{ opt.label }}</view>
					</view>
				</view>

				<view class="form-row">
					<text class="form-label u-m-b-16">现场照片</text>
					<view class="upload-area" @click="chooseImage">
						<view v-if="!form.image" class="upload-placeholder">
							<u-icon name="camera" size="56" color="#ccc"></u-icon>
							<view class="u-m-t-10 u-font-24 u-type-info">点击拍照或选择图片</view>
						</view>
						<image v-else :src="form.image" mode="aspectFill" class="upload-image"></image>
					</view>
				</view>
			</view>

			<view class="report-banner u-flex u-row-between u-col-center" @click="goProblemReport">
				<text class="report-banner-text">发现问题？一键上报</text>
				<u-icon name="arrow-right" size="28" color="#e65100"></u-icon>
			</view>

			<view class="u-m-t-30 u-m-b-16">
				<u-button
					type="primary"
					shape="circle"
					hover-class="none"
					:disabled="!canSubmit"
					@click="submit"
					:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '96rpx', fontSize: '32rpx' }"
				>完成督导并提交</u-button>
				<view class="reward-hint u-text-center u-m-t-16">完成后可获得 {{ rewardPoint }} 积分（审核通过后发放）</view>
			</view>
		</block>

		<view class="card u-m-t-24 u-m-b-20">
			<view class="section-title u-m-b-20">我的督导记录</view>
			<view v-if="historyList.length == 0" class="u-p-20">
				<u-empty text="暂无记录" mode="list"></u-empty>
			</view>
			<view v-for="(item, idx) in historyList" :key="idx" class="history-item u-m-b-16">
				<view class="u-flex u-row-between">
					<view class="u-font-28 u-line-1 u-flex-1">{{ item.point_name || item.community_name }}</view>
					<view class="u-font-24 u-type-info u-m-l-16">{{ item.supervision_date }}</view>
				</view>
				<view class="u-font-24 u-type-info u-m-t-8" v-if="item.point > 0">积分 +{{ item.point }}</view>
			</view>
		</view>

		<!-- 小区选择 -->
		<view v-if="showCommunityDropdown" class="community-dropdown-mask" @click="showCommunityDropdown = false">
			<view class="community-dropdown" @click.stop>
				<view class="u-font-28 u-font-bold u-m-b-16">选择督导小区</view>
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
						@click="selectCommunity(item)"
					>
						<view class="u-font-28">{{ item.name }}</view>
						<view class="u-font-24 u-type-info" v-if="item.parent_name">{{ item.parent_name }}</view>
					</view>
					<view v-if="communityLoading" class="u-text-center u-p-tb-20 u-font-24 u-type-info">加载中...</view>
					<view v-if="communityNoMore && communityResults.length" class="u-text-center u-p-tb-20 u-font-24 u-type-info">没有更多了</view>
				</scroll-view>
			</view>
		</view>
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
				rewardPoint: 100,
				checkRole: null,
				form: {
					community_id: 0,
					community_name: '',
					point_id: 0,
					point_name: '',
					image: '',
					guide_count: 0,
					correct_count: 0,
					bin_purity: 'good',
					facility_status: 'normal',
					bin_hygiene: 'tidy',
				},
				standardActions: [
					{ icon: 'eye', label: '观察投放行为' },
					{ icon: 'grid', label: '错投现场分拣' },
					{ icon: 'arrow-upward', label: '引导正确投放' },
					{ icon: 'chat', label: '柔性劝导' },
				],
				purityOptions: [
					{ label: '好', value: 'good' },
					{ label: '中', value: 'medium' },
					{ label: '差', value: 'poor' },
				],
				hygieneOptions: [
					{ label: '整洁', value: 'tidy' },
					{ label: '一般', value: 'average' },
					{ label: '较差', value: 'poor' },
				],
				showPoint: false,
				showCommunityDropdown: false,
				communityKeyword: '',
				communityResults: [],
				communityPage: 1,
				communityLastPage: 1,
				communityLoading: false,
				communitySearchTimer: null,
				pointList: [],
				historyList: [],
			};
		},
		computed: {
			communityNoMore() {
				return this.communityPage >= this.communityLastPage;
			},
			supervisorLabel() {
				if (this.checkRole && this.checkRole.role_name) {
					return '督导员' + this.checkRole.role_name;
				}
				const name = (this.userInfo && (this.userInfo.nickname || this.userInfo.user_nickname)) || '';
				return name ? '督导员' + name : '督导员';
			},
			canSubmit() {
				return this.form.community_id && this.form.point_id && this.form.image;
			},
			isVolunteerApproved() {
				return this.checkRole && this.checkRole.is_approved;
			},
		},
		onLoad() {
			this.loadToday();
			this.loadHistory();
			this.loadCheckRole();
		},
		onShow() {
			this.loadToday();
			this.loadHistory();
		},
		methods: {
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({ url: '/pages/index/index' });
					}
				});
			},
			loadCheckRole() {
				if (!this.isLogin) return;
				this.$u.api.getCheckRole().then(res => {
					this.checkRole = res.data || null;
				}).catch(() => {});
			},
			loadToday() {
				if (!this.isLogin) return;
				this.$u.api.getVolunteerToday().then(res => {
					this.todaySigned = res.data.today_signed;
					this.todayRecord = res.data.record;
					if (res.data.reward_point != null) {
						this.rewardPoint = res.data.reward_point;
					}
				}).catch(() => {});
			},
			openPointPicker() {
				if (!this.form.community_id) {
					uni.showToast({ title: '请先选择督导小区', icon: 'none' });
					return;
				}
				if (!this.pointList.length) {
					this.loadPointList(this.form.community_id, () => {
						if (!this.pointList.length) {
							uni.showToast({ title: '该小区暂无点位', icon: 'none' });
							return;
						}
						this.showPoint = true;
					});
					return;
				}
				this.showPoint = true;
			},
			openCommunityPicker() {
				this.showCommunityDropdown = true;
				this.communityKeyword = '';
				this.communityPage = 1;
				this.communityResults = [];
				this.loadCommunityList('', true);
			},
			loadCommunityList(keyword, isReset = false) {
				if (this.communityLoading) return;
				this.communityLoading = true;
				this.$u.api.search_community({ keyword, page: isReset ? 1 : this.communityPage, page_size: 20 }).then(res => {
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
			selectCommunity(item) {
				if (!item || !item.id) return;
				this.form.community_id = item.id;
				this.form.community_name = item.name;
				this.form.point_id = 0;
				this.form.point_name = '';
				this.showCommunityDropdown = false;
				this.loadPointList(item.id);
			},
			loadPointList(communityId, done) {
				this.$u.api.get_community_cate_list({ community_id: communityId }).then(res => {
					this.pointList = (res.data || []).map(row => ({
						label: row.name,
						value: row.id,
					}));
					if (typeof done === 'function') done();
				}).catch(() => {
					if (typeof done === 'function') done();
				});
			},
			confirmPoint(e) {
				const item = e[0];
				this.form.point_id = item.value;
				this.form.point_name = item.label;
			},
			adjustCount(field, delta) {
				const next = Math.max(0, (this.form[field] || 0) + delta);
				this.form[field] = next;
			},
			setFacilityStatus(val) {
				this.form.facility_status = val;
				if (val === 'abnormal') {
					uni.showModal({
						title: '设施异常',
						content: '可前往「问题反馈」上报设施问题，是否现在去反馈？',
						confirmText: '去反馈',
						cancelText: '稍后',
						success: (res) => {
							if (res.confirm) this.goProblemReport();
						},
					});
				}
			},
			goProblemReport() {
				if (!this.$u.func.checkLogin()) return;
				const approved = this.isVolunteerApproved ? 1 : 0;
				this.$u.route(`/pages/dtsh/report?location_type=1&can_submit=${approved}`);
			},
			chooseImage() {
				uni.chooseImage({
					count: 1,
					sourceType: ['camera', 'album'],
					success: (res) => {
						this.uploadImage(res.tempFilePaths[0]);
					},
				});
			},
			uploadImage(filePath) {
				uni.showLoading({ title: '上传中...' });
				uni.uploadFile({
					url: this.uploadUrl,
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
					},
				});
			},
			buildContent() {
				const purityMap = { good: '好', medium: '中', poor: '差' };
				const hygieneMap = { tidy: '整洁', average: '一般', poor: '较差' };
				const facilityMap = { normal: '正常', abnormal: '异常' };
				const lines = [
					`引导居民人次：${this.form.guide_count}`,
					`纠正错投次数：${this.form.correct_count}`,
					`桶内纯净度：${purityMap[this.form.bin_purity] || this.form.bin_purity}`,
					`设施状态：${facilityMap[this.form.facility_status] || this.form.facility_status}`,
					`桶位卫生：${hygieneMap[this.form.bin_hygiene] || this.form.bin_hygiene}`,
				];
				if (this.form.facility_status === 'abnormal') {
					lines.push('（设施异常，建议同步问题反馈）');
				}
				return lines.join('\n');
			},
			submit() {
				if (!this.$u.func.checkLogin()) return;
				if (!this.form.community_id) {
					uni.showToast({ title: '请先选择督导小区', icon: 'none' });
					this.openCommunityPicker();
					return;
				}
				if (!this.form.point_id) {
					uni.showToast({ title: '请选择督导点位', icon: 'none' });
					this.openPointPicker();
					return;
				}
				if (!this.form.image) {
					uni.showToast({ title: '请上传现场照片', icon: 'none' });
					return;
				}
				uni.showLoading({ title: '提交中...' });
				this.$u.api.postVolunteerSignIn({
					community_id: this.form.community_id,
					community_name: this.form.community_name,
					point_id: this.form.point_id,
					point_name: this.form.point_name,
					image: this.form.image,
					content: this.buildContent(),
				}).then(res => {
					uni.hideLoading();
					uni.showToast({
						title: res.msg || '督导提交成功',
						icon: 'none',
						duration: 2000,
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
					guide_count: 0,
					correct_count: 0,
					bin_purity: 'good',
					facility_status: 'normal',
					bin_hygiene: 'tidy',
				};
				this.pointList = [];
			},
			loadHistory() {
				if (!this.isLogin) return;
				this.$u.api.getVolunteerMyList({ page: 1, page_size: 10 }).then(res => {
					this.historyList = res.data.data || [];
				}).catch(() => {});
			},
		},
	};
</script>

<style lang="scss">
.page-wrap {
	min-height: 100vh;
	padding-bottom: 40rpx;
}
.page-bg {
	z-index: -1;
	top: 0;
	left: 0;
	right: 0;
	height: 552rpx;
	background: linear-gradient(to bottom, #2fb74c, #eff9f1);
}
.header-card,
.card {
	background: #fff;
	border-radius: 16rpx;
	padding: 28rpx 32rpx;
	box-shadow: 0 4rpx 24rpx rgba(24, 175, 56, 0.06);
}
.header-top {
	margin-bottom: 4rpx;
}
.time-badge {
	flex-shrink: 0;
	font-size: 22rpx;
	color: #18af38;
	background: #e8f8ec;
	padding: 8rpx 16rpx;
	border-radius: 8rpx;
	font-weight: 600;
}
.supervisor-name {
	flex: 1;
	min-width: 0;
	margin-left: 16rpx;
	font-size: 22rpx;
	color: #666;
	text-align: right;
}
.select-row {
	display: flex;
	align-items: center;
	padding: 22rpx 0;
	border-bottom: 1rpx solid #f0f0f0;
}
.select-row-last {
	border-bottom: none;
	padding-bottom: 4rpx;
}
.select-row-disabled .select-placeholder {
	color: #ccc;
}
.select-label {
	flex-shrink: 0;
	width: 150rpx;
	font-size: 28rpx;
	color: #333;
}
.select-value {
	min-width: 0;
	justify-content: flex-end;
}
.select-text {
	font-size: 28rpx;
	color: #333;
	max-width: 400rpx;
	text-align: right;
}
.select-placeholder {
	font-size: 28rpx;
	color: #999;
}
.action-row {
	width: 100%;
}
.section-title {
	font-size: 30rpx;
	font-weight: bold;
	color: #333;
}
.action-item {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 0 4rpx;
}
.action-icon {
	width: 72rpx;
	height: 72rpx;
	border-radius: 50%;
	background: #eef9f0;
	margin-bottom: 10rpx;
}
.action-label {
	font-size: 20rpx;
	color: #555;
	text-align: center;
	line-height: 1.35;
	width: 100%;
}
.form-row {
	padding: 24rpx 0;
	border-bottom: 1rpx solid #f0f0f0;
	&:last-child {
		border-bottom: none;
	}
}
.form-label {
	font-size: 28rpx;
	color: #333;
}
.stepper-btn {
	width: 56rpx;
	height: 56rpx;
	line-height: 52rpx;
	text-align: center;
	border: 1rpx solid #ddd;
	border-radius: 8rpx;
	font-size: 32rpx;
	color: #666;
}
.stepper-num {
	min-width: 64rpx;
	text-align: center;
	font-size: 32rpx;
	font-weight: bold;
	margin: 0 16rpx;
}
.seg-group {
	flex-wrap: wrap;
	gap: 16rpx;
}
.seg-item {
	padding: 12rpx 28rpx;
	font-size: 26rpx;
	color: #666;
	background: #f5f5f5;
	border-radius: 8rpx;
	border: 2rpx solid transparent;
	&.active {
		color: #18af38;
		background: #e8f8ec;
		border-color: #18af38;
		font-weight: 600;
	}
}
.seg-item-warn.active {
	color: #e65100;
	background: #fff3e0;
	border-color: #ff9800;
}
.field-hint {
	margin-top: 12rpx;
	font-size: 22rpx;
	color: #999;
}
.link-text {
	color: #18af38;
	text-decoration: underline;
}
.upload-area {
	width: 100%;
	height: 280rpx;
	background: #f5f5f5;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2rpx dashed #ddd;
	overflow: hidden;
}
.upload-placeholder {
	display: flex;
	flex-direction: column;
	align-items: center;
}
.upload-image {
	width: 100%;
	height: 280rpx;
}
.report-banner {
	margin-top: 24rpx;
	padding: 24rpx 28rpx;
	background: linear-gradient(90deg, #fff8e1, #fff);
	border-radius: 12rpx;
	border: 1rpx solid #ffe0b2;
}
.report-banner-text {
	font-size: 26rpx;
	color: #e65100;
	font-weight: 500;
}
.reward-hint {
	font-size: 22rpx;
	color: #999;
}
.history-item {
	padding: 20rpx;
	background: #f9f9f9;
	border-radius: 12rpx;
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
