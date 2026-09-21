<template>
	<view class="mall-page" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
		
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
		<view class="mall-bg"></view>

		<view class="u-p-30 mall-body">
			<view class="u-p-b-20" id="guide-mall-search">
				<u-search placeholder="搜索商品名称" v-model="keyword" border-color="#18AF38" search-icon-color="#18AF38" bg-color="#fff"></u-search>
			</view>

			<!-- 优惠券 -->
			<view class="u-m-b-30 u-rela" v-if="coupon_list.length">
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

			<!-- 地理产区：4 列网格，一屏可见 -->
			<view class="zone-panel" id="guide-mall-cate">
				<view class="zone-panel__head">
					<view class="zone-panel__title-wrap">
						<view class="zone-panel__dot"></view>
						<text class="zone-panel__title">地理产区</text>
					</view>
					<text class="zone-panel__hint">点选产区，查看当地特色好物</text>
				</view>
				<view class="zone-grid">
					<view
						class="zone-grid__item"
						:class="{'zone-grid__item--on': cate_type === index}"
						v-for="(item,index) in cate_list"
						:key="item.id || index"
						@click="cate_btn(index)"
					>
						<text class="zone-grid__text">{{ zoneShortName(item.cate_name) }}</text>
					</view>
				</view>
			</view>

			<!-- 商品列表 -->
			<view class="goods-section" id="guide-mall-goods">
				<view class="goods-section__head">
					<text class="goods-section__title">{{ goodsSectionTitle }}</text>
					<text class="goods-section__count" v-if="total">共 {{ total }} 件</text>
				</view>
				<view class="goods-grid" v-if="goods_list.length">
					<view class="goods-card" v-for="(item,index) in goods_list" :key="item.id || index" @click="$u.route('/pages/shop/details?id='+item.id)">
						<u-image width="334" height="334" :src="item.thumb"></u-image>
						<view class="goods-card__body">
							<view class="goods-card__zone" v-if="item.second_name">{{ item.second_name }}</view>
							<view class="goods-card__name u-line-2">{{ item.goods_name }}</view>
							<view class="goods-card__price">
								<text v-if="Number(item.price)">{{ item.price }}元</text>
								<text v-if="Number(item.price) && item.point"> + </text>
								<text v-if="item.point">{{ item.point }}积分</text>
							</view>
							<view class="goods-card__shop u-line-1">{{ item.shop_info.shop_name }}</view>
						</view>
					</view>
				</view>
				<view class="goods-empty" v-else-if="!loadingGoods">
					<u-icon name="shopping-cart" size="80" color="#C8C9CC"></u-icon>
					<text class="goods-empty__text">该产区暂无商品</text>
				</view>
			</view>
		</view>

		<view class="mall-float-btn" @click="$u.route('/pages/shop/cart')">
			<u-image width="100" height="100" src="https://oss.ausite.cn/xcxkf052/admin/20250521/0ee9d98c2273efcb512c5ea5abc876f1.png?watermark"></u-image>
		</view>
		<view class="mall-float-btn mall-float-btn--rank" @click="$u.route('/pages/shop/ranking')">
			<u-image width="100" height="100" src="https://oss.ausite.cn/xcxkf052/admin/20250521/4c26a124df730861668dd53ab9842f27.png?watermark"></u-image>
		</view>
		<view class="tabbar-placeholder"></view>
		<page-guide page="mall"></page-guide>
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
				coupon_list: [],
				cate_list: [],
				cate_type: 0,
				goods_list: [],
				page: 1,
				total: 0,
				loadingGoods: false,
			};
		},
		computed: {
			goodsSectionTitle() {
				const name = this.cate_list[this.cate_type]?.cate_name || '全部'
				return name === '全部' ? '全部好物' : name + ' · 特色好物'
			},
		},
		onLoad() {
			this.loadAllData()
		},
		onShow() {
			this.updateTabBarSelected(3)
			if (checkCommunity(this)) return
		},
		methods: {
			zoneShortName(name) {
				const map = {
					'全部': '全部',
					'东北山地林区': '东北山地',
					'东北平原黑土农区': '东北平原',
					'辽西粗粮干果区': '辽西粗粮',
					'辽东半岛冷水海鲜区': '辽东海鲜',
					'黄淮海平原农区': '黄淮海',
					'沂蒙丘陵农林区': '沂蒙丘陵',
					'黄土高原粗粮区': '黄土高原',
					'秦岭果蔬区': '秦岭果蔬',
					'河套-河西走廊农区': '河西走廊',
					'哈密盆地果蔬区': '哈密盆地',
					'内蒙古、新疆草原牧区': '草原牧区',
					'江南水产茶果区': '江南茶果',
					'四川天府农区': '四川天府',
					'云贵高原山珍区': '云贵山珍',
					'闽粤海鲜区': '闽粤海鲜',
					'海南热带水产鲜果区': '海南热带',
					'青藏高原农区': '青藏高原',
					'综合文创': '综合文创',
				}
				return map[name] || name
			},
			async loadAllData() {
				const startTime = Date.now()
				try {
					await Promise.all([
						this.get_shop_coupon_list(),
						this.get_category()
					])
				} catch (e) {
					console.log('数据加载失败:', e)
				} finally {
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
			get_category() {
				return this.$u.api.get_category({
					geo_zone: 1
				}).then(res => {
					this.cate_list = [{ id: 0, cate_name: '全部' }, ...(res.data || [])]
					return this.get_goods_list()
				})
			},
			cate_btn(index) {
				if (this.cate_type === index) return
				this.cate_type = index
				this.page = 1
				this.goods_list = []
				this.get_goods_list()
			},
			get_goods_list() {
				if (this.loadingGoods) return Promise.resolve()
				this.loadingGoods = true
				const params = {
					keyword: this.keyword,
					page: this.page,
				}
				const zoneId = this.cate_list[this.cate_type]?.id || 0
				if (zoneId) {
					params.second_cate = zoneId
				}
				return this.$u.api.get_goods_list(params).then(res => {
					const list = res.data?.data || []
					if (this.page === 1) {
						this.goods_list = list
					} else {
						this.goods_list = this.goods_list.concat(list)
					}
					this.total = res.data?.total || 0
				}).catch(err => {
					this.$u.toast(err.msg)
				}).finally(() => {
					this.loadingGoods = false
				})
			},
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
			get_shop_coupon_list() {
				return this.$u.api.get_shop_coupon_list({
					page: 1,
				}).then(res => {
					this.coupon_list = res.data?.data || []
				})
			},
			updateTabBarSelected(index) {
				const tabBar = this.$mp?.page?.getTabBar && this.$mp.page.getTabBar()
				if (tabBar) {
					tabBar.setSelected(index)
				}
			},
		},
		onReachBottom() {
			if (this.loadingGoods) return
			if (this.goods_list.length >= this.total) return
			this.page++
			this.get_goods_list()
		}
	}
</script>

<style lang="scss">
	.mall-page {
		min-height: 100vh;
		position: relative;
	}

	.mall-bg {
		position: absolute;
		z-index: -1;
		top: 0;
		left: 0;
		right: 0;
		height: 420rpx;
		background: linear-gradient(to bottom, #2fb74c, #EFF9F1);
	}

	.mall-body {
		position: relative;
		z-index: 1;
	}

	.zone-panel {
		background: #fff;
		border-radius: 24rpx;
		padding: 28rpx 24rpx 24rpx;
		box-shadow: 0 8rpx 32rpx rgba(24, 175, 56, 0.12);
		margin-bottom: 30rpx;
	}

	.zone-panel__head {
		margin-bottom: 24rpx;
	}

	.zone-panel__title-wrap {
		display: flex;
		align-items: center;
		margin-bottom: 8rpx;
	}

	.zone-panel__dot {
		width: 8rpx;
		height: 32rpx;
		border-radius: 4rpx;
		background: #18AF38;
		margin-right: 12rpx;
	}

	.zone-panel__title {
		font-size: 34rpx;
		font-weight: 700;
		color: #222;
	}

	.zone-panel__hint {
		font-size: 24rpx;
		color: #999;
		padding-left: 20rpx;
	}

	.zone-grid {
		display: flex;
		flex-wrap: wrap;
		margin: -8rpx;
	}

	.zone-grid__item {
		width: 25%;
		padding: 8rpx;
		box-sizing: border-box;
	}

	.zone-grid__item--on .zone-grid__text {
		background: linear-gradient(135deg, #2fb74c 0%, #18AF38 100%);
		color: #fff;
		border-color: transparent;
		font-weight: 600;
		box-shadow: 0 6rpx 16rpx rgba(24, 175, 56, 0.28);
	}

	.zone-grid__text {
		display: flex;
		align-items: center;
		justify-content: center;
		min-height: 88rpx;
		padding: 12rpx 8rpx;
		background: #F5F9F6;
		border: 2rpx solid #E8F0EA;
		border-radius: 16rpx;
		font-size: 24rpx;
		line-height: 1.35;
		color: #444;
		text-align: center;
		box-sizing: border-box;
	}

	.goods-section__head {
		display: flex;
		align-items: baseline;
		justify-content: space-between;
		margin-bottom: 24rpx;
	}

	.goods-section__title {
		font-size: 32rpx;
		font-weight: 700;
		color: #222;
	}

	.goods-section__count {
		font-size: 24rpx;
		color: #999;
	}

	.goods-grid {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
	}

	.goods-card {
		width: 334rpx;
		margin-bottom: 30rpx;
		background: #fff;
		border-radius: 20rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05);
	}

	.goods-card__body {
		padding: 16rpx;
	}

	.goods-card__zone {
		display: inline-block;
		max-width: 100%;
		padding: 4rpx 12rpx;
		margin-bottom: 10rpx;
		background: #FDF6EC;
		border-radius: 8rpx;
		font-size: 20rpx;
		color: #8B6914;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.goods-card__name {
		font-size: 28rpx;
		font-weight: 600;
		color: #222;
		line-height: 1.4;
		min-height: 78rpx;
	}

	.goods-card__price {
		margin-top: 12rpx;
		font-size: 28rpx;
		font-weight: 700;
		color: #18AF38;
	}

	.goods-card__shop {
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #999;
	}

	.goods-empty {
		display: flex;
		flex-direction: column;
		align-items: center;
		padding: 80rpx 0 60rpx;
	}

	.goods-empty__text {
		margin-top: 20rpx;
		font-size: 28rpx;
		color: #999;
	}

	.mall-float-btn {
		position: fixed;
		right: 30rpx;
		bottom: 180rpx;
		z-index: 9;
	}

	.mall-float-btn--rank {
		bottom: 280rpx;
	}

	.tabbar-placeholder {
		height: 160rpx;
		flex-shrink: 0;
	}
	
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
