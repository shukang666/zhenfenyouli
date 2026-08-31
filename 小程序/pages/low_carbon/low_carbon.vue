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

		<!-- 中间标签（第二个起，非打卡非备份）：显示该标签绑定分类下的活动列表 -->
		<view v-else-if="!is_backup_tab" class="u-p-30">
			<view class="u-w-690 u-m-30 u-radius-10 u-type-white-bg u-rela u-shadow" v-for="item in active_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<u-image width="690" height="285" :src="item.thumb"></u-image>
				<view class="u-abso u-abso-l-30 u-abso-t-230 u-flex u-radius-10" style="background: rgba(0,0,0,0.44);padding: 5rpx 20rpx;" v-if="item.active_count">
					<view class="u-flex">
						<image :src="img" class="u-w-30 u-h-30 u-radius-o" mode="aspectFill" style="margin-left: -10rpx;" v-for="(img,i) in item.avatar_array" :key="i"></image>
					</view>
					<view class="u-font-24 white u-m-l-10">{{item.active_count}}人参与</view>
				</view>
				<view class="u-p-30">
					<view class="u-font-32 u-font-bold u-line-1">{{item.name}}</view>
					<view class="u-flex u-row-between u-m-t-15">
						<view class="u-flex  u-font-24 u-hui u-w-400">
							<view class="u-flex">
								<u-icon name="clock"></u-icon>
								<text>{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="u-flex u-m-l-30">
								<u-icon name="map"></u-icon>
								<text>{{item.address}}</text>
							</view>
						</view>
						<u-button :custom-style="{width:'165rpx',height:'60rpx'}" size="mini" @click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">立即报名</u-button>
					</view>
				</view>
			</view>
			<view v-if="active_list.length === 0 && !active_loading" class="u-text-center u-p-60 u-type-info">暂无活动</view>
			<view v-if="active_loading" class="u-text-center u-p-30 u-type-info">加载中...</view>
			<view v-if="active_list.length > 0 && active_list.length >= active_total" class="u-text-center u-p-30 u-type-info">没有更多了</view>
		</view>

		<!-- 最后一个"备份"标签：保留页面现有内容（轮播+通知+banner+活动分类卡片），前端写死，后台 active_tab 表不可见 -->
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
				total: 0,
				// 当前分类标签下的活动列表
				active_list: [],
				active_page: 1,
				active_total: 0,
				active_loading: false,
				current_cate_id: 0,
			};
		},
		computed: {
			// 当前是否在最后一个"备份"标签（前端写死，后台 active_tab 表不可见）
			is_backup_tab() {
				return this.tab_list.length > 0
					&& this.current_tab == this.tab_list.length - 1
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_backup === 1
			}
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
			// 活动页标签：后台 active_tab 表返回（最多4个，第一个为固定打卡标签），
			// 末尾追加一个前端写死的"备份"标签，仅前端可见，后台不可见
			get_active_tab_list() {
				this.$u.api.get_active_tab_list().then(res => {
					let list = res.data || []
					list.push({ id: -1, name: '备份', cate_id: 0, is_fixed: 2, _is_backup: 1 })
					this.tab_list = list
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 切换标签：第1个固定打卡标签保持不变；最后一个"备份"标签显示现有内容；
			// 中间标签加载该标签绑定分类下的活动列表
			switch_tab(index) {
				if (this.current_tab == index) return
				this.current_tab = index
				let tab = this.tab_list[index]
				if (!tab) return
				// 备份标签和固定打卡标签不需要加载分类列表
				if (tab._is_backup === 1 || index == 0) return
				this.current_cate_id = tab.cate_id || 0
				this.active_list = []
				this.active_page = 1
				this.active_total = 0
				this.load_active_list()
			},
			// 加载当前分类标签绑定的活动列表
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
			// 仅在中间分类标签下加载更多活动列表
			if (this.current_tab == 0 || this.is_backup_tab) return
			if (this.active_list.length >= this.active_total) return
			if (this.active_loading) return
			this.active_page++
			this.load_active_list()
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
