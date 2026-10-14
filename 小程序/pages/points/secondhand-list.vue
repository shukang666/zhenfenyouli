<template>
	<view class="sh-page">
		<view class="sh-head">
			<text class="sh-head__title">社区闲置</text>
			<text class="sh-head__sub">线上浏览与发布；换货请到绿色二手市集现场交接</text>
		</view>
		<view class="sh-link-row" @tap="goIntro">
			<text class="sh-link-row__t">规则说明</text>
			<u-icon name="arrow-right" size="24" color="#076d3d"></u-icon>
		</view>
		<view class="sh-link-row sh-link-row--hub" @tap="goFleaHub">
			<text class="sh-link-row__t">查看市集活动时间与地点</text>
			<u-icon name="arrow-right" size="24" color="#076d3d"></u-icon>
		</view>
		<view class="sh-search glass-search">
			<u-search
				v-model="keyword"
				placeholder="搜索闲置名称"
				margin="0"
				height="68"
				bg-color="#f5f7f6"
				search-icon-color="#18AF38"
				border-color="transparent"
				:show-action="false"
				@search="reload"
				@clear="reload"
			></u-search>
		</view>
		<scroll-view scroll-x class="sh-cats" :show-scrollbar="false">
			<view class="sh-cats__inner">
				<view
					class="sh-pill"
					:class="{ 'sh-pill--on': category === c.key }"
					v-for="c in categories"
					:key="c.key"
					@tap="onCat(c.key)"
				>{{ c.label }}</view>
			</view>
		</scroll-view>
		<view class="sh-demo-tip">演示版：数据保存在本机。正式版将同步云端；积分交割仅在市集活动现场办理。</view>
		<view class="sh-grid" v-if="list.length">
			<view class="sh-card" v-for="item in list" :key="item.id" @tap="goDetail(item.id)">
				<image v-if="cover(item)" class="sh-card__img" :src="cover(item)" mode="aspectFill"></image>
				<view v-else class="sh-card__img sh-card__img--ph">闲置</view>
				<view class="sh-card__body">
					<text class="sh-card__title u-line-2">{{ item.title }}</text>
					<view class="sh-card__foot">
						<text class="sh-card__point">{{ item.point_price }} 积分</text>
						<text class="sh-card__area u-line-1">{{ item.area_text }}</text>
					</view>
				</view>
			</view>
		</view>
		<view class="sh-empty" v-else>
			<u-icon name="bag" size="80" color="#C8C9CC"></u-icon>
			<text class="sh-empty__t">暂无闲置，来做第一个发布者吧</text>
		</view>
		<view class="sh-bar">
			<view class="sh-bar__btn sh-bar__btn--ghost" @tap="goMy">我的发布</view>
			<view class="sh-bar__btn sh-bar__btn--primary" @tap="goPublish">发布闲置</view>
		</view>
	</view>
</template>

<script>
import { getCategories, listSecondhand } from '@/common/secondhandStore.js'
import { fleaMarketHubUrl, secondhandIntroUrl } from '@/common/secondhandNav.js'

export default {
	data() {
		return {
			categories: getCategories(),
			category: 'all',
			keyword: '',
			list: [],
		}
	},
	onShow() {
		this.reload()
	},
	methods: {
		reload() {
			this.list = listSecondhand({
				category: this.category,
				keyword: this.keyword,
			})
		},
		onCat(key) {
			this.category = key
			this.reload()
		},
		cover(item) {
			return (item.images && item.images[0]) || ''
		},
		goDetail(id) {
			this.$u.route('/pages/points/secondhand-detail?id=' + id)
		},
		goPublish() {
			if (!this.$u.func.checkLogin()) return
			this.$u.route('/pages/points/secondhand-publish')
		},
		goMy() {
			if (!this.$u.func.checkLogin()) return
			this.$u.route('/pages/points/secondhand-my')
		},
		goIntro() {
			this.$u.route(secondhandIntroUrl())
		},
		goFleaHub() {
			this.$u.route(fleaMarketHubUrl())
		},
	},
}
</script>

