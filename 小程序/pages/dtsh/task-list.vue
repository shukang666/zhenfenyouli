<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="整改任务" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>

		<!-- Tab 切换 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-30 u-p-20">
			<view class="u-font-24 u-type-info">每人每天最多领取 1 条</view>
		</view>
		<view class="u-type-white-bg u-radius-6 u-m-t-20 u-p-10">
			<u-tabs :list="tabs" :is-scroll="false" :current="currentTab" @change="changeTab" active-color="#18AF38"></u-tabs>
		</view>

		<!-- 可领取列表 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-20 u-p-30" v-if="currentTab == 0">
			<view v-if="taskList.length == 0"><u-empty text="暂无可领取任务" mode="list"></u-empty></view>
			<view v-for="(item, idx) in taskList" :key="idx" class="task-card u-m-b-20">
				<view class="u-font-30 u-font-bold">{{item.title}}</view>
				<image v-if="item.image" :src="item.image" mode="aspectFill" class="shot-thumb u-m-t-16" @click="previewShot(item.image)"></image>
				<view class="u-font-26 u-type-info u-m-t-10">{{item.content}}</view>
				<view class="u-flex u-row-between u-m-t-20">
					<view class="u-font-26" style="color:#18AF38;">奖励积分 +{{item.point}}</view>
					<u-button
						v-if="item.accepted"
						type="info"
						size="mini"
						shape="circle"
						disabled
					>已领取</u-button>
					<u-button
						v-else-if="item.daily_limit"
						type="info"
						size="mini"
						shape="circle"
						disabled
					>今日已领</u-button>
					<u-button
						v-else
						type="success"
						size="mini"
						shape="circle"
						@click="acceptTask(item)"
					>领取任务</u-button>
				</view>
			</view>
		</view>

		<!-- 我的任务列表 -->
		<view class="u-type-white-bg u-radius-6 u-m-t-20 u-p-30" v-if="currentTab == 1">
			<view v-if="myTasks.length == 0"><u-empty text="无我的任务" mode="list"></u-empty></view>
			<view v-for="(item, idx) in myTasks" :key="idx" class="task-card u-m-b-20" @click="goSubmit(item)">
				<view class="u-font-30 u-font-bold">{{ taskDisplayTitle(item) }}</view>
				<image v-if="item.task_image" :src="item.task_image" mode="aspectFill" class="shot-thumb u-m-t-16" @click.stop="previewShot(item.task_image)"></image>
				<view class="u-font-26 u-type-info u-m-t-10">{{ taskDisplayContent(item) }}</view>
				<view class="u-flex u-row-between u-m-t-20">
					<view class="u-font-26" style="color:#18AF38;">奖励积分 +{{ taskDisplayPoint(item) }}</view>
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
				const i = (idx && typeof idx === 'object') ? idx.index : idx
				this.currentTab = i
				if (i == 1) this.loadMyTasks()
				else this.loadTaskList()
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
					content: `确定领取「${item.title}」吗？每人每天最多领取 1 条。`,
					success: (r) => {
						if (!r.confirm) return;
						this.$u.api.acceptVolunteerTask({ task_id: item.id }).then(res => {
							uni.showToast({ title: res.msg || '领取成功', icon: 'none' });
							this.loadTaskList();
							this.loadMyTasks();
						}).catch(err => {
							uni.showToast({ title: (err && err.msg) || '领取失败', icon: 'none' });
						});
					}
				});
			},
			goSubmit(item) {
				if (!this.$u.func.checkLogin()) return;
				const st = parseInt(item.status, 10);
				if (st === 1) {
					uni.showToast({ title: '审核中，请勿重复提交', icon: 'none' });
					return;
				}
				if (st === 3) {
					uni.showToast({ title: '任务已完成', icon: 'none' });
					return;
				}
				this.$u.route({
					url: '/pages/dtsh/task-submit',
					params: {
						log_id: item.id,
						title: this.taskDisplayTitle(item),
						content: this.taskDisplayContent(item),
						point: this.taskDisplayPoint(item),
						status: item.status,
						shot_image: item.task_image || '',
						images: (item.images_arr && item.images_arr.length > 0) ? JSON.stringify(item.images_arr) : '',
					}
				});
			},
			taskStatusType(status) {
				const map = { 0: 'info', 1: 'warning', 2: 'error', 3: 'success' };
				return map[status] || 'info';
			},
			taskDisplayTitle(item) {
				const t = item && item.task_title;
				if (t === null || t === undefined || t === '' || t === 'null') {
					return '整改任务（已下架）';
				}
				return t;
			},
			taskDisplayContent(item) {
				const c = item && item.task_content;
				if (c === null || c === undefined || c === '' || c === 'null') {
					return '关联任务已删除，请等待管理员审核您的提交';
				}
				return c;
			},
			taskDisplayPoint(item) {
				const p = item && item.task_point;
				if (p === null || p === undefined || p === 'null' || p === '') {
					return 0;
				}
				const n = parseInt(p, 10);
				return isNaN(n) ? 0 : n;
			},
			previewShot(url) {
				if (!url) return;
				uni.previewImage({ urls: [url] });
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
.shot-thumb {
	width: 200rpx;
	height: 200rpx;
	border-radius: 12rpx;
	background: #eee;
}
</style>
