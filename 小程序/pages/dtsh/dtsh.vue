<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" title="垃圾分类打卡" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
			<!-- <view class="u-m-l-30">
                <u-image width="231" height="64" :src="setting.min_logo"></u-image>
            </view> -->
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
		</view>
		<!-- 主 Banner 图 -->
		<view class="dtsh-banner" @click="goToClock" v-if="dtshBanner">
			<u-image width="100%" height="360" :src="dtshBanner" mode="aspectFill" border-radius="16"></u-image>
		</view>
		<!-- 轻量化任务 -->
		<view class="task-box u-type-white-bg u-radius-6 u-m-t-20 u-flex u-row-around u-p-t-30 u-p-b-30">
			<view class="task-item u-flex u-col-center u-flex-col" @click="goToPage('/pages/dtsh/report')">
				<view class="task-icon u-flex u-col-center u-row-center">
					<u-icon name="camera" size="48" color="#18AF38"></u-icon>
				</view>
				<text class="task-text u-font-24 u-m-t-12">随手发现问题</text>
			</view>
			<view class="task-item u-flex u-col-center u-flex-col" @click="goToPage('/pages/dtsh/quiz')">
				<view class="task-icon u-flex u-col-center u-row-center">
					<u-icon name="edit-pen" size="48" color="#18AF38"></u-icon>
				</view>
				<text class="task-text u-font-24 u-m-t-12">分类知识挑战</text>
			</view>
			<view class="task-item u-flex u-col-center u-flex-col" @click="goToPage('/pages/dtsh/knowledge')">
				<view class="task-icon u-flex u-col-center u-row-center">
					<u-icon name="file-text" size="48" color="#18AF38"></u-icon>
				</view>
				<text class="task-text u-font-24 u-m-t-12">学会分类知识</text>
			</view>
		</view>

		<!-- <view class="u-p-20 u-type-white-bg u-radius-6 u-m-t-30 u-flex">
			<u-image width="160" height="160" src="https://oss.ausite.cn/xcxkf052/admin/20250512/11f9c0ccaefaeb375e7cea7000914a8b.png?watermark"></u-image>
			<view class="u-flex-1 u-m-l-20 u-flex u-row-between u-col-top u-flex-col u-h-160">
				<view class="u-font-bold">
					分类活动
				</view>
				<view class="u-font-24 u-type-info">
					参与垃圾分类打卡可获得积碳币，可用于
					兑换商品，可参与打卡排行榜
				</view>
				<view class="u-text-right u-width-100">
					<u-button type="primary" size="mini" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
						@click="$u.route('/pages/flhd/ljfldk')">立即参与</u-button>
				</view>
			</view>
		</view>
		<view class="u-p-20 u-type-white-bg u-radius-6 u-m-t-30 u-flex">
			<u-image width="160" height="160" src="https://oss.ausite.cn/xcxkf052/admin/20250512/91648750a6ffb2b270e7bb5e6cfbd4c8c.png?watermark"></u-image>
			<view class="u-flex-1 u-m-l-20 u-flex u-row-between u-col-top u-flex-col u-h-160">
				<view class="u-font-bold">
					自治活动
				</view>
				<view class="u-font-24 u-type-info">
					可进行督导员申请，查看工作，优秀事迹
					宣传，分类督导等一系列活动
				</view>
				<view class="u-text-right u-width-100">
					<u-button type="primary" size="mini" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
						@click="$u.route('/pages/zzhd/zzhd')">立即参与</u-button>
				</view>
			</view>
		</view>
		<view class="u-p-20 u-type-white-bg u-radius-6 u-m-t-30 u-flex" v-if="!setting.app_test">
			<u-image width="160" height="160" src="https://oss.ausite.cn/xcxkf052/admin/20250512/cf50f2ff1cc358f5fb5ecfaa6b51a4e2.png?watermark"></u-image>
			<view class="u-flex-1 u-m-l-20 u-flex u-row-between u-col-top u-flex-col u-h-160">
				<view class="u-font-bold">
					社交活动
				</view>
				<view class="u-font-24 u-type-info">
					可进行课程培训，社区活动，社区知识竞赛
					等一系列活动
				</view>
				<view class="u-text-right u-width-100">
					<u-button type="primary" size="mini" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
						@click="$u.route('/pages/sjhd/sjhd')">立即参与</u-button>
				</view>
			</view>
		</view> -->

		<!-- 文明标兵工作台（督导员+指导员功能合并） -->
		<view class="vol-section u-m-t-30 u-type-white-bg u-radius-16 u-p-30">
			<view class="u-font-32 u-font-bold u-m-b-24">文明标兵工作台</view>
			<view class="vol-grid vol-grid-single u-flex u-row-center">
				<view class="vol-card u-flex u-col-center u-row-center u-flex-col" @click="goVolunteerHome()">
					<view class="vol-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #a8e6cf 0%, #38c172 100%);">
						<u-icon name="edit-pen-fill" size="56" color="#fff"></u-icon>
					</view>
					<text class="u-font-28 u-font-bold u-m-t-14" style="color:#333;">文明标兵</text>
					<text class="u-font-22 u-type-info u-m-t-6">督导监测/巡查/治理</text>
				</view>
			</view>
		</view>

		<!-- 整改任务 -->
		<view class="task-section u-m-t-30 u-type-white-bg u-radius-16 u-p-30">
			<view class="u-font-32 u-font-bold u-m-b-20">整改任务</view>
			<!-- Tab 切换 -->
			<view class="u-p-lr-10 u-m-b-20">
				<u-tabs :list="taskTabs" :is-scroll="false" :current="currentTab" @change="changeTab" active-color="#18AF38"></u-tabs>
			</view>
			<!-- 可领取列表 -->
			<view v-if="currentTab == 0">
				<view v-if="taskList.length == 0"><u-empty text="暂无可领取任务" mode="list"></u-empty></view>
				<view v-for="(item, idx) in taskList" :key="idx" class="task-card u-m-b-20">
					<view class="u-font-30 u-font-bold">{{item.title}}</view>
					<view class="u-font-26 u-type-info u-m-t-10">{{item.content}}</view>
					<view class="u-flex u-row-between u-m-t-20">
						<view class="u-font-26" style="color:#18AF38;">奖励积分 +{{item.point}}</view>
						<u-button v-if="!item.accepted" type="success" size="mini" shape="circle" @click="acceptTask(item)">领取任务</u-button>
						<u-button v-else type="info" size="mini" shape="circle" disabled>已领取</u-button>
					</view>
				</view>
			</view>
			<!-- 我的任务列表 -->
			<view v-if="currentTab == 1">
				<view v-if="myTasks.length == 0"><u-empty text="无我的任务" mode="list"></u-empty></view>
				<view v-for="(item, idx) in myTasks" :key="idx" class="task-card u-m-b-20" @click="goSubmit(item)">
					<view class="u-font-30 u-font-bold">{{item.task_title}}</view>
					<view class="u-font-26 u-type-info u-m-t-10">{{item.task_content}}</view>
					<view class="u-flex u-row-between u-m-t-20">
						<view class="u-font-26" style="color:#18AF38;">奖励积分 +{{item.task_point}}</view>
						<u-tag :text="item.status_name" :type="taskStatusType(item.status)" mode="dark" />
					</view>
					<view v-if="item.status == 2 && item.reason" class="u-font-24 u-type-error u-m-t-10">拒绝原因：{{item.reason}}</view>
				</view>
			</view>
		</view>

		<u-gap height="40"></u-gap>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				swiper_list: [],
				dtshBanner: '',
				checkRole: null, // { is_approved, role_type, role_name }
				taskTabs: [
					{ name: '可领取' },
					{ name: '我的任务' },
				],
				currentTab: 0,
				taskList: [],
				myTasks: [],
			};
		},
		computed: {
			isVolunteer() {
				return this.checkRole && this.checkRole.is_approved;
			}
		},
		onLoad() {
			this.find_slide()
			this.loadBanner()
			this.loadCheckRole()
			this.loadTaskList()
			this.loadMyTasks()
		},
		onShow() {
			this.loadTaskList()
			this.loadMyTasks()
		},
		methods: {
		// 加载用户角色（允许未登录进入，不强制跳转登录）
		loadCheckRole() {
			if (!this.isLogin) return;
			this.$u.api.getCheckRole().then(res => {
				this.checkRole = res.data || null;
			}).catch(() => {});
		},
			// 跳转页面
			navigator(url) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
			},
			// 轮播图跳转
			async adLink(e, item) {
				let data = item[e];
				if (!data.url) return
				if (data.target == 'switchTab') return uni.switchTab({
					url: data.url
				});
				const openUrl = await this.$u.func.checkH5(data.url);
				this.$u.route(openUrl);
			},
			// 轮播图
			find_slide() {
				this.$u.api.find_slide({
					id: 6
				}).then(res => {
					this.swiper_list = res.data
				})
			},
			// 加载 Banner 配置
			async loadBanner() {
				try {
					const res = await this.$u.api.getPageConfig()
					const banner = res.data?.dtsh_banner || ''
					if (banner) {
						this.dtshBanner = banner
					}
				} catch (e) {
					// 配置加载失败不影响页面
				}
			},
		// 点击 Banner 进入打卡界面
		goToClock() {
			this.$u.route('/pages/flhd/ljfldk')
		},
		// 轻量化任务跳转
		goToPage(url) {
			this.$u.route(url)
		},
		// 跳转文明标兵门户（不强制登录，让用户先看到页面，功能入口点击时再检查登录）
		goVolunteerHome() {
			this.$u.route('/pages/dtsh/volunteer-home');
		},
		// === 整改任务相关 ===
		changeTab(idx) {
			this.currentTab = idx;
		},
		loadTaskList() {
			// 使用 isLogin 状态判断，允许未登录进入页面
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
			if (item.status == 3) {
				uni.showToast({ title: '任务已完成', icon: 'none' });
				return;
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
		// 自定义返回逻辑
		customBack() {
			uni.navigateBack({
				fail() {
					uni.switchTab({
						url: '/pages/index/index'
					})
				}
			})
		},
		}
	}
</script>

<style lang="scss">
.task-icon {
	width: 100rpx;
	height: 100rpx;
	background: #E8F7EA;
	border-radius: 16rpx;
}
.task-text {
	color: #333;
}
.task-card {
	padding: 24rpx;
	background: #f9f9f9;
	border-radius: 12rpx;
}
.vol-grid {
	gap: 20rpx;
}
.vol-card {
	flex: 1;
	padding: 30rpx 0;
	background: #fff;
	border-radius: 12rpx;
}
.vol-icon {
	width: 110rpx;
	height: 110rpx;
	border-radius: 28rpx;
	background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
}
.vol-icon-blue {
	background: linear-gradient(141deg, #64B5F6 0%, #1976D2 100%);
}
.task-section {
	margin-bottom: 30rpx;
}
</style>
