<template>
	<view class="container">
		<view class="xsd-hero" :style="heroBgStyle">
			<view class="xsd-hero-content">
				<text class="xsd-hero-title">小手拉大手</text>
				<text class="xsd-hero-subtitle">家长与孩子同行 · 一起学分类</text>
			</view>
		</view>

		<view class="fb-special-entries">
			<view class="fb-special-entry fb-s-lgfl" hover-class="fb-special-entry--press" :hover-stay-time="80" @click="gotoFlhd">
				<text class="fb-s-entry-title">垃圾分类</text>
				<text class="fb-s-entry-sub">学习分类 · 拍照真分</text>
				<text class="fb-s-entry-arrow">›</text>
			</view>
			<view class="fb-special-entry fb-s-qxl" hover-class="fb-special-entry--press" :hover-stay-time="80" @click="gotoQxl">
				<text class="fb-s-entry-title">我和七贤岭</text>
				<text class="fb-s-entry-sub">发现 · 玩 · 记录 · 守护</text>
				<text class="fb-s-entry-arrow">›</text>
			</view>
		</view>

		<view class="xsd-body">
			<view class="xsd-section" v-for="cate in sections" :key="cate.id">
				<view class="xsd-section-head">
					<view class="xsd-section-dot" :style="{ background: cate.accent }"></view>
					<text class="xsd-section-emoji">{{ cate.emoji }}</text>
					<text class="xsd-section-name">{{ cate.name }}</text>
					<text class="xsd-section-sub">{{ cate.subtitle }}</text>
				</view>

				<view class="xsd-section-list" v-if="cate.activities && cate.activities.length">
					<view class="xsd-act-card" v-for="act in cate.activities" :key="act.id" @click="gotoDetail(act.id)">
						<view class="xsd-act-cover">
							<u-image width="100%" height="100%" :src="act.thumb" mode="aspectFill"></u-image>
							<view class="xsd-act-badge" v-if="act.active_count > 0">
								<text>{{ act.active_count }}人参与</text>
							</view>
						</view>
						<view class="xsd-act-body">
							<view class="xsd-act-title u-line-1">{{ act.name }}</view>
							<view class="xsd-act-meta">
								<view class="xsd-act-meta-item">
									<u-icon name="clock" size="20" color="#8A8A8A"></u-icon>
									<text>{{ act.date | date('yy-mm-dd') }}</text>
								</view>
								<view class="xsd-act-meta-item" v-if="act.address && act.address.trim()">
									<u-icon name="map" size="20" color="#8A8A8A"></u-icon>
									<text class="u-line-1">{{ act.address }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>

				<view class="xsd-empty" v-else-if="!cate.loading">
					<text class="xsd-empty-text">暂无活动，敬请期待</text>
				</view>
				<view class="xsd-empty" v-else>
					<text class="xsd-empty-text">加载中...</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	const XSDLD_DECOR = {
		default: [
			{ subtitle: '云端亲子课堂 · 分类知识汇', emoji: '☁️', accent: '#FF6B9D' },
			{ subtitle: '动手实践 · 亲子分类闯关', emoji: '🌟', accent: '#18AF38' },
		],
	}

	export default {
		data() {
			return {
				sections: [],
				heroBgImage: '',
				statusBarHeight: 20,
			}
		},
		computed: {
			heroBgStyle() {
				const padTopPx = this.statusBarHeight + 44
				if (this.heroBgImage) {
					return `background-image: linear-gradient(180deg, rgba(0,0,0,0.25) 0%, rgba(0,0,0,0.05) 100%), url(${this.heroBgImage}); background-size: cover; background-position: center; padding-top: ${padTopPx}px;`
				}
				return `padding-top: ${padTopPx}px;`
			},
		},
		onLoad() {
			const sys = uni.getSystemInfoSync()
			this.statusBarHeight = sys.statusBarHeight || 20
			this.loadHeroBg()
			this.loadSections()
		},
		methods: {
			async loadHeroBg() {
				try {
					const res = await this.$u.api.getPageConfig()
					const url = res.data?.xsdld_hero_bg || ''
					if (url) this.heroBgImage = url
				} catch (e) {}
			},
			buildSections(list) {
				const defaults = ['云间亲子分类汇', '分类小当家·亲子实践营']
				const source = list && list.length ? list : defaults.map((name, i) => ({ id: i + 1, name }))
				return source.map((item, index) => {
					const decor = XSDLD_DECOR.default[index] || XSDLD_DECOR.default[0]
					return {
						id: item.id,
						name: item.name,
						subtitle: decor.subtitle,
						emoji: decor.emoji,
						accent: decor.accent,
						activities: [],
						loading: true,
					}
				})
			},
			async loadSections() {
				try {
					const res = await this.$u.api.get_xsdld_cate_list()
					this.sections = this.buildSections(res.data || [])
				} catch (e) {
					this.sections = this.buildSections(null)
				}
				this.loadAllActivities()
			},
			async loadAllActivities() {
				const tasks = this.sections.map(cate =>
					this.fetchActivities(cate.id).then(list => {
						this.$set(cate, 'activities', list)
						this.$set(cate, 'loading', false)
					}).catch(() => {
						this.$set(cate, 'activities', [])
						this.$set(cate, 'loading', false)
					}),
				)
				await Promise.all(tasks)
			},
			fetchActivities(xsdldCateId) {
				return this.$u.api.get_new_active_list({
					xsdld_cate_id: xsdldCateId,
					page_size: 20,
				}).then(res => res.data?.data || [])
			},
			gotoDetail(id) {
				this.$u.route(`/pages/activity/lsxf/details-new?id=${id}`)
			},
			gotoFlhd() {
				this.$u.route('/pages/activity/scene-clock-pick?mode=theme')
			},
			gotoQxl() {
				this.$u.route('/pages/activity/love-qixianling')
			},
		},
	}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #f0f2f5;
}

