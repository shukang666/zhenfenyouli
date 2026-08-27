<template>
	<view class="container">
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true'>
			<!-- 顶部导航栏：仅展示 logo，无返回按钮 -->
			<template slot="top">
				<u-navbar :is-back="false" title="" title-color="rgba(0,0,0,0)" :background="{background:'#ffffff'}" :border-bottom="false">
					<image src="/static/images/logo.png" class="u-w-320 u-h-88 u-m-l-30" mode="aspectFill"></image>
				</u-navbar>
			</template>

			<!-- Banner 轮播图 -->
			<view class="banner-wrap">
				<u-swiper :list="bannerList" height="340" border-radius="24" @click="adLink" bg-color="rgba(0,0,0,0)"></u-swiper>
			</view>

			<!-- 核心功能入口：四宫格大图，两行两列，加圆角和阴影提升卡片质感 -->
			<view class="entry-grid">
				<view class="entry-item" @click="$u.route({type:'switchTab',url:'/pages/low_carbon/low_carbon'})">
					<image src="/static/images/ditanshenghuo.png" mode="aspectFill" class="entry-img" lazy-load></image>
				</view>
				<view class="entry-item" @click="$u.route({type:'switchTab',url:'/pages/health/health'})">
					<image src="/static/images/jiankangceshi.png" mode="aspectFill" class="entry-img" lazy-load></image>
				</view>
				<view class="entry-item" @click="$u.route({type:'switchTab',url:'/pages/mall/mall'})">
					<image src="/static/images/jifenshangcheng.png" mode="aspectFill" class="entry-img" lazy-load></image>
				</view>
				<view class="entry-item" @click="$u.route({url:'/pages/sjhd/sjhd'})">
					<image src="/static/images/shequhudong.png" mode="aspectFill" class="entry-img" lazy-load></image>
				</view>
			</view>

			<!-- 热门活动模块标题 -->
			<view class="section-header" @click="$u.route({url:'/pages/low_carbon/active-list'})">
				<view class="u-flex u-flex-1">
					<image src="/static/index-5.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
					<view class="section-title">热门活动</view>
					<view class="section-divider">|</view>
					<view class="section-subtitle">共同创造美好生态环境</view>
				</view>
				<view class="section-more">
					<text>更多</text>
					<u-icon name="arrow-right" size="22"></u-icon>
				</view>
			</view>

			<!-- 热门活动横向滚动卡片 -->
			<scroll-view scroll-x class="active-scroll" :show-scrollbar="false">
				<view class="u-flex">
					<view class="active-card" v-for="item in active_list" :key="item.id"
						@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
						<u-image width="540" height="270" :src="item.thumb" border-radius="20rpx 20rpx 0 0"></u-image>
						<view class="active-card-body">
							<view class="active-card-title">{{item.name}}</view>
							<view class="active-card-info">
								<view class="u-flex u-flex-1">
									<u-icon name="clock" size="24"></u-icon>
									<text class="active-card-text">{{item.date | date('mm月dd日')}}</text>
								</view>
								<view class="u-flex u-flex-1 u-m-l-20">
									<u-icon name="map" size="24"></u-icon>
									<text class="active-card-text u-line-1">{{item.address}}</text>
								</view>
							</view>
							<view class="active-card-btn">活动报名</view>
						</view>
					</view>
				</view>
			</scroll-view>

			<!-- 最受欢迎兑换商品模块标题 -->
			<view class="section-header" @click="$u.route({type:'switchTab',url:'/pages/mall/mall'})">
				<view class="u-flex u-flex-1">
					<image src="/static/index-6.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
					<view class="section-title">最受欢迎兑换商品</view>
				</view>
				<view class="section-more">
					<text>更多</text>
					<u-icon name="arrow-right" size="22"></u-icon>
				</view>
			</view>

			<!-- 兑换商品列表 -->
			<view class="goods-card" v-for="item in dataList" :key="item.id" @click="$u.route('/pages/mall/details?id='+item.id)">
				<u-image border-radius="16rpx" width="180" height="180" :src="item.thumb" :lazy-load="true"></u-image>
				<view class="goods-card-body">
					<view class="goods-card-title">{{item.goods_name}}</view>
					<view class="goods-card-sold">已兑换{{item.sell_count}}次</view>
					<view class="u-flex u-row-between u-flex-1">
						<view class="goods-card-price">
							<text class="price-main" v-if="Number(item.price)">{{item.price}}元</text>
							<text class="price-plus" v-if="Number(item.price) && item.point">+</text>
							<text class="price-point" v-if="item.point">{{item.point}}积分</text>
						</view>
						<image src="/static/images/car.png" class="u-w-48 u-h-48" mode="aspectFill"></image>
					</view>
				</view>
			</view>
		</z-paging>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				bannerList: [],
				dataList: [],
				active_list: [],
			}
		},
		onShareAppMessage() {
			return {
				path: `/pages/index/index?scene=${this.userInfo.id}`
			}
		},
		onShareTimeline() {
			return {
				path: `/pages/index/index?scene=${this.userInfo.id}`
			}
		},
		onLoad(op) {
			if (op.scene) {
				uni.setStorageSync('vcode', op.scene)
			}
			this.getBannerList()
			this.get_new_active_list()
		},
		onShow() {
			// 已登录但未设置小区信息，强制跳转完善信息页（设置完社区小区和昵称后才可使用）
			// 用 nextTick 延迟跳转，避免在首页渲染过程中跳转导致白屏
			this.$nextTick(() => {
				if (this.isLogin && !this.userInfo.community_id) {
					uni.navigateTo({
						url: '/pages/user/info',
						fail: () => {}
					})
				}
			})
		},
		methods: {
			// 获取活动列表
			async get_new_active_list() {
				try {
					let params = {
						is_recommendation: 1
					}
					let res = await this.$u.api.get_new_active_list(params)
					this.active_list = res.data.data
				} catch (e) {
					console.log(e);
					this.$u.toast(e.msg)
				}
			},
			switchTab(url) {
				uni.switchTab({
					url: url
				})
			},
			// 获取商品列表
			async queryList(page) {
				try {
					let params = {
						page,
						keyword: this.keyword,
						is_recommend: 1
					}
					let res = await this.$u.api.get_goods_list(params)
					this.$refs.paging.complete(res.data.data);
				} catch (e) {
					console.log(e);
					this.$refs.paging.complete([]);
				}
			},
			// 获取轮播图
			async getBannerList() {
				try {
					let params = {
						id: 1
					}
					let res = await this.$u.api.find_slide(params)
					this.bannerList = res.data
				} catch (e) {
					console.log(e);
				}
			},
			// 轮播图点击跳转
			adLink(e) {
				let data = this.bannerList[e];
				if (!data.url) return
				uni.navigateTo({
					url: data.url,
					fail() {
						uni.switchTab({
							url: data.url
						})
					}
				})
			},
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F6F8;
	}

	/* Banner 轮播图容器：左右留白，顶部间距 */
	.banner-wrap {
		padding: 30rpx 30rpx 0;
	}

	/* 核心功能入口：四宫格大图，两行两列布局 */
	.entry-grid {
		display: flex;
		flex-wrap: wrap;
		justify-content: space-between;
		padding: 30rpx;
	}
	.entry-item {
		width: 334rpx;
		margin-bottom: 24rpx;
	}
	.entry-img {
		width: 334rpx;
		height: 245rpx;
		border-radius: 20rpx;
		box-shadow: 0 6rpx 24rpx rgba(0, 0, 0, 0.08);
	}

	/* 模块标题：图标 + 标题 + 分隔线 + 副标题 + 更多 */
	.section-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 40rpx 30rpx 24rpx;
	}
	.section-title {
		margin-left: 16rpx;
		font-size: 32rpx;
		font-weight: bold;
		color: #1a1a1a;
	}
	.section-divider {
		margin: 0 20rpx;
		color: #ddd;
	}
	.section-subtitle {
		font-size: 22rpx;
		color: #999;
	}
	.section-more {
		display: flex;
		align-items: center;
		font-size: 24rpx;
		color: #999;
	}

	/* 热门活动横向滚动卡片 */
	.active-scroll {
		white-space: nowrap;
		padding-left: 30rpx;
	}
	.active-card {
		flex-shrink: 0;
		width: 540rpx;
		margin-right: 24rpx;
		background: #fff;
		border-radius: 20rpx;
		overflow: hidden;
		box-shadow: 0 6rpx 28rpx rgba(0, 0, 0, 0.07);
	}
	.active-card-body {
		padding: 24rpx;
	}
	.active-card-title {
		font-size: 30rpx;
		font-weight: bold;
		color: #1a1a1a;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.active-card-info {
		display: flex;
		align-items: center;
		margin-top: 16rpx;
		font-size: 24rpx;
		color: #999;
	}
	.active-card-text {
		margin-left: 8rpx;
	}
	/* 活动报名按钮：渐变绿色，呼应品牌色 */
	.active-card-btn {
		margin-top: 20rpx;
		background: linear-gradient(141deg, #A4DB88 0%, #18AF38 100%);
		color: #fff;
		text-align: center;
		padding: 18rpx 0;
		border-radius: 12rpx;
		font-size: 26rpx;
		font-weight: bold;
	}

	/* 兑换商品卡片：白底圆角带阴影 */
	.goods-card {
		display: flex;
		margin: 0 30rpx 24rpx;
		padding: 24rpx;
		background: #fff;
		border-radius: 20rpx;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05);
	}
	.goods-card-body {
		margin-left: 20rpx;
		flex: 1;
		display: flex;
		flex-direction: column;
		height: 180rpx;
	}
	/* 商品标题：最多两行省略 */
	.goods-card-title {
		font-size: 30rpx;
		font-weight: bold;
		color: #1a1a1a;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
	}
	.goods-card-sold {
		margin-top: 8rpx;
		font-size: 22rpx;
		color: #bbb;
	}
	/* 商品价格：绿色突出，积分+元组合展示 */
	.goods-card-price {
		display: flex;
		align-items: baseline;
	}
	.price-main {
		font-size: 34rpx;
		font-weight: bold;
		color: #18AF38;
	}
	.price-plus {
		margin: 0 6rpx;
		font-size: 28rpx;
		color: #18AF38;
		font-weight: bold;
	}
	.price-point {
		font-size: 30rpx;
		font-weight: bold;
		color: #18AF38;
	}
</style>
