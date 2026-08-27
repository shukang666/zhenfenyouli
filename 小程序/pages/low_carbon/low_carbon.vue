<template>
	<view class="container">
		<view class="u-m-30">
			<u-swiper :list="swiper_list" height="340" mode="none" @click="adLink($event,swiper_list)"></u-swiper>
		</view>

		<view class="card-all u-flex" style="margin-top: 0;padding: 10rpx 30rpx;">
			<image src="/static/images/guanbbo.png" class="u-w-40 u-h-40 u-m-r-30" mode="aspectFill"></image>
			<view class="u-w-560">
				<u-notice-bar :volume-icon="false" mode="horizontal" :list="setting.carbon_notice" color="#333333" bg-color="#fff" border-radius="6"></u-notice-bar>
			</view>

		</view>
		<!-- <view class="u-m-t-30" @click="$u.route('/pages/dtsh/dtsh')" v-if="setting.is_waste">
			<u-image width="100%" height="240" src="https://oss.ausite.cn/xcxkf052/admin/20250606/68d14d845f207d15e52bdc15d735c387.png?watermark"></u-image>
		</view> -->
		<view class="u-flex u-row-between u-m-lr-30">
			<image src="/static/images/lianjifenlei.png" class="u-w-334 u-h-146" mode="aspectFill" @click="$u.route('/pages/dtsh/dtsh')"></image>
			<image src="/static/images/jiankangceshi2.png" class="u-w-334 u-h-146" mode="aspectFill" @click="$u.route('/pages/low_carbon/research')"></image>
		</view>

		<view class="u-flex u-m-l-30 u-p-t-30">
			<image src="/static/index-5.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
			<text class="u-font-30 u-font-bold u-m-l-15">活动分类</text>
		</view>

		<!-- 		<view class="u-p-20 u-type-white-bg u-radius-6 u-m-t-30 u-flex" v-for="(item,index) in cate_list" :key="index">
			<u-image width="160" height="160" :src="item.thumb"></u-image>
			<view class="u-flex-1 u-m-l-20 u-flex u-row-between u-col-top u-flex-col u-h-160">
				<view class="u-font-bold">
					{{item.name}}
				</view>
				<view class="u-font-24 u-type-info u-line-2">
					{{item.desc}}
				</view>
				<view class="u-text-right u-width-100">
					<u-button type="primary" size="mini" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
						@click="$u.route('/pages/low_carbon/lsxf/lsxf?id='+item.id+'&name='+item.name+'&type=1')">立即参与</u-button>
				</view>
			</view>
		</view> -->
		<view class="card-all u-flex u-flex-wrap u-p-0" style="overflow: hidden;">
			<view class="u-text-center u-p-tb-30 u-border-bottom u-border-right u-shrink-no" style="width:230rpx;" v-for="item in cate_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/active-list?cate_id=${item.id}`)">
				<image :src="item.image" class="u-w-80 u-h-80" mode="aspectFill"></image>
				<view class="u-font-28 u-font-bold u-m-t-15">{{item.name}}</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				swiper_list: [],
				cate_list: [],
				page: 1,
				total: 0
			};
		},
		onLoad() {
			this.find_slide()
			this.get_new_cate_active_list()
		},
		onShow() {
			if (this.isLogin) {
				this.getUserInfo()
			}
		},
		methods: {
			// 获取用户信息
			getUserInfo() {
				this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				})
			},
			// 活动分类
			get_new_cate_active_list() {
				this.$u.api.get_new_cate_active_list({
					// keyword: this.keyword,
					page: this.page,
				}).then(res => {
					this.cate_list.push(...res.data)
					// this.total = res.data.total
				}).catch(err => {
					this.$u.toast(err.msg)
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
					id: 5
				}).then(res => {
					this.swiper_list = res.data
				})
			},
		},
		onReachBottom() {
			if (this.cate_list.length >= this.total) return
			this.page++
			this.get_active_cate_list()
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F5F5;
	}
</style>
