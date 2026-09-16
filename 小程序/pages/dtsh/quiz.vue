<template>
	<view class="quiz-page">
		<!-- 顶部渐变背景 -->
		<u-navbar title-color="#fff" title="分类知识挑战" :is-back="true" back-icon-color="#fff" background="transparent"
			:borderBottom="false" :custom-back="customBack">
		</u-navbar>
		<view class="header-bg"></view>

		<view class="content">
			<!-- 未答题 - 开始答题卡 -->
			<view v-if="!answered && !loading && questions.length === 0" class="card start-card">
				<view class="card-icon">
					<u-icon name="question-circle" size="80" color="#18AF38"></u-icon>
				</view>
				<view class="card-title u-m-t-20">分类知识挑战</view>
				<view class="card-desc u-m-t-10">
					每日随机5道题，每题2积分
				</view>
				<view class="card-desc">
					全部答对可获得10积分
				</view>
				<view class="card-desc u-m-t-10" style="color: #999; font-size: 24rpx;">
					每天只能答题一次，请认真作答
				</view>
				<u-button type="primary" shape="circle" hover-class="none"
					:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '80rpx', fontSize: '30rpx' }"
					class="u-m-t-40" @click="startQuiz">开始答题</u-button>
			</view>

			<!-- 加载中 -->
			<view v-if="loading" class="card">
				<u-loading mode="circle" color="#18AF38" size="60"></u-loading>
				<view class="u-m-t-20 u-font-26 u-type-info">题目准备中...</view>
			</view>

			<!-- 答题中 -->
			<view v-if="!answered && questions.length > 0">
				<!-- 进度条 -->
				<view class="progress-bar">
					<view class="progress-text">{{ currentIndex + 1 }} / {{ questions.length }}</view>
					<u-line-progress :percentage="(currentIndex / questions.length) * 100" activeColor="#18AF38"
						height="8" :showText="false"></u-line-progress>
				</view>

				<!-- 题目卡片 -->
				<view class="card question-card">
					<view class="question-num">第 {{ currentIndex + 1 }} 题</view>
					<view class="question-title">{{ currentQuestion.title }}</view>

					<view class="options">
						<view v-for="(option, key) in currentOptions" :key="key" class="option-item"
							:class="{ selected: answers[currentIndex] === key }" @click="selectAnswer(key)">
							<view class="option-label">{{ key }}</view>
							<view class="option-text">{{ option }}</view>
						</view>
					</view>
				</view>

				<!-- 底部按钮 -->
				<view class="bottom-bar">
					<u-button v-if="currentIndex > 0" type="default" shape="circle" hover-class="none"
						:customStyle="{ height: '80rpx' }" @click="prevQuestion">上一题</u-button>
					<u-button v-if="currentIndex < questions.length - 1" type="primary" shape="circle"
						hover-class="none"
						:disabled="!answers[currentIndex]"
						:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '80rpx' }"
						@click="nextQuestion">下一题</u-button>
					<u-button v-if="currentIndex === questions.length - 1" type="primary" shape="circle"
						hover-class="none"
						:disabled="!answers[currentIndex]"
						:customStyle="{ background: 'linear-gradient(141deg, #A4DB88 0%, #18AF38 100%)', height: '80rpx' }"
						@click="submitQuiz">提交</u-button>
				</view>
			</view>

			<!-- 已答题 - 结果展示 -->
			<view v-if="answered" class="card result-card">
				<view class="result-icon">
					<u-icon :name="resultIcon" size="80" :color="resultColor"></u-icon>
				</view>
				<view class="card-title u-m-t-20">今日答题已完成</view>
				<view class="result-score u-m-t-20">
					<text class="score-num">{{ correctCount }}</text>
					<text class="score-label"> / {{ totalCount }} 题</text>
				</view>
				<view class="result-point u-m-t-10" v-if="earnedPoint > 0">
					<u-icon name="integral" size="36" color="#FF9900"></u-icon>
					<text>+{{ earnedPoint }} 积分</text>
				</view>
				<view class="result-info u-m-t-20">
					明天再来挑战吧！
				</view>

				<!-- 回顾答案 -->
				<view class="review-section u-m-t-30" v-if="answersDetail.length > 0">
					<view class="review-title" @click="showReview = !showReview">
						<text>查看答案</text>
						<u-icon :name="showReview ? 'arrow-up' : 'arrow-down'" size="24" color="#999"></u-icon>
					</view>
					<view v-if="showReview" class="review-list">
						<view v-for="(item, idx) in answersDetail" :key="idx" class="review-item">
							<view class="review-q">第{{ idx + 1 }}题</view>
							<view class="review-a" :class="item.is_correct ? 'correct' : 'wrong'">
								<text v-if="item.is_correct">回答正确</text>
								<text v-else>你的答案：{{ item.answer || '未选' }}，正确答案：{{ item.correct }}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				loading: false,
				questions: [],
				answers: {},
				currentIndex: 0,
				answered: false,
				correctCount: 0,
				totalCount: 0,
				earnedPoint: 0,
				answersDetail: [],
				showReview: false,
			};
		},
		onShow() {
			this.checkStatus();
		},
		computed: {
			currentQuestion() {
				return this.questions[this.currentIndex] || {};
			},
			currentOptions() {
				const q = this.currentQuestion;
				const opts = {};
				if (q.option_a) opts.A = q.option_a;
				if (q.option_b) opts.B = q.option_b;
				if (q.option_c) opts.C = q.option_c;
				if (q.option_d) opts.D = q.option_d;
				return opts;
			},
			resultIcon() {
				const rate = this.correctCount / this.totalCount;
				if (rate === 1) return 'checkmark-circle';
				if (rate >= 0.6) return 'checkmark-circle';
				return 'error-circle';
			},
			resultColor() {
				const rate = this.correctCount / this.totalCount;
				if (rate >= 0.6) return '#18AF38';
				return '#FF4D4F';
			}
		},
		methods: {
			customBack() {
				uni.navigateBack({
					fail() {
						uni.switchTab({
							url: '/pages/index/index'
						});
					}
				});
			},
			checkStatus() {
				this.loading = true;
				this.$u.api.checkTodayQuiz().then(res => {
					this.loading = false;
					if (res.code == 1 && res.data.answered) {
						this.answered = true;
						this.correctCount = res.data.correct_count || 0;
						this.totalCount = res.data.total_count || 5;
						this.earnedPoint = res.data.point || 0;
					}
				}).catch(() => {
					this.loading = false;
				});
			},
			startQuiz() {
				this.loading = true;
				this.$u.api.getQuiz().then(res => {
					this.loading = false;
					if (res.code == 1) {
						if (res.data.answered) {
							this.answered = true;
							this.correctCount = res.data.correct_count || 0;
							this.totalCount = res.data.total_count || 5;
							this.earnedPoint = res.data.point || 0;
						} else {
							this.questions = res.data.questions || [];
							this.answers = {};
							this.currentIndex = 0;
						}
					}
				}).catch(() => {
					this.loading = false;
				});
			},
			selectAnswer(key) {
				this.answers[this.currentIndex] = key;
				this.$forceUpdate();
			},
			prevQuestion() {
				if (this.currentIndex > 0) {
					this.currentIndex--;
				}
			},
			nextQuestion() {
				if (this.currentIndex < this.questions.length - 1) {
					this.currentIndex++;
				}
			},
		submitQuiz() {
			// 检查是否所有题目都已作答
			for (let i = 0; i < this.questions.length; i++) {
				if (!this.answers[i]) {
					uni.showToast({ title: '请完成第' + (i + 1) + '题', icon: 'none' });
					this.currentIndex = i;
					return;
				}
			}

			uni.showLoading({ title: '提交中...' });

			// 构造答案数组
			const answersArr = this.questions.map((q, idx) => ({
				qid: q.id,
				answer: this.answers[idx]
			}));

			this.$u.api.submitQuiz({
				answers: answersArr
			}).then(res => {
				uni.hideLoading();
				if (res.code == 1) {
					this.answered = true;
					this.correctCount = res.data.correct_count || 0;
					this.totalCount = res.data.total_count || 5;
					this.earnedPoint = res.data.point || 0;
					this.answersDetail = res.data.answers_detail || [];

					if (this.earnedPoint > 0) {
						uni.showToast({ title: '恭喜获得' + this.earnedPoint + '积分', icon: 'success' });
					}
				} else {
					uni.showToast({ title: res.msg || '提交失败', icon: 'none' });
				}
			}).catch(() => {
				uni.hideLoading();
				uni.showToast({ title: '网络错误，请重试', icon: 'none' });
			});
		},
	}
}
</script>

