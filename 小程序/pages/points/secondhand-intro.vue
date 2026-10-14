<template>

	<view class="shi-page">

		<view class="shi-hero">

			<image
				class="shi-hero__img"
				:src="introHeroUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-summary">

			<image
				class="shi-summary__img"
				:src="introSummaryUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-steps">

			<image
				class="shi-steps__img"
				:src="introStepsUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-notice">

			<image
				class="shi-notice__img"
				:src="introNoticeUrl"
				mode="widthFix"
				show-menu-by-longpress="false"
			></image>

		</view>



		<view class="shi-actions">

			<view class="shi-btn shi-btn--primary" hover-class="shi-btn--press" @tap="goFleaHub">

				<text class="shi-btn__t">查看市集活动时间与地点</text>

				<text class="shi-btn__sub">进入活动 · 绿色二手集市</text>

			</view>

			<view class="shi-btn shi-btn--ghost" hover-class="shi-btn--press" @tap="goList">

				<text class="shi-btn__t">浏览社区闲置</text>

			</view>

			<view class="shi-btn shi-btn--ghost" hover-class="shi-btn--press" @tap="goPublish">

				<text class="shi-btn__t">发布我的闲置</text>

			</view>

		</view>



		<text class="shi-foot">官方积分商城兑换商品请仍在「积分权益」里下单；本功能仅针对居民之间的闲置交换。</text>

	</view>

</template>



<script>

import settings from '@/common/settings.js'

import {

	fleaMarketHubUrl,

	secondhandListUrl,

	secondhandPublishUrl,

} from '@/common/secondhandNav.js'



const INTRO_HERO_URL = settings.staticAssetBase + 'mall/secondhand-intro-hero.png'

const INTRO_NOTICE_URL = settings.staticAssetBase + 'mall/secondhand-intro-notice.png'

const INTRO_SUMMARY_URL = settings.staticAssetBase + 'mall/secondhand-intro-summary.png'

const INTRO_STEPS_URL = settings.staticAssetBase + 'mall/secondhand-intro-steps.png'



export default {

	data() {

		return {

			introHeroUrl: INTRO_HERO_URL,

			introNoticeUrl: INTRO_NOTICE_URL,

			introSummaryUrl: INTRO_SUMMARY_URL,

			introStepsUrl: INTRO_STEPS_URL,

		}

	},

	methods: {

		goFleaHub() {

			this.$u.route(fleaMarketHubUrl())

		},

		goList() {

			this.$u.route(secondhandListUrl())

		},

		goPublish() {

			if (!this.$u.func.checkLogin()) return

			this.$u.route(secondhandPublishUrl())

		},

	},

}

</script>



<style lang="scss" scoped>

.shi-page {

	min-height: 100vh;

	background: #e4e8e5;

	padding: 24rpx 30rpx calc(40rpx + env(safe-area-inset-bottom));

	box-sizing: border-box;

}



.shi-hero {

	border-radius: 24rpx;

	overflow: hidden;

	background: #076d3d;

}



.shi-hero__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-summary {

	margin-top: 24rpx;

	border-radius: 20rpx;

	overflow: hidden;

}



.shi-summary__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-steps {

	margin-top: 24rpx;

	border-radius: 20rpx;

	overflow: hidden;

}



.shi-steps__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-notice {

	margin-top: 24rpx;

	border-radius: 20rpx;

	overflow: hidden;

}



.shi-notice__img {

	width: 100%;

	display: block;

	vertical-align: top;

}



.shi-actions {

	margin-top: 32rpx;

	display: flex;

	flex-direction: column;

	gap: 20rpx;

}



.shi-btn {

	border-radius: 20rpx;

	padding: 28rpx 32rpx;

}



.shi-btn--primary {

	background: #18af38;

}



.shi-btn--ghost {

	background: #fff;

	border: 2rpx solid #c8e6d4;

}



.shi-btn--press {

	opacity: 0.88;

}



.shi-btn__t {

	display: block;

	font-size: 32rpx;

	font-weight: 700;

	color: #fff;

	text-align: center;

}



.shi-btn--ghost .shi-btn__t {

	color: #076d3d;

}



.shi-btn__sub {

	display: block;

	margin-top: 8rpx;

	font-size: 24rpx;

	color: rgba(255, 255, 255, 0.88);

	text-align: center;

}



.shi-foot {

	display: block;

	margin-top: 28rpx;

	font-size: 24rpx;

	color: #8e8e93;

	line-height: 1.55;

	text-align: center;

	padding: 0 12rpx;

}

</style>

