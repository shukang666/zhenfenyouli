<template>
	<view class="container">
		<!-- 顶部标签栏：后台"社区活动-活动标签"可配置1-4个；第一个为固定标签（显示垃圾分类打卡内容），其他标签暂显示现有内容，待按绑定分类模板化 -->
		<view class="card-all tab-bar u-flex" style="margin: 20rpx 30rpx 0;padding: 8rpx;" v-if="tab_list.length">
			<view class="tab-item u-text-center" :class="{active: current_tab == index}" v-for="(tab,index) in tab_list"
				:key="tab.id" @click="switch_tab(index)">{{tab.name}}</view>
		</view>

		<!-- 第一个标签：固定内容，与"分类活动"（pages/flhd/flhd）页一致 -->
		<view v-if="current_tab == 0" class="u-p-30">
			<view class="u-type-white-bg u-radius-10" @click="navigator('/pages/flhd/ljfldk',1)">
				<u-image width="100%" height="326" src="https://oss.ausite.cn/xcxkf052/admin/20250512/25a5d7f724a31ba6eccbe9d660e605ef.png?watermark"></u-image>
				<view class="u-p-20 u-flex u-row-between">
					<view class="">
						<view class="u-font-bold u-font-32">
							垃圾分类打卡
						</view>
						<view class="u-m-t-10 u-font-24 u-type-info">
							参与垃圾分类学习通过考核后即可解锁
						</view>
					</view>
					<view class="">
						<u-button type="primary" size="mini" hover-class="none" :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
							@click.stop="navigator('/pages/flhd/ljfldk',1)">立即参与</u-button>
					</view>
				</view>
			</view>
			<view class="u-m-t-50">
				<view class="u-flex u-row-center">
					<u-image width="447" height="28" src="https://oss.ausite.cn/xcxkf052/admin/20250512/8912eace6b085bd4b6225c5c8ae185ff.png?watermark"></u-image>
				</view>
				<view class="u-m-t-30 u-flex">
					<view class="u-w-218 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="navigator('/pages/flhd/list',2)">
						<view class="u-flex u-row-center">
							<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/40bfaab5aeaffdd0457cf369eca0e567.png?watermark"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							垃圾分类指导
						</view>
					</view>
					<view class="u-w-218 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-l-30">
						<view class="u-flex u-row-center" @click="navigator('/pages/answer/examine?id=1')">
							<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/ee564dd59c747c37e5b5b01eb2db7c54.png?watermark"></u-image>
						</view>
						<view class="u-m-t-30 u-font-bold">
							答题考试
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 其他标签：暂显示现有内容，待后续按绑定的活动分类模板化 -->
		<view v-else>
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
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	export default {
		data() {
			return {
				swiper_list: [],
				cate_list: [],
				tab_list: [],
				current_tab: 0,
				page: 1,
				total: 0
			};
		},
		onLoad() {
			this.find_slide()
			this.get_new_cate_active_list()
			this.get_active_tab_list()
		},
		onShow() {
			if (checkCommunity(this)) return
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
			// 活动页标签
			get_active_tab_list() {
				this.$u.api.get_active_tab_list().then(res => {
					this.tab_list = res.data
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 切换标签（标签内容暂未模板化，下方统一显示现有内容）
			switch_tab(index) {
				if (this.current_tab == index) return
				this.current_tab = index
			},
			// 跳转页面（type==1 为垃圾分类打卡入口，需先通过答题考试）
			navigator(url, type) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (!this.userInfo.is_answer && type == 1) {
					var that = this
					uni.showModal({
						title: '提示',
						content: '请先学习垃圾分类指导，通过答题考试后可参与活动',
						showCancel: false,
						confirmText: '立即学习',
						confirmColor: '#39BF3E',
						success: function(res) {
							if (res.confirm) {
								that.$u.route('/pages/flhd/list')
							} else if (res.cancel) {}
						}
					});
					return
				}
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

	.tab-bar {
		justify-content: space-around;

		.tab-item {
			flex: 1;
			font-size: 28rpx;
			color: #666;
			padding: 16rpx 0;
			border-radius: 10rpx;
			transition: all .2s;

			&.active {
				color: #18AF38;
				font-weight: bold;
				background: rgba(24, 175, 56, 0.10);
			}
		}
	}
</style>
