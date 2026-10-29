<template>
	<view class="discover">
		<view class="discover-nav glass-nav">
			<text class="discover-nav__title">发现活动</text>
			<view class="discover-search glass-search" hover-class="discover-search--on" @click="$emit('search')">
				<u-icon name="search" size="28" color="#18AF38"></u-icon>
				<text class="discover-search__ph">搜索活动、主题或主办方</text>
			</view>
		</view>

		<scroll-view scroll-x class="discover-chips" :show-scrollbar="false">
			<view class="discover-chips__inner">
				<view
					v-for="chip in filterOptions"
					:key="chip.key"
					class="discover-chip"
					:class="{ 'discover-chip--on': filter === chip.key }"
					@click="$emit('filter', chip.key)"
				>
					<text>{{ chip.label }}</text>
				</view>
			</view>
		</scroll-view>

		<view class="discover-cates">
			<view class="discover-cates__inner">
				<view
					class="discover-cate"
					v-for="cate in categories"
					:key="cate.id"
					hover-class="discover-cate--on"
					:hover-stay-time="80"
					@click="$emit('open-cate', cate)"
				>
					<text class="discover-cate__emoji">{{ cate.emoji }}</text>
					<text class="discover-cate__name">{{ cate.shortName || cate.name }}</text>
				</view>
			</view>
		</view>

		<view
			class="discover-month"
			v-if="monthTheme"
			hover-class="discover-month--on"
			:hover-stay-time="80"
			@click="$emit('open-cate', monthTheme)"
		>
			<view class="discover-month__bg" aria-hidden="true"></view>
			<view class="discover-month__copy">
				<text class="discover-month__title">本月运动健康季</text>
				<text class="discover-month__sub">下班后动一动，让身体和心情都松下来</text>
				<view class="discover-month__cta">
					<text>查看专题</text>
					<u-icon name="arrow-right" size="22" color="#1565D8"></u-icon>
				</view>
			</view>
		</view>

		<view class="discover-section">
			<view class="discover-section__head">
				<text class="discover-section__title">正在报名</text>
				<view class="discover-section__filter" @click="$emit('more-filter')">
					<text>筛选</text>
				</view>
			</view>

			<view class="discover-card" v-for="item in list" :key="item.id" @click="$emit('open', item)">
				<view class="discover-card__thumb">
					<u-image
						v-if="item._thumb || item.thumb"
						width="128rpx"
						height="128rpx"
						:src="item._thumb || item.thumb"
						mode="aspectFill"
						border-radius="16"
					></u-image>
					<text v-else class="discover-card__thumb-ph">🎯</text>
				</view>
				<view class="discover-card__body">
					<text class="discover-card__name u-line-2">{{ item.name }}</text>
					<text class="discover-card__meta u-line-2">{{ item._date_line }}</text>
					<view class="discover-card__tags" v-if="item._tags && item._tags.length">
						<text class="discover-card__tag" v-for="(tag, idx) in item._tags" :key="idx">{{ tag }}</text>
					</view>
					<view class="discover-card__foot">
						<text class="discover-card__quota">{{ item._quota_hint }}</text>
						<view class="discover-card__btn" @click.stop="$emit('open', item)">
							<text>{{ item._btn_text || '去报名' }}</text>
						</view>
					</view>
				</view>
			</view>

			<view class="discover-load" v-if="loading">
				<u-loading mode="circle" color="#18AF38"></u-loading>
				<text>加载中…</text>
			</view>
			<view class="discover-load discover-load--muted" v-else-if="!list.length">
				<text>暂无符合条件的活动</text>
			</view>
			<view class="discover-load discover-load--muted" v-else-if="!hasMore">
				<text>没有更多了</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: "ActivityTabDiscover",
	props: {
		filter: { type: String, default: "recommend" },
		categories: { type: Array, default: () => [] },
		monthTheme: { type: Object, default: null },
		list: { type: Array, default: () => [] },
		loading: { type: Boolean, default: false },
		hasMore: { type: Boolean, default: true },
	},
	data() {
		return {
			filterOptions: [
				{ key: "recommend", label: "推荐" },
				{ key: "weekly", label: "本周" },
				{ key: "nearby", label: "附近" },
				{ key: "enrolling", label: "报名中" },
				{ key: "free", label: "免费" },
			],
		};
	},
};
</script>

<style lang="scss" scoped>
@import "@/static/styles/glass-ui.scss";

.discover {
	position: relative;
	z-index: 1;
	padding-bottom: 32rpx;
}

.discover-nav {
	padding: 16rpx 24rpx;
	padding-top: calc(env(safe-area-inset-top) + 16rpx);
}
.discover-nav__title {
	display: block;
	text-align: center;
	font-size: 36rpx;
	font-weight: 800;
	color: #1a2e22;
	margin-bottom: 16rpx;
}
.discover-search {
	display: flex;
	align-items: center;
	height: 76rpx;
	padding: 0 20rpx;
	border-radius: 999rpx;
}
.discover-search--on {
	opacity: 0.92;
}
.discover-search__ph {
	margin-left: 12rpx;
	font-size: 26rpx;
	color: #9aa39c;
}

