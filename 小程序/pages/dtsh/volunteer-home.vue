<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" :title="pageTitle" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>

		<!-- 标题卡片 -->
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30 u-text-center">
			<view class="u-font-36 u-font-bold" style="color:#18AF38;">{{pageTitle}}</view>
			<view class="u-font-24 u-type-info u-m-t-10">{{roleName}}工作平台</view>
			<!-- 未通过审核：显示申请按钮 -->
			<view class="u-m-t-20" v-if="!isApproved">
				<u-button type="primary" size="mini" hover-class="none"
					:customStyle="{background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)'}"
					@click="goApply">申请{{roleName}}</u-button>
			</view>
		</view>

		<!-- 功能入口 4 宫格 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-30 u-p-30">
			<view class="u-font-30 u-font-bold u-m-b-20">快捷功能</view>
			<view class="grid-2">
				<!-- 志愿督导 -->
				<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==1" @click="goToPage('/pages/dtsh/volunteer-sign')">
					<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #a8e6cf 0%, #38c172 100%);">
						<u-icon name="clock" size="48" color="#fff"></u-icon>
					</view>
					<text class="u-font-26 u-m-t-10">志愿督导</text>
				</view>
				<!-- 督导巡查 -->
				<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==1" @click="goToPage('/pages/zzhd/dwxj/dwxj?type=3')">
					<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #ffd3a5 0%, #fd794f 100%);">
						<u-icon name="eye" size="48" color="#fff"></u-icon>
					</view>
					<text class="u-font-26 u-m-t-10">督导巡查</text>
				</view>
				<!-- 问题反馈（督导员） -->
				<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==1" @click="goToReport(1)">
					<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #90caf9 0%, #1976d2 100%);">
						<u-icon name="warning" size="48" color="#fff"></u-icon>
					</view>
					<text class="u-font-26 u-m-t-10">问题反馈</text>
				</view>
				<!-- 督导监测（指导员） -->
				<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==2" @click="$u.toast('开发中，敬请期待')">
					<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #90caf9 0%, #1976d2 100%);">
						<u-icon name="eye" size="48" color="#fff"></u-icon>
					</view>
					<text class="u-font-26 u-m-t-10">督导监测</text>
				</view>
				<!-- 随手发现问题（指导员） -->
				<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==2" @click="goToReport(2)">
					<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #ffd3a5 0%, #fd794f 100%);">
						<u-icon name="camera" size="48" color="#fff"></u-icon>
					</view>
					<text class="u-font-26 u-m-t-10">随手发现问题</text>
				</view>
				<!-- 整改任务（通用） -->
				<view class="grid-item u-flex u-col-center u-row-center u-flex-col" @click="goToPage('/pages/dtsh/task-list')">
					<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #f48fb1 0%, #d81b60 100%);">
						<u-icon name="file-text" size="48" color="#fff"></u-icon>
					</view>
					<text class="u-font-26 u-m-t-10">整改任务</text>
				</view>
				<!-- 商户共治（指导员预留） -->
				<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==2" @click="$u.toast('开发中，敬请期待')">
					<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #ce93d8 0%, #6a1b9a 100%);">
						<u-icon name="shopping-cart" size="48" color="#fff"></u-icon>
					</view>
					<text class="u-font-26 u-m-t-10">商户共治</text>
				</view>
			</view>
		</view>

		<!-- 我的志愿督导记录 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-30 u-p-30" v-if="roleType==1 && supervision.total > 0">
			<view class="u-font-30 u-font-bold u-m-b-20">我的志愿督导</view>
			<view class="u-flex u-row-between">
				<view class="u-type-info u-font-24">累计签到次数</view>
				<view class="u-font-bold" style="color:#18AF38; font-size: 32rpx;">{{supervision.total}} 次</view>
			</view>
			<view class="u-flex u-row-between u-m-t-10" v-if="supervision.last_date">
				<view class="u-type-info u-font-24">最近签到</view>
				<view class="u-font-26">{{supervision.last_date}}</view>
			</view>
		</view>

		<u-gap height="40"></u-gap>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				roleType: 1,          // 1=督导员 2=指导员
				checkInfo: null,
				checkStatus: 0,        // 1=待审核 2=通过 3=拒绝
				isApproved: false,     // 是否已审核通过
				supervision: {
					total: 0,
					last_date: '',
				},
			};
		},
		computed: {
			pageTitle() {
				return this.roleType == 2 ? '我是指导员' : '我是督导员';
			},
			roleName() {
				return this.roleType == 2 ? '指导员' : '督导员';
			},
		},
		onLoad(option) {
			if (option.role_type) {
				this.roleType = parseInt(option.role_type) == 2 ? 2 : 1;
			}
			this.loadData();
		},
		onShow() {
			this.loadData();
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
		loadData() {
				// 查审核状态
				if (this.$u.func.checkLogin()) {
					this.$u.api.getCheckRole().then(res => {
						this.checkInfo = res.data;
						this.checkStatus = res.data.status || 0;
						this.roleType = res.data.role_type == 2 ? 2 : 1;
						this.isApproved = !!res.data.is_approved;
					}).catch(err => {
						// 暂无申请记录：视为未通过
						this.isApproved = false;
					});
					// 查我的志愿督导（仅已批准用户）
					if (this.roleType == 1 && this.isApproved) {
						this.$u.api.getVolunteerMyList({page: 1, page_size: 1}).then(res => {
							this.supervision.total = res.data.total || 0;
							if (res.data.data && res.data.data.length > 0) {
								this.supervision.last_date = res.data.data[0].supervision_date;
							}
						}).catch(() => {});
					}
				}
			},
			goToPage(url) {
				if (!this.$u.func.checkLogin()) return;
				// 未批准也能进入页面看效果，但传参控制提交权限
				this.$u.route(url);
			},
			goToReport(locationType) {
				if (!this.$u.func.checkLogin()) return;
				// 未批准也能进入页面看效果，但传参控制提交权限
				this.$u.route(`/pages/dtsh/report?location_type=${locationType}&can_submit=${this.isApproved ? 1 : 0}`);
			},
			goApply() {
				// 申请引导：根据当前申请状态给出提示
				if (!this.checkInfo) {
					// 未申请：提示用户去申请
					this.$u.toast(`请先填写申请成为${this.roleName}`);
				} else if (this.checkStatus == 1) {
					this.$u.toast('您的申请正在审核中，请耐心等待');
				} else if (this.checkStatus == 3) {
					this.$u.toast('您的申请已被拒绝，可重新申请');
				}
			},
		}
	}
</script>

<style lang="scss">
.grid-2 {
	display: flex;
	flex-wrap: wrap;
}
.grid-item {
	width: 50%;
	padding: 24rpx 0;
}
.grid-icon {
	width: 100rpx;
	height: 100rpx;
	border-radius: 24rpx;
}
</style>
