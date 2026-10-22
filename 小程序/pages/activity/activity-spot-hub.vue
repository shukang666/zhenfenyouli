<template>

	<view class="container">

		<view :class="heroClass">

			<view class="spot-hero-deco" aria-hidden="true">

				<view class="spot-hero-deco-hill spot-hero-deco-hill--back"></view>

				<view class="spot-hero-deco-hill spot-hero-deco-hill--front"></view>

				<view class="spot-hero-deco-leaf spot-hero-deco-leaf--1"></view>

				<view class="spot-hero-deco-leaf spot-hero-deco-leaf--2"></view>

			</view>

			<view class="spot-hero-content">

				<text class="spot-hero-mark" v-if="moduleEmoji">{{ moduleEmoji }}</text>

				<text class="spot-hero-title">{{ moduleName }}</text>

				<text class="spot-hero-sub" v-if="moduleSubtitle">{{ moduleSubtitle }}</text>

			</view>

		</view>



		<view class="spot-body">

			<view class="spot-panel spot-panel--intro" v-if="moduleIntro">

				<view class="spot-section-head spot-section-head--tight">

					<text class="spot-section-label">活动介绍</text>

				</view>

				<text class="spot-intro-text">{{ moduleIntro }}</text>

			</view>

			<view class="spot-panel">

				<view class="spot-section-head">

					<view class="spot-section-title-row">

						<text class="spot-section-label">{{ isFleaMarket ? '市集场次' : '线下点位' }}</text>

						<text class="spot-section-count" v-if="venueCount > 0">共 {{ venueCount }} 个</text>

					</view>

					<text class="spot-section-hint">同一专题因地点不同拆成多个入口，请选您方便的{{ isFleaMarket ? '场次' : '点位' }}进入参与。</text>

				</view>



				<view class="spot-flea-bridge" v-if="false">

					<text class="spot-flea-bridge__title">线上闲置</text>

					<text class="spot-flea-bridge__hint">浏览邻居发布、上传自己的闲置，先看规则再操作</text>

					<view class="spot-flea-bridge__row" hover-class="spot-flea-bridge__row--press" @tap="goSecondhandIntro">

						<text class="spot-flea-bridge__label">社区闲置规则说明</text>

						<u-icon name="arrow-right" size="28" color="#357a5c"></u-icon>

					</view>

					<view class="spot-flea-bridge__row" hover-class="spot-flea-bridge__row--press" @tap="goSecondhandList">

						<text class="spot-flea-bridge__label">浏览 / 发布社区闲置</text>

						<u-icon name="arrow-right" size="28" color="#357a5c"></u-icon>

					</view>

				</view>



				<view class="spot-venue-list" v-if="venues.length">

					<view

						class="spot-venue-row"

						v-for="(item, vIdx) in venues"

						:key="vIdx"

						hover-class="spot-venue-row--press"

						:hover-stay-time="80"

						@tap="onTapVenue(vIdx)"

					>

						<view class="spot-venue-pin" aria-hidden="true">

							<u-icon name="map-fill" size="32" :color="iconAccentColor"></u-icon>

						</view>

						<view class="spot-venue-main">

							<text class="spot-venue-place">{{ venuePlaceLabel(item) }}</text>

							<text class="spot-venue-addr u-line-2" v-if="venueAddressLine(item)">{{ venueAddressLine(item) }}</text>

							<view class="spot-venue-meta">

								<text class="spot-venue-meta-item" v-if="item.date_text">{{ item.date_text }}</text>

								<text class="spot-venue-meta-item" v-if="item.active_count > 0">{{ item.active_count }} 人参与</text>

							</view>

						</view>

						<text class="spot-venue-action">进入</text>

					</view>

				</view>



				<view class="spot-empty" v-else-if="!loading">

					<text class="spot-empty-text">{{ isFleaMarket ? '暂无近期场次，请关注后续公布' : '暂无活动地点，敬请期待' }}</text>

				</view>

				<view class="spot-empty" v-else>

					<text class="spot-empty-text">加载中...</text>

				</view>

			</view>

		</view>

	</view>