.xsd-hero {
	position: relative;
	height: 320rpx;
	background: linear-gradient(135deg, #ff8fab 0%, #18af38 100%);
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	color: #fff;
}

.xsd-hero-content {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 12rpx;
}

.xsd-hero-title {
	font-size: 44rpx;
	font-weight: 800;
	letter-spacing: 4rpx;
	text-shadow: 0 4rpx 8rpx rgba(0, 0, 0, 0.25);
}

.xsd-hero-subtitle {
	font-size: 26rpx;
	margin-top: 10rpx;
	opacity: 0.92;
	letter-spacing: 2rpx;
}

.fb-special-entries {
	display: flex;
	gap: 16rpx;
	padding: 16rpx;
	background: #f0f2f5;
	position: relative;
	z-index: 11;
}
.fb-special-entry {
	flex: 1;
	display: flex;
	flex-direction: column;
	justify-content: center;
	padding: 28rpx 24rpx;
	border-radius: 16rpx;
	position: relative;
}
.fb-special-entry--press {
	opacity: 0.88;
}
.fb-s-lgfl {
	background: linear-gradient(135deg, #4a9bff 0%, #1877f2 100%);
	color: #fff;
}
.fb-s-qxl {
	background: linear-gradient(135deg, #34c759 0%, #28a745 100%);
	color: #fff;
}
.fb-s-entry-title {
	font-size: 30rpx;
	font-weight: 700;
}
.fb-s-entry-sub {
	font-size: 22rpx;
	opacity: 0.9;
	margin-top: 4rpx;
}
.fb-s-entry-arrow {
	position: absolute;
	right: 20rpx;
	top: 50%;
	transform: translateY(-50%);
	font-size: 40rpx;
	font-weight: 300;
	opacity: 0.9;
}

.xsd-body {
	padding: 8rpx 16rpx 100rpx;
	margin-top: 0;
	position: relative;
	z-index: 10;
}

.xsd-section {
	background: #fff;
	border-radius: 20rpx;
	padding: 20rpx 20rpx 8rpx;
	margin-bottom: 20rpx;
	box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
	border: 1rpx solid #e4e6eb;
}

.xsd-section-head {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 8rpx;
	padding: 4rpx 4rpx 16rpx;
	border-bottom: 1rpx solid #f0f2f5;
	margin-bottom: 16rpx;
}

.xsd-section-dot {
	width: 8rpx;
	height: 28rpx;
	border-radius: 4rpx;
}

.xsd-section-emoji {
	font-size: 32rpx;
}

.xsd-section-name {
	font-size: 30rpx;
	font-weight: 700;
	color: #050505;
}

.xsd-section-sub {
	font-size: 22rpx;
	color: #8a8a8a;
	margin-left: 4rpx;
	flex: 1;
	min-width: 200rpx;
}

.xsd-section-list {
	display: flex;
	flex-direction: column;
	gap: 16rpx;
}

.xsd-act-card {
	display: flex;
	background: #fafbfc;
	border-radius: 16rpx;
	overflow: hidden;
	border: 1rpx solid #edeef0;
}

.xsd-act-card:active {
	opacity: 0.88;
}

.xsd-act-cover {
	width: 200rpx;
	height: 160rpx;
	position: relative;
	flex-shrink: 0;
	background: #f0f2f5;
}

.xsd-act-badge {
	position: absolute;
	left: 8rpx;
	bottom: 8rpx;
	height: 30rpx;
	padding: 0 12rpx;
	background: rgba(0, 0, 0, 0.55);
	border-radius: 15rpx;
	font-size: 18rpx;
	color: #fff;
	line-height: 30rpx;
}

.xsd-act-body {
	flex: 1;
	padding: 16rpx 18rpx;
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	min-width: 0;
}

.xsd-act-title {
	font-size: 28rpx;
	font-weight: 600;
	color: #050505;
	line-height: 1.4;
}

.xsd-act-meta {
	display: flex;
	flex-direction: column;
	gap: 6rpx;
	margin-top: 8rpx;
}

.xsd-act-meta-item {
	display: flex;
	align-items: center;
	gap: 4rpx;
	font-size: 22rpx;
	color: #65676b;
	min-width: 0;
}

.xsd-act-meta-item text {
	flex: 1;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.xsd-empty {
	padding: 40rpx 0 32rpx;
	text-align: center;
}

.xsd-empty-text {
	font-size: 24rpx;
	color: #8a8a8a;
}
</style>
