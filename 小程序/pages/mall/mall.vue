<template>
	<view class="mall-page glass-page" @touchstart="onCareTouchStart" @touchmove="onCareTouchMove" @touchend="onCareTouchEnd">
		<view class="glass-page__ambient"></view>
		
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
			<view class="points-nav">
				<text class="points-nav__title">积分权益</text>
			</view>
			<view slot="right" class="points-nav__actions">
				<view class="points-nav__action" hover-class="points-nav__action--press" @tap="goPointsLog">
					<u-icon name="order" size="40" color="#2d4a3e"></u-icon>
				</view>
				<view class="points-nav__action" hover-class="points-nav__action--press" @tap="goCart">
					<u-icon name="shopping-cart" size="40" color="#2d4a3e"></u-icon>
				</view>
			</view>
		</u-navbar>
		<care-pull-hint :visible="!showCareHint"></care-pull-hint>
		<view class="mall-body glass-page__content">
			<view class="points-hub" id="guide-mall-points">
				<view class="points-hub__card">
					<text class="points-hub__label">我的可用积分</text>
					<view class="points-hub__value-row">
						<text class="points-hub__value">{{ displayPointText }}</text>
						<text class="points-hub__unit">积分</text>
					</view>
					<view class="points-hub__foot">
						<text class="points-hub__slogan">环保行动，让积分发挥价值</text>
						<view class="points-hub__link" hover-class="points-hub__link--press" @tap="goPointsLog">
							<text>积分明细</text>
							<u-icon name="arrow-right" size="24" color="#076d3d"></u-icon>
						</view>
					</view>
				</view>
			</view>
			<view class="u-p-30 mall-body-inner">
			<view class="mall-search-wrap glass-search" id="guide-mall-search">
				<u-search
					placeholder="搜索商品名称"
					v-model="keyword"
					border-color="transparent"
					search-icon-color="#18AF38"
					bg-color="#ffffff"
					height="72"
					:show-action="false"
				></u-search>
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

			<!-- 分类：一行切换「全部 / 产区地图 / 商品类型」 -->
			<view class="mall-cate" id="guide-mall-cate">
				<scroll-view scroll-x class="mall-cate__primary" :show-scrollbar="false">
					<view class="mall-cate__primary-inner">
						<view
							class="mall-cate-pill"
							:class="{ 'mall-cate-pill--on': primaryKey === tab.key }"
							v-for="tab in primaryTabs"
							:key="tab.key"
							hover-class="mall-cate-pill--press"
							@tap="onPrimaryTab(tab)"
						>
							<text>{{ tab.label }}</text>
						</view>
					</view>
				</scroll-view>
				<text class="mall-cate__hint">{{ primaryHint }}</text>

				<view class="mall-cate__geo" v-if="primaryKey === 'geo'">
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
					<text class="mall-cate__geo-summary">{{ zonePanelSummary }}</text>
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
						<u-image width="100%" height="320" mode="aspectFill" :src="item.thumb"></u-image>
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
				first_cate_list: [],
				cate_type: 0,
				primaryKey: 'all',
				first_cate_id: 0,
				mapRegion: 'all',
				goods_list: [],
				page: 1,
				total: 0,
				loadingGoods: false,
				keyword: '',
			};
		},
		computed: {
			primaryTabs() {
				const tabs = [
					{ key: 'all', label: '全部', mode: 'all' },
					{ key: 'geo', label: '产区地图', mode: 'geo' },
				]
				;(this.first_cate_list || []).forEach(item => {
					if (!item || !item.id) return
					tabs.push({
						key: 'first-' + item.id,
						label: item.cate_name,
						mode: 'first',
						firstId: item.id,
					})
				})
				return tabs
			},
			primaryHint() {
				if (this.primaryKey === 'geo') {
					return '点地图选大区，或选下方具体产区'
				}
				if (this.first_cate_id > 0) {
					const name = this.first_cate_list.find(c => Number(c.id) === Number(this.first_cate_id))?.cate_name || '该类型'
					return '当前浏览：' + name + ' · 列表筛选'
				}
				return '浏览全部可兑换好物'
			},
			displayPointText() {
				if (!this.isLogin) {
					return '--'
				}
				const n = Number(this.userInfo && this.userInfo.point)
				const val = Number.isFinite(n) ? Math.max(0, Math.floor(n)) : 0
				return String(val).replace(/\B(?=(\d{3})+(?!\d))/g, ',')
			},
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
				if (this.primaryKey === 'geo') {
					const name = this.cate_list[this.cate_type]?.cate_name || '全部'
					return name === '全部' ? '产区好物' : name + ' · 特色好物'
				}
				if (this.first_cate_id > 0) {
					const fc = this.first_cate_list.find(c => Number(c.id) === Number(this.first_cate_id))
					return (fc && fc.cate_name) ? fc.cate_name + ' · 精选' : '分类好物'
				}
				return '全部好物'
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
			this.refreshUserPoint()
		},
		methods: {
			refreshUserPoint() {
				if (!this.isLogin) return
				this.$u.api.getUserInfo().then(res => {
					if (res.data) {
						this.$u.vuex('userInfo', res.data)
					}
				}).catch(() => {})
			},
			goPointsLog() {
				if (!this.$u.func.checkLogin()) return
				this.$u.route('/pages/points/points')
			},
			goCart() {
				if (!this.$u.func.checkLogin()) return
				this.$u.route('/pages/shop/cart')
			},
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
				}
			},
			fetchMallTabHome({ page = 1, secondCate = 0, firstCate = 0 } = {}) {
				const zoneId = this.primaryKey === 'geo'
					? (secondCate || (this.cate_list[this.cate_type]?.id || 0))
					: 0
				const firstId = this.primaryKey.startsWith('first-')
					? (firstCate || this.first_cate_id || 0)
					: 0
				const params = {
					page,
					keyword: this.keyword || ''
				}
				if (zoneId) params.second_cate = zoneId
				if (firstId) params.first_cate = firstId
				return this.$u.api.get_mall_tab_home(params).then(res => {
					const d = res.data || {}
					if (Object.prototype.hasOwnProperty.call(d, 'coupon_list')) {
						this.coupon_list = d.coupon_list || []
					}
					if (Object.prototype.hasOwnProperty.call(d, 'cate_list')) {
						this.cate_list = [{ id: 0, cate_name: '全部' }, ...(d.cate_list || [])]
					}
					if (Object.prototype.hasOwnProperty.call(d, 'first_cate_list')) {
						this.first_cate_list = d.first_cate_list || []
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
			onPrimaryTab(tab) {
				if (!tab || tab.key === this.primaryKey) return
				this.primaryKey = tab.key
				this.cate_type = 0
				this.mapRegion = 'all'
				if (tab.mode === 'first') {
					this.first_cate_id = tab.firstId || 0
				} else {
					this.first_cate_id = 0
				}
				this.page = 1
				this.goods_list = []
				this.get_goods_list()
			},
			cate_btn(index) {
				if (this.primaryKey !== 'geo') {
					this.primaryKey = 'geo'
					this.first_cate_id = 0
				}
				const changed = this.cate_type !== index
				this.cate_type = index
				this.syncMapFromCateIndex(index)
				if (changed) {
					this.page = 1
					this.goods_list = []
					this.get_goods_list()
				}
			},
			get_goods_list() {
				if (this.loadingGoods) return Promise.resolve()
				this.loadingGoods = true
				const zoneId = this.cate_list[this.cate_type]?.id || 0
				return this.fetchMallTabHome({
					page: this.page,
					secondCate: zoneId,
					firstCate: this.first_cate_id,
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
	@import '@/static/styles/glass-ui.scss';

	.mall-page {
		position: relative;
	}

	.mall-body {
		position: relative;
	}

	.mall-body-inner {
		padding-top: 0;
	}

	.points-nav {
		padding-left: 30rpx;
		display: flex;
		align-items: center;
		height: 100%;
	}

	.points-nav__title {
		font-size: 40rpx;
		font-weight: 700;
		color: #1d1d1f;
		letter-spacing: 1rpx;
	}

	.points-nav__actions {
		display: flex;
		align-items: center;
		gap: 8rpx;
		padding-right: 24rpx;
	}

	.points-nav__action {
		width: 72rpx;
		height: 72rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 50%;
	}

	.points-nav__action--press {
		background: rgba(45, 74, 62, 0.08);
	}

	.points-hub {
		padding: 8rpx 30rpx 24rpx;
	}

	.points-hub__card {
		background: #fff;
		border-radius: 24rpx;
		padding: 32rpx 32rpx 28rpx;
		box-shadow: 0 8rpx 32rpx rgba(7, 109, 61, 0.06);
		border: 1rpx solid rgba(255, 255, 255, 0.9);
	}

	.points-hub__label {
		display: block;
		font-size: 26rpx;
		color: #8e8e93;
		line-height: 1.4;
	}

	.points-hub__value-row {
		display: flex;
		align-items: baseline;
		margin-top: 12rpx;
		gap: 8rpx;
	}

	.points-hub__value {
		font-size: 72rpx;
		font-weight: 700;
		color: #076d3d;
		line-height: 1.1;
		font-variant-numeric: tabular-nums;
		letter-spacing: -1rpx;
	}

	.points-hub__unit {
		font-size: 30rpx;
		font-weight: 600;
		color: #076d3d;
		opacity: 0.92;
	}

	.points-hub__foot {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-top: 28rpx;
		padding-top: 24rpx;
		border-top: 1rpx solid #f0f2f1;
		gap: 20rpx;
	}

	.points-hub__slogan {
		flex: 1;
		min-width: 0;
		font-size: 26rpx;
		font-weight: 600;
		color: #076d3d;
		line-height: 1.45;
	}

	.points-hub__link {
		flex-shrink: 0;
		display: flex;
		align-items: center;
		gap: 4rpx;
		font-size: 26rpx;
		font-weight: 600;
		color: #076d3d;
	}

	.points-hub__link--press {
		opacity: 0.72;
	}

	.mall-search-wrap {
		position: relative;
		z-index: 4;
		padding: 12rpx 8rpx 28rpx;
		margin-bottom: 8rpx;
		background: transparent;
	}

	.mall-cate {
		position: relative;
		z-index: 2;
		padding: 24rpx 12rpx 20rpx;
		margin-bottom: 30rpx;
		background: #fff;
		border-radius: 24rpx;
		box-shadow: 0 8rpx 32rpx rgba(7, 109, 61, 0.05);
		border: 1rpx solid rgba(232, 238, 235, 0.95);
	}

	.mall-cate__primary {
		width: 100%;
		white-space: nowrap;
	}

	.mall-cate__primary-inner {
		display: inline-flex;
		padding: 0 16rpx 4rpx;
		gap: 16rpx;
	}

	.mall-cate-pill {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 16rpx 32rpx;
		border-radius: 999rpx;
		background: #f2f4f3;
		font-size: 28rpx;
		color: #3a3a3c;
		flex-shrink: 0;
		transition: background 0.2s ease, color 0.2s ease;
	}

	.mall-cate-pill--on {
		background: #e3f3ea;
		color: #076d3d;
		font-weight: 600;
	}

	.mall-cate-pill--press {
		opacity: 0.85;
	}

	.mall-cate__hint {
		display: block;
		padding: 12rpx 24rpx 0;
		font-size: 24rpx;
		color: #8e8e93;
		line-height: 1.45;
	}

	.mall-cate__geo {
		padding: 8rpx 12rpx 4rpx;
	}

	.mall-cate__geo-summary {
		display: block;
		margin-top: 16rpx;
		padding: 12rpx 16rpx;
		font-size: 24rpx;
		color: #076d3d;
		font-weight: 500;
		background: #f3faf6;
		border-radius: 12rpx;
	}

	.goods-section {
		@include glass-sheet;
		margin-top: 8rpx;
		padding: 24rpx 20rpx 8rpx;
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
		background: #e3f3ea;
		border-color: #c5e6d2;
		color: #076d3d;
		font-weight: 600;
		box-shadow: none;
	}

	.goods-section__head {
		display: flex;
		align-items: baseline;
		justify-content: space-between;
		margin-bottom: 24rpx;
	}

	.goods-section__title {
		font-size: 30rpx;
		font-weight: 600;
		color: $glass-text;
	}

	.goods-section__count {
		font-size: 24rpx;
		color: $glass-text-tertiary;
	}

	.goods-grid {
		display: flex;
		flex-wrap: wrap;
		align-content: flex-start;
		gap: 24rpx 16rpx;
	}

	.goods-card {
		@include glass-surface(0.62, 40px);
		box-sizing: border-box;
		width: calc((100% - 16rpx) / 2);
		flex: 0 0 calc((100% - 16rpx) / 2);
		border-radius: 20rpx;
		overflow: hidden;
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
</style>
