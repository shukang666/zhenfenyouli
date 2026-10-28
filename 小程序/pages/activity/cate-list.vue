<template>
	<view class="container">
		<!-- 公益行动：真分入口 COS 整图（与活动 Tab 同款，非底部工作台大图） -->
		<view
			class="fb-zhenfen-entry-poster-wrap"
			v-if="cate_id === 5"
			@click="gotoGarbageClassify"
		>
			<image
				class="fb-zhenfen-entry-poster-img"
				:src="zhenfenEntryBanner"
				mode="widthFix"
				show-menu-by-longpress="false"
			/>
		</view>

		<!-- 社区专题：全部 4 项（与 garbage-classify 同款） -->
		<view class="fb-section fb-spot-modules" v-if="cate_id === 5" id="cate-spot">
			<view class="fb-section-header">
				<view class="fb-hd">
					<view class="fb-entry-kicker-dot"></view>
					<text class="fb-section-title">社区专题</text>
				</view>
			</view>
			<view class="fb-spot-grid">
				<view
					class="fb-spot-card fb-spot-card--poster"
					v-for="mod in gc_spot_modules"
					:key="mod.key"
					hover-class="fb-press"
					:hover-stay-time="80"
					@click="gotoSpotModule(mod)"
				>
					<image
						class="fb-spot-poster-img"
						:src="spotModuleCover(mod)"
						mode="widthFix"
					/>
				</view>
			</view>
		</view>

		<!-- 活动列表 -->
		<view class="fb-act-list">
			<view class="fb-act-card" v-for="item in active_list" :key="item.id"
				@click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)">
				<view class="fb-act-cover">
					<u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
					<view class="fb-act-badge" v-if="item.active_count > 0">
						<view class="fb-act-avatars" v-if="item.avatar_array && item.avatar_array.length">
							<image :src="img" class="fb-act-avatar" mode="aspectFill"
								v-for="(img, i) in item.avatar_array.slice(0, 3)" :key="i"></image>
						</view>
						<text class="fb-act-badge-text">{{item.active_count}}人参与</text>
					</view>
				</view>
				<view class="fb-act-body">
					<view class="fb-act-title u-line-1">{{item.name}}</view>
					<view class="fb-act-meta">
						<view class="fb-meta-item">
							<u-icon name="clock" size="22" color="#65676B"></u-icon>
							<text class="fb-meta-text">{{item.date | date('yy-mm-dd')}}</text>
						</view>
						<view class="fb-meta-item" v-if="item.address && item.address.trim()">
							<u-icon name="map" size="22" color="#65676B"></u-icon>
							<text class="fb-meta-text u-line-1">{{item.address}}</text>
						</view>
					</view>
				</view>
			</view>

			<view class="fb-empty" v-if="active_list.length === 0 && !active_loading && cate_id !== 5">
				<text class="fb-empty-text">暂无活动</text>
			</view>

			<!-- 加载提示 -->
			<view class="fb-load-tip" v-if="active_loading">加载中...</view>
			<view class="fb-load-tip"
				v-if="!active_loading && active_list.length > 0 && active_list.length >= active_total">没有更多了</view>
		</view>
	</view>
</template>

<script>
import settings from "@/common/settings.js";
import { ACTIVITY_TAB_ENTRY_BANNER_URL } from "@/common/activityTabScreenshot.js";

/** 社区专题：整卡仅一张图（文案/图标都在图里） */
const SPOT_MODULE_COVER_VER = "2026101415";
function spotModuleCoverUrl(fileName) {
	return (
		settings.staticAssetBase +
		"spot-modules/" +
		fileName +
		"?v=" +
		SPOT_MODULE_COVER_VER
	);
}
const SPOT_MODULE_COVERS = {
	qxl: spotModuleCoverUrl("qxl-cover.png"),
	hiking: spotModuleCoverUrl("hiking-cover.png"),
	flea_market: spotModuleCoverUrl("flea-market-cover.png"),
	zhenfen_class: spotModuleCoverUrl("zhenfen-class-cover.png"),
};

