<template>
	<view class="u-p-30">
		<u-navbar title-color="#fff" :is-back="false" background="transparent" :borderBottom="false">
			<view class="u-m-l-30">
				<u-image width="231" height="64" :src="setting.min_logo"></u-image>
			</view>
		</u-navbar>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
			<!-- <u-image width="100%" height="527" src="/static/index-bg.png"></u-image> -->
		</view>
		<view class="u-p-b-30">
			<u-search placeholder="搜索商品名称" v-model="keyword" border-color="#18AF38" search-icon-color="#18AF38"></u-search>
		</view>
		<u-swiper :list="swiper_list" height="350" mode="none" @click="adLink($event,swiper_list)"></u-swiper>
		<!-- 优惠卷 -->
		<view class="u-m-t-30 u-rela" v-if="coupon_list.length">
			<u-image width="100%" height="210" src="https://oss.ausite.cn/xcxkf052/admin/20250521/b8d12662e66f63e36234f3b08e119731.png?watermark"></u-image>
			<view class="u-abso u-abso-t-0 u-abso-l-0 u-abso-r-0" style="z-index: 1;">
				<view class="u-p-20">
					<view class="u-text-right" @click="$u.route('/pages/coupon/coupon')">
						<u-icon name="arrow-right" color="#D32A0E" label-color="#D32A0E" label="查看更多" size="20" label-size="22" label-pos="left"></u-icon>
					</view>
					<view class="u-m-t-20">
						<scroll-view :scroll-x="true" class="u-width-100" style="white-space: nowrap">
							<view class="u-type-white-bg u-p-20 u-m-r-15 u-radius-6 " v-for="(item,index) in coupon_list" :key="index" style="display: inline-block;">
								<view class="u-flex">
									<view class="u-flex">
										<view class="u-type-error">
											￥
											<text class="u-font-40 u-font-bold">{{item.amount}}</text>
										</view>
										<view class="u-m-l-50">
											<view class="u-font-30 ">
												{{item.name}}
											</view>
											<view class="u-font-24 u-type-info u-m-t-10">
												有效期{{item.indate_day}}天
											</view>
										</view>
									</view>
									<view class="u-m-l-50">
										<u-button type="error" shape="circle" size="mini" @click="receive_coupon(item)" v-if="item.status==0">领取</u-button>
										<u-button type="error" hover-class="none" :customStyle="{background: '#82848a'}" shape="circle" size="mini" v-else>已领取</u-button>
									</view>
								</view>
							</view>
						</scroll-view>
					</view>
				</view>
			</view>
		</view>
		<!--  -->
		<view class="u-m-t-30">
			<scroll-view scroll-x="true">
				<view class="u-flex">
					<view class="u-m-r-20 u-shrink-no" v-for="(item,index) in cate_list" :key="index" @click="cate_btn(index)">
						<u-tag :text="item.cate_name" shape="circle" :bg-color="cate_type==index?'#18AF38':'#fff'" :color="cate_type==index?'#fff':'#666666'" border-color="transparent" />
					</view>
				</view>
			</scroll-view>
		</view>
		<view class="u-m-t-30 u-flex u-row-between u-flex-wrap">
			<view class="u-type-white-bg u-radius-10 u-w-334 u-m-b-30" v-for="(item,index) in goods_list" :key="index" @click="$u.route('/pages/mall/details?id='+item.id)">
				<u-image width="334" height="334" :src="item.thumb"></u-image>
				<view class="u-p-10">
					<view class="u-font-bold u-line-1">
						{{item.goods_name}}
					</view>
					<view class="u-m-t-10" style="color: #18AF38;">
						<text v-if="Number(item.price)">{{item.price}}元</text>
						<text v-if="Number(item.price) && item.point"> + </text>
						<text v-if="item.point">{{item.point}}积分</text>
					</view>
					<view class="u-m-t-10 u-font-bold u-main-c">{{item.shop_info.shop_name}}</view>
				</view>
			</view>
		</view>

		<view class="" style="position: fixed;right: 30rpx;bottom: 15%;z-index: 9;" @click="$u.route('/pages/mall/cart')">
			<u-image width="100" height="100" src="https://oss.ausite.cn/xcxkf052/admin/20250521/0ee9d98c2273efcb512c5ea5abc876f1.png?watermark"></u-image>
		</view>
		<view class="" style="position: fixed;right: 30rpx;bottom: 4%;z-index: 9;" @click="$u.route('/pages/mall/ranking')">
			<u-image width="100" height="100" src="https://oss.ausite.cn/xcxkf052/admin/20250521/4c26a124df730861668dd53ab9842f27.png?watermark"></u-image>
		</view>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	export default {
		data() {
			return {
				swiper_list: [],
				coupon_list: [],
				cate_list: [],
				cate_type: 0,
				goods_list: [],
				page: 1,
				total: 0
			};
		},
		onLoad() {
			this.find_slide()
			this.get_shop_coupon_list()
			this.get_category()
		},
		onShow() {
			if (checkCommunity(this)) return
		},
		methods: {
			// 分类
			get_category() {
				this.$u.api.get_category({
					goods_type: 1
				}).then(res => {
					this.cate_list = res.data
					this.get_goods_list()
				})
			},
			// 切换分类
			cate_btn(index) {
				this.cate_type = index
				this.page = 1
				this.goods_list = []
				this.get_goods_list()
			},
			// 列表
			get_goods_list() {
				this.$u.api.get_goods_list({
					keyword: this.keyword,
					first_cate: this.cate_list[this.cate_type].id,
					page: this.page,
				}).then(res => {
					this.goods_list.push(...res.data.data)
					this.total = res.data.total
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 领取优惠券
			receive_coupon(item) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.api.receive_coupon({
					id: item.id
				}).then(res => {
					item.status = 1
					this.$u.toast(res.msg)
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 优惠卷列表
			get_shop_coupon_list(id) {
				this.$u.api.get_shop_coupon_list({
					page: this.page,
					// exam_id: id
				}).then(res => {
					this.coupon_list.push(...res.data.data)
				})
			},
			// 跳转页面
			navigator(url) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
			},
			// 轮播图跳转
			async adLink(e, item) {
				let data = item[e];
				if (!data.url) return
				if (data.target == 'switchTab') return uni.switchTab({
					url: data.url
				});
				const openUrl = await this.$u.func.checkH5(data.url);
				this.$u.route(openUrl);
			},
			// 轮播图
			find_slide() {
				this.$u.api.find_slide({
					id: 7
				}).then(res => {
					this.swiper_list = res.data
				})
			},
		},
		onReachBottom() {
			if (this.goods_list.length >= this.total) return
			this.page++
			this.get_goods_list()
		}
	}
</script>

<style lang="scss">
	/* 占位：uni-app 空 style 块不生成 wxss，需至少一条规则 */
	.mall-page {}
</style>