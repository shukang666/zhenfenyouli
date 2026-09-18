<template>
	<view class="u-p-30" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
		
		<!-- 关怀版下拉提示 -->
		<view class="care-pull-layer" :style="{ height: carePullHeight + 'px', opacity: careHintOpacity }" v-if="showCareHint">
			<view class="care-pull-content">
<text class="care-pull-text">{{ carePullHeight >= 90 ? '松开进入关怀版' : '下拉进入关怀版' }}</text>
			<u-icon :name="carePullHeight >= 90 ? 'arrow-up' : 'arrow-down'" size="28" color="#18AF38"></u-icon>
			</view>
		</view>
		
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="mall"></skeleton-screen>
		<u-navbar title-color="#fff" :is-back="false" background="transparent" :borderBottom="false">
			<view class="u-m-l-30">
				<u-image width="231" height="64" :src="setting.min_logo"></u-image>
			</view>
		</u-navbar>
		<care-pull-hint :visible="!showCareHint"></care-pull-hint>
		<view class="u-abso u-h-552" style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
			<!-- <u-image width="100%" height="527" src="https://admin.circular-economy.cn/static/index-bg.png"></u-image> -->
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
			<view class="u-type-white-bg u-radius-10 u-w-334 u-m-b-30" v-for="(item,index) in goods_list" :key="index" @click="$u.route('/pages/shop/details?id='+item.id)">
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

		<view class="" style="position: fixed;right: 30rpx;bottom: 180rpx;z-index: 9;" @click="$u.route('/pages/shop/cart')">
			<u-image width="100" height="100" src="https://oss.ausite.cn/xcxkf052/admin/20250521/0ee9d98c2273efcb512c5ea5abc876f1.png?watermark"></u-image>
		</view>
		<view class="" style="position: fixed;right: 30rpx;bottom: 280rpx;z-index: 9;" @click="$u.route('/pages/shop/ranking')">
			<u-image width="100" height="100" src="https://oss.ausite.cn/xcxkf052/admin/20250521/4c26a124df730861668dd53ab9842f27.png?watermark"></u-image>
		</view>
		<!-- tabbar 占位：防止悬浮 tabbar 遮挡底部内容 -->
		<view style="height: 160rpx; flex-shrink: 0;"></view>
		<ai-ask-fab></ai-ask-fab>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	import careGesture from '@/common/care-gesture.mixin.js'
	export default {
		components: {
			SkeletonScreen
		},
		mixins: [careGesture],
		data() {
			return {
				isLoading: true,
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
			this.loadAllData()
		},
		onShow() {
			// 更新自定义tabbar选中态
			this.updateTabBarSelected(3)
			if (checkCommunity(this)) return
		},
		methods: {
		// 并行加载所有数据，全部完成后隐藏骨架屏（最少显示 400ms 防止闪烁）
		async loadAllData() {
			const startTime = Date.now()
			try {
				await Promise.all([
					this.find_slide(),
					this.get_shop_coupon_list(),
					this.get_category()
				])
			} catch (e) {
				console.log('数据加载失败:', e)
			} finally {
				// 确保骨架屏最少显示 400ms，防止加载过快导致闪烁
				const elapsed = Date.now() - startTime
				const minDelay = 400
				if (elapsed < minDelay) {
					setTimeout(() => {
						this.isLoading = false
					}, minDelay - elapsed)
				} else {
					this.isLoading = false
				}
			}
		},
		// 分类
		get_category() {
			return this.$u.api.get_category({
				goods_type: 1
			}).then(res => {
				this.cate_list = res.data || []
				if (this.cate_list.length > 0) {
					return this.get_goods_list()
				}
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
			return this.$u.api.get_goods_list({
				keyword: this.keyword,
				first_cate: this.cate_list[this.cate_type]?.id || 0,
				page: this.page,
			}).then(res => {
				this.goods_list = res.data?.data || []
				this.total = res.data?.total || 0
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
		get_shop_coupon_list() {
			return this.$u.api.get_shop_coupon_list({
				page: this.page,
			}).then(res => {
				this.coupon_list = res.data?.data || []
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
			return this.$u.api.find_slide({
				id: 7
			}).then(res => {
				this.swiper_list = res.data || []
			})
		},
			// 更新自定义tabbar选中索引
			updateTabBarSelected(index) {
				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()
				if (tabBar) {
					tabBar.setSelected(index)
				}
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
	
	/* 关怀版下拉提示层 */
	.care-pull-layer {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		background: linear-gradient(180deg, #E8F5EE 0%, rgba(232, 245, 238, 0.9) 100%);
		display: flex;
		align-items: flex-end;
		justify-content: center;
		padding-bottom: 16rpx;
		z-index: 9998;
		pointer-events: none;
		
		.care-pull-content {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 8rpx;
			
			.care-pull-text {
				font-size: 28rpx;
				color: #18AF38;
				font-weight: 500;
			}
		}
	}
</style>