<template>
	<view class="container">
		<!-- 头部 Hero -->
		<view class="qxl-hero" :style="heroBgStyle">
			<view class="qxl-hero-content">
				<text class="qxl-hero-title">我和七贤岭</text>
				<text class="qxl-hero-subtitle">一起守护这片绿水青山</text>
			</view>
		</view>

		<!-- 4 个分类 section；发现七贤岭(6) 内嵌学习与考试入口，其余仅活动卡片 -->
		<view class="qxl-body">
			<view class="qxl-section" v-for="cate in qxl_categories" :key="cate.id">
				<!-- section 标题条 -->
				<view class="qxl-section-head">
					<view class="qxl-section-dot" :style="{background: cate.accent}"></view>
					<text class="qxl-section-emoji">{{cate.emoji}}</text>
					<text class="qxl-section-name">{{cate.name}}</text>
					<text class="qxl-section-sub">{{cate.subtitle}}</text>
				</view>

				<!-- 发现七贤岭：学与考入口 -->
				<view class="qxl-learn-grid" v-if="cate.id === 6">
					<view class="qxl-learn-card qxl-learn-card--learn" @click="goLearn">
						<u-icon name="bookmark" size="40" color="#fff"></u-icon>
						<text class="qxl-learn-name">七贤岭学习</text>
						<text class="qxl-learn-desc">了解七贤岭故事</text>
					</view>
					<view class="qxl-learn-card qxl-learn-card--quiz" @click="goQuiz">
						<u-icon name="edit-pen" size="40" color="#fff"></u-icon>
						<text class="qxl-learn-name">七贤岭考试</text>
						<text class="qxl-learn-desc">每日答题赢积分</text>
					</view>
				</view>

				<!-- 活动卡片列表（发现七贤岭在学与考下方，其他分类直接接标题） -->
				<view class="qxl-section-list" v-if="cate.activities && cate.activities.length">
					<view class="qxl-act-card" v-for="act in cate.activities" :key="act.id"
						@click="gotoDetail(act.id)">
						<view class="qxl-act-cover">
							<u-image width="100%" height="100%" :src="act.thumb" mode="aspectFill"></u-image>
							<view class="qxl-act-badge" v-if="act.active_count > 0">
								<text>{{act.active_count}}人参与</text>
							</view>
						</view>
						<view class="qxl-act-body">
							<view class="qxl-act-title u-line-1">{{act.name}}</view>
							<view class="qxl-act-meta">
								<view class="qxl-act-meta-item">
									<u-icon name="clock" size="20" color="#8A8A8A"></u-icon>
									<text>{{act.date | date('yy-mm-dd')}}</text>
								</view>
								<view class="qxl-act-meta-item" v-if="act.address && act.address.trim()">
									<u-icon name="map" size="20" color="#8A8A8A"></u-icon>
									<text class="u-line-1">{{act.address}}</text>
								</view>
							</view>
						</view>
					</view>
				</view>

				<!-- 暂无活动（发现七贤岭 id=6 是固定栏目，不显示空态；加载中不显示任何字样） -->
				<view class="qxl-empty" v-else-if="!cate.loading && cate.id !== 6">
					<text class="qxl-empty-text">暂无活动，敬请期待</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	// 七贤岭本地装饰贴条（emoji / 副标题 / 渐变配色不存 DB，便于前端微调）
	const QXL_CATE_DECOR = {
		6: { subtitle: '探索自然，发现美好', emoji: '🔍', accent: '#1877F2', bgColor: 'linear-gradient(135deg, #4A9BFF 0%, #1877F2 100%)' },
		7: { subtitle: '亲子玩乐，互动体验', emoji: '🎮', accent: '#34C759', bgColor: 'linear-gradient(135deg, #34C759 0%, #28A745 100%)' },
		8: { subtitle: '用镜头记录每一刻', emoji: '📷', accent: '#FF9500', bgColor: 'linear-gradient(135deg, #FF9500 0%, #E68900 100%)' },
		9: { subtitle: '守护绿水青山，从我做起', emoji: '🌱', accent: '#AF52DE', bgColor: 'linear-gradient(135deg, #AF52DE 0%, #8B41C2 100%)' },
	}

	export default {
		data() {
			return {
				// 4 个七贤岭分类：基础字段从后端 cmf_active_community_cate 拉取，
				// 装饰字段（emoji / accent / bgColor）从前端装饰贴条 QXL_CATE_DECOR 合并
				qxl_categories: [],
				heroBgImage: '',
				statusBarHeight: 20,
				navBarHeight: 88,
			};
		},
	computed: {
		// 导航栏绝对定位在 Hero 顶部，Hero 只需 padding-top 留出安全区+导航栏高度
		heroBgStyle() {
			// 导航栏占据的像素 = statusBarHeight(px) + 88(rpx/2=px)
			const padTopPx = this.statusBarHeight + 44
			if (this.heroBgImage) {
				return `background-image: linear-gradient(180deg, rgba(0,0,0,0.25) 0%, rgba(0,0,0,0.05) 100%), url(${this.heroBgImage}); background-size: cover; background-position: center; padding-top: ${padTopPx}px;`
			}
			return `padding-top: ${padTopPx}px;`
		}
	},
		onLoad() {
			const sys = uni.getSystemInfoSync()
			this.statusBarHeight = sys.statusBarHeight || 20
			this.loadHeroBg()
			this.loadQxlCategories()
		},
		methods: {
			// 加载七贤岭 Hero 背景图配置
			async loadHeroBg() {
				try {
					const res = await this.$u.api.getPageConfig()
					const qxlHeroBg = res.data?.qxl_hero_bg || ''
					if (qxlHeroBg) {
						this.heroBgImage = qxlHeroBg
					}
				} catch (e) {
					// 配置获取失败时使用默认渐变背景
				}
			},
		// 加载七贤岭 4 个板块：id=6 是固定栏目（学与考），id=7/8/9 是分类（绑活动）
		// 无论如何都要渲染 4 个板块，API 失败时走本地硬编码兜底
		async loadQxlCategories() {
			const buildList = (source) => {
				const defaultNames = { 6: '发现七贤岭', 7: '玩在七贤岭', 8: '记录七贤岭', 9: '守护七贤岭' }
				return [6, 7, 8, 9].map(id => {
					const found = source ? source.find(c => Number(c.id) === id) : null
					const decor = QXL_CATE_DECOR[id] || {}
					return {
						id,
						name: (found && found.name) || defaultNames[id] || '七贤岭',
						subtitle: decor.subtitle || '',
						emoji: decor.emoji || '📌',
						accent: decor.accent || '#34C759',
						bgColor: decor.bgColor || 'linear-gradient(135deg, #34C759 0%, #28A745 100%)',
						activities: [],
						loading: true,
					}
				})
			}
			try {
				const res = await this.$u.api.get_community_cate_list()
				this.qxl_categories = buildList(res.data || [])
			} catch (e) {
				this.qxl_categories = buildList(null)
			}
			this.loadAllSections()
		},
			// 并行加载 4 个分类的活动
			async loadAllSections() {
				const tasks = this.qxl_categories.map(cate =>
					this.fetchCateActivities(cate.id).then(list => {
						this.$set(cate, 'activities', list)
						this.$set(cate, 'loading', false)
					}).catch(() => {
						this.$set(cate, 'activities', [])
						this.$set(cate, 'loading', false)
					})
				)
				await Promise.all(tasks)
			},
			fetchCateActivities(communityCateId) {
				return this.$u.api.get_new_active_list({
					community_cate_id: communityCateId,
					page_size: 20,
				}).then(res => {
					return res.data?.data || []
				})
			},
		gotoDetail(id) {
			this.$u.route(`/pages/activity/lsxf/details-new?id=${id}`)
		},
		goLearn() {
			this.$u.route('/pages/activity/qxl-knowledge')
		},
		goQuiz() {
			this.$u.route('/pages/activity/qxl-quiz')
		},
		navigateBack() {
			uni.navigateBack()
		},
		}
	}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #F0F2F5;
}

