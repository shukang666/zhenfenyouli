<template>
	<view class="container">
		<!-- 开发者测试标识 -->
		<view class="dev-test-badge">
			<u-tag text="开发测试版" type="warning" size="mini" />
			<text class="dev-test-tip">此页面与主程序分离，仅用于测试</text>
		</view>

		<!-- 侧边标签栏：后台"社区活动-活动标签"可配置1-4个；第一个为固定标签（显示垃圾分类打卡内容），其他标签暂显示现有内容，待按绑定分类模板化。改为侧边垂直布局，与右侧内容区联动 -->
		<view class="layout-wrapper" v-if="tab_list.length">
			<scroll-view class="side-bar" scroll-y>
				<view class="tab-item" :class="{active: current_tab == index}" v-for="(tab,index) in tab_list"
					:key="tab.id" @click="switch_tab(index)">
					<view class="tab-name">{{tab.name}}</view>
				</view>
			</scroll-view>
			<!-- 右侧内容区：根据 current_tab 联动显示对应标签内容 -->
			<view class="content-area">

		<!-- 本月推荐标签（前端写死第一个标签，unshift 到 tab_list 头部） -->
	<view v-if="is_monthly_rec_tab" class="u-p-30">
		<!-- 顶部 banner 图（后台轮播图管理 id=8 组配置，高度半屏，不点击） -->
		<view v-if="monthly_rec_banner" class="u-m-b-30 u-radius-10" style="overflow:hidden;">
			<u-image width="100%" height="70vh" :src="monthly_rec_banner" mode="aspectFill"></u-image>
		</view>
		<!-- 置顶：垃圾分类打卡固定入口卡片，复用打卡标签里的同款卡片 + top-pinned-card 样式 -->
		<view class="u-type-white-bg u-radius-10 u-rela top-pinned-card u-m-b-30" @click="navigator('/pages/flhd/ljfldk',1)">
				<u-image width="100%" height="326" src="https://oss.ausite.cn/xcxkf052/admin/20250512/25a5d7f724a31ba6eccbe9d660e605ef.png?watermark"></u-image>
				<view class="u-p-20 u-flex" style="align-items:center;">
					<view class="" style="flex:1;min-width:0;">
						<view class="u-font-bold u-font-32 u-line-1">
							垃圾分类打卡
						</view>
						<view class="u-m-t-10 u-font-22 u-type-info u-line-1">
							参与垃圾分类学习通过考核后即可解锁
						</view>
					</view>
				</view>
			</view>
			<view class="u-radius-10 u-type-white-bg u-rela u-shadow u-m-b-30" v-for="item in monthly_rec_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<u-image width="100%" height="285" :src="item.thumb"></u-image>
				<view class="u-abso u-abso-l-30 u-abso-t-230 u-flex u-radius-10" style="background: rgba(0,0,0,0.44);padding: 5rpx 20rpx;" v-if="item.active_count">
					<view class="u-flex">
						<image :src="img" class="u-w-30 u-h-30 u-radius-o" mode="aspectFill" style="margin-left: -10rpx;" v-for="(img,i) in item.avatar_array" :key="i"></image>
					</view>
					<view class="u-font-24 white u-m-l-10">{{item.active_count}}人参与</view>
				</view>
				<view class="u-p-30">
					<view class="u-font-32 u-font-bold u-line-1">{{item.name}}</view>
					<view class="u-flex u-row-between u-m-t-15" style="align-items:center;">
						<view class="u-flex u-font-24 u-hui u-flex-1 u-m-r-15" style="min-width:0;">
							<view class="u-flex" style="flex-shrink:0;white-space:nowrap;">
								<u-icon name="clock"></u-icon>
								<text style="font-size:22rpx;">{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="u-flex u-m-l-30" style="flex-shrink:0;white-space:nowrap;overflow:hidden;" v-if="item.address && item.address.trim()">
								<u-icon name="map"></u-icon>
								<text style="font-size:22rpx;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200rpx;">{{item.address}}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view v-if="monthly_rec_list.length === 0 && !monthly_rec_loading" class="u-text-center u-p-60 u-type-info">暂无推荐活动</view>
			<view v-if="monthly_rec_loading" class="u-text-center u-p-30 u-type-info">加载中...</view>
			<view v-if="!monthly_rec_loading && monthly_rec_list.length > 0 && monthly_rec_list.length >= monthly_rec_total" class="u-text-center u-p-30 u-type-info">没有更多了</view>
		</view>

		<!-- 打卡标签（后台第一个固定标签，index===1）：绑定活动分类，顶部写死"垃圾分类打卡"入口卡片，下方动态加载该分类活动列表 -->
		<view v-else-if="is_daka_tab" class="u-p-30">
			<view class="u-type-white-bg u-radius-10 u-rela top-pinned-card" @click="navigator('/pages/flhd/ljfldk',1)">
				<u-image width="100%" height="326" src="https://oss.ausite.cn/xcxkf052/admin/20250512/25a5d7f724a31ba6eccbe9d660e605ef.png?watermark"></u-image>
				<view class="u-p-20 u-flex" style="align-items:center;">
					<view class="" style="flex:1;min-width:0;">
						<view class="u-font-bold u-font-32 u-line-1">
							垃圾分类打卡
						</view>
						<view class="u-m-t-10 u-font-22 u-type-info u-line-1">
							参与垃圾分类学习通过考核后即可解锁
						</view>
					</view>
				</view>
			</view>
			<view class="u-m-t-30">
			<!-- 打卡标签绑定分类下的活动列表（与中间分类标签共用 active_list） -->
			<view class="u-radius-10 u-type-white-bg u-rela u-shadow u-m-b-30" v-for="item in active_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<u-image width="100%" height="285" :src="item.thumb"></u-image>
				<view class="u-abso u-abso-l-30 u-abso-t-230 u-flex u-radius-10" style="background: rgba(0,0,0,0.44);padding: 5rpx 20rpx;" v-if="item.active_count">
					<view class="u-flex">
						<image :src="img" class="u-w-30 u-h-30 u-radius-o" mode="aspectFill" style="margin-left: -10rpx;" v-for="(img,i) in item.avatar_array" :key="i"></image>
					</view>
					<view class="u-font-24 white u-m-l-10">{{item.active_count}}人参与</view>
				</view>
				<view class="u-p-30">
					<view class="u-font-32 u-font-bold u-line-1">{{item.name}}</view>
					<view class="u-flex u-row-between u-m-t-15" style="align-items:center;">
						<view class="u-flex u-font-24 u-hui u-flex-1 u-m-r-15" style="min-width:0;">
							<view class="u-flex" style="flex-shrink:0;white-space:nowrap;">
								<u-icon name="clock"></u-icon>
								<text style="font-size:22rpx;">{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="u-flex u-m-l-30" style="flex-shrink:0;white-space:nowrap;overflow:hidden;" v-if="item.address && item.address.trim()">
								<u-icon name="map"></u-icon>
								<text style="font-size:22rpx;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200rpx;">{{item.address}}</text>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view v-if="active_list.length === 0 && !active_loading && !is_daka_tab" class="u-text-center u-p-60 u-type-info">暂无活动</view>
			<view v-if="active_loading" class="u-text-center u-p-30 u-type-info">加载中...</view>
			<view v-if="!active_loading && (is_daka_tab || (active_list.length > 0 && active_list.length >= active_total))" class="u-text-center u-p-30 u-type-info">没有更多了</view>
		</view>
		</view>

		<!-- 最后一个默认标签 -->
		<view v-else>
		<view class="u-m-30">
			<u-swiper :list="swiper_list" height="340" mode="none" @click="adLink($event,swiper_list)"></u-swiper>
		</view>

		<view class="card-all u-flex" style="margin-top: 0;padding: 10rpx 30rpx;">
			<image src="/static/images/guanbbo.png" class="u-w-40 u-h-40 u-m-r-30" mode="aspectFill"></image>
			<view class="u-flex-1">
				<u-notice-bar :volume-icon="false" mode="horizontal" :list="setting.carbon_notice" color="#333333" bg-color="#fff" border-radius="6"></u-notice-bar>
			</view>
		</view>
		<view class="u-flex u-m-lr-30">
			<image src="/static/images/lianjifenlei.png" style="flex:1;height:146rpx;margin-right:20rpx;" mode="aspectFill" @click="$u.route('/pages/dtsh/dtsh')"></image>
			<image src="/static/images/jiankangceshi2.png" style="flex:1;height:146rpx;" mode="aspectFill" @click="$u.route('/pages/low_carbon/research')"></image>
		</view>

		<view class="u-flex u-m-l-30 u-p-t-30">
			<image src="/static/index-5.png" class="u-w-32 u-h-32" mode="aspectFill"></image>
			<text class="u-font-30 u-font-bold u-m-l-15">活动分类</text>
		</view>

		<view class="card-all u-flex u-flex-wrap u-p-0" style="overflow: hidden;">
			<view class="u-text-center u-p-tb-30 u-border-bottom u-shrink-no" style="flex:1 1 33.33%;max-width:33.33%;box-sizing:border-box;" v-for="(item,idx) in cate_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/active-list?cate_id=${item.id}`)">
				<image :src="item.image" class="u-w-80 u-h-80" mode="aspectFill"></image>
				<view class="u-font-28 u-font-bold u-m-t-15">{{item.name}}</view>
			</view>
		</view>
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
				monthly_rec_banner: '',
				cate_list: [],
				tab_list: [],
				current_tab: 0,
				page: 1,
				total: 0,
				active_list: [],
				active_page: 1,
				active_total: 0,
				active_loading: false,
				current_cate_id: 0,
				shopInfo: {},
				health_list: [],
				monthly_rec_list: [],
				monthly_rec_page: 1,
				monthly_rec_total: 0,
				monthly_rec_loading: false,
			};
		},
		computed: {
			is_backup_tab() {
				return this.tab_list.length > 0
					&& this.current_tab == this.tab_list.length - 1
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_backup === 1
			},
			is_zzhd_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'zzhd'
			},
			is_sjhd_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'sjhd'
			},
			is_monthly_rec_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'monthly_rec'
			},
			is_daka_tab() {
				return this.current_tab === 1
			}
		},
		onLoad() {
			this.find_slide()
			this.get_monthly_rec_banner()
			this.get_new_cate_active_list()
			this.get_active_tab_list()
		},
		onShow() {
			if (checkCommunity(this)) return
			if (!this.isLogin) return
			if (this._isChecking) return
			this._isChecking = true
			this.getUserInfo().then(() => {
				this._isChecking = false
				if (!this.isLogin) return
				if (!this.userInfo.is_learn) {
					this.$u.route('/pages/flhd/list')
				} else if (!this.userInfo.is_answer) {
					this.$u.route('/pages/answer/examine?id=1')
				}
			})
		},
		methods: {
			getUserInfo() {
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				})
			},
			get_new_cate_active_list() {
				this.$u.api.get_new_cate_active_list({
					page: this.page,
				}).then(res => {
					this.cate_list.push(...res.data)
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			get_active_tab_list() {
				this.$u.api.get_active_tab_list().then(res => {
					let list = res.data || []
					list.unshift({ id: -99, name: '本月推荐', cate_id: 0, is_fixed: 2, _is_custom: 'monthly_rec' })
					this.tab_list = list
					this.load_monthly_rec_list()
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			switch_tab(index) {
				if (this.current_tab == index) return
				this.current_tab = index
				let tab = this.tab_list[index]
				if (!tab) return
				if (tab._is_backup === 1) return
				if (tab._is_custom === 'monthly_rec') {
					this.load_monthly_rec_list()
					return
				}
				this.current_cate_id = tab.cate_id || 0
				this.active_list = []
				this.active_page = 1
				this.active_total = 0
				this.load_active_list()
			},
			load_monthly_rec_list() {
				if (this.monthly_rec_loading) return
				this.monthly_rec_loading = true
				this.$u.api.get_new_active_list({
					page: this.monthly_rec_page,
					page_size: 10,
					is_month_recommend: 1
				}).then(res => {
					this.monthly_rec_loading = false
					let data = res.data || {}
					let list = data.data || []
					this.monthly_rec_total = data.total || 0
					if (this.monthly_rec_page <= 1) {
						this.monthly_rec_list = list
					} else {
						this.monthly_rec_list.push(...list)
					}
				}).catch(err => {
					this.monthly_rec_loading = false
					this.$u.toast(err.msg)
				})
			},
			load_active_list() {
				if (this.active_loading) return
				if (!this.current_cate_id) {
					this.active_list = []
					this.active_total = 0
					return
				}
				this.active_loading = true
				this.$u.api.get_new_active_list({
					page: this.active_page,
					community_cate_id: this.current_cate_id,
				}).then(res => {
					let data = res.data || {}
					let list = data.data || []
					if (this.active_page == 1) {
						this.active_list = list
					} else {
						this.active_list.push(...list)
					}
					this.active_total = data.total || list.length
				}).catch(err => {
					this.$u.toast(err.msg)
				}).finally(() => {
					this.active_loading = false
				})
			},
			navigator(url, type) {
				if (type == 1) {
					if (this.isLogin && !this.userInfo.is_answer) {
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
								}
							}
						});
						return
					}
					this.$u.route(url)
					return
				}
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				this.$u.route(url)
			},
			async adLink(e, item) {
				let data = item[e];
				if (!data.url) return
				if (data.target == 'switchTab') return uni.switchTab({
					url: data.url
				});
				const openUrl = await this.$u.func.checkH5(data.url);
				this.$u.route(openUrl);
			},
			find_slide() {
				this.$u.api.find_slide({
					id: 5
				}).then(res => {
					this.swiper_list = res.data
				})
			},
			get_monthly_rec_banner() {
				this.$u.api.find_slide({
					id: 8
				}).then(res => {
					if (res.data && res.data.length > 0) {
						this.monthly_rec_banner = res.data[0].image
					}
				})
			},
			onReachBottom() {
				if (this.is_backup_tab) return
				if (this.is_monthly_rec_tab) {
					if (this.monthly_rec_list.length >= this.monthly_rec_total) return
					if (this.monthly_rec_loading) return
					this.monthly_rec_page++
					this.load_monthly_rec_list()
					return
				}
				if (this.active_list.length >= this.active_total) return
				if (this.active_loading) return
				this.active_page++
				this.load_active_list()
			}
		}
	}
