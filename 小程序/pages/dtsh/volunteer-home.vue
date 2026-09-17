<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" :title="pageTitle" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>

		<!-- 标题卡片 -->
		<view class="u-p-40 u-type-white-bg u-radius-6 u-m-t-30 u-text-center">
			<view class="u-font-40 u-font-bold" style="color:#18AF38;">{{pageTitle}}</view>
			<view class="u-font-28 u-type-info u-m-t-10">{{roleName}}工作平台</view>
			<view class="u-font-24 u-type-info u-m-t-16 u-line-2" style="line-height: 1.8;">
				<template v-if="roleType==1">履行督导职责，参与桶边督导与巡检巡查，共建绿色社区</template>
				<template v-else>监测督导动态，协调问题反馈，推动商户共治与分类治理</template>
			</view>
			<!-- 未通过审核：显示申请按钮 -->
			<view class="u-m-t-20" v-if="!isApproved">
				<u-button type="primary" size="mini" hover-class="none"
					:customStyle="{background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)'}"
					@click="goApply">申请{{roleName}}</u-button>
			</view>
		</view>

		<!-- 功能入口 1行3列 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-30 u-p-30">
			<view class="u-font-32 u-font-bold u-m-b-20">快捷功能</view>
			<view class="grid-3">
			<!-- 第1个：志愿督导/督导监测 -->
			<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==1" @click="goToPage('/pages/dtsh/volunteer-sign')">
				<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #a8e6cf 0%, #38c172 100%);">
					<u-icon name="clock" size="48" color="#fff"></u-icon>
				</view>
				<text class="u-font-26 u-m-t-10">志愿督导</text>
			</view>
			<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==2" @click="goToMonitor">
				<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #90caf9 0%, #1976d2 100%);">
					<u-icon name="eye" size="48" color="#fff"></u-icon>
				</view>
				<text class="u-font-26 u-m-t-10">督导监测</text>
			</view>
			<!-- 第2个：督导巡查/随手发现问题 -->
			<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==1" @click="goToPage('/pages/zzhd/dwxj/dwxj?type=3')">
				<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #ffd3a5 0%, #fd794f 100%);">
					<u-icon name="eye" size="48" color="#fff"></u-icon>
				</view>
				<text class="u-font-26 u-m-t-10">督导巡查</text>
			</view>
			<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==2" @click="goToReport(2)">
				<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #ffd3a5 0%, #fd794f 100%);">
					<u-icon name="camera" size="48" color="#fff"></u-icon>
				</view>
				<text class="u-font-26 u-m-t-10">随手发现问题</text>
			</view>
			<!-- 第3个：仅督导员=问题反馈；指导员=商户共治 -->
			<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==1" @click="goToReport(roleType)">
				<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #90caf9 0%, #1976d2 100%);">
					<u-icon name="warning" size="48" color="#fff"></u-icon>
				</view>
				<text class="u-font-26 u-m-t-10">问题反馈</text>
			</view>
			<view class="grid-item u-flex u-col-center u-row-center u-flex-col" v-if="roleType==2" @click="goToMerchantInspect">
				<view class="grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #42a5f5 0%, #1565c0 100%);">
					<u-icon name="home" size="48" color="#fff"></u-icon>
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
						// ⚠️ 不覆盖 this.roleType！保持从入口参数确定的角色
						// 判断角色是否匹配：用户审核通过的角色必须和当前页面角色一致
						const approvedRoleType = res.data.role_type == 2 ? 2 : 1;
						this.isApproved = !!res.data.is_approved && (approvedRoleType === this.roleType);
					}).catch(err => {
						// 暂无申请记录：视为未通过
						this.isApproved = false;
					});
					// 查我的志愿督导（仅当前页面是督导员 且 确实是督导员身份）
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
			// 督导监测：复用指导员权限模式，未批准可看但禁提交
			goToMonitor() {
				if (!this.$u.func.checkLogin()) return;
				this.$u.route(`/pages/dtsh/supervision-monitor?can_submit=${this.isApproved ? 1 : 0}`);
			},
			goToMerchantInspect() {
				if (!this.$u.func.checkLogin()) return;
				this.$u.route('/pages/dtsh/merchant-inspect');
			},
			goApply() {
				// 跳转申请表单，传入 role_type 锁定角色
				this.$u.route({
					url: '/pages/zzhd/add',
					params: { role_type: this.roleType }
				});
			},
		}
	}
</script>

<style lang="scss">
.grid-3 {
	display: flex;
	flex-wrap: wrap;
}
.grid-item {
	width: 33.33%;
	padding: 24rpx 0;
}
.grid-icon {
	width: 100rpx;
	height: 100rpx;
	border-radius: 24rpx;
}
</style>
