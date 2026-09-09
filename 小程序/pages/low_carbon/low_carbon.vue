<template>
	<view class="container">
		<!-- 骨架屏 -->
		<skeleton-screen :show="isLoading" type="low_carbon"></skeleton-screen>
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

		<!-- 活动搜索入口（所有标签共用，点击进入搜索页） -->
		<view class="active-search-entry" @click="$u.route('/pages/low_carbon/active-search')">
			<view class="search-entry-box">
				<u-icon name="search" size="28" color="#b0b3bb"></u-icon>
				<text class="search-entry-text">搜索活动名称 / 地点</text>
				<!-- 扫码图标：点击呼出二维码扫码模块（扫码逻辑暂注释未启用，见 onScanQrcode） -->
				<view class="search-entry-scan" @click.stop="onScanQrcode">
					<u-icon name="scan" size="30" color="#8e8e93"></u-icon>
				</view>
			</view>
		</view>

		<!-- 本月推荐标签：展示海报+推荐活动列表 -->
	<view v-if="is_monthly_rec_tab" class="pane monthly-rec-pane">
		<view v-if="monthly_rec_banner" class="rec-banner">
			<u-image width="100%" height="100%" :src="monthly_rec_banner" mode="aspectFill"></u-image>
		</view>
		<view class="monthly-list-wrap">
			<view v-if="monthly_rec_list.length === 0 && !monthly_rec_loading" class="empty-tip">暂无推荐活动</view>
			<view v-else class="ios-card" v-for="item in monthly_rec_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<view class="ios-card-img">
					<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
					<view class="ios-card-badge" v-if="item.active_count > 0">
						<text class="badge-text">{{item.active_count}}人参与</text>
					</view>
				</view>
				<view class="ios-card-info">
					<view class="ios-card-title u-line-1">{{item.name}}</view>
					<view class="ios-card-desc u-line-2">{{item.dec || '精彩活动等你来参与'}}</view>
					<view class="ios-card-meta">
						<text class="ios-card-status" v-if="item.status_name">{{item.status_name}}</text>
						<view class="ios-card-avatar" v-if="item.avatar_array && item.avatar_array.length">
							<image :src="img" class="avatar-img" mode="aspectFill" v-for="(img,i) in item.avatar_array.slice(0,3)" :key="i"></image>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>

		<!-- 打卡标签（后台第一个固定标签，index===1）：绑定活动分类，顶部写死"垃圾分类打卡"入口卡片，下方动态加载该分类活动列表 -->
		<view v-else-if="is_daka_tab" class="pane">
			<view class="act-card top-pinned-card" @click="navigator('/pages/flhd/ljfldk',1)" style="margin: 24rpx 24rpx 30rpx;">
				<u-image width="100%" height="220" src="https://oss.ausite.cn/xcxkf052/admin/20250512/25a5d7f724a31ba6eccbe9d660e605ef.png?watermark"></u-image>
				<view class="act-card-info" style="padding: 20rpx 24rpx;">
					<view class="act-title">垃圾分类打卡</view>
					<view class="act-meta" style="color: #8e8e93;font-size: 22rpx;">参与垃圾分类学习通过考核后即可解锁</view>
				</view>
			</view>
			<view class="act-card" v-for="item in active_list" :key="item.id"
				@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
				<u-image width="100%" height="280" :src="item.thumb" mode="aspectFill"></u-image>
				<view class="card-participate" v-if="item.active_count > 0">
					<view class="avatar-list" v-if="item.avatar_array && item.avatar_array.length">
						<image :src="img" class="avatar-img" mode="aspectFill" v-for="(img,i) in item.avatar_array.slice(0,3)" :key="i"></image>
					</view>
					<text class="count-text">{{item.active_count}}人参与</text>
				</view>
				<view class="act-card-info">
					<view class="act-title u-line-1">{{item.name}}</view>
					<view class="act-meta">
						<u-icon name="clock" size="22"></u-icon>
						<text class="meta-text">{{item.date | date('yy-mm-dd')}}</text>
						<text class="meta-text" style="margin: 0 12rpx;">·</text>
						<u-icon name="map" size="22" v-if="item.address && item.address.trim()"></u-icon>
						<text class="meta-text" v-if="item.address && item.address.trim()">{{item.address}}</text>
					</view>
				</view>
			</view>
			<view v-if="active_list.length === 0 && !active_loading && !is_daka_tab" class="u-text-center u-p-60 u-type-info">暂无活动</view>
			<view v-if="active_loading" class="u-text-center u-p-30 u-type-info">加载中...</view>
			<view v-if="!active_loading && (is_daka_tab || (active_list.length > 0 && active_list.length >= active_total))" class="u-text-center u-p-30 u-type-info">没有更多了</view>
		</view>

		<!-- 自治活动标签（备份前第一个写死标签，复刻 zzhd.vue 主体内容，不引入页面级背景） -->
		<view v-else-if="is_zzhd_tab">
			<view class="u-rela">
				<u-image width="100%" height="260" src="https://oss.ausite.cn/xcxkf052/admin/20260617/08e04d3c0f112405a9b9c2d40c82862a.png?watermark"></u-image>
				<view class="u-p-20 white u-radius-10 u-abso u-abso-b-30 u-abso-l-30 u-abso-r-30" style="background: rgba(255,255,255,0.38);">
					{{setting.civilization_notice}}
				</view>
			</view>
			<view class="u-p-30">
				<!-- 督导员 -->
				<view class="">
					<view class="u-flex u-row-center">
						<u-divider color="#f9a04c" bg-color="rgba(0,0,0,0)" font-size="30">督导员</u-divider>
					</view>
					<view class="u-m-t-30 u-flex" style="gap:15rpx;">
						<view class="u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" style="flex:1;" @click="zzhd_navigator('/pages/zzhd/wmbb',1)">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/e48645c17b800739e2f492b8f95c4ba6.png?watermark"></u-image>
							</view>
							<view class="u-m-t-30 u-font-bold">
								督导员申请
							</view>
						</view>
						<view class="u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" style="flex:1;" @click="zzhd_navigator('/pages/zzhd/ranking')">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/6f8d9cc52ce9afca23824a19564aed64.png?watermark"></u-image>
							</view>
							<view class="u-m-t-30 u-font-bold">
								督导员排行
							</view>
						</view>
					</view>
				</view>
				<!-- 点位管理 -->
				<view class="u-m-t-30" v-if="!setting.app_test">
					<view class="u-flex u-row-center">
						<u-divider color="#f9a04c" bg-color="rgba(0,0,0,0)" font-size="30">点位管理</u-divider>
					</view>
					<view class="u-m-t-30 u-flex" style="gap:15rpx;">
						<view class="u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" style="flex:1;" @click="zzhd_navigator('/pages/zzhd/dwxj/dwxj?type=3')">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/51956890c56bc0241bad6c6eacf709dc.png?watermark"></u-image>
							</view>
							<view class="u-m-t-30 u-font-bold">
								督导巡查
							</view>
						</view>
						<view class="u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" style="flex:1;" @click="zzhd_navigator('/pages/zzhd/yxsj/yxsj?type=6')" v-if="setting.is_deed">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/18e7281ba65d9a3afb6bc9902b0e9139.png?watermark"></u-image>
							</view>
							<view class="u-m-t-30 u-font-bold">
								优秀事迹
							</view>
						</view>
						<view class="u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" style="flex:1;" @click="zzhd_navigator('/pages/zzhd/dwxj/dwxj?type=4')">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/ae1cb9fd359b5fe3d106e4293a174f57.png?watermark"></u-image>
							</view>
							<view class="u-m-t-30 u-font-bold">
								其他督导
							</view>
						</view>
					</view>
				</view>
				<!-- 商家自治 -->
				<view class="u-m-t-30" v-if="!setting.app_test">
					<view class="u-flex u-row-center">
						<u-divider color="#f9a04c" bg-color="rgba(0,0,0,0)" font-size="30">商家自治</u-divider>
					</view>
					<view class="u-m-t-30 u-flex" style="gap:15rpx;">
						<view class="u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" style="flex:1;" @click="goApplyShop">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20260611/8c95821e23155697d1e0a2fd8e8439c8.png?watermark"></u-image>
							</view>
							<view class="u-m-t-30 u-font-bold">
								商家申请
							</view>
						</view>
						<view class="u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" style="flex:1;" @click="goShopSign">
							<view class="u-flex u-row-center">
								<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20260611/1b473a1a2a5fde71f6d0aa98459bab92.png?watermark"></u-image>
							</view>
							<view class="u-m-t-30 u-font-bold">
								商家打卡
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<!-- 社交活动标签（备份前第二个写死标签，复刻 sjhd.vue 主体内容 + research.vue 问卷调查区块） -->
		<view v-else-if="is_sjhd_tab">
			<view class="">
				<u-image width="100%" height="260" src="https://oss.ausite.cn/xcxkf052/admin/20250512/5d288456e544b333329d345733847049.png?watermark"></u-image>
			</view>
			<view class="u-p-30">
				<!-- 文明标兵 -->
				<view class="">
					<view class="u-flex u-row-center">
						<u-image width="447" height="28" src="https://oss.ausite.cn/xcxkf052/admin/20250512/ef3263d29adf8ccfcce54e46827409ea.png?watermark" style="max-width:100%;width:auto;"></u-image>
					</view>
					<view class="u-m-t-30">
						<u-row gutter="15">
							<u-col span="4">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="sjhd_navigator('/pages/sjhd/list?type=2')" v-if="setting.is_course">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/6dd071cac6cdfc81e7297998c0a011ee.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										课程培训
									</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="sjhd_navigator('/pages/low_carbon/lsxf/lsxf?name=社区活动&type=3')" v-if="setting.is_active">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/8f9e9a13bb18f32ed74e6e7a43f4b081.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										社区活动
									</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="sjhd_navigator('/pages/sjhd/list?type=3')" v-if="setting.is_match">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/956ddc6fd692c9cf48c6b2dd2ef6f5d2.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										社区知识竞赛
									</view>
								</view>
							</u-col>
						</u-row>
					</view>
				</view>
				<!-- 宣传教育 -->
				<view class="u-m-t-30">
					<view class="u-flex u-row-center">
						<u-image width="447" height="28" src="https://oss.ausite.cn/xcxkf052/admin/20250512/cd4026f1bc19470be6bbf9efb0a9b38a.png?watermark" style="max-width:100%;width:auto;"></u-image>
					</view>
					<view class="u-m-t-30">
						<u-row gutter="15">
							<u-col span="4">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="sjhd_navigator('/pages/article/article?type=1')">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/6dd071cac6cdfc81e7297998c0a011ee.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										宣传手册
									</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="sjhd_navigator('/pages/article/article?type=2')">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/8f9e9a13bb18f32ed74e6e7a43f4b081.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										入户宣传
									</view>
								</view>
							</u-col>
							<u-col span="4">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center" @click="sjhd_navigator('/pages/zzhd/yxsj/yxsj?type=7')" v-if="!setting.is_environmental">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/f02ace719b53730c610058eda02a8006.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										环保论坛
									</view>
								</view>
							</u-col>
						</u-row>

						<u-row gutter="15">
							<u-col span="4">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-t-30" @click="sjhd_navigator('/pages/low_carbon/lsxf/lsxf?name=环保活动&type=2')"
									v-if="!setting.app_test">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/b2bc3031e9b1fcb65fb59b52c2be27f5.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										环保活动
									</view>
								</view>
							</u-col>
							<u-col span="4" v-if="!setting.is_mutual">
								<view class="u-width-100 u-type-white-bg u-radius-10 u-p-tb-30 u-text-center u-m-t-30" @click="sjhd_navigator('/pages/zzhd/yxsj/yxsj?type=5')">
									<view class="u-flex u-row-center">
										<u-image width="80" height="80" src="https://oss.ausite.cn/xcxkf052/admin/20250512/6e77a6e3afc5faefab1d80db1fb13e4c.png?watermark"></u-image>
									</view>
									<view class="u-m-t-30 u-font-bold">
										互助平台
									</view>
								</view>
							</u-col>
						</u-row>
					</view>
				</view>
				<!-- 问卷调查入口（点击跳转独立问卷列表页 pages/low_carbon/research） -->
				<view class="u-m-t-30">
					<view class="u-flex u-row-center">
						<u-divider color="#f9a04c" bg-color="rgba(0,0,0,0)" font-size="30">问卷调查</u-divider>
					</view>
					<view class="u-type-white-bg u-radius-10 u-p-30 u-m-t-30 u-flex u-row-between" style="align-items:center;" @click="sjhd_navigator('/pages/low_carbon/research')">
						<view class="">
							<view class="u-font-bold u-font-30">
								问卷调查
							</view>
							<view class="u-m-t-10 u-font-22 u-type-info">
								点击查看所有问卷，参与即可获得积分
							</view>
						</view>
						<u-icon name="arrow-right" color="#999" size="28"></u-icon>
					</view>
				</view>
			</view>
		</view>

	<!-- 中间标签（第二个起，非打卡非备份非自定义）：显示该标签绑定分类下的活动列表 -->
	<view v-else-if="!is_backup_tab" class="pane">
		<view class="act-card" v-for="item in active_list" :key="item.id"
			@click="$u.route(`/pages/low_carbon/lsxf/details-new?id=${item.id}`)">
			<u-image width="100%" height="280" :src="item.thumb" mode="aspectFill"></u-image>
			<view class="card-participate" v-if="item.active_count > 0">
				<view class="avatar-list" v-if="item.avatar_array && item.avatar_array.length">
					<image :src="img" class="avatar-img" mode="aspectFill" v-for="(img,i) in item.avatar_array.slice(0,3)" :key="i"></image>
				</view>
				<text class="count-text">{{item.active_count}}人参与</text>
			</view>
			<view class="act-card-info">
				<view class="act-title u-line-1">{{item.name}}</view>
				<view class="act-meta">
					<u-icon name="clock" size="22"></u-icon>
					<text class="meta-text">{{item.date | date('yy-mm-dd')}}</text>
					<text class="meta-text" style="margin: 0 12rpx;">·</text>
					<u-icon name="map" size="22" v-if="item.address && item.address.trim()"></u-icon>
					<text class="meta-text" v-if="item.address && item.address.trim()">{{item.address}}</text>
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
			<view class="u-flex-1">
				<u-notice-bar :volume-icon="false" mode="horizontal" :list="setting.carbon_notice" color="#333333" bg-color="#fff" border-radius="6"></u-notice-bar>
			</view>

		</view>
		<!-- <view class="u-m-t-30" @click="$u.route('/pages/dtsh/dtsh')" v-if="setting.is_waste">
			<u-image width="100%" height="240" src="https://oss.ausite.cn/xcxkf052/admin/20250606/68d14d845f207d15e52bdc15d735c387.png?watermark"></u-image>
		</view> -->
		<view class="u-flex u-m-lr-30">
			<image src="/static/images/lianjifenlei.png" style="flex:1;height:146rpx;margin-right:20rpx;" mode="aspectFill" @click="$u.route('/pages/dtsh/dtsh')"></image>
			<image src="/static/images/jiankangceshi2.png" style="flex:1;height:146rpx;" mode="aspectFill" @click="$u.route('/pages/low_carbon/research')"></image>
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
	import SkeletonScreen from '@/components/skeleton-screen/index.vue'
	export default {
		components: {
			SkeletonScreen
		},
		data() {
			return {
				isLoading: true,
				swiper_list: [],
				// 本月推荐标签顶部 banner 图（后台轮播图管理 id=8 组配置，单张图，不点击）
				monthly_rec_banner: '',
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
			// 本月推荐活动列表
			monthly_rec_list: [],
			monthly_rec_loading: false,
			// 自治活动标签：商家信息（复刻 zzhd.vue）
			shopInfo: {},
			// 社交活动标签：问卷调查列表（复刻 research.vue）
			health_list: [],
		};
	},
	computed: {
			// 当前是否在最后一个"备份"标签（前端写死，后台 active_tab 表不可见）
			is_backup_tab() {
				return this.tab_list.length > 0
					&& this.current_tab == this.tab_list.length - 1
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_backup === 1
			},
			// 自治活动标签（备份前第一个写死标签，复刻 zzhd.vue）
			is_zzhd_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'zzhd'
			},
			// 社交活动标签（备份前第二个写死标签，复刻 sjhd.vue + research.vue）
			is_sjhd_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'sjhd'
			},
			// 本月推荐标签（前端写死第一个标签，unshift 到 tab_list 头部）
			is_monthly_rec_tab() {
				return this.tab_list.length > 0
					&& this.tab_list[this.current_tab]
					&& this.tab_list[this.current_tab]._is_custom === 'monthly_rec'
			},
			// 打卡标签：后台返回的第一个固定标签，index 必为 1（本月推荐 unshift 在 0 位）
			is_daka_tab() {
				return this.current_tab === 1
			}
		},
		onLoad(options) {
			// 支持通过 URL 参数 tab 指定打开哪个标签（值为标签名，如"本月推荐"、"环保分类"等）
			this._tabQueryName = options ? options.tab : ''
			this.loadAllData()
		},
		onShow() {
			// 更新自定义tabbar选中态
			this.updateTabBarSelected(1)
			// 检查 banner 跳转标记：从首页 banner 或"社区活动"入口跳过来需要切到"本月推荐"标签
			let jumpMonthlyRec = uni.getStorageSync('jump_monthly_rec')
			if (jumpMonthlyRec) {
				// tab_list 已加载：立即消费标记并切换；未加载：保留标记，等 get_active_tab_list 回调消费
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
				// API 失败时 isLogin 被置为 false，直接返回
				if (!this.isLogin) return
				// 强制学习/考试检查：未学完跳垃圾分类指导，未通过考试跳答题考试
				if (!this.userInfo.is_learn) {
					this.$u.route('/pages/flhd/list')
				} else if (!this.userInfo.is_answer) {
					this.$u.route('/pages/answer/examine?id=1')
				}
			})
		},
		methods: {
		// 并行加载所有数据，全部完成后隐藏骨架屏（最少显示 400ms 防止闪烁）
		async loadAllData() {
			const startTime = Date.now()
			try {
				await Promise.all([
					this.find_slide(),
					this.get_monthly_rec_banner(),
					this.get_new_cate_active_list(),
					this.get_active_tab_list()
				])
			} catch (e) {
				console.log('数据加载失败:', e)
			} finally {
				// 加载推荐活动列表
				await this.load_monthly_rec_list()
				// 确保骨架屏最少显示 400ms，防止加载过快导致闪烁
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
			// 获取用户信息
			getUserInfo() {
				return this.$u.api.getUserInfo().then(res => {
					this.$u.vuex('userInfo', res.data)
				}).catch(err => {
					this.$u.vuex('userInfo', {})
					this.$u.vuex('isLogin', false)
				})
			},
		// 活动分类
		get_new_cate_active_list() {
			return this.$u.api.get_new_cate_active_list({
				page: this.page,
			}).then(res => {
				this.cate_list = res.data || []
			}).catch(err => {
				this.$u.toast(err.msg)
			})
		},
		// 活动页标签：后台 active_tab 表返回（最多4个，第一个为固定打卡标签），
		// 前端在头部 unshift "本月推荐"写死标签，自治活动/社交活动追加在后台标签后，备份标签注释掉
		get_active_tab_list() {
			return this.$u.api.get_active_tab_list().then(res => {
				let list = res.data || []
				// 头部 unshift 本月推荐标签（前端写死第一个）
				list.unshift({ id: -99, name: '本月推荐', cate_id: 0, is_fixed: 2, _is_custom: 'monthly_rec' })
				list.push({ id: -2, name: '自治活动', cate_id: 0, is_fixed: 2, _is_custom: 'zzhd' })
				list.push({ id: -3, name: '社交活动', cate_id: 0, is_fixed: 2, _is_custom: 'sjhd' })
				// list.push({ id: -1, name: '备份', cate_id: 0, is_fixed: 2, _is_backup: 1 })
				this.tab_list = list

			// 优先处理 URL 参数 tab（二维码扫码跳转场景）
			if (this._tabQueryName) {
				let targetIndex = list.findIndex(t => t.name === this._tabQueryName)
				if (targetIndex >= 0) {
					this.current_tab = targetIndex
					let targetTab = list[targetIndex]
					// 触发对应标签的加载逻辑
					if (targetTab._is_custom === 'monthly_rec') {
						this.load_monthly_rec_list()
					} else if (targetTab._is_custom === 'zzhd') {
						if (this.isLogin) {
							this.getUserInfo()
							this.get_shop_new()
						}
					} else if (targetTab._is_custom === 'sjhd') {
						// 社交活动无需预加载
					} else if (targetTab._is_backup === 1) {
						// 备份标签无需预加载
					} else if (targetTab.cate_id) {
						this.current_cate_id = targetTab.cate_id
						this.active_list = []
						this.active_page = 1
						this.active_total = 0
						this.load_active_list()
					}
				}
				this._tabQueryName = ''
				return
			}

			// 检查是否有跳转标记（banner 或"社区活动"入口）
			let jumpMonthlyRec = uni.getStorageSync('jump_monthly_rec')
			if (jumpMonthlyRec) {
				uni.removeStorageSync('jump_monthly_rec')
				this.current_tab = 0
				return
			}
			// 默认加载"本月推荐"标签（current_tab==0），若本月推荐为空则 fallback 到第一个有 cate_id 的后台标签
			let firstTab = list[0]
			if (firstTab._is_custom === 'monthly_rec') {
				// 本月推荐仅展示海报，无需加载列表
			} else if (firstTab && firstTab.cate_id) {
				this.current_cate_id = firstTab.cate_id
				this.active_list = []
				this.active_page = 1
				this.active_total = 0
				this.load_active_list()
			}
		}).catch(err => {
			this.$u.toast(err.msg)
		})
	},
		// 切换标签：第1个固定打卡标签也加载分类活动列表（顶部仍保留"垃圾分类打卡"入口卡片）；
		// 最后一个"备份"标签显示现有内容；
		// 自治活动标签加载商家信息（复刻 zzhd.vue onShow）；社交活动标签无需预加载列表；
		// 中间分类标签加载该标签绑定分类下的活动列表
		switch_tab(index) {
			if (this.current_tab == index) return
			this.current_tab = index
			let tab = this.tab_list[index]
			if (!tab) return
			// 备份标签不加载分类列表
			if (tab._is_backup === 1) return
		// 本月推荐标签：加载推荐活动列表
		if (tab._is_custom === 'monthly_rec') {
				this.load_monthly_rec_list()
				return
			}
			// 自治活动标签：加载用户信息和商家信息
				if (tab._is_custom === 'zzhd') {
					if (this.isLogin) {
						this.getUserInfo()
						this.get_shop_new()
					}
					return
				}
				// 社交活动标签：问卷调查改为入口按钮，点击跳独立页，无需预加载列表
				if (tab._is_custom === 'sjhd') return
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
			// 点击搜索框右侧扫码图标：呼出二维码扫码模块（真实拉起系统扫码）
			// 说明：扫码成功后的业务跳转暂未接入（见 success 中 TODO），当前仅打印扫码内容
			onScanQrcode() {
				// #ifdef H5
				// 浏览器环境没有系统扫码能力，扫码仅支持在微信小程序端调用
				this.$u.toast('扫码需在微信小程序内使用')
				// #endif
				// #ifndef H5
				uni.scanCode({
					onlyFromCamera: false,
					success: (res) => {
						// 扫码成功：res.result 为二维码内容，解析出活动id后直达活动详情
						const raw = (res && res.result != null) ? String(res.result).trim() : ''
						console.log('scanCode result =>', raw)
						if (!raw) return this.$u.toast('未识别到二维码内容')
						let activeId = ''
						// 兼容三种内容格式：纯数字活动id / scene=xx / ?id=xx
						if (/^\d+$/.test(raw)) {
							activeId = raw
						} else {
							const m = raw.match(/scene=(\d+)/) || raw.match(/[?&]id=(\d+)/)
							if (m) activeId = m[1]
						}
						if (activeId) {
							this.$u.route('/pages/low_carbon/lsxf/details-new?id=' + activeId)
						} else {
							this.$u.toast('未识别到活动二维码')
						}
					},
					fail: (err) => {
						// 用户手动取消扫码时静默处理，其它异常打印便于排查
						const msg = (err && err.errMsg) || ''
						if (msg.indexOf('cancel') === -1) console.log('scanCode fail =>', msg)
					}
				})
				// #endif
			},
			// 跳转页面（type==1 为垃圾分类打卡入口，需先通过答题考试）
			navigator(url, type) {
				// 垃圾分类打卡：允许未登录用户进入详情页，点击打卡时再跳登录
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
				// 其他类型保持原逻辑：未登录跳登录
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
			return this.$u.api.find_slide({
				id: 5
			}).then(res => {
				this.swiper_list = res.data || []
			})
		},
	// 本月推荐标签顶部 banner 图（后台轮播图管理 id=8 组，单张图，不点击）
	get_monthly_rec_banner() {
		return this.$u.api.find_slide({
			id: 8
		}).then(res => {
			if (res.data && res.data.length > 0) {
				this.monthly_rec_banner = res.data[0].image
			}
		})
	},
	// 加载本月推荐活动列表（is_month_recommend=1）
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
			console.log('推荐活动加载失败:', err)
		})
	},
			// ===== 自治活动标签方法（复刻 zzhd.vue，与首页 navigator 区分以保留督导员权限校验） =====
			// 跳转页面（type 真值=跳过督导员权限校验，例如督导员申请本身就不需要先有督导员身份）
			zzhd_navigator(url, type) {
				if (!this.isLogin) return this.$u.route('/pages/public/login')
				if (this.userInfo.check_status != 2 && !type) return this.$u.toast('无权限，请先申请督导员')
				this.$u.route(url)
			},
			// 商家打卡
			goShopSign() {
				if (this.shopInfo.status != 2) return this.$u.toast("请先申请商家")
				uni.navigateTo({
					url: '/pages/zzhd/shop-sign'
				})
			},
			// 商家申请（根据当前审核状态跳不同页）
			goApplyShop() {
				if (!this.$u.func.checkLogin()) return
				if (this.shopInfo.status == 1 || this.shopInfo.status == 3) {
					uni.navigateTo({
						url: '/pages/zzhd/apply-status'
					})
					return
				}
				uni.navigateTo({
					url: '/pages/zzhd/apply-shop'
				})
			},
			// 跳转其他小程序
			navigateToMiniProgram(appId) {
				uni.navigateToMiniProgram({
					appId,
				})
			},
			// 获取商家信息
			async get_shop_new() {
				try {
					let res = await this.$u.api.get_shop_new({})
					this.shopInfo = res.data
				} catch (e) {
					console.log(e)
				}
			},
			// ===== 社交活动标签方法（复刻 sjhd.vue + research.vue 问卷调查） =====
			// 社交活动入口直接跳转（不做登录/权限校验，与 sjhd.vue 一致）
			sjhd_navigator(url) {
				this.$u.route(url)
			},
			// 问卷调研按钮（复刻 research.vue wj_btn）
			wj_btn(item) {
				this.$u.route('/pages/health/participate?id=' + item.id)
			},
			// 获取问卷调查列表（复刻 research.vue get_health_home_list）
			get_health_home_list(type) {
				this.$u.api.get_health_home_list({
					type: type,
				}).then(res => {
					this.health_list = res.data
				}).catch(err => {
					this.$u.toast(err.msg)
				})
			},
			// 更新自定义tabbar选中索引
			updateTabBarSelected(index) {
				if (typeof this.$mp.page.getTabBar === 'function' && this.$mp.page.getTabBar()) {
					this.$mp.page.getTabBar().setSelected(index)
				}
			},
		},
	onReachBottom() {
		// 打卡标签和中间分类标签支持分页；本月推荐/备份/自治/社交标签不分页
		if (this.is_backup_tab || this.is_zzhd_tab || this.is_sjhd_tab || this.is_monthly_rec_tab) return
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

	/* 活动搜索入口（吸顶于右侧内容区顶部） */
	.active-search-entry {
		position: sticky;
		top: 0;
		z-index: 20;
		padding: 16rpx 20rpx;
		background: #F5F5F5;
	}

	.search-entry-box {
		display: flex;
		align-items: center;
		height: 64rpx;
		padding: 0 24rpx;
		background: #ffffff;
		border-radius: 32rpx;
		box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.04);
	}

	.search-entry-text {
		flex: 1;
		margin-left: 12rpx;
		font-size: 26rpx;
		color: #b0b3bb;
	}

	/* 搜索框右侧扫码图标 */
	.search-entry-scan {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 52rpx;
		height: 52rpx;
		margin-left: 8rpx;
		padding-left: 20rpx;
		border-left: 1rpx solid #eeeeee;
	}

	/* 左右联动布局：左侧固定宽度侧边栏 + 右侧自适应内容区 */
	.layout-wrapper {
		display: flex;
		min-height: calc(100vh - 88rpx - var(--status-bar-height, 0px));
		align-items: flex-start;
	}

	/* iOS 风格通用活动卡片（打卡标签 + 中间分类标签共用） */
	.act-card {
		position: relative;
		background: #fff;
		border-radius: 24rpx;
		margin: 0 24rpx 24rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06), 0 1rpx 3rpx rgba(0, 0, 0, 0.04);
	}
	.act-card:active {
		opacity: 0.85;
	}

	/* 卡片内活动图片 */
	.act-card > .u-image {
		display: block;
		width: 100% !important;
		height: 360rpx !important;
	}

	/* 参与人数 + 头像 badge（浮在图片左下角） */
	.card-participate {
		position: absolute;
		left: 16rpx;
		bottom: 16rpx;
		z-index: 2;
		display: inline-flex;
		align-items: center;
		height: 40rpx;
		padding: 0 14rpx;
		background: rgba(0, 0, 0, 0.5);
		border-radius: 20rpx;
	}
	.card-participate .avatar-list {
		display: flex;
	}
	.card-participate .avatar-img {
		width: 28rpx;
		height: 28rpx;
		border-radius: 50%;
		margin-left: -8rpx;
		border: 2rpx solid #fff;
		background: #e5e5ea;
	}
	.card-participate .avatar-img:first-child {
		margin-left: 0;
	}
	.card-participate .count-text {
		color: #fff;
		font-size: 20rpx;
		margin-left: 8rpx;
	}

	/* 卡片信息区 */
	.act-card-info {
		padding: 20rpx 24rpx 24rpx;
	}
	.act-card-info .act-title {
		font-size: 30rpx;
		font-weight: 600;
		color: #1c1c1e;
		letter-spacing: -0.3rpx;
	}
	.act-card-info .act-meta {
		display: flex;
		align-items: center;
		margin-top: 12rpx;
		font-size: 22rpx;
		color: #8e8e93;
	}
	.act-card-info .act-meta .u-icon {
		margin-right: 4rpx;
	}
	.act-card-info .act-meta .meta-text {
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
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
		min-height: calc(100vh - 88rpx - var(--status-bar-height, 0px));
		max-height: calc(100vh - 88rpx - var(--status-bar-height, 0px));
		overflow-y: auto;
		background: #ffffff;
		border-right: 1rpx solid #f0f0f0;
		box-sizing: border-box;

		.tab-item {
			position: relative;
			padding: 24rpx 10rpx;
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

				/* 左侧绿色色条标识当前选中 */
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

	/* 右侧内容区：flex:1 自适应宽度 + 独立滚动 + 防横向溢出 */
	.content-area {
		flex: 1;
		min-width: 0;
		min-height: calc(100vh - 88rpx - var(--status-bar-height, 0px));
		max-height: calc(100vh - 88rpx - var(--status-bar-height, 0px));
		overflow-y: auto;
		overflow-x: hidden;
		box-sizing: border-box;
	}

	/* 本月推荐：内容区自适应高度 */
	.monthly-rec-pane {
		width: 100%;
		padding: 0 !important;
	}

	/* 本月推荐海报 - 占屏幕可视区 0.7 */
	.rec-banner {
		width: 100%;
		height: calc(70vh - 88rpx - var(--status-bar-height, 0px));
		display: block;
		background: #f2f2f7;

		::v-deep .u-image {
			width: 100% !important;
			height: 100% !important;
		}

		image {
			width: 100%;
			height: 100%;
		}
	}

	/* 卡片列表区域 */
	.monthly-list-wrap {
		padding: 24rpx;
		background: #f2f2f7;
	}

	.empty-tip {
		text-align: center;
		padding: 80rpx 0;
		color: #8e8e93;
		font-size: 28rpx;
	}

	/* iOS 大卡片 */
	.ios-card {
		display: flex;
		flex-direction: column;
		background: #fff;
		border-radius: 24rpx;
		margin-bottom: 20rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.06), 0 1rpx 3rpx rgba(0, 0, 0, 0.04);
	}
	.ios-card:active {
		opacity: 0.85;
	}

	/* 卡片图片 */
	.ios-card-img {
		width: 100%;
		height: 360rpx;
		position: relative;
		background: #e5e5ea;

		::v-deep .u-image {
			width: 100% !important;
			height: 100% !important;
		}
	}

	/* 参与人数 badge */
	.ios-card-badge {
		position: absolute;
		left: 16rpx;
		bottom: 16rpx;
		z-index: 2;
		display: inline-flex;
		align-items: center;
		height: 40rpx;
		padding: 0 14rpx;
		background: rgba(0, 0, 0, 0.5);
		border-radius: 20rpx;
	}
	.badge-text {
		color: #fff;
		font-size: 20rpx;
		font-weight: 500;
		line-height: 1;
	}

	/* 卡片信息区 */
	.ios-card-info {
		padding: 20rpx 24rpx 24rpx;
	}

	.ios-card-title {
		font-size: 30rpx;
		font-weight: 600;
		color: #1c1c1e;
		line-height: 1.3;
		letter-spacing: -0.3rpx;
	}

	.ios-card-desc {
		font-size: 24rpx;
		color: #8e8e93;
		line-height: 1.4;
		margin-top: 6rpx;
	}

	/* 底部元数据行 */
	.ios-card-meta {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-top: 14rpx;
	}

	.ios-card-status {
		display: inline-block;
		font-size: 20rpx;
		color: #34c759;
		font-weight: 600;
	}

	.ios-card-avatar {
		display: flex;
	}
	.avatar-img {
		width: 36rpx;
		height: 36rpx;
		border-radius: 50%;
		margin-left: -10rpx;
		border: 2rpx solid #fff;
		background: #e5e5ea;
	}
	.avatar-img:first-child {
		margin-left: 0;
	}
</style>