<style lang="scss" scoped>
	.quiz-page {
		min-height: 100vh;
	}

	.header-bg {
		position: absolute;
		top: 0;
		left: 0;
		right: 0;
		height: 400rpx;
		background: linear-gradient(to bottom, #2fb74c, #EFF9F1);
	}

	.content {
		position: relative;
		padding: 30rpx;
		padding-top: 20rpx;
	}

	.card {
		background: #fff;
		border-radius: 24rpx;
		padding: 50rpx 30rpx;
		margin-bottom: 30rpx;
		text-align: center;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05);
	}

	.start-card {
		.card-icon {
			margin-top: 30rpx;
		}

		.card-title {
			font-size: 36rpx;
			font-weight: bold;
			color: #333;
		}

		.card-desc {
			font-size: 26rpx;
			color: #666;
			margin-top: 8rpx;
		}
	}

	.progress-bar {
		margin-bottom: 20rpx;
		padding: 0 10rpx;

		.progress-text {
			font-size: 24rpx;
			color: #666;
			margin-bottom: 10rpx;
		}
	}

	.question-card {
		text-align: left;

		.question-num {
			font-size: 24rpx;
			color: #18AF38;
			font-weight: bold;
			margin-bottom: 20rpx;
		}

		.question-title {
			font-size: 30rpx;
			color: #333;
			line-height: 1.6;
			margin-bottom: 40rpx;
		}
	}

	.options {
		.option-item {
			display: flex;
			align-items: center;
			padding: 24rpx 30rpx;
			border: 2rpx solid #eee;
			border-radius: 16rpx;
			margin-bottom: 20rpx;
			transition: all 0.2s;

			&.selected {
				border-color: #18AF38;
				background: #f0faf2;
			}

			.option-label {
				width: 56rpx;
				height: 56rpx;
				border-radius: 50%;
				background: #f5f5f5;
				text-align: center;
				line-height: 56rpx;
				font-size: 28rpx;
				font-weight: bold;
				color: #333;
				margin-right: 24rpx;
				flex-shrink: 0;
			}

			&.selected .option-label {
				background: #18AF38;
				color: #fff;
			}

			.option-text {
				font-size: 28rpx;
				color: #333;
				flex: 1;
			}
		}
	}

	.bottom-bar {
		display: flex;
		gap: 20rpx;
		margin-top: 40rpx;
	}

	.result-card {
		.result-icon {
			margin-top: 20rpx;
		}

		.card-title {
			font-size: 36rpx;
			font-weight: bold;
			color: #333;
		}

		.result-score {
			.score-num {
				font-size: 60rpx;
				font-weight: bold;
				color: #18AF38;
			}

			.score-label {
				font-size: 28rpx;
				color: #666;
			}
		}

		.result-point {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8rpx;
			font-size: 30rpx;
			color: #FF9900;
			font-weight: bold;
		}

		.result-info {
			font-size: 26rpx;
			color: #999;
		}
	}

	.review-section {
		text-align: left;
		border-top: 2rpx solid #eee;
		padding-top: 20rpx;

		.review-title {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 8rpx;
			font-size: 26rpx;
			color: #666;
			margin-bottom: 20rpx;
		}

		.review-list {
			.review-item {
				padding: 16rpx 0;
				border-bottom: 2rpx solid #f5f5f5;

				.review-q {
					font-size: 24rpx;
					color: #999;
					margin-bottom: 6rpx;
				}

				.review-a {
					font-size: 26rpx;

					&.correct {
						color: #18AF38;
					}

					&.wrong {
						color: #FF4D4F;
					}
				}
			}
		}
	}
</style>