</template>



<script>

import { openActiveDetailWithContext, appendActiveIdToWebUrl, buildH5WrapperPath, isWebDisplayActive } from '@/common/activeOpen.js'
import { secondhandIntroUrl, secondhandListUrl } from '@/common/secondhandNav.js'




const SPOT_DECOR = {

	hiking: { emoji: '🥾', name: '步行山海，净美家园', subtitle: '边走边捡 · 净美山海' },

	flea_market: { emoji: '♻️', name: '七贤岭绿色二手集市', subtitle: '闲置循环 · 绿色生活' },

	zhenfen_class: { emoji: '📗', name: '垃圾真分小课堂', subtitle: '学分类 · 真投放' },

}

const SPOT_INTRO_FALLBACK = {

	hiking: '徒步山海沿线捡拾垃圾，净美家园。下方为各线路/集合点入口，请选就近点位参与。',

	flea_market: '线下绿色二手市集，闲置当面交接。下方为各场次时间与地点，请选场次进入。',

	zhenfen_class: '垃圾分类小课堂，现场学习真分。下方为各教学点，请选就近点位进入。',

}



export default {

	data() {

		return {

			spotKey: '',

			moduleName: '',

			moduleSubtitle: '',

			moduleEmoji: '📌',

			moduleIntro: '',

			venues: [],

			loading: true,

		}

	},

	computed: {

		heroClass() {

			const k = this.spotKey || 'default'

			return 'spot-hero spot-hero--' + k

		},

		venueCount() {

			return Array.isArray(this.venues) ? this.venues.length : 0

		},

		iconAccentColor() {

			const map = {

				hiking: '#2d6a4f',

				flea_market: '#40916c',

				zhenfen_class: '#1b7a4a',

			}

			return map[this.spotKey] || '#2d6a4f'

		},

		isFleaMarket() {

			return this.spotKey === 'flea_market'

		},

	},

	onLoad(options) {

		this.spotKey = String(options.spot || options.spot_key || '').trim()

		const nameFromQuery = options.name ? decodeURIComponent(options.name) : ''

		if (nameFromQuery) {

			this.moduleName = nameFromQuery

		}

		const decor = SPOT_DECOR[this.spotKey] || {}

		this.moduleEmoji = decor.emoji || '📌'

		if (decor.name) {

			this.moduleName = decor.name

		}

		if (decor.subtitle) {

			this.moduleSubtitle = decor.subtitle

		}

		this.moduleIntro = SPOT_INTRO_FALLBACK[this.spotKey] || ''

		this.loadHub()

	},

	methods: {
		goSecondhandIntro() { this.$u.route(secondhandIntroUrl()) },
		goSecondhandList() { this.$u.route(secondhandListUrl()) },


		venuePlaceLabel(item) {
			if (!item) return '地点待定'
			const place = String(item.place_name || item.title || '').trim()
			return place || '地点待定'
		},
		venueAddressLine(item) {
			if (!item) return ''
			const addr = String(item.address || '').trim()
			const place = this.venuePlaceLabel(item)
			if (!addr) return ''
			if (addr === place) return ''
			if (addr.indexOf(place) >= 0 && place.length >= 4) return ''
			return addr
		},

		loadHub() {

			if (!this.spotKey) {

				this.loading = false

				this.$u.toast('专题参数缺失')

				return

			}

			this.loading = true

			this.$u.api.get_activity_spot_venues({ spot_key: this.spotKey }).then(res => {

				const data = res.data || {}

				const mod = data.module || {}

				const decor = SPOT_DECOR[this.spotKey] || {}

				if (!decor.name && mod.name && !this.moduleName) {

					this.moduleName = mod.name

				} else if (decor.name) {

					this.moduleName = decor.name

				}

				if (!decor.subtitle && mod.subtitle) {

					this.moduleSubtitle = mod.subtitle

				} else if (decor.subtitle) {

					this.moduleSubtitle = decor.subtitle

				}

				if (mod.emoji && !decor.emoji) this.moduleEmoji = mod.emoji

				if (mod.intro) {
					this.moduleIntro = mod.intro
				}

				this.venues = data.venues || []

				if (this.moduleName) {

					uni.setNavigationBarTitle({ title: this.moduleName })

				}

			}).catch(err => {

				this.$u.toast(err.msg || '加载失败')

			}).finally(() => {

				this.loading = false

			})

		},

		onTapVenue(vIdx) {

			const item = this.venues[vIdx]

			if (!item || !item.active_id) return

			const payload = {

				id: item.active_id,

				display_mode: item.display_mode,

				web_url: item.web_url,

				web_launch_url: item.web_launch_url,

				web_page_url: item.web_page_url,

			}

			if (isWebDisplayActive(payload)) {

				let pageUrl = item.web_launch_url || item.web_page_url || ''

				if (!pageUrl && item.web_url) {

					pageUrl = appendActiveIdToWebUrl(item.web_url, item.active_id)

				}

				if (item.session_id) {

					pageUrl += '#session/' + encodeURIComponent(item.session_id)

				}

				const issueContext = typeof this.$u.api.issue_web_context === 'function'

					? (id) => this.$u.api.issue_web_context({ active_id: id })

					: null

				if (issueContext) {

					openActiveDetailWithContext(payload, null, issueContext)

					return

				}

				uni.navigateTo({ url: buildH5WrapperPath(pageUrl) })

				return

			}

			this.$u.route(`/pages/activity/lsxf/details-new?id=${item.active_id}`)

		},

	},

}