</script>

<style lang="scss" scoped>
	.container {
		min-height: 100vh;
		background: #F5F5F5;
	}

	.dev-test-badge {
		position: fixed;
		top: 20rpx;
		right: 20rpx;
		z-index: 999;
		background: rgba(255, 153, 0, 0.9);
		padding: 10rpx 20rpx;
		border-radius: 30rpx;
		display: flex;
		align-items: center;
		flex-direction: column;

		.dev-test-tip {
			font-size: 18rpx;
			color: #fff;
			margin-top: 5rpx;
		}
	}

	.layout-wrapper {
		display: flex;
		height: 100vh;
		overflow: hidden;
	}

	.top-pinned-card {
		overflow: hidden;
		position: relative;
	}
	.top-pinned-card::before {
		content: '置顶';
		position: absolute;
		top: 0;
		right: 0;
		z-index: 10;
		padding: 6rpx 18rpx;
		font-size: 22rpx;
		line-height: 1.4;
		color: #fff;
		background: linear-gradient(135deg, #ff6b35 0%, #ff3d00 100%);
		border-bottom-left-radius: 14rpx;
		border-top-right-radius: 10rpx;
		box-shadow: 0 2rpx 6rpx rgba(255, 61, 0, .35);
	}

	.side-bar {
		width: 180rpx;
		flex-shrink: 0;
		height: 100vh;
		background: #ffffff;
		border-right: 1rpx solid #f0f0f0;
		box-sizing: border-box;

		.tab-item {
			position: relative;
			padding: 32rpx 0;
			text-align: center;
			font-size: 26rpx;
			color: #666;
			transition: all .2s;

			.tab-name {
				word-break: break-all;
				line-height: 1.3;
			}

			&.active {
				color: #18AF38;
				font-weight: bold;
				background: rgba(24, 175, 56, 0.10);

				&::before {
					content: '';
					position: absolute;
					left: 0;
					top: 50%;
					transform: translateY(-50%);
					width: 6rpx;
					height: 36rpx;
					background: #18AF38;
					border-radius: 0 6rpx 6rpx 0;
				}
			}
		}
	}

	.content-area {
		flex: 1;
		min-width: 0;
		height: 100vh;
		overflow-y: auto;
		overflow-x: hidden;
		box-sizing: border-box;
	}
</style>
