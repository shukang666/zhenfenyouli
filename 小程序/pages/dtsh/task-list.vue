<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="整改任务" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>

		<!-- Tab 切换 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-30 u-p-10">
			<u-tabs :list="tabs" :is-scroll="false" :current="currentTab" @change="changeTab" active-color="#18AF38"></u-tabs>
		</view>

		<!-- 可领取列表 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-20 u-p-30" v-if="currentTab == 0">
			<view v-if="taskList.length == 0"><u-empty text="暂无可领取任务" mode="list"></u-empty></view>
			<view v-for="(item, idx) in taskList" :key="idx" class="task-card u-m-b-20">
				<view class="u-font-30 u-font-bold">{{item.title}}</view>
				<view class="u-font-26 u-type-info u-m-t-10">{{item.content}}</view>
				<view class="u-flex u-row-between u-m-t-20">
					<view class="u-font-26" style="color:#18AF38;">奖励积分 +{{item.point}}</view>
					<u-button
						v-if="!item.accepted"
						type="success"
						size="mini"
						shape="circle"
						@click="acceptTask(item)"
					>领取任务</u-button>
					<u-button
						v-else
						type="info"
						size="mini"
						shape="circle"
						disabled
					>已领取</u-button>
				</view>
			</view>
		</view>

		<!-- 我的任务列表 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-20 u-p-30" v-if="currentTab == 1">
			<view v-if="myTasks.length == 0"><u-empty text="无我的任务" mode="list"></u-empty></view>
			<view v-for="(item, idx) in myTasks" :key="idx" class="task-card u-m-b-20" @click="goSubmit(item)">
				<view class="u-font-30 u-font-bold">{{item.task_title}}</view>
				<view class="u-font-26 u-type-info u-m-t-10">{{item.task_content}}</view>
				<view class="u-flex u-row-between u-m-t-20">
					<view class="u-font-26" style="color:#18AF38;">奖励积分 +{{item.task_point}}</view>
					<view>
						<u-tag :text="item.status_name" :type="taskStatusType(item.status)" mode="dark" />
					</view>
				</view>
				<view v-if="item.status == 2 && item.reason" class="u-font-24 u-type-error u-m-t-10">拒绝原因：{{item.reason}}</view>
			</view>
		</view>

		<u-gap height="40"></u-gap>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				tabs: [
					{ name: '可领取' },
					{ name: '我的任务' },
				],
				currentTab: 0,
				taskList: [],
				myTasks: [],
				page: 1,
				myPage: 1,
			};
		},
		onLoad() {
			this.loadTaskList();
			this.loadMyTasks();
		},
		onShow() {
			this.loadTaskList();
			this.loadMyTasks();
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
			changeTab(idx) {
				this.currentTab = idx;
			},
			loadTaskList() {
				// 使用 isLogin 状态判断（不自动跳转登录页），让用户可以看到页面内容，点击领取任务时才检查登录
				if (!this.isLogin) return;
				this.$u.api.getVolunteerTaskList().then(res => {
					this.taskList = res.data || [];
				}).catch(() => {});
			},
			loadMyTasks() {
				if (!this.isLogin) return;
				this.$u.api.getMyVolunteerTasks({ page: 1, page_size: 20 }).then(res => {
					this.myTasks = res.data.data || [];
				}).catch(() => {});
			},
			acceptTask(item) {
				// 点击领取任务时才检查登录
				if (!this.$u.func.checkLogin()) return;
				uni.showModal({
					title: '确认领取',
					content: `确定领取整改任务「${item.title}」吗？`,
					success: (r) => {
						if (!r.confirm) return;
						this.$u.api.acceptVolunteerTask({ task_id: item.id }).then(res => {
							uni.showToast({ title: res.msg || '领取成功', icon: 'none' });
							this.loadTaskList();
						}).catch(err => {
							uni.showToast({ title: (err && err.msg) || '领取失败', icon: 'none' });
						});
					}
				});
			},
			goSubmit(item) {
				// 点击任务卡片进入提交页时才检查登录
				if (!this.$u.func.checkLogin()) return;
				if (item.status != 0) {
					// 待提交(status=0) 才进入提交页；待审核、审核失败也允许重新提交
					if (item.status == 3) {
						// 审核通过后不可再提交
						uni.showToast({ title: '任务已完成', icon: 'none' });
						return;
					}
				}
				this.$u.route({
					url: '/pages/dtsh/task-submit',
					params: {
						log_id: item.id,
						title: item.task_title,
						content: item.task_content,
						point: item.task_point,
						status: item.status,
						images: (item.images_arr && item.images_arr.length > 0) ? JSON.stringify(item.images_arr) : '',
					}
				});
			},
			taskStatusType(status) {
				const map = { 0: 'info', 1: 'warning', 2: 'error', 3: 'success' };
				return map[status] || 'info';
			},
		},
	}
</script>

<style lang="scss">
.task-card {
	padding: 24rpx;
	background: #f9f9f9;
	border-radius: 12rpx;
}
</style>