</script>



<style lang="scss" scoped>

.container {

	min-height: 100vh;

	background: #eef1f0;

}



.spot-hero {

	position: relative;

	overflow: hidden;

	padding: 28rpx 40rpx 52rpx;

	display: flex;

	flex-direction: column;

	align-items: center;

	justify-content: center;

	color: #fff;

	min-height: 200rpx;

	box-sizing: border-box;

}



.spot-hero--default {

	background: #2d6a4f;

}



.spot-hero--hiking {

	background: #245a42;

}



.spot-hero--flea_market {

	background: #357a5c;

}



.spot-hero--zhenfen_class {

	background: #1e6b4a;

}



.spot-hero-deco {

	position: absolute;

	inset: 0;

	pointer-events: none;

	opacity: 0.35;

}



.spot-hero-deco-hill {

	position: absolute;

	bottom: 0;

	border-radius: 50% 50% 0 0;

}



.spot-hero-deco-hill--back {

	left: -20%;

	width: 70%;

	height: 88rpx;

	background: rgba(255, 255, 255, 0.12);

}



.spot-hero-deco-hill--front {

	right: -15%;

	width: 55%;

	height: 64rpx;

	background: rgba(255, 255, 255, 0.18);

}



.spot-hero-deco-leaf {

	position: absolute;

	width: 20rpx;

	height: 32rpx;

	border-radius: 0 80% 0 80%;

	background: rgba(149, 213, 178, 0.45);

	transform: rotate(-25deg);

}



.spot-hero-deco-leaf--1 {

	top: 24rpx;

	right: 56rpx;

}



.spot-hero-deco-leaf--2 {

	top: 48rpx;

	left: 48rpx;

	transform: rotate(35deg);

	opacity: 0.7;

}



.spot-hero-content {

	position: relative;

	z-index: 1;

	display: flex;

	flex-direction: column;

	align-items: center;

	text-align: center;

	gap: 8rpx;

	max-width: 640rpx;

}



.spot-hero-mark {

	font-size: 36rpx;

	line-height: 1;

	margin-bottom: 4rpx;

	opacity: 0.95;

}



.spot-hero-title {

	font-size: 44rpx;

	font-weight: 700;

	line-height: 1.3;

	letter-spacing: 1rpx;

	color: #fff;

}



.spot-hero-sub {

	font-size: 26rpx;

	line-height: 1.45;

	color: rgba(255, 255, 255, 0.88);

	font-weight: 400;

}



