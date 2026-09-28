<template>
	<view class="wb-page u-p-30">
		<u-navbar title-color="#fff" title="垃圾分类工作台" :is-back="true" back-icon-color="#fff"
			background="transparent" :borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="wb-bg"></view>

		<view class="wb-hero">
			<text class="wb-hero-title">垃圾分类工作台</text>
			<text class="wb-hero-sub">一起守护七贤岭的绿色家园</text>
		</view>

		<view class="wb-dash u-type-white-bg" id="guide-dtsh-vol">
			<view class="wb-user u-flex u-col-center">
				<u-avatar :src="userAvatar" size="96"></u-avatar>
				<view class="u-m-l-20 u-flex-1">
					<view class="wb-greet">{{ timeGreeting }}，{{ displayName }}！</view>
					<view class="wb-greet-sub">感谢您为社区垃圾分类付出</view>
				</view>
			</view>
			<view class="wb-stats u-flex u-row-around">
				<view class="wb-stat">
					<text class="wb-stat-num">{{ statCompleted }}</text>
					<text class="wb-stat-lab">已完成任务</text>
				</view>
				<view class="wb-stat">
					<text class="wb-stat-num">{{ statPoints }}</text>
					<text class="wb-stat-lab">累计积分</text>
				</view>
				<view class="wb-stat">
					<text class="wb-stat-num">{{ statHours }}</text>
					<text class="wb-stat-lab">督导时长</text>
				</view>
			</view>
			<view class="wb-apply" v-if="!isVolunteer">
				<u-button type="primary" size="mini" hover-class="none"
					:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #078b5f 100%)' }"
					@click="goVolunteerApply">申请督导员</u-button>
			</view>
			<view class="wb-tip" v-if="volunteerSupervision.last_date">
				<text class="wb-tip-icon">✓</text>
				<text class="wb-tip-t">最近桶边督导：{{ volunteerSupervision.last_date }}</text>
			</view>
		</view>

		<view class="wb-section">
			<text class="wb-section-title">志愿督导员工作</text>
			<view class="wb-job" @click="goVolunteerPage('/pages/dtsh/volunteer-sign')">
				<view class="wb-job-icon wb-job-icon--green">🪣</view>
				<view class="wb-job-main">
					<text class="wb-job-name">桶边督导</text>
					<text class="wb-job-desc">随时到岗 · 桶边留证与登记</text>
				</view>
				<view class="wb-job-btn">去执行</view>
			</view>
			<view class="wb-job" @click="goVolunteerPage('/pages/zzhd/dwxj/dwxj?type=3')">
				<view class="wb-job-icon wb-job-icon--mint">📋</view>
				<view class="wb-job-main">
					<text class="wb-job-name">小区分类检查</text>
					<text class="wb-job-desc">点位巡查 · 现场记录与留证</text>
				</view>
				<view class="wb-job-btn">去检查</view>
			</view>
			<view class="wb-job" @click="goVolunteerMerchantInspect">
				<view class="wb-job-icon wb-job-icon--blue">🏪</view>
				<view class="wb-job-main">
					<text class="wb-job-name">商户共治</text>
					<text class="wb-job-desc">沿街商户分类共治检查</text>
				</view>
				<view class="wb-job-btn">去检查</view>
			</view>
		</view>

		<view class="wb-section">
			<text class="wb-section-title">单位指导员 · 点位监测</text>
			<view class="wb-job" @click="goVolunteerMonitor">
				<view class="wb-job-icon wb-job-icon--teal">📍</view>
				<view class="wb-job-main">
					<text class="wb-job-name">督导监测</text>
					<text class="wb-job-desc">厨余督导信息登记与监测</text>
				</view>
				<view class="wb-job-btn">去监测</view>
			</view>
		</view>

		<view class="wb-section task-section" id="guide-dtsh-task">
			<view class="wb-section-head u-flex u-row-between u-col-center">
				<text class="wb-section-title u-m-b-0">可领取的整改工作</text>
				<text class="wb-section-more" @click="currentTab = 1" v-if="myTasks.length">我的任务 ›</text>
			</view>
			<text class="wb-section-hint">每人每天最多领取 1 条</text>

			<scroll-view scroll-x class="wb-task-scroll" v-if="currentTab === 0 && taskList.length">
				<view class="wb-task-row">
					<view class="wb-task-card" v-for="(item, idx) in taskList" :key="idx">
						<view class="wb-task-tag">{{ taskTypeLabel(item) }}</view>
						<text class="wb-task-id">#{{ taskSerial(item) }}</text>
						<text class="wb-task-loc">{{ taskLocation(item) }}</text>
						<view class="wb-task-foot u-flex u-row-between u-col-center">
							<text class="wb-task-time">{{ taskTimeText(item) }}</text>
							<view
								v-if="!item.accepted && !item.daily_limit"
								class="wb-task-claim"
								@click.stop="acceptTask(item)"
							>领取</view>
							<text v-else class="wb-task-muted">{{ item.accepted ? '已领' : '今日已领' }}</text>
						</view>
					</view>
				</view>
			</scroll-view>
			<view v-if="currentTab === 0 && taskList.length === 0" class="wb-task-empty">
				<u-empty text="暂无可领取任务" mode="list"></u-empty>
			</view>

			<view class="u-p-lr-10 u-m-t-20">
				<u-tabs :list="taskTabs" :is-scroll="false" :current="currentTab" @change="changeTab" active-color="#078b5f"></u-tabs>
			</view>

			<view v-if="currentTab == 0" class="u-m-t-16">
				<view v-for="(item, idx) in taskList" :key="'full-' + idx" class="task-card u-m-b-20">
					<view class="u-font-30 u-font-bold">{{ item.title }}</view>
					<image v-if="item.image" :src="item.image" mode="aspectFill" class="shot-thumb u-m-t-16" @click="previewShot(item.image)"></image>
					<view class="u-font-26 u-type-info u-m-t-10">{{ item.content }}</view>
					<view class="u-flex u-row-between u-m-t-20">
						<view class="u-font-26" style="color:#078b5f;">奖励积分 +{{ item.point }}</view>
						<u-button v-if="item.accepted" type="info" size="mini" shape="circle" disabled>已领取</u-button>
						<u-button v-else-if="item.daily_limit" type="info" size="mini" shape="circle" disabled>今日已领</u-button>
						<u-button v-else type="success" size="mini" shape="circle" @click="acceptTask(item)">领取任务</u-button>
					</view>
				</view>
			</view>
			<view v-if="currentTab == 1">
				<view v-if="myTasks.length == 0"><u-empty text="无我的任务" mode="list"></u-empty></view>
				<view v-for="(item, idx) in myTasks" :key="idx" class="task-card u-m-b-20" @click="goSubmit(item)">
					<view class="u-font-30 u-font-bold">{{ taskDisplayTitle(item) }}</view>
					<image v-if="item.task_image" :src="item.task_image" mode="aspectFill" class="shot-thumb u-m-t-16" @click.stop="previewShot(item.task_image)"></image>
					<view class="u-font-26 u-type-info u-m-t-10">{{ taskDisplayContent(item) }}</view>
					<view class="u-flex u-row-between u-m-t-20">
						<view class="u-font-26" style="color:#078b5f;">奖励积分 +{{ taskDisplayPoint(item) }}</view>
						<u-tag :text="item.status_name" :type="taskStatusType(item.status)" mode="dark" />
					</view>
					<view v-if="item.status == 2 && item.reason" class="u-font-24 u-type-error u-m-t-10">拒绝原因：{{ item.reason }}</view>
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
				checkRole: null,
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
			},
			displayName() {
				if (this.isLogin && this.userInfo && this.userInfo.nickname) {
					return this.userInfo.nickname;
				}
				return '督导员';
			},
			userAvatar() {
				if (this.isLogin && this.userInfo && this.userInfo.avatar) {
					return this.userInfo.avatar;
				}
				return '';
			},
			timeGreeting() {
				const h = new Date().getHours();
				if (h < 12) return '早上好';
				if (h < 18) return '下午好';
				return '晚上好';
			},
			statCompleted() {
				const done = (this.myTasks || []).filter(t => parseInt(t.status, 10) === 3).length;
				const sup = this.volunteerSupervision.total || 0;
				return `${done + sup}次`;
			},
			statPoints() {
				const p = (this.isLogin && this.userInfo && this.userInfo.point) ? this.userInfo.point : 0;
				return `${p}分`;
			},
			statHours() {
				const n = this.volunteerSupervision.total || 0;
				const hours = n > 0 ? Math.max(2, n * 2) : 0;
				return hours ? `${hours}小时` : '—';
			},
		},
		onLoad() {
			this.find_slide();
			this.loadBanner();
			this.loadCheckRole();
			this.loadTaskList();
			this.loadMyTasks();
		},
		onShow() {
			this.loadCheckRole();
			this.loadTaskList();
			this.loadMyTasks();
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
			navigator(url) {
				if (!this.isLogin) return this.$u.route('/pages/public/login');
				this.$u.route(url);
			},
			async adLink(e, item) {
				const data = item[e];
				if (!data.url) return;
				if (data.target == 'switchTab') {
					return uni.switchTab({ url: data.url });
				}
				const openUrl = await this.$u.func.checkH5(data.url);
				this.$u.route(openUrl);
			},
			find_slide() {
				this.$u.api.find_slide({ id: 6 }).then(res => {
					this.swiper_list = res.data;
				});
			},
			async loadBanner() {
				try {
					const res = await this.$u.api.getPageConfig();
					const banner = res.data?.dtsh_banner || '';
					if (banner) this.dtshBanner = banner;
				} catch (e) {}
			},
			goToClock() {
				this.$u.route('/pages/flhd/ljfldk');
			},
			changeTab(idx) {
				const i = (idx && typeof idx === 'object') ? idx.index : idx;
				this.currentTab = i;
				if (i == 1) this.loadMyTasks();
				else this.loadTaskList();
			},
			loadTaskList() {
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
			taskTypeLabel(item) {
				const t = (item && item.title) || '';
				if (/满溢|桶满/.test(t)) return '桶满溢';
				if (/设施|损坏/.test(t)) return '设施损坏';
				if (/大件/.test(t)) return '大件垃圾';
				return t.length > 6 ? t.slice(0, 6) : (t || '整改');
			},
			taskSerial(item) {
				const id = item && item.id;
				if (!id) return '—';
				const d = new Date();
				const ymd = `${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}`;
				return `${ymd}${String(id).padStart(3, '0')}`;
			},
			taskLocation(item) {
				const c = (item && item.content) || '';
				if (c.length > 28) return c.slice(0, 28) + '…';
				return c || (item && item.title) || '见任务说明';
			},
			taskTimeText(item) {
				const ts = item && (item.create_time || item.update_time);
				if (!ts) return '待领取';
				const n = parseInt(ts, 10);
				const d = new Date(n > 1e12 ? n : n * 1000);
				const now = new Date();
				const sameDay = d.toDateString() === now.toDateString();
				const hh = String(d.getHours()).padStart(2, '0');
				const mm = String(d.getMinutes()).padStart(2, '0');
				return sameDay ? `今天 ${hh}:${mm}` : `${d.getMonth() + 1}月${d.getDate()}日`;
			},
			acceptTask(item) {
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
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({ url: '/pages/index/index' });
					}
				});
			},
		}
	};