<style lang="scss" scoped>
@import '@/static/styles/glass-ui.scss';

.sh-page {
	min-height: 100vh;
	background: #e4e8e5;
	padding: 24rpx 30rpx calc(160rpx + env(safe-area-inset-bottom));
	box-sizing: border-box;
}

.sh-head__title {
	display: block;
	font-size: 40rpx;
	font-weight: 700;
	color: #1d1d1f;
}

.sh-head__sub {
	display: block;
	margin-top: 8rpx;
	font-size: 26rpx;
	color: #8e8e93;
	line-height: 1.45;
}

.sh-link-row {
	margin-top: 16rpx;
	padding: 20rpx 24rpx;
	background: #fff;
	border-radius: 16rpx;
	display: flex;
	flex-direction: row;
	align-items: center;
	justify-content: space-between;
}

.sh-link-row--hub {
	margin-top: 12rpx;
	background: #e3f3ea;
}

.sh-link-row__t {
	font-size: 28rpx;
	font-weight: 600;
	color: #076d3d;
}

.sh-search {
	margin-top: 24rpx;
	padding: 14rpx 16rpx;
	box-sizing: border-box;
	min-height: 96rpx;
	display: flex;
	align-items: center;
}

.sh-cats {
	margin-top: 24rpx;
	white-space: nowrap;
}

.sh-cats__inner {
	display: inline-flex;
	gap: 16rpx;
	padding: 4rpx 0;
}

.sh-pill {
	padding: 14rpx 28rpx;
	border-radius: 999rpx;
	background: #f2f4f3;
	font-size: 26rpx;
	color: #3a3a3c;
}

.sh-pill--on {
	background: #e3f3ea;
	color: #076d3d;
	font-weight: 600;
}

.sh-demo-tip {
	margin-top: 20rpx;
	font-size: 22rpx;
	color: #8e8e93;
	line-height: 1.5;
}

.sh-grid {
	margin-top: 24rpx;
	display: flex;
	flex-wrap: wrap;
	gap: 20rpx 16rpx;
}

.sh-card {
	width: calc((100% - 16rpx) / 2);
	background: #fff;
	border-radius: 20rpx;
	overflow: hidden;
	box-shadow: 0 8rpx 24rpx rgba(7, 109, 61, 0.06);
}

.sh-card__img {
	width: 100%;
	height: 280rpx;
	background: #eef1f0;
}

.sh-card__img--ph {
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 28rpx;
	color: #b0b8bc;
}

.sh-card__body {
	padding: 16rpx;
}

.sh-card__title {
	font-size: 28rpx;
	font-weight: 600;
	color: #1a1a1a;
	min-height: 78rpx;
	line-height: 1.35;
}

.sh-card__foot {
	margin-top: 12rpx;
}

.sh-card__point {
	font-size: 28rpx;
	font-weight: 700;
	color: #076d3d;
}

.sh-card__area {
	display: block;
	margin-top: 6rpx;
	font-size: 22rpx;
	color: #8e8e93;
}

.sh-empty {
	padding: 100rpx 0;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.sh-empty__t {
	margin-top: 20rpx;
	font-size: 28rpx;
	color: #999;
}

.sh-bar {
	position: fixed;
	left: 0;
	right: 0;
	bottom: 0;
	padding: 16rpx 30rpx calc(16rpx + env(safe-area-inset-bottom));
	background: rgba(255, 255, 255, 0.96);
	display: flex;
	gap: 20rpx;
	box-shadow: 0 -4rpx 24rpx rgba(0, 0, 0, 0.06);
}

.sh-bar__btn {
	flex: 1;
	height: 88rpx;
	line-height: 88rpx;
	text-align: center;
	border-radius: 999rpx;
	font-size: 30rpx;
	font-weight: 600;
}

.sh-bar__btn--ghost {
	background: #f2f4f3;
	color: #076d3d;
}

.sh-bar__btn--primary {
	background: #18af38;
	color: #fff;
}
</style>
