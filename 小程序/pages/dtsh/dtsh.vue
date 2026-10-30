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
					<text class="wb-stat-lab">督导次数</text>
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
				<text class="wb-tip-t">最近点位督导：{{ volunteerSupervision.last_date }}</text>
			</view>
		</view>

		<view class="wb-section">
			<text class="wb-section-title">志愿督导员工作</text>
			<view class="wb-job" @click="goVolunteerPage('/pages/dtsh/volunteer-sign')">
				<view class="wb-job-icon wb-job-icon--green">
					<u-icon name="clock" size="44" color="#fff"></u-icon>
				</view>
				<view class="wb-job-main">
					<text class="wb-job-name">点位督导</text>
					<text class="wb-job-desc">随时到岗 · 桶边留证与登记</text>
				</view>
				<view class="wb-job-btn">去执行</view>
			</view>
			<view class="wb-job" @click="goVolunteerPage('/pages/zzhd/dwxj/dwxj?type=3')">
				<view class="wb-job-icon wb-job-icon--mint">
					<u-icon name="file-text" size="44" color="#fff"></u-icon>
				</view>
				<view class="wb-job-main">
					<text class="wb-job-name">小区巡查</text>
					<text class="wb-job-desc">点位巡查 · 现场记录与留证</text>
				</view>
				<view class="wb-job-btn">去检查</view>
			</view>
			<view class="wb-job" @click="goVolunteerMerchantInspect">
				<view class="wb-job-icon wb-job-icon--blue">
					<u-icon name="home" size="44" color="#fff"></u-icon>
				</view>
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
				<view class="wb-job-icon wb-job-icon--teal">
					<u-icon name="map-fill" size="44" color="#fff"></u-icon>
				</view>
				<view class="wb-job-main">
					<text class="wb-job-name">督导监测</text>
					<text class="wb-job-desc">厨余督导信息登记与监测</text>
				</view>
				<view class="wb-job-btn">去监测</view>
			</view>
		</view>

		<u-gap height="40"></u-gap>
		<page-guide page="dtsh"></page-guide>
	</view>
</template>

<script>
	import PageGuide from './components/page-guide/index.vue'

	export default {
		components: {
			PageGuide,
		},
		data() {
			return {
				checkRole: null,
				volunteerSupervision: {
					total: 0,
					last_date: '',
				},
			};
		},
		computed: {
			isVolunteer() {
				if (this.checkRole && this.checkRole.is_approved) {
					return true;
				}
				if (!this.isLogin || !this.userInfo) {
					return false;
				}
				return Number(this.userInfo.is_supervisor) === 1;
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
				const sup = this.volunteerSupervision.total || 0;
				return `${sup}次`;
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
			this.loadCheckRole();
		},
		onShow() {
			this.loadCheckRole();
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
				}).catch(() => {
					this.checkRole = null;
				}).finally(() => {
					if (this.isVolunteer) {
						this.loadVolunteerSupervision();
					} else {
						this.volunteerSupervision = { total: 0, last_date: '' };
					}
				});
			},
			ensureVolunteerApproved() {
				if (this.isVolunteer) {
					return true;
				}
				uni.showToast({
					title: '请先申请并通过督导员审核',
					icon: 'none',
				});
				return false;
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
				if (!this.ensureVolunteerApproved()) return;
				this.$u.route(url);
			},
			goVolunteerMonitor() {
				if (!this.$u.func.checkLogin()) return;
				if (!this.ensureVolunteerApproved()) return;
				this.$u.route('/pages/dtsh/supervision-monitor?can_submit=1');
			},
			goVolunteerMerchantInspect() {
				if (!this.$u.func.checkLogin()) return;
				if (!this.ensureVolunteerApproved()) return;
				this.$u.route('/pages/dtsh/merchant-inspect');
			},
			goVolunteerApply() {
				if (!this.$u.func.checkLogin()) return;
				this.$u.route('/pages/zzhd/add');
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

</style>
