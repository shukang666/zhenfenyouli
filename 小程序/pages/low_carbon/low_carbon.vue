<template>
	<view class="container">
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="low_carbon"></skeleton-screen>
		<!-- 顶部 Banner（后台轮播图 id=8） -->
		<view class="top-banner" v-if="monthly_rec_banner">
			<u-image width="100%" height="320rpx" :src="monthly_rec_banner" mode="aspectFill" border-radius="0"></u-image>
		</view>
		<!-- 固定分类标签栏：横向可滑动，区分固定分类(tag_type=1)和动态主题标签 -->
		<view class="cate-tab-bar-wrap">
			<scroll-view scroll-x class="cate-tab-bar" :show-scrollbar="false" :scroll-into-view="'tab-' + current_tab">
				<view class="cate-tab-list">
					<!-- 本月推荐（固定首位，默认选中） -->
					<view class="cate-tab-item" :class="{'cate-tab-active': current_tab === 0}" id="tab-0" @click="switch_tab(0)">
						<text>本月推荐</text>
					</view>
					<!-- 后台分类标签：固定分类(tag_type=1) -->
					<view class="cate-tab-item" :class="{'cate-tab-active': current_tab == idx + 1}" :id="'tab-' + (idx + 1)"
						v-for="(tab, idx) in fixed_cate_tabs" :key="tab.id" @click="switch_tab(idx + 1)">
						<text>{{tab.name}}</text>
					</view>
					<!-- 自治活动 / 社交活动（写死在分类标签后） -->
					<view class="cate-tab-item" :class="{'cate-tab-active': current_tab == zzhd_tab_index}" :id="'tab-' + zzhd_tab_index"
						@click="switch_tab(zzhd_tab_index)">
						<text>自治活动</text>
					</view>
					<view class="cate-tab-item" :class="{'cate-tab-active': current_tab == sjhd_tab_index}" :id="'tab-' + sjhd_tab_index"
						@click="switch_tab(sjhd_tab_index)">
						<text>社交活动</text>
					</view>
					<!-- 动态主题标签入口（tag_type=2，有主题时显示下拉筛选触发器） -->
					<view class="cate-tab-item cate-tab-theme" v-if="theme_tabs.length > 0" @click="toggle_theme_dropdown">
						<text>主题活动</text>
						<u-icon :name="show_theme_dropdown ? 'arrow-up' : 'arrow-down'" size="20" style="margin-left:4rpx;"></u-icon>
					</view>
				</view>
			</scroll-view>
			<!-- 主题下拉筛选弹窗 -->
			<view class="theme-dropdown-mask" v-if="show_theme_dropdown" @click="show_theme_dropdown = false"></view>
			<view class="theme-dropdown-panel" v-if="show_theme_dropdown">
				<view class="theme-dropdown-title">限时主题活动</view>
				<view class="theme-dropdown-list">
					<view class="theme-dropdown-item" v-for="tab in theme_tabs" :key="tab.id"
						:class="{'theme-dropdown-active': selected_theme && selected_theme.id === tab.id}"
						@click="select_theme_tab(tab)">
						<text>{{tab.name}}</text>
					</view>
				</view>
			</view>
		</view>
		<!-- 主内容区：根据 current_tab 联动显示 -->
		<view class="main-content">
			<!-- 本月推荐标签：展示海报+推荐活动列表 -->
			<view v-if="is_monthly_rec_tab" class="pane monthly-rec-pane">
				<view v-if="monthly_rec_list.length === 0 && !monthly_rec_loading" class="empty-tip">暂无推荐活动</view>
				<view v-else class="act-card" v-for="(item, index) in monthly_rec_list" :key="item.id"
					@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
					<view class="act-card-img">
						<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
						<view class="act-card-badge" v-if="item.active_count > 0">
							<text class="badge-text">{{item.active_count}}人参与</text>
						</view>
					</view>
					<view class="act-card-info">
						<view class="act-card-title">{{item.name}}</view>
						<view class="act-card-desc u-line-2">{{item.dec || '精彩活动等你来参与'}}</view>
						<view class="act-card-meta">
							<text class="act-card-status" v-if="item.status_name">{{item.status_name}}</text>
							<view class="act-card-avatars" v-if="item.avatar_array && item.avatar_array.length">
								<image :src="img" class="avatar-img" mode="aspectFill"
									v-for="(img,i) in item.avatar_array.slice(0,3)" :key="i"></image>
							</view>
						</view>
					</view>
				</view>
			</view>
			<!-- 固定分类标签（tag_type=1）：显示活动列表，第一个分类顶部置顶打卡入口卡片 -->
			<view v-else-if="is_fixed_cate_tab" class="pane">
				<!-- 第一个分类标签顶部写死"垃圾分类打卡"入口卡片 -->
				<view class="act-card top-pinned-card" v-if="is_first_cate_tab" @click="navigator('/pages/flhd/ljfldk',1)">
					<view class="act-card-thumb">
						<u-image width="100%" height="100%"
							src="https://oss.ausite.cn/xcxkf052/admin/20250512/25a5d7f724a31ba6eccbe9d660e605ef.png?watermark"
							mode="aspectFill"></u-image>
					</view>
					<view class="act-card-info">
						<view class="act-card-title">垃圾分类打卡</view>
						<view class="act-card-sub">参与垃圾分类学习通过考核后即可解锁</view>
					</view>
				</view>
				<!-- 活动列表 -->
				<view class="act-card" v-for="item in active_list" :key="item.id"
					@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
					<view class="act-card-thumb">
						<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
						<view class="card-participate" v-if="item.active_count > 0">
							<view class="avatar-list" v-if="item.avatar_array && item.avatar_array.length">
								<image :src="img" class="avatar-img" mode="aspectFill"
									v-for="(img,i) in item.avatar_array.slice(0,3)" :key="i"></image>
							</view>
							<text class="count-text">{{item.active_count}}人参与</text>
						</view>
					</view>
					<view class="act-card-info">
						<view class="act-card-title u-line-1">{{item.name}}</view>
						<view class="act-card-meta-row">
							<view class="meta-item">
								<u-icon name="clock" size="22"></u-icon>
								<text class="meta-text">{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="meta-item meta-item-loc" v-if="item.address && item.address.trim()">
								<u-icon name="map" size="22"></u-icon>
								<text class="meta-text">{{item.address}}</text>
							</view>
						</view>
					</view>
				</view>
				<view v-if="active_list.length === 0 && !active_loading" class="empty-tip">暂无活动</view>
				<view v-if="active_loading" class="loading-tip">加载中...</view>
				<view v-if="!active_loading && active_list.length > 0 && active_list.length >= active_total" class="loading-tip">没有更多了</view>
			</view>
			<!-- 自治活动标签 -->
			<view v-else-if="is_zzhd_tab" class="pane">
				<view class="u-rela fb-banner-wrap">
					<u-image width="100%" height="260"
						src="https://oss.ausite.cn/xcxkf052/admin/20260617/08e04d3c0f112405a9b9c2d40c82862a.png?watermark"></u-image>
					<view class="u-p-20 white u-radius-12 u-abso u-abso-b-20 u-abso-l-20 u-abso-r-20 fb-banner-notice"
						style="background: rgba(0,0,0,0.45); backdrop-filter: blur(10rpx);">
						{{setting.civilization_notice}}
					</view>
				</view>
				<view class="u-p-20">
					<view class="fb-section-title">督导员</view>
					<view class="u-m-t-20 u-flex" style="gap:14rpx;">
						<view class="fb-entry-card" style="flex:1;"
							@click="zzhd_navigator('/pages/zzhd/wmbb',1)">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80"
									src="https://oss.ausite.cn/xcxkf052/admin/20250512/e48645c17b800739e2f492b8f95c4ba6.png?watermark"></u-image>
							</view>
							<view class="u-m-t-20 u-font-bold fb-entry-label">督导员申请</view>
						</view>
						<view class="fb-entry-card" style="flex:1;"
							@click="zzhd_navigator('/pages/zzhd/ranking')">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80"
									src="https://oss.ausite.cn/xcxkf052/admin/20250512/6f8d9cc52ce9afca23824a19564aed64.png?watermark"></u-image>
							</view>
							<view class="u-m-t-20 u-font-bold fb-entry-label">督导员排行</view>
						</view>
					</view>
				</view>
				<view class="u-p-20 u-p-t-0" v-if="!setting.app_test">
					<view class="fb-section-title">点位管理</view>
					<view class="u-m-t-20 u-flex" style="gap:14rpx;">
						<view class="fb-entry-card" style="flex:1;"
							@click="zzhd_navigator('/pages/zzhd/dwxj/dwxj?type=3')">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80"
									src="https://oss.ausite.cn/xcxkf052/admin/20250512/51956890c56bc0241bad6c6eacf709dc.png?watermark"></u-image>
							</view>
							<view class="u-m-t-20 u-font-bold fb-entry-label">督导巡查</view>
						</view>
						<view class="fb-entry-card" style="flex:1;"
							@click="zzhd_navigator('/pages/zzhd/yxsj/yxsj?type=6')" v-if="setting.is_deed">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80"
									src="https://oss.ausite.cn/xcxkf052/admin/20250512/18e7281ba65d9a3afb6bc9902b0e9139.png?watermark"></u-image>
							</view>
							<view class="u-m-t-20 u-font-bold fb-entry-label">优秀事迹</view>
						</view>
						<view class="fb-entry-card" style="flex:1;"
							@click="zzhd_navigator('/pages/zzhd/dwxj/dwxj?type=4')">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80"
									src="https://oss.ausite.cn/xcxkf052/admin/20250512/ae1cb9fd359b5fe3d106e4293a174f57.png?watermark"></u-image>
							</view>
							<view class="u-m-t-20 u-font-bold fb-entry-label">其他督导</view>
						</view>
					</view>
				</view>
				<view class="u-p-20 u-p-t-0" v-if="!setting.app_test">
					<view class="fb-section-title">商家自治</view>
					<view class="u-m-t-20 u-flex" style="gap:14rpx;">
						<view class="fb-entry-card" style="flex:1;"
							@click="goApplyShop">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80"
									src="https://oss.ausite.cn/xcxkf052/admin/20260611/8c95821e23155697d1e0a2fd8e8439c8.png?watermark"></u-image>
							</view>
							<view class="u-m-t-20 u-font-bold fb-entry-label">商家申请</view>
						</view>
						<view class="fb-entry-card" style="flex:1;"
							@click="goShopSign">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80"
									src="https://oss.ausite.cn/xcxkf052/admin/20260611/1b473a1a2a5fde71f6d0aa98459bab92.png?watermark"></u-image>
							</view>
							<view class="u-m-t-20 u-font-bold fb-entry-label">商家打卡</view>
						</view>
					</view>
				</view>
			</view>
			<!-- 社交活动标签 -->
			<view v-else-if="is_sjhd_tab" class="pane">
				<view class="">
					<u-image width="100%" height="260"
						src="https://oss.ausite.cn/xcxkf052/admin/20250512/5d288456e544b333329d345733847049.png?watermark"></u-image>
				</view>
				<view class="u-p-20">
					<view class="fb-section-title">社区共建</view>
					<view class="u-m-t-20">
						<u-row gutter="14">
							<u-col span="4">
								<view class="fb-entry-card"
									@click="sjhd_navigator('/pages/sjhd/list?type=2')" v-if="setting.is_course">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/6dd071cac6cdfc81e7297998c0a011ee.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">课程培训</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="fb-entry-card"
									@click="sjhd_navigator('/pages/low_carbon/lsxf/lsxf?name=社区活动&type=3')"
									v-if="setting.is_active">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/8f9e9a13bb18f32ed74e6e7a43f4b081.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">社区活动</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="fb-entry-card"
									@click="sjhd_navigator('/pages/sjhd/list?type=3')" v-if="setting.is_match">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/956ddc6fd692c9cf48c6b2dd2ef6f5d2.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">社区知识竞赛</view>
								</view>
							</u-col>
						</u-row>
					</view>
				</view>
				<view class="u-p-20 u-p-t-0">
					<view class="fb-section-title">宣传互动</view>
					<view class="u-m-t-20">
						<u-row gutter="14">
							<u-col span="4">
								<view class="fb-entry-card"
									@click="sjhd_navigator('/pages/article/article?type=1')">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/6dd071cac6cdfc81e7297998c0a011ee.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">宣传手册</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="fb-entry-card"
									@click="sjhd_navigator('/pages/article/article?type=2')">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/8f9e9a13bb18f32ed74e6e7a43f4b081.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">入户宣传</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="fb-entry-card"
									@click="sjhd_navigator('/pages/zzhd/yxsj/yxsj?type=7')"
									v-if="!setting.is_environmental">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/f02ace719b53730c610058eda02a8006.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">环保论坛</view>
								</view>
							</u-col>
						</u-row>
						<u-row gutter="14">
							<u-col span="4">
								<view class="fb-entry-card u-m-t-14"
									@click="sjhd_navigator('/pages/low_carbon/lsxf/lsxf?name=环保活动&type=2')"
									v-if="!setting.app_test">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/b2bc3031e9b1fcb65fb59b52c2be27f5.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">环保活动</view>
								</view>
							</u-col>
							<u-col span="4" v-if="!setting.is_mutual">
								<view class="fb-entry-card u-m-t-14"
									@click="sjhd_navigator('/pages/zzhd/yxsj/yxsj?type=5')">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80"
											src="https://oss.ausite.cn/xcxkf052/admin/20250512/6e77a6e3afc5faefab1d80db1fb13e4c.png?watermark"></u-image>
									</view>
									<view class="u-m-t-20 u-font-bold fb-entry-label">互助平台</view>
								</view>
							</u-col>
						</u-row>
					</view>
				</view>
				<view class="u-p-20 u-p-t-0">
					<view class="fb-section-title">问卷调查</view>
					<view class="fb-list-item u-m-t-20 u-flex u-row-between"
						style="align-items:center;"
						@click="sjhd_navigator('/pages/low_carbon/research')">
						<view class="">
							<view class="u-font-bold fb-list-title">问卷调查</view>
							<view class="u-m-t-10 fb-list-sub">点击查看所有问卷，参与即可获得积分</view>
						</view>
						<u-icon name="arrow-right" color="#65676B" size="32"></u-icon>
					</view>
				</view>
			</view>
			<!-- 动态主题标签（tag_type=2）：按主题绑定分类显示活动列表 -->
			<view v-else-if="is_theme_tab" class="pane">
				<view class="act-card" v-for="item in active_list" :key="item.id"
					@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
					<view class="act-card-thumb">
						<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
						<view class="card-participate" v-if="item.active_count > 0">
							<view class="avatar-list" v-if="item.avatar_array && item.avatar_array.length">
								<image :src="img" class="avatar-img" mode="aspectFill"
									v-for="(img,i) in item.avatar_array.slice(0,3)" :key="i"></image>
							</view>
							<text class="count-text">{{item.active_count}}人参与</text>
						</view>
					</view>
					<view class="act-card-info">
						<view class="act-card-title u-line-1">{{item.name}}</view>
						<view class="act-card-meta-row">
							<view class="meta-item">
								<u-icon name="clock" size="22"></u-icon>
								<text class="meta-text">{{item.date | date('yy-mm-dd')}}</text>
							</view>
							<view class="meta-item meta-item-loc" v-if="item.address && item.address.trim()">
								<u-icon name="map" size="22"></u-icon>
								<text class="meta-text">{{item.address}}</text>
							</view>
						</view>
					</view>
				</view>
				<view v-if="active_list.length === 0 && !active_loading" class="empty-tip">暂无活动</view>
				<view v-if="active_loading" class="loading-tip">加载中...</view>
				<view v-if="!active_loading && active_list.length > 0 && active_list.length >= active_total" class="loading-tip">没有更多了</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { checkCommunity } from '@/common/communityCheck.mixin.js'
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	export default {
		components: { SkeletonScreen },
		data() {
			return {
				isLoading: true,
				monthly_rec_banner: '',
				tab_list: [],
				current_tab: 0,
				show_theme_dropdown: false,
				page: 1,
				active_list: [],
				active_page: 1,
				active_total: 0,
				active_loading: false,
				current_cate_id: 0,
				monthly_rec_list: [],
				monthly_rec_loading: false,
				shopInfo: {},
				health_list: [],
				// 当前选中的主题标签（从下拉筛选中选择，不在 tab_list 中）
				selected_theme: null,
			};
		},
		computed: {
			// 固定分类标签（tag_type!=2，不含本月推荐/自治/社交写死标签）
			fixed_cate_tabs() {
				return this.tab_list.filter(t => !t._is_custom && t.tag_type != 2)
			},
			// 动态主题标签（tag_type=2）
			theme_tabs() {
				return this.tab_list.filter(t => t.tag_type == 2)
			},
			// 自治活动在 tab_list 中的索引
			zzhd_tab_index() {
				return this.tab_list.findIndex(t => t._is_custom === 'zzhd')
			},
			// 社交活动在 tab_list 中的索引
			sjhd_tab_index() {
				return this.tab_list.findIndex(t => t._is_custom === 'sjhd')
			},
			// 当前标签类型判断
			is_monthly_rec_tab() {
				return this.current_tab === 0 && this.tab_list.length > 0
			},
			is_fixed_cate_tab() {
				let tab = this.tab_list[this.current_tab]
				return tab && !tab._is_custom && tab.tag_type != 2
			},
			is_first_cate_tab() {
				return this.is_fixed_cate_tab && this.fixed_cate_tabs.length > 0
					&& this.tab_list[this.current_tab] === this.fixed_cate_tabs[0]
			},
			is_zzhd_tab() {
				let tab = this.tab_list[this.current_tab]
				return tab && tab._is_custom === 'zzhd'
			},
			is_sjhd_tab() {
				let tab = this.tab_list[this.current_tab]
				return tab && tab._is_custom === 'sjhd'
			},
			is_theme_tab() {
				return this.selected_theme != null
			},
		},
		onLoad(options) {
			this._tabQueryName = options ? options.tab : ''
			this.loadAllData()
		},
		onShow() {
			this.updateTabBarSelected(1)
			let jumpMonthlyRec = uni.getStorageSync('jump_monthly_rec')
			if (jumpMonthlyRec) {
				if (this.tab_list.length > 0) {
					uni.removeStorageSync('jump_monthly_rec')
					this.current_tab = 0
				}
				return
			}
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
			async loadAllData() {
				const startTime = Date.now()
				try {
					await Promise.all([
						this.get_monthly_rec_banner(),
						this.get_active_tab_list()
					])
				} catch (e) {
					console.log('数据加载失败:', e)
				} finally {
					await this.load_monthly_rec_list()
					const elapsed = Date.now() - startTime
					if (elapsed < 400) {
						setTimeout(() => { this.isLoading = false }, 400 - elapsed)
					} else {
						this.isLoading = false
					}
				}
			},
			getUserInfo() {
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				})
			},
			// 活动页标签列表：后台返回含 tag_type 字段，前端组装 tab_list
			// 顺序：本月推荐(写死) → 固定分类(tag_type=1) → 自治活动(写死) → 社交活动(写死)
			// 动态主题(tag_type=2)不在标签栏直接显示，通过下拉筛选弹窗选择
			get_active_tab_list() {
				return this.$u.api.get_active_tab_list().then(res => {
					let list = res.data || []
					// 分离固定分类和动态主题
					// tag_type=2 的是动态主题，其他（tag_type=1 或无 tag_type）都是固定分类
					let fixedTabs = list.filter(t => t.tag_type != 2)
					// 组装完整 tab_list
					let fullList = []
					fullList.push({ id: -99, name: '本月推荐', cate_id: 0, tag_type: 1, _is_custom: 'monthly_rec' })
					fullList.push(...fixedTabs)
					fullList.push({ id: -2, name: '自治活动', cate_id: 0, tag_type: 1, _is_custom: 'zzhd' })
					fullList.push({ id: -3, name: '社交活动', cate_id: 0, tag_type: 1, _is_custom: 'sjhd' })
					this.tab_list = fullList
					// 处理 URL 参数 tab 跳转
					if (this._tabQueryName) {
						let targetIndex = fullList.findIndex(t => t.name === this._tabQueryName)
						if (targetIndex >= 0) {
							this.current_tab = targetIndex
							this._loadTabData(fullList[targetIndex])
						}
						this._tabQueryName = ''
						return
					}
					// 检查 banner 跳转标记
					let jumpMonthlyRec = uni.getStorageSync('jump_monthly_rec')
					if (jumpMonthlyRec) {
						uni.removeStorageSync('jump_monthly_rec')
						this.current_tab = 0
						return
					}
					// 默认选中"本月推荐"
					this.current_tab = 0
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 根据标签加载对应数据
			_loadTabData(tab) {
				if (!tab) return
				if (tab._is_custom === 'monthly_rec') {
					this.load_monthly_rec_list()
				} else if (tab._is_custom === 'zzhd') {
					if (this.isLogin) { this.getUserInfo(); this.get_shop_new() }
				} else if (tab._is_custom === 'sjhd') {
					// 社交活动无需预加载
				} else if (tab.cate_id) {
					this.current_cate_id = tab.cate_id
					this.active_list = []
					this.active_page = 1
					this.active_total = 0
					this.load_active_list()
				}
			},
			// 切换标签
			switch_tab(index) {
				if (this.current_tab == index && !this.selected_theme) return
				this.current_tab = index
				this.selected_theme = null
				this.show_theme_dropdown = false
				let tab = this.tab_list[index]
				if (!tab) return
				this._loadTabData(tab)
			},
			// 主题下拉筛选
			toggle_theme_dropdown() {
				this.show_theme_dropdown = !this.show_theme_dropdown
			},
			select_theme_tab(themeTab) {
				this.show_theme_dropdown = false
				// 取消其他标签选中，设置选中主题
				this.selected_theme = themeTab
				this.current_tab = -1
				// 加载该主题绑定分类的活动列表
				if (themeTab.cate_id) {
					this.current_cate_id = themeTab.cate_id
					this.active_list = []
					this.active_page = 1
					this.active_total = 0
					this.load_active_list()
				}
			},
			// 加载当前分类的活动列表
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
			// 本月推荐 banner
			get_monthly_rec_banner() {
				return this.$u.api.find_slide({ id: 8 }).then(res => {
					if (res.data && res.data.length > 0) {
						this.monthly_rec_banner = res.data[0].image
					}
				})
			},
			// 加载本月推荐活动列表
			async load_monthly_rec_list() {
				this.monthly_rec_loading = true
				return this.$u.api.get_new_active_list({
					page: 1,
					is_month_recommend: 1
				}).then(res => {
					this.monthly_rec_loading = false
					let data = res.data || {}
					this.monthly_rec_list = data.data || []
				}).catch(err => {
					this.monthly_rec_loading = false
				})
			},
			// 跳转（type==1 答题考试 gating）
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
								if (res.confirm) that.$u.route('/pages/flhd/list')
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
			// 自治活动方法
			zzhd_navigator(url, type) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (this.userInfo.check_status != 2 && !type) return this.$u.toast('无权限，请先申请督导员')
				this.$u.route(url)
			},
			goShopSign() {
				if (this.shopInfo.status != 2) return this.$u.toast("请先申请商家")
				uni.navigateTo({ url: '/pages/zzhd/shop-sign' })
			},
			goApplyShop() {
				if (!this.$u.func.checkLogin()) return
				if (this.shopInfo.status == 1 || this.shopInfo.status == 3) {
					uni.navigateTo({ url: '/pages/zzhd/apply-status' }); return
				}
				uni.navigateTo({ url: '/pages/zzhd/apply-shop' })
			},
			async get_shop_new() {
				try { this.shopInfo = (await this.$u.api.get_shop_new({})).data } catch (e) { console.log(e) }
			},
			// 社交活动方法
			sjhd_navigator(url) { this.$u.route(url) },
			// tabbar
			updateTabBarSelected(index) {
				if (typeof this.$mp.page.getTabBar === 'function' && this.$mp.page.getTabBar()) {
					this.$mp.page.getTabBar().setSelected(index)
				}
			},
		},
		onReachBottom() {
			if (this.is_monthly_rec_tab || this.is_zzhd_tab || this.is_sjhd_tab) return
			if (!this.is_fixed_cate_tab && !this.is_theme_tab) return
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
	background: #F0F2F5;
}

/* ===== 顶部 Banner ===== */
.top-banner {
	width: 100%;
	background: #fff;
}

/* ===== 顶部标签栏：Facebook 风格横向滑动 ===== */
.cate-tab-bar-wrap {
	position: sticky;
	top: 0;
	z-index: 20;
	background: #fff;
	border-bottom: 1rpx solid #E4E6EB;
}
.cate-tab-bar {
	white-space: nowrap;
	height: 96rpx;
}
.cate-tab-list {
	display: inline-flex;
	align-items: center;
	padding: 0 16rpx;
	height: 100%;
}
.cate-tab-item {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 0 28rpx;
	height: 64rpx;
	margin: 0 6rpx;
	font-size: 28rpx;
	color: #65676B;
	background: #F0F2F5;
	border-radius: 32rpx;
	flex-shrink: 0;
	transition: all 0.2s ease;
	white-space: nowrap;
	font-weight: 500;
}
.cate-tab-item:active { opacity: 0.85; }
.cate-tab-active {
	color: #fff;
	background: #1877F2;
	font-weight: 600;
	box-shadow: 0 2rpx 8rpx rgba(24, 119, 242, 0.3);
}
.cate-tab-theme {
	color: #1877F2;
	background: #E7F0FE;
	font-weight: 600;
}

/* ===== 主题下拉筛选弹窗 ===== */
.theme-dropdown-mask {
	position: fixed;
	top: 0; left: 0; right: 0; bottom: 0;
	background: rgba(0, 0, 0, 0.4);
	z-index: 18;
}
.theme-dropdown-panel {
	position: absolute;
	top: 96rpx;
	left: 0; right: 0;
	background: #fff;
	z-index: 19;
	padding: 24rpx 30rpx;
	box-shadow: 0 8rpx 32rpx rgba(0, 0, 0, 0.12);
	border-radius: 0 0 24rpx 24rpx;
	max-height: 60vh;
	overflow-y: auto;
}
.theme-dropdown-title {
	font-size: 24rpx;
	color: #65676B;
	margin-bottom: 20rpx;
	padding-left: 4rpx;
	font-weight: 600;
}
.theme-dropdown-list {
	display: flex;
	flex-wrap: wrap;
	gap: 16rpx;
}
.theme-dropdown-item {
	padding: 16rpx 32rpx;
	font-size: 26rpx;
	color: #050505;
	background: #F0F2F5;
	border-radius: 32rpx;
	border: 2rpx solid transparent;
	transition: all 0.2s;
	font-weight: 500;
}
.theme-dropdown-active {
	color: #1877F2;
	background: #E7F0FE;
	border-color: #1877F2;
	font-weight: 600;
}

/* ===== 主内容区 ===== */
.main-content {
	padding-bottom: calc(88rpx + env(safe-area-inset-bottom));
}
.pane {
	padding: 16rpx 0;
}

/* ===== 本月推荐 ===== */
.monthly-rec-pane {
	padding: 16rpx 16rpx;
}

.empty-tip {
	text-align: center;
	padding: 100rpx 0;
	color: #65676B;
	font-size: 28rpx;
}
.loading-tip {
	text-align: center;
	padding: 30rpx 0;
	color: #65676B;
	font-size: 24rpx;
}

/* ===== 活动卡片：Facebook 风格 ===== */
.act-card {
	position: relative;
	display: flex;
	align-items: center;
	background: #fff;
	border-radius: 16rpx;
	margin: 0 16rpx 16rpx;
	padding: 16rpx;
	overflow: hidden;
	box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
	border: 1rpx solid #E4E6EB;
}
.act-card:active { opacity: 0.85; }

/* 本月推荐大卡片：纵向布局（上图下文） */
.monthly-rec-pane > .act-card {
	flex-direction: column;
	align-items: stretch;
	margin: 0 0 16rpx;
	padding: 0;
	overflow: hidden;
}

/* 卡片缩略图 */
.act-card-thumb,
.act-card-img {
	position: relative;
	width: 220rpx;
	height: 165rpx;
	flex-shrink: 0;
	margin-right: 16rpx;
	border-radius: 12rpx;
	overflow: hidden;
	background: #F0F2F5;
}
.monthly-rec-pane > .act-card > .act-card-img {
	width: 100%;
	height: 360rpx;
	margin-right: 0;
	margin-bottom: 0;
	border-radius: 0;
}
.act-card-thumb ::v-deep .u-image,
.act-card-img ::v-deep .u-image {
	width: 100% !important;
	height: 100% !important;
}

/* 参与人数 badge */
.card-participate {
	position: absolute;
	left: 12rpx; bottom: 12rpx;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	height: 36rpx;
	padding: 0 12rpx;
	background: rgba(0, 0, 0, 0.55);
	border-radius: 18rpx;
}
.card-participate .avatar-list { display: flex; }
.card-participate .avatar-img {
	width: 24rpx; height: 24rpx;
	border-radius: 50%;
	margin-left: -6rpx;
	border: 2rpx solid #fff;
	background: #F0F2F5;
}
.card-participate .avatar-img:first-child { margin-left: 0; }
.card-participate .count-text {
	color: #fff; font-size: 20rpx; margin-left: 6rpx; font-weight: 500;
}

/* 本月推荐参与 badge */
.act-card-badge {
	position: absolute;
	left: 16rpx; bottom: 16rpx;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	height: 40rpx;
	padding: 0 16rpx;
	background: rgba(0, 0, 0, 0.55);
	border-radius: 20rpx;
}
.badge-text {
	color: #fff; font-size: 22rpx; font-weight: 500;
}

/* 卡片信息区 */
.act-card-info {
	flex: 1; min-width: 0; padding: 0;
}
.monthly-rec-pane > .act-card > .act-card-info {
	padding: 20rpx 24rpx;
}
.act-card-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #050505;
	letter-spacing: -0.3rpx;
	line-height: 1.4;
}
.monthly-rec-pane > .act-card > .act-card-info .act-card-title {
	font-size: 34rpx;
	margin-bottom: 6rpx;
}
.act-card-desc {
	font-size: 24rpx;
	color: #65676B;
	line-height: 1.5;
	margin-top: 8rpx;
}
.act-card-sub {
	color: #65676B;
	font-size: 24rpx;
	margin-top: 6rpx;
}
.act-card-meta-row {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	margin-top: 10rpx;
	font-size: 22rpx;
	color: #65676B;
}
.meta-item {
	display: flex;
	align-items: center;
	min-width: 0;
}
.meta-item-loc { margin-left: 16rpx; }
.meta-item .u-icon { margin-right: 4rpx; }
.meta-text {
	max-width: 100%;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}

/* 本月推荐底部元数据 */
.act-card-meta {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-top: 12rpx;
}
.act-card-status {
	display: inline-block;
	font-size: 22rpx;
	color: #1877F2;
	font-weight: 600;
	padding: 4rpx 14rpx;
	background: #E7F0FE;
	border-radius: 8rpx;
}
.act-card-avatars { display: flex; }
.avatar-img {
	width: 36rpx; height: 36rpx;
	border-radius: 50%;
	margin-left: -10rpx;
	border: 2rpx solid #fff;
	background: #F0F2F5;
}
.avatar-img:first-child { margin-left: 0; }

/* 置顶打卡入口卡片 */
.top-pinned-card::before {
	content: '置顶';
	position: absolute;
	top: 0; right: 0;
	z-index: 10;
	padding: 6rpx 18rpx;
	font-size: 22rpx;
	line-height: 1.4;
	color: #fff;
	background: #1877F2;
	border-bottom-left-radius: 14rpx;
	border-top-right-radius: 12rpx;
	box-shadow: 0 2rpx 6rpx rgba(24, 119, 242, .35);
	font-weight: 600;
}

/* ===== Facebook 风格功能区入口 ===== */
.fb-section-title {
	font-size: 32rpx;
	font-weight: 700;
	color: #050505;
	padding-left: 8rpx;
	margin-top: 8rpx;
}
.fb-entry-card {
	background: #fff;
	border-radius: 16rpx;
	padding: 30rpx 0;
	text-align: center;
	box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
	border: 1rpx solid #E4E6EB;
	transition: all 0.2s;
}
.fb-entry-card:active {
	background: #F0F2F5;
	transform: scale(0.98);
}
.fb-entry-label {
	font-size: 26rpx;
	color: #050505;
	font-weight: 600;
}

/* ===== Facebook 风格列表项 ===== */
.fb-list-item {
	background: #fff;
	border-radius: 16rpx;
	padding: 28rpx 30rpx;
	box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
	border: 1rpx solid #E4E6EB;
}
.fb-list-item:active { background: #F0F2F5; }
.fb-list-title {
	font-size: 30rpx;
	color: #050505;
	font-weight: 600;
}
.fb-list-sub {
	font-size: 24rpx;
	color: #65676B;
}

/* ===== Banner 通知 ===== */
.fb-banner-wrap {
	border-radius: 0;
	overflow: hidden;
}
.fb-banner-notice {
	color: #fff;
	font-size: 26rpx;
	line-height: 1.5;
}
</style>