</script>

<style lang="scss">
.wb-page {
	min-height: 100vh;
	background: #f4f7f5;
	padding-bottom: 40rpx;
	position: relative;
}

.wb-bg {
	position: absolute;
	z-index: 0;
	top: 0;
	left: 0;
	right: 0;
	height: 520rpx;
	background: linear-gradient(165deg, #078b5f 0%, #0a6b4a 55%, #f4f7f5 100%);
}

.wb-hero {
	position: relative;
	z-index: 1;
	margin: 0 0 0;
	padding: 8rpx 8rpx 40rpx;
}

.wb-hero-title {
	display: block;
	font-size: 40rpx;
	font-weight: 700;
	color: #fff;
}

.wb-hero-sub {
	display: block;
	margin-top: 8rpx;
	font-size: 26rpx;
	color: rgba(255, 255, 255, 0.85);
}

.wb-dash {
	margin: -16rpx 0 0;
	padding: 32rpx 28rpx;
	border-radius: 20rpx;
	box-shadow: 0 8rpx 32rpx rgba(7, 139, 95, 0.12);
	position: relative;
	z-index: 2;
}

.wb-greet {
	font-size: 32rpx;
	font-weight: 700;
	color: #1a1a1a;
}

.wb-greet-sub {
	margin-top: 8rpx;
	font-size: 24rpx;
	color: #888;
}

.wb-stats {
	margin-top: 28rpx;
	padding-top: 24rpx;
	border-top: 1rpx solid #f0f0f0;
}

.wb-stat {
	text-align: center;
	flex: 1;
}

.wb-stat-num {
	display: block;
	font-size: 36rpx;
	font-weight: 700;
	color: #078b5f;
}

.wb-stat-lab {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #999;
}

.wb-apply {
	margin-top: 20rpx;
	text-align: center;
}

.wb-tip {
	margin-top: 20rpx;
	padding: 16rpx 20rpx;
	background: #e8f7ef;
	border-radius: 12rpx;
	display: flex;
	align-items: center;
}

.wb-tip-icon {
	color: #078b5f;
	margin-right: 12rpx;
	font-size: 28rpx;
}

.wb-tip-t {
	font-size: 24rpx;
	color: #2d6a4f;
	flex: 1;
}

.wb-section {
	position: relative;
	z-index: 1;
	margin: 32rpx 0 0;
}

.wb-section-title {
	display: block;
	font-size: 32rpx;
	font-weight: 700;
	color: #1a1a1a;
	margin-bottom: 20rpx;
}

.wb-section-head .wb-section-title {
	margin-bottom: 0;
}

.wb-section-more {
	font-size: 26rpx;
	color: #078b5f;
}

.wb-section-hint {
	display: block;
	font-size: 24rpx;
	color: #999;
	margin-bottom: 16rpx;
}

.wb-job {
	display: flex;
	align-items: center;
	padding: 24rpx;
	margin-bottom: 16rpx;
	background: #e8f7ef;
	border-radius: 16rpx;
}

.wb-job-icon {
	width: 72rpx;
	height: 72rpx;
	border-radius: 16rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 36rpx;
	margin-right: 20rpx;
	flex-shrink: 0;
}

.wb-job-icon--green { background: linear-gradient(135deg, #a8e6cf, #38c172); }
.wb-job-icon--mint { background: linear-gradient(135deg, #c8f0dc, #5cb88a); }
.wb-job-icon--blue { background: linear-gradient(135deg, #90caf9, #1976d2); }
.wb-job-icon--teal { background: linear-gradient(135deg, #80cbc4, #00897b); }

.wb-job-main {
	flex: 1;
	min-width: 0;
}

.wb-job-name {
	display: block;
	font-size: 30rpx;
	font-weight: 700;
	color: #1a1a1a;
}

.wb-job-desc {
	display: block;
	margin-top: 6rpx;
	font-size: 24rpx;
	color: #666;
}

.wb-job-btn {
	padding: 12rpx 28rpx;
	background: #078b5f;
	color: #fff;
	font-size: 24rpx;
	border-radius: 999rpx;
	flex-shrink: 0;
}

.wb-task-scroll {
	width: 100%;
	white-space: nowrap;
}

.wb-task-row {
	display: inline-flex;
	padding: 8rpx 0 16rpx;
}

.wb-task-card {
	display: inline-block;
	vertical-align: top;
	width: 280rpx;
	margin-right: 20rpx;
	padding: 20rpx;
	background: #fff;
	border: 1rpx solid #e8ece9;
	border-radius: 16rpx;
	white-space: normal;
}

.wb-task-tag {
	display: inline-block;
	padding: 6rpx 14rpx;
	background: #078b5f;
	color: #fff;
	font-size: 22rpx;
	border-radius: 8rpx;
	margin-bottom: 12rpx;
}

.wb-task-id {
	display: block;
	font-size: 22rpx;
	color: #999;
}

.wb-task-loc {
	display: block;
	margin-top: 8rpx;
	font-size: 26rpx;
	color: #333;
	line-height: 1.45;
	min-height: 72rpx;
}

.wb-task-foot {
	margin-top: 16rpx;
}

.wb-task-time {
	font-size: 22rpx;
	color: #aaa;
}

.wb-task-claim {
	padding: 8rpx 20rpx;
	background: #078b5f;
	color: #fff;
	font-size: 22rpx;
	border-radius: 999rpx;
}

.wb-task-muted {
	font-size: 22rpx;
	color: #999;
}

.wb-task-empty {
	padding: 20rpx 0;
}

.task-card {
	padding: 24rpx;
	background: #fff;
	border-radius: 12rpx;
	border: 1rpx solid #eef0ee;
}

.shot-thumb {
	width: 200rpx;
	height: 200rpx;
	border-radius: 12rpx;
	background: #eee;
}

.task-section {
	margin-bottom: 30rpx;
}
</style>
