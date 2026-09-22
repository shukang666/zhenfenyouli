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
		<!-- <view class="dtsh-banner" id="guide-dtsh-clock" @click="goToClock" v-if="dtshBanner">
			<u-image width="100%" height="360" :src="dtshBanner" mode="aspectFill" border-radius="16"></u-image>
		</view> -->
		<!-- 轻量化任务 -->
		<view class="task-box u-type-white-bg u-radius-6 u-m-t-20 u-flex u-row-around u-p-t-30 u-p-b-30" id="guide-dtsh-entry">
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

		<!-- 督导员工作台（原 volunteer-home 内容内嵌） -->
		<view class="vol-section u-m-t-30 u-type-white-bg u-radius-16 u-p-30" id="guide-dtsh-vol">
			<view class="u-font-32 u-font-bold u-m-b-20">督导员工作台</view>
			<view class="vol-intro u-text-center u-p-b-24" style="border-bottom: 1rpx solid #f0f0f0;">
				<view class="u-font-28 u-font-bold" style="color:#18AF38;">督导员工作平台</view>
				<view class="u-font-22 u-type-info u-m-t-12 u-line-2" style="line-height: 1.7;">
					履行督导职责，参与桶边督导与巡检巡查，监测督导动态，推动商户共治与分类治理
				</view>
				<view class="u-m-t-16" v-if="!isVolunteer">
					<u-button type="primary" size="mini" hover-class="none"
						:customStyle="{background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)'}"
						@click="goVolunteerApply">申请督导员</u-button>
				</view>
			</view>
			<view class="u-font-28 u-font-bold u-m-t-24 u-m-b-8">快捷功能</view>
			<view class="vol-grid-3">
				<view class="vol-grid-item u-flex u-col-center u-row-center u-flex-col" @click="goVolunteerPage('/pages/dtsh/volunteer-sign')">
					<view class="vol-grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #a8e6cf 0%, #38c172 100%);">
						<u-icon name="clock" size="44" color="#fff"></u-icon>
					</view>
					<text class="u-font-24 u-m-t-8">志愿督导</text>
				</view>
				<view class="vol-grid-item u-flex u-col-center u-row-center u-flex-col" @click="goVolunteerMonitor">
					<view class="vol-grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #90caf9 0%, #1976d2 100%);">
						<u-icon name="eye" size="44" color="#fff"></u-icon>
					</view>
					<text class="u-font-24 u-m-t-8">督导监测</text>
				</view>
				<view class="vol-grid-item u-flex u-col-center u-row-center u-flex-col" @click="goVolunteerPage('/pages/zzhd/dwxj/dwxj?type=3')">
					<view class="vol-grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #ffd3a5 0%, #fd794f 100%);">
						<u-icon name="eye" size="44" color="#fff"></u-icon>
					</view>
					<text class="u-font-24 u-m-t-8">督导巡查</text>
				</view>
				<view class="vol-grid-item u-flex u-col-center u-row-center u-flex-col" @click="goVolunteerReport(2)">
					<view class="vol-grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #ffd3a5 0%, #fd794f 100%);">
						<u-icon name="camera" size="44" color="#fff"></u-icon>
					</view>
					<text class="u-font-24 u-m-t-8">随手发现问题</text>
				</view>
				<view class="vol-grid-item u-flex u-col-center u-row-center u-flex-col" @click="goVolunteerReport(1)">
					<view class="vol-grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #90caf9 0%, #1976d2 100%);">
						<u-icon name="warning" size="44" color="#fff"></u-icon>
					</view>
					<text class="u-font-24 u-m-t-8">问题反馈</text>
				</view>
				<view class="vol-grid-item u-flex u-col-center u-row-center u-flex-col" @click="goVolunteerMerchantInspect">
					<view class="vol-grid-icon u-flex u-col-center u-row-center" style="background: linear-gradient(135deg, #42a5f5 0%, #1565c0 100%);">
						<u-icon name="home" size="44" color="#fff"></u-icon>
					</view>
					<text class="u-font-24 u-m-t-8">商户共治</text>
				</view>
			</view>
			<view class="u-m-t-24 u-p-t-20" v-if="volunteerSupervision.total > 0" style="border-top: 1rpx solid #f0f0f0;">
				<view class="u-font-28 u-font-bold u-m-b-16">我的志愿督导</view>
				<view class="u-flex u-row-between">
					<view class="u-type-info u-font-24">累计签到次数</view>
					<view class="u-font-bold" style="color:#18AF38;">{{ volunteerSupervision.total }} 次</view>
				</view>
				<view class="u-flex u-row-between u-m-t-10" v-if="volunteerSupervision.last_date">
					<view class="u-type-info u-font-24">最近签到</view>
					<view class="u-font-24">{{ volunteerSupervision.last_date }}</view>
				</view>
			</view>
		</view>

		<view class="task-section u-m-t-30 u-type-white-bg u-radius-16 u-p-30" id="guide-dtsh-task">
			<view class="u-font-32 u-font-bold">整改任务</view>
			<view class="u-font-24 u-type-info u-m-t-8 u-m-b-20">每人每天最多领取 1 条</view>
			<view class="u-p-lr-10 u-m-b-20">
				<u-tabs :list="taskTabs" :is-scroll="false" :current="currentTab" @change="changeTab" active-color="#18AF38"></u-tabs>
			</view>
			<view v-if="currentTab == 0">
				<view v-if="taskList.length == 0"><u-empty text="暂无可领取任务" mode="list"></u-empty></view>
				<view v-for="(item, idx) in taskList" :key="idx" class="task-card u-m-b-20">
					<view class="u-font-30 u-font-bold">{{item.title}}</view>
					<image v-if="item.image" :src="item.image" mode="aspectFill" class="shot-thumb u-m-t-16" @click="previewShot(item.image)"></image>
					<view class="u-font-26 u-type-info u-m-t-10">{{item.content}}</view>
					<view class="u-flex u-row-between u-m-t-20">
						<view class="u-font-26" style="color:#18AF38;">奖励积分 +{{item.point}}</view>
						<u-button v-if="item.accepted" type="info" size="mini" shape="circle" disabled>已领取</u-button>
						<u-button v-else-if="item.daily_limit" type="info" size="mini" shape="circle" disabled>今日已领</u-button>
						<u-button v-else type="success" size="mini" shape="circle" @click="acceptTask(item)">领取任务</u-button>
					</view>
				</view>
			</view>
			<view v-if="currentTab == 1">
				<view v-if="myTasks.length == 0"><u-empty text="无我的任务" mode="list"></u-empty></view>
				<view v-for="(item, idx) in myTasks" :key="idx" class="task-card u-m-b-20" @click="goSubmit(item)">
					<view class="u-font-30 u-font-bold">{{item.task_title}}</view>
					<image v-if="item.task_image" :src="item.task_image" mode="aspectFill" class="shot-thumb u-m-t-16" @click.stop="previewShot(item.task_image)"></image>
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
		<page-guide page="dtsh"></page-guide>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				swiper_list: [],
				dtshBanner: '',
				checkRole: null, // { is_approved, role_type, role_name }
				volunteerSupervision: {
					total: 0,
					last_date: '',
				},
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
			this.loadCheckRole()
			this.loadTaskList()
			this.loadMyTasks()
		},
		methods: {
		loadCheckRole() {
			if (!this.isLogin) {
				this.checkRole = null;
				this.volunteerSupervision = { total: 0, last_date: '' };
				return;
			}
			this.$u.api.getCheckRole().then(res => {
				this.checkRole = res.data || null;
				if (this.checkRole && this.checkRole.is_approved) {
					this.loadVolunteerSupervision();
				} else {
					this.volunteerSupervision = { total: 0, last_date: '' };
				}
			}).catch(() => {
				this.checkRole = null;
				this.volunteerSupervision = { total: 0, last_date: '' };
			});
		},
		loadVolunteerSupervision() {
			this.$u.api.getVolunteerMyList({ page: 1, page_size: 1 }).then(res => {
				this.volunteerSupervision.total = res.data.total || 0;
				if (res.data.data && res.data.data.length > 0) {
					this.volunteerSupervision.last_date = res.data.data[0].supervision_date;
				}
			}).catch(() => {});
		},
		goVolunteerPage(url) {
			if (!this.$u.func.checkLogin()) return;
			this.$u.route(url);
		},
		goVolunteerReport(locationType) {
			if (!this.$u.func.checkLogin()) return;
			const approved = this.isVolunteer ? 1 : 0;
			this.$u.route(`/pages/dtsh/report?location_type=${locationType}&can_submit=${approved}`);
		},
		goVolunteerMonitor() {
			if (!this.$u.func.checkLogin()) return;
			const approved = this.isVolunteer ? 1 : 0;
			this.$u.route(`/pages/dtsh/supervision-monitor?can_submit=${approved}`);
		},
		goVolunteerMerchantInspect() {
			if (!this.$u.func.checkLogin()) return;
			this.$u.route('/pages/dtsh/merchant-inspect');
		},
		goVolunteerApply() {
			if (!this.$u.func.checkLogin()) return;
			this.$u.route('/pages/zzhd/add');
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
		// === 整改任务相关 ===
		changeTab(idx) {
			const i = (idx && typeof idx === 'object') ? idx.index : idx
			this.currentTab = i
			if (i == 1) this.loadMyTasks()
			else this.loadTaskList()
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
					title: item.task_title,
					content: item.task_content,
					point: item.task_point,
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
		previewShot(url) {
			if (!url) return;
			uni.previewImage({ urls: [url] });
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
.shot-thumb {
	width: 200rpx;
	height: 200rpx;
	border-radius: 12rpx;
	background: #eee;
}
.vol-grid-3 {
	display: flex;
	flex-wrap: wrap;
	margin: 0 -8rpx;
}
.vol-grid-item {
	width: 33.33%;
	padding: 16rpx 8rpx;
	box-sizing: border-box;
}
.vol-grid-icon {
	width: 88rpx;
	height: 88rpx;
	border-radius: 20rpx;
}
.task-section {
	margin-bottom: 30rpx;
}
</style>
