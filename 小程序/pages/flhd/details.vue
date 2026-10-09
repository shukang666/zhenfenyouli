<template>
	<view class="details-page">
	<!-- 顶部渐变背景 -->
	<view class="u-abso green-bg"></view>

	<!-- 导航栏 -->
	<!-- <u-navbar title-color="#fff" :title="title || '学习详情'" :is-back="true" back-icon-color="#fff" background="transparent" :borderBottom="false" :custom-back="customBack">
	</u-navbar> -->

	<!-- 学习进度卡片 -->
	<view class="progress-card" v-if="totalCount > 0">
		<view class="progress-header">
			<text class="progress-text">学习进度 {{learnedCount}}/{{totalCount}}</text>
			<text class="progress-percent">{{Math.round(learnedCount/totalCount*100)}}%</text>
		</view>
		<u-line-progress active-color="#18AF38" :percent="(learnedCount/totalCount*100)" :show-percent="false" height="8"></u-line-progress>
	</view>

	<!-- 内容区 -->
	<view class="content-card">
		<view class="content-title" v-if="title">{{title}}</view>
		<u-parse :html="disease"></u-parse>
	</view>

	<!-- 底部操作栏 -->
	<view class="bottom-bar">
		<view class="completed-tip" v-if="justCompleted">
			<u-icon name="checkmark-circle-fill" size="36" color="#18AF38"></u-icon>
			<text class="completed-text">学习完成！已记录学习进度</text>
		</view>

		<!-- 学完全部但没考试 -->
		<block v-if="allLearned && !isAnswer">
			<view class="next-tip">恭喜学完全部垃圾分类指导，参加考试通过后即可真分</view>
			<u-button type="primary" hover-class="none"
				:customStyle="{background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '90rpx', fontSize: '30rpx'}"
				@click="$u.route('/pages/answer/examine?id=1')">去答题考试</u-button>
		</block>

		<!-- 还有下一篇 -->
		<block v-else-if="nextId">
			<u-button type="primary" hover-class="none"
				:customStyle="{background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '90rpx', fontSize: '30rpx'}"
				@click="goNext">学习下一篇</u-button>
		</block>

		<!-- 已全部学完且已考试 -->
		<block v-else>
			<view class="next-tip">已全部学完，返回列表查看</view>
			<u-button type="primary" hover-class="none"
				:customStyle="{background: '#999', height: '90rpx', fontSize: '30rpx'}"
				@click="goBack">返回列表</u-button>
		</block>
	</view>
</view>
</template>

<script>
export default {
	data() {
		return {
			disease: '',
			title: '',
			currentId: 0,
			// 总文章数
			totalCount: 0,
			// 已学习数
			learnedCount: 0,
			// 是否已全部学完
			allLearned: false,
			// 是否已通过考试
			isAnswer: false,
			// 是否刚完成本篇（首次进入时为true，因为后端自动记录了）
			justCompleted: false,
			// 下一篇文章id
			nextId: 0,
		};
	},
	onLoad(option) {
		this.currentId = option.id || 0;
		if (this.currentId) {
			this.getDetail(this.currentId);
		}
		this.getProgress();
	},
	methods: {
		customBack() {
			uni.navigateBack({
				fail() {
					uni.switchTab({ url: '/pages/index/index' });
				}
			});
		},
		getDetail(id) {
			this.$u.api.get_waste_list({ id }).then(res => {
				this.disease = this.$u.func.formatRichText(res.data.content);
				this.title = res.data.name;
				uni.setNavigationBarTitle({ title: res.data.name });
				// 后端已自动写入学习记录，标记为本篇已完成
				this.justCompleted = true;
			}).catch(err => {
				this.$u.toast(err.msg);
			});
		},
		// 获取学习进度（从列表接口拿）
		getProgress() {
			this.$u.api.get_waste_list({}).then(res => {
				const d = res.data || {};
				this.totalCount = Number(d.waste_count) || 0;
				this.learnedCount = Number(d.user_count) || 0;
				this.allLearned = this.totalCount > 0 && this.learnedCount >= this.totalCount;

				// 找下一篇未学的
				const list = (d.list && d.list.data) || [];
				this.nextId = this.findNextUnlearned(list);

				// 如果学完全部，查考试状态
				if (this.allLearned) {
					this.getUserAnswerState();
				}
			}).catch(() => {});
		},
		// 在列表里找到下一篇未学的文章id
		findNextUnlearned(list) {
			for (let i = 0; i < list.length; i++) {
				if (Number(list[i].is_study) === 0 && Number(list[i].id) !== Number(this.currentId)) {
					return list[i].id;
				}
			}
			return 0;
		},
		getUserAnswerState() {
			this.$u.api.getUserInfo().then(res => {
				const info = res.data || {};
				this.isAnswer = Number(info.is_answer) === 1;
			}).catch(() => {});
		},
		goNext() {
			if (this.nextId) {
				// 重置状态加载下一篇
				this.disease = '';
				this.title = '';
				this.justCompleted = false;
				this.currentId = this.nextId;
				uni.setNavigationBarTitle({ title: '学习详情' });
				this.getDetail(this.nextId);
				this.getProgress();
				uni.pageScrollTo({ scrollTop: 0, duration: 0 });
			}
		},
		goBack() {
			uni.navigateBack();
		},
	}
}
</script>

<style lang="scss" scoped>
	page { background: #F5F7FA; }
	.details-page {
		min-height: 100vh;
		padding-bottom: 200rpx;
		position: relative;
	}

	/* ===== 顶部背景 ===== */
	.green-bg {
		position: absolute;
		z-index: 0;
		top: 0; left: 0; right: 0;
		height: 300rpx;
		background: linear-gradient(to bottom, #2fb74c, #EFF9F1);
		pointer-events: none;
	}

	/* ===== 进度卡片 ===== */
	.progress-card {
		position: relative;
		z-index: 1;
		margin: 20rpx 30rpx;
		padding: 24rpx 30rpx;
		background: #fff;
		border-radius: 16rpx;
		box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.06);

		.progress-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 16rpx;
		}
		.progress-text {
			font-size: 26rpx;
			color: #333;
			font-weight: 600;
		}
		.progress-percent {
			font-size: 28rpx;
			color: #18AF38;
			font-weight: 700;
		}
	}

	/* ===== 内容卡片 ===== */
	.content-card {
		position: relative;
		z-index: 1;
		margin: 0 30rpx;
		padding: 40rpx 30rpx;
		background: #fff;
		border-radius: 16rpx;
		box-shadow: 0 4rpx 16rpx rgba(0,0,0,0.06);
		min-height: 400rpx;

		.content-title {
			font-size: 36rpx;
			font-weight: 700;
			color: #222;
			margin-bottom: 30rpx;
			line-height: 1.5;
		}
	}

	/* ===== 底部操作栏 ===== */
	.bottom-bar {
		position: fixed;
		z-index: 2;
		bottom: 0;
		left: 0;
		right: 0;
		padding: 24rpx 30rpx;
		padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
		background: #fff;
		box-shadow: 0 -4rpx 20rpx rgba(0,0,0,0.08);

		.completed-tip {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 10rpx;
			margin-bottom: 20rpx;
		}
		.completed-text {
			font-size: 28rpx;
			color: #18AF38;
			font-weight: 600;
		}

		.next-tip {
			text-align: center;
			font-size: 26rpx;
			color: #666;
			margin-bottom: 20rpx;
		}
	}
</style>
