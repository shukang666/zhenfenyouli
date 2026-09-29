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
				<u-image width="100%" height="210" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250521/b8d12662e66f63e36234f3b08e119731.png"></u-image>
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

			<!-- 地理产区：可折叠，选完自动收起 -->
			<view class="zone-panel" id="guide-mall-cate">
				<u-collapse
					ref="zoneCollapse"
					:accordion="true"
					:arrow="false"
					:head-style="zoneCollapseHeadStyle"
					:body-style="zoneCollapseBodyStyle"
					:item-style="zoneCollapseItemStyle"
				>
					<u-collapse-item :open="zonePanelOpen" name="geo" @change="onZonePanelToggle">
						<view slot="title-all" class="zone-panel__head">
							<view class="zone-panel__head-main">
								<view class="zone-panel__title-wrap">
									<view class="zone-panel__dot"></view>
									<text class="zone-panel__title">地理产区</text>
								</view>
								<text class="zone-panel__summary">{{ zonePanelSummary }}</text>
							</view>
							<u-icon
								class="zone-panel__chevron"
								:class="{ 'zone-panel__chevron--up': zonePanelOpen }"
								name="arrow-down"
								size="28"
								color="#999"
							></u-icon>
						</view>
						<view class="zone-panel__body">
							<text class="zone-panel__hint">点地图大区，再选具体产区</text>
							<mall-china-map :value="mapRegion" @change="onMapRegionChange" />
							<scroll-view
								v-if="regionZoneList.length"
								scroll-x
								class="zone-strip"
								:show-scrollbar="false"
							>
								<view class="zone-strip__inner">
									<view
										class="zone-chip"
										:class="{ 'zone-chip--on': cate_type === item.index }"
										v-for="item in regionZoneList"
										:key="item.id"
										@click="cate_btn(item.index)"
									>
										<text>{{ zoneShortName(item.cate_name) }}</text>
									</view>
								</view>
							</scroll-view>
						</view>
					</u-collapse-item>
				</u-collapse>
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
			<u-image width="100" height="100" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250521/0ee9d98c2273efcb512c5ea5abc876f1.png"></u-image>
		</view>
		<view class="mall-float-btn mall-float-btn--rank" @click="$u.route('/pages/shop/ranking')">
			<u-image width="100" height="100" src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250521/4c26a124df730861668dd53ab9842f27.png"></u-image>
		</view>
		<view class="tabbar-placeholder"></view>
		<page-guide page="mall"></page-guide>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	import MallChinaMap from '@/components/mall-china-map/mall-china-map.vue'
	import { zonesInRegion, zoneShortName, matchZoneToRegion, regionLabel } from '@/common/mallGeoMap.js'
	import careGesture from '@/common/care-gesture.mixin.js'
	export default {
		components: {
			SkeletonScreen,
			MallChinaMap,
		},
		mixins: [careGesture],
		data() {
			return {
				isLoading: true,
				coupon_list: [],
				cate_list: [],
				cate_type: 0,
				mapRegion: 'all',
				goods_list: [],
				page: 1,
				total: 0,
				loadingGoods: false,
				zonePanelOpen: true,
				zoneCollapseHeadStyle: { padding: 0 },
				zoneCollapseBodyStyle: { padding: 0 },
				zoneCollapseItemStyle: { border: 'none' },
			};
		},
		computed: {
			zonePanelSummary() {
				if (this.mapRegion === 'creative') {
					return '综合文创'
				}
				if (this.cate_type <= 0) {
					return '全国 · 全部产区'
				}
				const zone = this.currentZoneLabel
				const region = this.mapRegion && this.mapRegion !== 'all' ? regionLabel(this.mapRegion) : ''
				if (region && zone) {
					return region + ' · ' + zone
				}
				return zone || '已选产区'
			},
			goodsSectionTitle() {
				const name = this.cate_list[this.cate_type]?.cate_name || '全部'
				return name === '全部' ? '全部好物' : name + ' · 特色好物'
			},
			regionZoneList() {
				if (!this.mapRegion || this.mapRegion === 'all') return []
				return zonesInRegion(this.cate_list, this.mapRegion)
			},
			currentZoneLabel() {
				const item = this.cate_list[this.cate_type]
				const name = item && item.cate_name ? item.cate_name : ''
				return name ? zoneShortName(name) : ''
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
				return zoneShortName(name)
			},
			onMapRegionChange(key) {
				this.mapRegion = key
				if (key === 'all') {
					this.cate_btn(0)
					return
				}
				const zones = zonesInRegion(this.cate_list, key)
				if (zones.length) {
					this.cate_btn(zones[0].index)
				} else {
					this.cate_btn(0)
				}
			},
			syncMapFromCateIndex(index) {
				if (index <= 0) {
					this.mapRegion = 'all'
					return
				}
				const name = this.cate_list[index]?.cate_name || ''
				const region = matchZoneToRegion(name)
				this.mapRegion = region ? region : 'all'
			},
			async loadAllData() {
				const startTime = Date.now()
				this.page = 1
				this.goods_list = []
				try {
					await this.fetchMallTabHome({ page: 1 })
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
					this.$nextTick(() => this.refreshZoneCollapseHeight())
				}
			},
			fetchMallTabHome({ page = 1, secondCate = 0 } = {}) {
				const zoneId = secondCate || (this.cate_list[this.cate_type]?.id || 0)
				const params = {
					page,
					keyword: this.keyword || ''
				}
				if (zoneId) params.second_cate = zoneId
				return this.$u.api.get_mall_tab_home(params).then(res => {
					const d = res.data || {}
					if (Object.prototype.hasOwnProperty.call(d, 'coupon_list')) {
						this.coupon_list = d.coupon_list || []
					}
					if (Object.prototype.hasOwnProperty.call(d, 'cate_list')) {
						this.cate_list = [{ id: 0, cate_name: '全部' }, ...(d.cate_list || [])]
					}
					const list = d.goods_list || []
					if (page === 1) {
						this.goods_list = list
					} else {
						this.goods_list = this.goods_list.concat(list)
					}
					this.total = d.goods_total || 0
					this.page = page
				}).catch(err => {
					this.$u.toast(err.msg)
				}).finally(() => {
					this.loadingGoods = false
				})
			},
			get_category() {
				this.page = 1
				this.goods_list = []
				return this.fetchMallTabHome({ page: 1 })
			},
			onZonePanelToggle(e) {
				this.zonePanelOpen = !!e.show
				if (e.show) {
					this.refreshZoneCollapseHeight()
				}
			},
			refreshZoneCollapseHeight() {
				this.$nextTick(() => {
					const collapse = this.$refs.zoneCollapse
					if (collapse && collapse.init) {
						collapse.init()
					}
				})
			},
			collapseZonePanel() {
				this.zonePanelOpen = false
			},
			cate_btn(index) {
				const changed = this.cate_type !== index
				this.cate_type = index
				this.syncMapFromCateIndex(index)
				if (changed) {
					this.page = 1
					this.goods_list = []
					this.get_goods_list()
				}
				this.$nextTick(() => {
					this.collapseZonePanel()
				})
			},
			get_goods_list() {
				if (this.loadingGoods) return Promise.resolve()
				this.loadingGoods = true
				const zoneId = this.cate_list[this.cate_type]?.id || 0
				return this.fetchMallTabHome({
					page: this.page,
					secondCate: zoneId
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
				return this.fetchMallTabHome({ page: 1 })
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
		padding: 8rpx 24rpx 8rpx;
		box-shadow: 0 8rpx 32rpx rgba(24, 175, 56, 0.12);
		margin-bottom: 30rpx;
	}

	.zone-panel__head {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 20rpx 0;
		width: 100%;
	}

	.zone-panel__head-main {
		flex: 1;
		min-width: 0;
		padding-right: 16rpx;
	}

	.zone-panel__title-wrap {
		display: flex;
		align-items: center;
		margin-bottom: 8rpx;
	}

	.zone-panel__summary {
		display: block;
		font-size: 24rpx;
		color: #18af38;
		padding-left: 20rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.zone-panel__chevron {
		flex-shrink: 0;
		transition: transform 0.3s ease;
		margin-right: 4rpx;
	}

	.zone-panel__chevron--up {
		transform: rotate(180deg);
	}

	.zone-panel__body {
		padding-bottom: 16rpx;
	}

	.zone-panel__hint {
		display: block;
		margin-bottom: 16rpx;
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

	.zone-panel .u-collapse-head {
		padding: 0 !important;
	}

	.zone-panel .u-collapse-body {
		transition: height 0.32s ease;
	}

	.zone-strip {
		margin-top: 20rpx;
		width: 100%;
		white-space: nowrap;
	}

	.zone-strip__inner {
		display: inline-flex;
		padding: 4rpx 0 8rpx;
		gap: 16rpx;
	}

	.zone-chip {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 14rpx 28rpx;
		border-radius: 999rpx;
		background: #f5f9f6;
		border: 2rpx solid #e8f0ea;
		font-size: 24rpx;
		color: #444;
		flex-shrink: 0;
	}

	.zone-chip--on {
		background: linear-gradient(135deg, #2fb74c 0%, #18af38 100%);
		border-color: transparent;
		color: #fff;
		font-weight: 600;
		box-shadow: 0 6rpx 16rpx rgba(24, 175, 56, 0.28);
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
		height: 172rpx;
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