/* ===== 导航栏 ===== */
.fb-navbar {
	position: sticky;
	top: 0;
	z-index: 30;
	background: linear-gradient(180deg, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0) 100%);
	padding: 0 16rpx;
}
.fb-nav-content {
	display: flex;
	align-items: center;
	justify-content: space-between;
}
.fb-nav-back { width: 80rpx; display: flex; align-items: center; justify-content: center; }
.fb-nav-title {
	font-size: 34rpx; font-weight: 700; color: #fff;
	flex: 1; text-align: center; text-shadow: 0 2rpx 4rpx rgba(0,0,0,0.3);
}
.fb-nav-placeholder { width: 80rpx; }

/* ===== Hero ===== */
.qxl-hero {
	position: relative;
	height: 320rpx;
	background: linear-gradient(135deg, #1877F2 0%, #34C759 100%);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	color: #fff;
	/* margin-top / padding-top 由 heroBgStyle 计算属性动态写入（含安全区补偿） */
}
.qxl-hero-content{
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
}
.qxl-hero-title {
	font-size: 44rpx; font-weight: 800; letter-spacing: 4rpx;
	text-shadow: 0 4rpx 8rpx rgba(0,0,0,0.25);
}
.qxl-hero-subtitle {
	font-size: 26rpx; 
	margin-top: 10rpx; 
	opacity: 0.9;
	 letter-spacing: 2rpx;
}

/* ===== Body ===== */
.qxl-body {
	padding: 24rpx 16rpx 100rpx;
	margin-top: -24rpx;
	position: relative;
	z-index: 10;
}

/* ===== Section ===== */
.qxl-section {
	background: #fff;
	border-radius: 20rpx;
	padding: 20rpx 20rpx 8rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 2rpx 8rpx rgba(0,0,0,0.06);
	border: 1rpx solid #E4E6EB;
}
.qxl-section-head {
	display: flex;
	align-items: center;
	gap: 8rpx;
	padding: 4rpx 4rpx 16rpx;
	border-bottom: 1rpx solid #F0F2F5;
	margin-bottom: 16rpx;
}
.qxl-section-dot {
	width: 8rpx; height: 28rpx; border-radius: 4rpx;
}
.qxl-section-emoji {
	font-size: 32rpx;
}
.qxl-section-name {
	font-size: 30rpx; font-weight: 700; color: #050505;
}
.qxl-section-sub {
	font-size: 22rpx; color: #8A8A8A; margin-left: 4rpx;
}

/* ===== 活动卡片（横向一行） ===== */
.qxl-section-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}
.qxl-act-card {
	display: flex;
	background: #FAFBFC;
	border-radius: 16rpx;
	overflow: hidden;
	border: 1rpx solid #EDEEF0;
}
.qxl-act-card:active { opacity: 0.88; }
.qxl-act-cover {
	width: 200rpx; height: 160rpx;
	position: relative; flex-shrink: 0; background: #F0F2F5;
}
.qxl-act-badge {
	position: absolute; left: 8rpx; bottom: 8rpx;
	height: 30rpx; padding: 0 12rpx;
	background: rgba(0,0,0,0.55); border-radius: 15rpx;
	font-size: 18rpx; color: #fff; line-height: 30rpx;
}
.qxl-act-body {
	flex: 1; padding: 16rpx 18rpx;
	display: flex; flex-direction: column; justify-content: space-between;
	min-width: 0;
}
.qxl-act-title {
	font-size: 28rpx; font-weight: 600; color: #050505;
	line-height: 1.4;
}
.qxl-act-meta {
	display: flex; flex-direction: column; gap: 6rpx;
	margin-top: 8rpx;
}
.qxl-act-meta-item {
	display: flex; align-items: center; gap: 4rpx;
	font-size: 22rpx; color: #65676B;
	min-width: 0;
}
.qxl-act-meta-item text {
	flex: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
}

/* ===== 发现七贤岭 内嵌学与考入口 ===== */
.qxl-learn-grid {
	display: flex;
	gap: 16rpx;
	margin-bottom: 16rpx;
}
.qxl-learn-card {
	flex: 1;
	border-radius: 16rpx;
	padding: 20rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8rpx;
}
.qxl-learn-card:active { transform: scale(0.97); }
.qxl-learn-card--learn {
	background: linear-gradient(135deg, #4A9BFF 0%, #1877F2 100%);
}
.qxl-learn-card--quiz {
	background: linear-gradient(135deg, #34C759 0%, #28A745 100%);
}
.qxl-learn-name {
	font-size: 28rpx;
	font-weight: 700;
	color: #fff;
}
.qxl-learn-desc {
	font-size: 22rpx;
	color: rgba(255,255,255,0.8);
}

/* ===== 空态 ===== */
.qxl-empty {
	padding: 30rpx 0; text-align: center;
}
.qxl-empty-text {
	font-size: 24rpx; color: #8A8A8A;
}
</style>