/** 社区专题卡片展示文案 */
const SPOT_MODULES_DEFAULT = [
	{
		key: "qxl",
		name: "我的七贤岭",
		subtitle: "认识 · 发现",
		emoji: "⛰️",
		cover: SPOT_MODULE_COVERS.qxl,
		entry_type: "qxl_page",
	},
	{
		key: "hiking",
		name: "步行山海，净美家园",
		subtitle: "边走边捡 · 洁净山野",
		emoji: "🥾",
		cover: SPOT_MODULE_COVERS.hiking,
		entry_type: "spot_hub",
	},
	{
		key: "flea_market",
		name: "七贤岭绿色二手集市",
		subtitle: "闲置循环 · 绿色生活",
		emoji: "♻️",
		cover: SPOT_MODULE_COVERS.flea_market,
		entry_type: "spot_hub",
	},
	{
		key: "zhenfen_class",
		name: "垃圾真分小课堂",
		subtitle: "学分类 · 真投放",
		emoji: "📗",
		cover: SPOT_MODULE_COVERS.zhenfen_class,
		entry_type: "spot_hub",
	},
];

function mergeSpotModulesFromApi(fromApi) {
	if (!Array.isArray(fromApi) || fromApi.length < 4) {
		return SPOT_MODULES_DEFAULT.map((m) => ({ ...m }));
	}
	const remoteByKey = {};
	fromApi.forEach((item) => {
		if (item && item.key) remoteByKey[item.key] = item;
	});
	return SPOT_MODULES_DEFAULT.map((local) => {
		const remote = remoteByKey[local.key];
		if (!remote) return { ...local };
		return {
			...local,
			entry_type: remote.entry_type || local.entry_type,
		};
	});
}

	export default {
		data() {
			return {
				zhenfenEntryBanner: ACTIVITY_TAB_ENTRY_BANNER_URL,
				cate_id: 0,
				cate_name: '',
				active_list: [],
				active_page: 1,
				active_total: 0,
				active_loading: false,
				gc_spot_modules: SPOT_MODULES_DEFAULT.map((m) => ({ ...m })),
			};
		},
		onLoad(options) {
			this.cate_id = parseInt(options.cate_id || 0)
			this.cate_name = decodeURIComponent(options.name || '活动')
			// 动态设置导航栏标题为当前分类名
			uni.setNavigationBarTitle({ title: this.cate_name })
			this.loadActiveList()
			if (this.cate_id === 5) {
				this.loadSpotModules()
			}
		},
		methods: {
			loadActiveList() {
				if (this.active_loading) return
				this.active_loading = true

				this.$u.api.get_new_active_list({
					page: this.active_page,
					community_cate_id: this.cate_id,
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

			gotoGarbageClassify() {
				this.$u.route('/pages/activity/garbage-classify');
			},

			async loadSpotModules() {
				try {
					const res = await this.$u.api.get_activity_spot_modules();
					const list = res.data || [];
					if (Array.isArray(list) && list.length >= 4) {
						this.gc_spot_modules = mergeSpotModulesFromApi(list);
					}
				} catch (e) {
					/* 保留本地默认四项 */
				}
			},
			spotModuleCover(mod) {
				if (!mod) return "";
				const c = mod.cover || "";
				if (/^https?:\/\//i.test(c)) return c;
				return c;
			},
			gotoSpotModule(mod) {
				if (!mod || !mod.key) return;
				if (mod.entry_type === "qxl_page" || mod.key === "qxl") {
					this.gotoQxl();
					return;
				}
				this.$u.route(
					`/pages/activity/activity-spot-hub?spot=${mod.key}&name=${encodeURIComponent(
						mod.name || ""
					)}`
				);
			},
			gotoQxl() {
				this.$u.route("/pages/activity/love-qixianling");
			},

			navigateBack() {
				uni.navigateBack()
			},
		},
		onReachBottom() {
			if (this.active_list.length >= this.active_total) return
			if (this.active_loading) return
			this.active_page++
			this.loadActiveList()
		}
	}
</script>

<style lang="scss" scoped>
.container {
	min-height: 100vh;
	background: #F0F2F5;
}

/* ===== 导航栏 ===== */
.fb-navbar {
	position: sticky;
	top: 0;
	z-index: 30;
	display: flex;
	align-items: center;
	justify-content: space-between;
	background: #fff;
	border-bottom: 1rpx solid #E4E6EB;
	padding: 0 16rpx;
	height: 88rpx;
	padding-top: env(safe-area-inset-top);
}
.fb-nav-back {
	width: 80rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}
.fb-nav-title {
	font-size: 32rpx;
	font-weight: 600;
	color: #050505;
	flex: 1;
	text-align: center;
}
.fb-nav-placeholder {
	width: 80rpx;
}

/* ===== 真分入口整图 ===== */
.fb-zhenfen-entry-poster-wrap {
  padding: 16rpx;
}
.fb-zhenfen-entry-poster-img {
  width: 100%;
  display: block;
  border-radius: 20rpx;
  box-shadow: 0 12rpx 32rpx rgba(106, 79, 158, 0.22);
}

.fb-press {
  opacity: 0.92;
  transform: scale(0.98);
}

/* ===== 通用板块标题（社区专题） ===== */
.fb-section {
  margin: 28rpx 0 8rpx;
}
.fb-section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24rpx 18rpx;
}
.fb-hd {
  display: flex;
  align-items: center;
  min-width: 0;
}
.fb-entry-kicker-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 50%;
  background: #18af38;
  margin-right: 10rpx;
  box-shadow: 0 0 0 6rpx rgba(24, 175, 56, 0.18);
}
.fb-section-title {
  font-size: 34rpx;
  font-weight: 700;
  color: #1c2b1e;
  margin-right: 8rpx;
}

/* ===== 社区专题网格（4 项） ===== */
.fb-spot-modules {
  padding-top: 8rpx;
}
.fb-spot-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16rpx;
  padding: 0 16rpx 8rpx;
}
.fb-spot-card--poster {
  border-radius: 20rpx;
  padding: 0;
  overflow: hidden;
  box-shadow: 0 12rpx 30rpx rgba(31, 54, 42, 0.1),
    0 0 0 1rpx rgba(255, 255, 255, 0.72);
  background: transparent;
}
.fb-spot-poster-img {
  width: 100%;
  display: block;
  border-radius: 20rpx;
}

/* ===== 活动列表 ===== */
.fb-act-list {
	padding: 16rpx;
	padding-bottom: calc(88rpx + env(safe-area-inset-bottom));
}
.fb-act-card {
	background: #fff;
	border-radius: 16rpx;
	margin-bottom: 16rpx;
	overflow: hidden;
	box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
	border: 1rpx solid #E4E6EB;
}
.fb-act-card:active { opacity: 0.92; }
.fb-act-cover {
	position: relative;
	width: 100%;
	height: 320rpx;
	background: #F0F2F5;
}
.fb-act-badge {
	position: absolute;
	left: 12rpx;
	bottom: 12rpx;
	z-index: 2;
	display: inline-flex;
	align-items: center;
	height: 36rpx;
	padding: 0 14rpx;
	background: rgba(0, 0, 0, 0.55);
	border-radius: 18rpx;
}
.fb-act-avatars { display: flex; }
.fb-act-avatar {
	width: 24rpx; height: 24rpx;
	border-radius: 50%;
	margin-left: -6rpx;
	border: 2rpx solid #fff;
	background: #F0F2F5;
}
.fb-act-avatar:first-child { margin-left: 0; }
.fb-act-badge-text {
	color: #fff;
	font-size: 20rpx;
	font-weight: 500;
	margin-left: 6rpx;
}
.fb-act-body { padding: 20rpx 24rpx 22rpx; }
.fb-act-title {
	font-size: 30rpx;
	font-weight: 600;
	color: #050505;
	line-height: 1.4;
}
.fb-act-meta {
	display: flex;
	flex-wrap: wrap;
	gap: 20rpx;
	margin-top: 12rpx;
}
.fb-meta-item {
	display: flex;
	align-items: center;
	min-width: 0;
	flex: 1;
}
.fb-meta-item .u-icon { margin-right: 4rpx; }
.fb-meta-text {
	font-size: 22rpx;
	color: #65676B;
	flex: 1;
	overflow: hidden;
	white-space: nowrap;
	text-overflow: ellipsis;
}

.fb-empty { text-align: center; padding: 100rpx 0; }
.fb-empty-text { font-size: 28rpx; color: #65676B; }
.fb-load-tip { text-align: center; padding: 30rpx 0; font-size: 24rpx; color: #65676B; }
</style>
