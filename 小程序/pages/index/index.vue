<template>
	<view class="container">
		<z-paging ref='paging' v-model='dataList' @query='queryList' :fixed='true'>
			<template slot="top">
				<u-navbar :is-back="false" title="" title-color="rgba(0,0,0,0)" :background="{background:'rgba(255,255,255,1)'}" :border-bottom="false">
					<image src="/static/images/logo.png" class="u-w-320 u-h-88 u-m-l-30" mode="aspectFill"></image>
				</u-navbar>
			</template>

			<view class="u-m-lr-30 u-p-t-30 u-rela">
				<u-swiper :list="bannerList" height="340" border-radius="30" @click="adLink" bg-color="rgba(0,0,0,0)"></u-swiper>
			</view>

			<view class="u-flex u-row-between u-flex-wrap u-m-lr-30">
				<image src="/static/images/ditanshenghuo.png" mode="aspectFill" class="u-w-334 u-h-245 u-m-t-30" @click="$u.route({type:'switchTab',url:'/pages/low_carbon/low_carbon'})"></image>
				<image src="/static/images/jiankangceshi.png" mode="aspectFill" class="u-w-334 u-h-245 u-m-t-30" @click="$u.route({type:'switchTab',url:'/pages/health/health'})"></image>
				<image src="/static/images/jifenshangcheng.png" mode="aspectFill" class="u-w-334 u-h-245 u-m-t-30" @click="$u.route({type:'switchTab',url:'/pages/mall/mall'})"></image>
				<image src="/static/images/shequhudong.png" mode="aspectFill" class="u-w-334 u-h-245 u-m-t-30" @click="$u.route({url:'/pages/sjhd/sjhd'})"></image>
			</view>

			<view class="u-flex u-row-between u-m-30" @click="$u.route({url:'/pages/low_carbon/active-list'})">
				<view class="u-flex">
					<image src="/static/index-5.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
					<view class="u-font-30 u-font-bold u-m-l-15">热门活动</view>
					<view class="grey u-m-lr-20">|</view>
					<view class="u-font-22 u-hui">共同创造美好生态环境</view>
				</view>
				<view class="u-flex u-font-22 u-hui">
					<view>更多</view>
					<u-icon name="arrow-right"></u-icon>
				</view>
			</view>
			<scroll-view scroll-y="true" scroll-x class="u-m-l-30">
				<view class="u-flex">
					<view class="u-shrink-no u-m-r-30 u-radius-10 u-type-white-bg u-rela u-w-570" v-for="item in active_list" :key="item.id"
						@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
						<u-image width="570" height="285" :src="item.thumb"></u-image>
						<view class="u-p-20">
							<view class="u-font-32 u-font-bold u-line-1">{{item.name}}</view>
							<view class="u-flex u-m-t-15 u-font-24 u-hui">
								<view class="u-flex">
									<u-icon name="clock"></u-icon>
									<text>{{item.date | date('mm月dd日')}} </text>
									<!-- {{item.day_type}} -->
								</view>
								<view class="u-flex u-m-l-30 u-line-1">
									<u-icon name="map"></u-icon>
									<text>{{item.address}}</text>
								</view>
							</view>
							<view class="u-m-t-15 u-hui-bg u-main-c u-text-center u-p-tb-20 u-font-26 u-font-bold">
								活动报名
							</view>
						</view>
					</view>
				</view>
			</scroll-view>
			<view class="u-flex u-row-between u-m-30" @click="$u.route({type:'switchTab',url:'/pages/mall/mall'})">
				<view class="u-flex">
					<image src="/static/index-6.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
					<view class="u-font-30 u-font-bold u-m-l-15">最受欢迎兑换商品</view>
				</view>
				<view class="u-flex u-font-22 u-hui">
					<view>更多</view>
					<u-icon name="arrow-right"></u-icon>
				</view>
			</view>
			<view class="u-w-690 u-type-white-bg u-radius-10 u-p-30 u-flex" style="margin: 0 30rpx 30rpx;" v-for="item in dataList" :key="item.id" @click="$u.route('/pages/mall/details?id='+item.id)">
				<u-image border-radius="10" width="180" height="180" :src="item.thumb"></u-image>
				<view class="u-m-l-15 u-h-180 u-flex-c-c u-w-480">
					<view class="u-font-30 u-font-bold u-line-2">{{item.goods_name}}</view>
					<view class="u-font-22 grey">已兑换{{item.sell_count}}次</view>
					<view class="u-flex u-row-between">
						<view>
							<!-- <text >{{item.point}}</text> -->
							<text class="u-main-c u-font-32 u-font-bold" v-if="Number(item.price)">{{item.price}}元</text>
							<text class="u-main-c u-font-32 u-font-bold" v-if="Number(item.price) && item.point"> + </text>
							<text class="u-main-c u-font-32 u-font-bold" v-if="item.point">{{item.point}}积分</text>
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
			if (this.isLogin && !this.userInfo.community_id) {
				uni.navigateTo({
					url: '/pages/user/info',
					fail: () => {
						// 避免重复跳转或已在信息页时静默失败
					}
				})
			}
		},
		methods: {
			// 获活动列表
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
			// 获取产品列表
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
		background: #F5F5F5;
	}
</style>