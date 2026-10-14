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



		<view class="shi-card">

			<text class="shi-card__title">一句话说明</text>

			<text class="shi-card__p">

				您可以在小程序里浏览邻居的闲置、发布自己的闲置并标积分价；真正换货、交物品，请到

				<text class="shi-em">「七贤岭绿色二手市集」</text>

				活动现场，由工作人员统一组织，不在线上自行完成交接。

			</text>

		</view>



		<view class="shi-card">

			<text class="shi-card__title">怎么参与（三步）</text>

			<view class="shi-step" v-for="(s, idx) in steps" :key="idx">

				<text class="shi-step__num">{{ idx + 1 }}</text>

				<view class="shi-step__body">

					<text class="shi-step__t">{{ s.title }}</text>

					<text class="shi-step__d">{{ s.desc }}</text>

				</view>

			</view>

		</view>



		<view class="shi-card shi-card--warn">

			<text class="shi-card__title">请您留意</text>

			<text class="shi-li" v-for="(line, idx) in notices" :key="idx">{{ line }}</text>

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



export default {

	data() {

		return {

			introHeroUrl: INTRO_HERO_URL,

			steps: [

				{

					title: '线上：看闲置或发布',

					desc: '在「社区闲置」里搜索、浏览他人发布；登录后可上传照片、填写说明和积分价，发布自己的物品。',

				},

				{

					title: '线上：和对方约好换什么',

					desc: '看中某件物品后，先通过沟通确认要交换的闲置与积分价（正式版将提供预约记录；当前演示版请自行联系发布者）。',

				},

				{

					title: '线下：到市集现场交接',

					desc: '按「绿色二手市集」公布的场次，携带物品到指定场地，由工作人员统一组织交换，完成物品与积分的确认。',

				},

			],

			notices: [

				'· 不支持居民在私下地点自行完成「官方积分」交割，请到市集现场办理。',

				'· 市集为定期活动，场次以活动页公布为准，去之前请先查看时间与地址。',

				'· 发布需审核通过后才会在列表展示。',

				'· 与官方商城商品无关；商城兑换仍走订单与快递/自提流程。',

			],

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



.shi-card {

	margin-top: 24rpx;

	background: #fff;

	border-radius: 20rpx;

	padding: 28rpx;

	box-shadow: 0 8rpx 24rpx rgba(7, 109, 61, 0.06);

}



.shi-card--warn {

	background: #fffef8;

	border: 2rpx solid #f0e6c8;

}



.shi-card__title {

	display: block;

	font-size: 32rpx;

	font-weight: 700;

	color: #1d1d1f;

	margin-bottom: 16rpx;

}



.shi-card__p {

	display: block;

	font-size: 28rpx;

	color: #444;

	line-height: 1.65;

}



.shi-em {

	color: #076d3d;

	font-weight: 600;

}



.shi-step {

	display: flex;

	flex-direction: row;

	align-items: flex-start;

	margin-top: 20rpx;

}



.shi-step:first-of-type {

	margin-top: 0;

}



.shi-step__num {

	width: 44rpx;

	height: 44rpx;

	border-radius: 50%;

	background: #e3f3ea;

	color: #076d3d;

	font-size: 26rpx;

	font-weight: 700;

	text-align: center;

	line-height: 44rpx;

	flex-shrink: 0;

	margin-right: 16rpx;

}



.shi-step__body {

	flex: 1;

}



.shi-step__t {

	display: block;

	font-size: 30rpx;

	font-weight: 600;

	color: #1d1d1f;

}



.shi-step__d {

	display: block;

	margin-top: 8rpx;

	font-size: 26rpx;

	color: #666;

	line-height: 1.55;

}



.shi-li {

	display: block;

	font-size: 26rpx;

	color: #5c4a32;

	line-height: 1.6;

	margin-top: 10rpx;

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