.spot-body {

	margin-top: -24rpx;

	padding: 0 24rpx calc(48rpx + env(safe-area-inset-bottom));

	position: relative;

	z-index: 2;

	display: flex;

	flex-direction: column;

	gap: 20rpx;

}



.spot-panel {

	background: #fff;

	border-radius: 24rpx;

	overflow: hidden;

	box-shadow: 0 8rpx 32rpx rgba(36, 90, 66, 0.06);

}



.spot-panel--intro {

	padding-bottom: 8rpx;

}



.spot-section-head--tight {

	padding-bottom: 12rpx;

}



.spot-intro-text {

	display: block;

	padding: 0 28rpx 28rpx;

	font-size: 28rpx;

	line-height: 1.65;

	color: #4a524e;

}



.spot-section-head {

	padding: 32rpx 28rpx 8rpx;

}



.spot-section-title-row {

	display: flex;

	align-items: baseline;

	justify-content: space-between;

	flex-wrap: wrap;

	gap: 8rpx 16rpx;

	margin-bottom: 10rpx;

}



.spot-section-label {

	font-size: 34rpx;

	font-weight: 700;

	color: #1a1a1a;

	line-height: 1.3;

}



.spot-section-count {

	font-size: 24rpx;

	color: #5a8f72;

	font-weight: 500;

	flex-shrink: 0;

}



.spot-section-hint {

	display: block;

	font-size: 26rpx;

	color: #8a9290;

	line-height: 1.45;

}



.spot-flea-bridge {

	margin: 8rpx 20rpx 0;

	padding: 24rpx;

	background: #f0faf4;

	border-radius: 20rpx;

	border: 2rpx solid #c8e6d4;

}



.spot-flea-bridge__title {

	display: block;

	font-size: 30rpx;

	font-weight: 700;

	color: #1d3d2e;

}



.spot-flea-bridge__hint {

	display: block;

	margin-top: 8rpx;

	font-size: 24rpx;

	color: #5a7268;

	line-height: 1.45;

	margin-bottom: 16rpx;

}



.spot-flea-bridge__row {

	display: flex;

	flex-direction: row;

	align-items: center;

	justify-content: space-between;

	padding: 22rpx 20rpx;

	background: #fff;

	border-radius: 14rpx;

	margin-top: 12rpx;

}



.spot-flea-bridge__row--press {

	opacity: 0.88;

}



.spot-flea-bridge__label {

	font-size: 28rpx;

	font-weight: 600;

	color: #357a5c;

}



.spot-venue-list {

	padding: 8rpx 0 16rpx;

}



.spot-venue-row {

	display: flex;

	align-items: flex-start;

	padding: 26rpx 28rpx;

	border-top: 1rpx solid #eef2f0;

	box-sizing: border-box;

}



.spot-venue-row:first-child {

	border-top: none;

}



.spot-venue-row--press {

	background: #f6faf7;

}



.spot-venue-pin {

	flex-shrink: 0;

	width: 44rpx;

	padding-top: 4rpx;

	margin-right: 16rpx;

}



.spot-venue-main {

	flex: 1;

	min-width: 0;

	display: flex;

	flex-direction: column;

	gap: 8rpx;

}



.spot-venue-place {

	font-size: 30rpx;

	font-weight: 600;

	color: #1a1a1a;

	line-height: 1.4;

}



.spot-venue-addr {

	font-size: 24rpx;

	color: #6b7370;

	line-height: 1.45;

}



.spot-venue-meta {

	display: flex;

	flex-wrap: wrap;

	gap: 16rpx;

}



.spot-venue-meta-item {

	font-size: 22rpx;

	color: #5a8f72;

}



.spot-venue-action {

	flex-shrink: 0;

	margin-left: 12rpx;

	padding-top: 4rpx;

	font-size: 26rpx;

	font-weight: 600;

	color: #357a5c;

}



.spot-empty {

	padding: 56rpx 28rpx 64rpx;

	text-align: center;

}



.spot-empty-text {

	font-size: 28rpx;

	color: #8a9290;

	line-height: 1.5;

}

</style>