.discover-chips {
	margin-top: 8rpx;
	white-space: nowrap;
}
.discover-chips__inner {
	display: inline-flex;
	padding: 0 24rpx 8rpx;
	gap: 12rpx;
}
.discover-chip {
	display: inline-flex;
	padding: 10rpx 26rpx;
	border-radius: 999rpx;
	background: #ffffff;
	font-size: 24rpx;
	font-weight: 600;
	color: #5f7166;
	box-shadow: 0 4rpx 12rpx rgba(22, 53, 36, 0.05);
}
.discover-chip--on {
	background: #1a2420;
	color: #ffffff;
}

.discover-cates {
	margin-top: 8rpx;
	padding: 8rpx 24rpx 16rpx;
}
.discover-cates__inner {
	display: flex;
	flex-direction: row;
	align-items: flex-start;
	justify-content: space-between;
}
.discover-cate {
	flex: 1;
	min-width: 0;
	display: flex;
	flex-direction: column;
	align-items: center;
}
.discover-cate--on {
	opacity: 0.85;
}
.discover-cate__emoji {
	font-size: 44rpx;
	line-height: 1.1;
}
.discover-cate__name {
	margin-top: 8rpx;
	font-size: 22rpx;
	font-weight: 600;
	color: #3d4f44;
	text-align: center;
	white-space: normal;
	width: 100%;
}

.discover-month {
	position: relative;
	margin: 8rpx 24rpx 20rpx;
	padding: 28rpx 24rpx;
	border-radius: 24rpx;
	overflow: hidden;
	min-height: 200rpx;
	box-shadow: 0 12rpx 32rpx rgba(24, 119, 242, 0.22);
}
.discover-month--on {
	opacity: 0.94;
}
.discover-month__bg {
	position: absolute;
	inset: 0;
	background: linear-gradient(135deg, #2f6fd8 0%, #5a9ae8 50%, #9ec9f7 100%);
}
.discover-month__bg::after {
	content: "";
	position: absolute;
	right: -20rpx;
	top: 20rpx;
	width: 160rpx;
	height: 160rpx;
	border-radius: 50%;
	border: 12rpx solid rgba(255, 255, 255, 0.15);
}
.discover-month__copy {
	position: relative;
	z-index: 1;
}
.discover-month__title {
	display: block;
	font-size: 34rpx;
	font-weight: 800;
	color: #ffffff;
}
.discover-month__sub {
	display: block;
	margin-top: 10rpx;
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.92);
	line-height: 1.45;
	max-width: 420rpx;
}
.discover-month__cta {
	display: inline-flex;
	align-items: center;
	gap: 6rpx;
	margin-top: 20rpx;
	padding: 10rpx 22rpx;
	border-radius: 999rpx;
	background: rgba(255, 255, 255, 0.95);
	font-size: 24rpx;
	font-weight: 700;
	color: #1565d8;
}

.discover-section {
	padding: 0 24rpx;
}
.discover-section__head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16rpx;
}
.discover-section__title {
	font-size: 32rpx;
	font-weight: 800;
	color: #1a2e22;
}
.discover-section__filter text {
	font-size: 24rpx;
	font-weight: 600;
	color: #18af38;
}

.discover-card {
	display: flex;
	flex-direction: row;
	align-items: stretch;
	padding: 20rpx;
	margin-bottom: 16rpx;
	border-radius: 20rpx;
	background: #ffffff;
	box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.06);
}
.discover-card__thumb {
	flex: 0 0 128rpx;
	width: 128rpx;
	height: 128rpx;
	border-radius: 16rpx;
	overflow: hidden;
	background: #eef4f0;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
}
.discover-card__thumb ::v-deep .u-image,
.discover-card__thumb ::v-deep image {
	width: 128rpx !important;
	height: 128rpx !important;
	display: block;
}
.discover-card__thumb-ph {
	font-size: 48rpx;
}
.discover-card__body {
	flex: 1;
	min-width: 0;
	margin-left: 18rpx;
	display: flex;
	flex-direction: column;
}
.discover-card__name {
	font-size: 30rpx;
	font-weight: 700;
	color: #1a2e22;
	line-height: 1.35;
}
.discover-card__meta {
	margin-top: 8rpx;
	font-size: 22rpx;
	color: #8a9690;
	line-height: 1.4;
}
.discover-card__tags {
	display: flex;
	flex-wrap: wrap;
	gap: 8rpx;
	margin-top: 10rpx;
}
.discover-card__tag {
	padding: 4rpx 12rpx;
	border-radius: 8rpx;
	background: #f0f2f5;
	font-size: 20rpx;
	color: #6a756f;
}
.discover-card__foot {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: auto;
	padding-top: 12rpx;
}
.discover-card__quota {
	font-size: 22rpx;
	font-weight: 600;
	color: #e86b20;
	flex: 1;
	min-width: 0;
	margin-right: 12rpx;
}
.discover-card__btn {
	flex-shrink: 0;
	padding: 10rpx 22rpx;
	border-radius: 999rpx;
	background: #18af38;
}
.discover-card__btn text {
	font-size: 24rpx;
	font-weight: 700;
	color: #ffffff;
}

.discover-load {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 12rpx;
	padding: 24rpx 0 40rpx;
	font-size: 24rpx;
	color: #8a9690;
}
.discover-load--muted text {
	color: #aab4ae;
}
</style>
