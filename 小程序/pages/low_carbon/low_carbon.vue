<template>
  <view
    class="container glass-page"
    @touchstart="onCareTouchStart"
    @touchmove="onCareTouchMove"
    @touchend="onCareTouchEnd"
  >
    <view class="glass-page__ambient"></view>
    <!-- 关怀版下拉提示 -->
    <view
      class="care-pull-layer"
      :style="{ height: carePullHeight + 'px', opacity: careHintOpacity }"
      v-if="showCareHint"
    >
      <view class="care-pull-content">
        <text class="care-pull-text">{{
          carePullHeight >= 90 ? "松开进入关怀版" : "下拉进入关怀版"
        }}</text>
        <u-icon
          :name="carePullHeight >= 90 ? 'arrow-up' : 'arrow-down'"
          size="28"
          color="#18AF38"
        ></u-icon>
      </view>
    </view>
    <!-- 自定义顶部导航：搜索栏 -->
    <view class="fb-navbar glass-nav">
      <view class="fb-navbar-inner">
        <view class="fb-search-box glass-search" id="guide-fb-search" @click="gotoSearch">
          <view class="fb-search-ico">
            <u-icon name="search" size="28" color="#18AF38"></u-icon>
          </view>
          <text class="fb-search-ph">搜索活动名称 / 地点</text>
        </view>
      </view>
    </view>

    <care-pull-hint :visible="!showCareHint"></care-pull-hint>

    <!-- 活动 Tab 顶部 Banner（在关怀版拉绳下方，纯展示无点击） -->
    <view class="fb-activity-top-banner" v-if="activityTabTopBanner">
      <image
        class="fb-activity-top-banner-img"
        :src="activityTabTopBanner"
        mode="widthFix"
        show-menu-by-longpress="false"
      />
    </view>

    <skeleton-screen :show="isLoading" type="low_carbon"></skeleton-screen>

    <!-- 轻任务：后台配置的 Web 式活动，作为活动页醒目线上入口 -->
    <view class="fb-task-section" v-if="light_list && light_list.length">
      <view class="fb-task-heading">
        <view class="fb-task-heading-main">
          <text class="fb-task-title">轻任务</text>
          <text class="fb-task-label">线上活动</text>
        </view>
        <view class="fb-task-more" @click="goMoreLight">
          <text>更多任务</text>
          <u-icon name="arrow-right" size="22" color="#b65b22"></u-icon>
        </view>
      </view>
      <view
        class="fb-task-card"
        v-if="light_task_featured"
        hover-class="fb-task-card--pressed"
        :hover-stay-time="90"
        @click="$u.func.openActiveDetail(light_task_featured)"
      >
        <view class="fb-task-copy">
          <text class="fb-task-eyebrow">轻松参与 · 线上完成</text>
          <text class="fb-task-name u-line-2">{{ light_task_featured.name }}</text>
          <view class="fb-task-cta">
            <text>立即参与</text>
            <u-icon name="arrow-right" size="22" color="#fff"></u-icon>
          </view>
        </view>
        <view class="fb-task-image-wrap" v-if="light_task_featured.thumb">
          <u-image width="100%" height="100%" :src="light_task_featured.thumb" mode="aspectFill"></u-image>
        </view>
        <view class="fb-task-orb"></view>
      </view>
    </view>

    <!-- 主题活动：左主卡 + 右副卡 + 三小格（参考陪玩页层次） -->
    <view class="fb-section fb-theme-act" id="guide-fb-theme">
      <view class="fb-section-header">
        <view class="fb-hd">
          <view class="fb-entry-kicker-dot"></view>
          <text class="fb-section-title">主题活动</text>
        </view>
      </view>
      <view class="fb-theme-bento" v-if="themeHeroCate">
        <view
          class="fb-theme-hero"
          hover-class="fb-theme-card--press"
          :hover-stay-time="80"
          @click="gotoCateList(themeHeroCate)"
        >
          <view
            class="fb-theme-card-bg"
            :style="{ background: themeHeroCate.surfaceBg }"
          ></view>
          <text class="fb-theme-hero-ribbon">精选</text>
          <view class="fb-theme-hero-copy">
            <text class="fb-theme-card-title">{{ themeHeroCate.name }}</text>
            <view
              class="fb-theme-pill"
              :style="{
                background: themeHeroCate.pillBg,
                color: themeHeroCate.pillColor,
              }"
            >
              <text>{{ themeHeroCate.pillText }}</text>
            </view>
          </view>
          <view class="fb-theme-hero-icon">
            <view
              class="fb-theme-icon-disk fb-theme-icon-disk--lg"
              :style="{ boxShadow: '0 12rpx 28rpx ' + themeHeroCate.iconHalo }"
            >
              <u-icon
                :name="themeHeroCate.icon"
                :color="themeHeroCate.iconColor"
                size="64"
              ></u-icon>
            </view>
          </view>
        </view>
        <view class="fb-theme-side">
          <view
            v-if="themeSideCate"
            class="fb-theme-side-card"
            hover-class="fb-theme-card--press"
            :hover-stay-time="80"
            @click="gotoCateList(themeSideCate)"
          >
            <view
              class="fb-theme-card-bg"
              :style="{ background: themeSideCate.surfaceBg }"
            ></view>
            <view class="fb-theme-side-copy">
              <text class="fb-theme-card-title fb-theme-card-title--sm">{{
                themeSideCate.name
              }}</text>
              <view
                class="fb-theme-pill fb-theme-pill--sm"
                :style="{
                  background: themeSideCate.pillBg,
                  color: themeSideCate.pillColor,
                }"
              >
                <text>{{ themeSideCate.pillText }}</text>
              </view>
            </view>
            <view class="fb-theme-side-icon">
              <u-icon
                :name="themeSideCate.icon"
                :color="themeSideCate.iconColor"
                size="44"
              ></u-icon>
            </view>
          </view>
          <view class="fb-theme-mini-row">
            <view
              v-for="cate in themeMiniCates"
              :key="cate.id"
              class="fb-theme-mini"
              hover-class="fb-theme-card--press"
              :hover-stay-time="80"
              @click="gotoCateList(cate)"
            >
              <view
                class="fb-theme-card-bg"
                :style="{ background: cate.surfaceBg }"
              ></view>
              <view class="fb-theme-mini-icon">
                <u-icon
                  :name="cate.icon"
                  :color="cate.iconColor"
                  size="34"
                ></u-icon>
              </view>
              <text class="fb-theme-mini-name">{{ cate.name }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 公益行动特殊入口（仅活动 Tab，COS 整图） -->
    <view
      class="fb-special-bundle-poster-wrap fb-special-bundle-poster-wrap--compact"
      @click="gotoGarbageClassify"
    >
      <view class="fb-special-bundle-poster-frame">
        <image
          class="fb-special-bundle-poster-img"
          :src="activityTabEntryBanner"
          mode="widthFix"
          show-menu-by-longpress="false"
        />
      </view>
    </view>

    <!-- 暂时不需要，注释掉了 -->
    <!-- 分类轻量化入口（与快捷入口同套卡片样式） -->
    <!-- <view class="fb-light-task" id="guide-fb-light-task">
      <view class="fb-entry-kicker">
        <view class="fb-entry-kicker-dot"></view>
        <text class="fb-entry-kicker-text">分类随手学</text>
      </view>
      <view class="fb-entry-row fb-entry-row--triple">
        <view
          class="fb-entry-card fb-entry-shot"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="gotoDtshPage('/pages/dtsh/report')"
        >
          <text class="fb-entry-mark">随手拍</text>
          <view class="fb-entry-main">
            <text class="fb-entry-emoji">📷</text>
            <view class="fb-entry-text">
              <text class="fb-entry-title">随手发现问题</text>
              <text class="fb-entry-sub">拍照上报现场</text>
            </view>
            <view class="fb-entry-go">
              <text class="fb-entry-arrow">›</text>
            </view>
          </view>
        </view>
        <view
          class="fb-entry-card fb-entry-quiz"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="gotoDtshPage('/pages/dtsh/quiz')"
        >
          <text class="fb-entry-mark">小挑战</text>
          <view class="fb-entry-main">
            <text class="fb-entry-emoji">✏️</text>
            <view class="fb-entry-text">
              <text class="fb-entry-title">分类知识挑战</text>
              <text class="fb-entry-sub">答题领积分</text>
            </view>
            <view class="fb-entry-go">
              <text class="fb-entry-arrow">›</text>
            </view>
          </view>
        </view>
        <view
          class="fb-entry-card fb-entry-know"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="gotoDtshPage('/pages/dtsh/knowledge')"
        >
          <text class="fb-entry-mark">学知识</text>
          <view class="fb-entry-main">
            <text class="fb-entry-emoji">📚</text>
            <view class="fb-entry-text">
              <text class="fb-entry-title">学会分类知识</text>
              <text class="fb-entry-sub">边看边学分类</text>
            </view>
            <view class="fb-entry-go">
              <text class="fb-entry-arrow">›</text>
            </view>
          </view>
        </view>
      </view>
    </view> -->

    <!-- 本周推荐：大图海报横滑 -->
    <view class="fb-section fb-weekly-rec" id="guide-fb-weekly" v-if="weekly_display_list.length > 0">
      <view class="fb-section-header">
        <view class="fb-hd">
          <view class="fb-hd-bar fb-hd-bar--gold"></view>
          <text class="fb-section-title">本周推荐</text>
          <view class="fb-hd-chip fb-hd-chip--gold">
            <text class="fb-hd-chip-text">精选海报</text>
          </view>
        </view>
      </view>
      <scroll-view scroll-x class="fb-weekly-scroll" :show-scrollbar="false">
        <view class="fb-weekly-list">
          <view
            class="fb-weekly-card"
            :class="{ 'fb-weekly-card--pin': item.isWeeklyPin }"
            v-for="(item, wIdx) in weekly_display_list"
            :key="'weekly-' + wIdx"
            hover-class="fb-press"
            :hover-stay-time="80"
            @tap="onWeeklyCardTap(wIdx)"
          >
            <u-image
              width="520"
              height="320"
              :src="weeklyCardCover(item)"
              mode="aspectFill"
            ></u-image>
            <view class="fb-weekly-hatch"></view>
            <view class="fb-weekly-mask"></view>
            <view class="fb-weekly-tag">
              <text class="fb-weekly-tag-text">{{ item.isWeeklyPin ? '垃圾真分' : (item.meta || '社区专题') }}</text>
            </view>
            <view class="fb-weekly-badge" v-if="!item.isWeeklyPin && actEngagementCount(item) > 0">
              <text class="fb-weekly-badge-text"
                >{{ actEngagementCount(item) }}{{ actEngagementSuffix(item) }}</text
              >
            </view>
            <view class="fb-weekly-foot">
              <text class="fb-weekly-name u-line-2">{{ item.name }}</text>
              <text class="fb-weekly-date">{{ weeklyCardDateLine(item) }}</text>
            </view>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 现在就能参加（暂时隐藏，恢复时同步 TabHomeService light_list / goMoreLight）
    <view class="fb-section fb-light-act" v-if="light_list.length > 0">
      <view class="fb-section-header">
        <view class="fb-hd">
          <view class="fb-live-dot"></view>
          <text class="fb-section-title">现在就能参加</text>
        </view>
        <view class="fb-section-more" @click="goMoreLight">
          <text class="fb-more-text">更多</text>
          <u-icon name="arrow-right" size="22" color="#18AF38"></u-icon>
        </view>
      </view>
      <view class="fb-light-grid">
        <view
          class="fb-light-card"
          v-for="item in light_list"
          :key="item.id"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="$u.func.openActiveDetail(item)"
        >
          <view class="fb-light-cover">
            <u-image
              width="100%"
              height="100%"
              :src="
                item.thumb ||
                'https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/static/images/default-avatar.png'
              "
              mode="aspectFill"
            ></u-image>
            <view class="fb-weekly-hatch fb-weekly-hatch--soft"></view>
          </view>
          <view class="fb-light-info">
            <text class="fb-light-name u-line-2">{{ item.name }}</text>
          </view>
          <view class="fb-light-btn">
            <text class="fb-light-btn-text">立即参与</text>
          </view>
        </view>
      </view>
    </view>
    -->

    <!-- 近期活动（暂时隐藏，恢复时同步 TabHomeService recent_list）
    <view class="fb-section fb-recent-act" v-if="recent_list.length > 0">
      <view class="fb-section-header">
        <view class="fb-hd">
          <view class="fb-hd-bar fb-hd-bar--ink"></view>
          <text class="fb-section-title">近期活动</text>
        </view>
        <view class="fb-section-more" @click="goMoreRecent">
          <text class="fb-more-text">更多</text>
          <u-icon name="arrow-right" size="22" color="#18AF38"></u-icon>
        </view>
      </view>
      <view class="fb-recent-list">
        <view
          class="fb-recent-card"
          v-for="item in recent_list"
          :key="item.id"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="$u.func.openActiveDetail(item)"
        >
          <view class="fb-recent-cal">
            <text class="fb-recent-cal-d">{{ formatRecentCalDay(item) }}</text>
            <text class="fb-recent-cal-m">{{
              formatRecentCalMonth(item)
            }}</text>
          </view>
          <view class="fb-recent-cover">
            <u-image
              width="100%"
              height="100%"
              :src="item.thumb"
              mode="aspectFill"
            ></u-image>
          </view>
          <view class="fb-recent-info">
            <text class="fb-recent-name u-line-2">{{ item.name }}</text>
            <text class="fb-recent-meta">{{ formatRecentMeta(item) }}</text>
          </view>
          <u-icon name="arrow-right" size="24" color="#c5d0c7"></u-icon>
        </view>
      </view>
    </view>
    -->

    <!-- 标签栏：热门活动 + 动态标签（已隐藏，保留代码备用） -->
    <view class="fb-tab-bar" v-if="false">
      <scroll-view
        scroll-x
        class="fb-tab-scroll"
        :show-scrollbar="false"
        :scroll-into-view="'tab-' + current_tab"
        scroll-with-animation
      >
        <view class="fb-tab-list">
          <view
            class="fb-tab-item"
            :class="{ 'fb-tab-active': current_tab === 0 }"
            id="tab-0"
            @click="switchTab(0)"
          >
            <text class="fb-tab-text">热门活动</text>
          </view>
          <view
            class="fb-tab-item"
            :class="{ 'fb-tab-active': current_tab === idx + 1 }"
            v-for="(tag, idx) in tag_list"
            :key="tag"
            :id="'tab-' + (idx + 1)"
            @click="switchTab(idx + 1)"
          >
            <text class="fb-tab-text">{{ tag }}</text>
          </view>
        </view>
      </scroll-view>
    </view>

    <!-- 全部活动列表（暂时隐藏，恢复时同步 TabHomeService active_list 分页）
    <view class="fb-act-list">
      <view class="fb-section-header fb-act-list-hd">
        <view class="fb-hd">
          <view class="fb-entry-kicker-dot"></view>
          <text class="fb-section-title">全部活动</text>
          <text class="fb-entry-kicker-hint" v-if="active_total > 0">共 {{ active_total }} 场</text>
        </view>
      </view>
      <view
        class="fb-act-card"
        v-for="item in active_list_preview"
        :key="item.id"
        @click="$u.func.openActiveDetail(item)"
      >
        <view class="fb-act-cover">
          <u-image
            width="100%"
            height="100%"
            :src="item.thumb"
            mode="aspectFill"
          ></u-image>
          <view class="fb-act-badge" v-if="actEngagementCount(item) > 0">
            <view
              class="fb-act-avatars"
              v-if="item.avatar_array && item.avatar_array.length"
            >
              <image
                :src="img"
                class="fb-act-avatar"
                mode="aspectFill"
                v-for="(img, i) in item.avatar_array.slice(0, 3)"
                :key="i"
              ></image>
            </view>
            <text class="fb-act-badge-text">{{ actEngagementCount(item) }}{{ actEngagementSuffix(item) }}</text>
          </view>
        </view>
        <view class="fb-act-body">
          <view class="fb-act-title u-line-1">{{ item.name }}</view>
          <view class="fb-act-meta">
            <view class="fb-meta-item">
              <u-icon name="clock" size="22" color="#65676B"></u-icon>
              <text class="fb-meta-text">{{ formatActiveDate(item) }}</text>
            </view>
            <view
              class="fb-meta-item fb-meta-loc"
              v-if="item.address && item.address.trim()"
            >
              <u-icon name="map" size="22" color="#65676B"></u-icon>
              <text class="fb-meta-text u-line-1">{{ item.address }}</text>
            </view>
          </view>
        </view>
      </view>

      <view
        class="fb-act-more-wrap"
        v-if="showActiveListMore && active_list_preview.length > 0"
        @click="goMoreAll"
      >
        <view class="fb-act-more-btn" hover-class="fb-act-more-btn--on">
          <text class="fb-act-more-text">查看更多活动</text>
          <u-icon name="arrow-right" size="24" color="#18AF38"></u-icon>
        </view>
      </view>
    </view>
    -->
    <page-guide page="low_carbon"></page-guide>
  </view>
</template>

<script>
import { checkCommunity } from "@/common/communityCheck.mixin.js";
import {
  openSceneClockHub,
  parseBannerSceneClockEntry,
  openSceneClockFromBannerEntry,
} from "@/common/sceneClockEntry.js";
import settings from "@/common/settings.js";
import {
  getWeeklyRecommendDisplayList,
  navigateHomeHotSpotEntry,
  resolveHomeHotThumb,
} from "@/common/homeHotSpotEntries.js";
import careGesture from "@/common/care-gesture.mixin.js";
import SkeletonScreen from "@/components/skeleton-screen/index.vue";
import {
  ACTIVITY_TAB_ENTRY_BANNER_URL,
  ACTIVITY_TAB_TOP_BANNER_URL,
} from "@/common/activityTabScreenshot.js";

/** 活动 Tab 底部「全部活动」仅预览条数，完整列表见 active-list */
const ACTIVE_LIST_PREVIEW_SIZE = 2;

/** 换 COS 图后递增，避免小程序/WebView 缓存旧图 */
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

/** 社区专题：整卡仅一张图（文案/图标都在图里） */
const SPOT_MODULE_COVERS = {
  qxl: spotModuleCoverUrl("qxl-cover.png"),
  hiking: spotModuleCoverUrl("hiking-cover.png"),
  flea_market: spotModuleCoverUrl("flea-market-cover.png"),
  zhenfen_class: spotModuleCoverUrl("zhenfen-class-cover.png"),
};

/** 社区专题卡片展示文案（以前端为准，避免线上 API 未发版时副标题被旧数据覆盖） */
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
    // 名称、副标题以前端 SPOT_MODULES_DEFAULT 为准（避免线上 API 未发版时仍显示旧文案）
    return {
      ...local,
      entry_type: remote.entry_type || local.entry_type,
    };
  });
}

export default {
  components: { SkeletonScreen },
  mixins: [careGesture],
  data() {
    return {
      activityTabTopBanner: ACTIVITY_TAB_TOP_BANNER_URL,
      activityTabEntryBanner: ACTIVITY_TAB_ENTRY_BANNER_URL,
      isLoading: true,
      banner_list: [],
      current_tab: 0,
      tag_list: [],
      active_list: [],
      active_page: 1,
      active_total: 0,
      active_loading: false,
      weekly_list: [],
      light_list: [],
      recent_list: [],
      // 5 大活动分类（硬编码，后台新增不显示）
      // community_cate_id 对应数据库 cmf_active_community_cate.id
      main_categories: [
        {
          id: 1,
          name: "健康运动",
          subtitle: "Healthy Life",
          pillText: "动出活力",
          icon: "play-circle-fill",
          iconColor: "#1877F2",
          iconHalo: "rgba(24, 119, 242, 0.35)",
          bgColor: "linear-gradient(160deg, #E7F0FE 0%, #1877F2 100%)",
          surfaceBg:
            "linear-gradient(145deg, #ffffff 0%, #eef4ff 42%, #c8dcff 100%)",
          pillBg: "rgba(24, 119, 242, 0.14)",
          pillColor: "#1565D8",
        },
        {
          id: 2,
          name: "学习成长",
          subtitle: "Growing Up",
          pillText: "充电提升",
          icon: "star-fill",
          iconColor: "#1F9D55",
          iconHalo: "rgba(31, 157, 85, 0.32)",
          bgColor: "linear-gradient(160deg, #D4FC79 0%, #3CB878 100%)",
          surfaceBg:
            "linear-gradient(145deg, #ffffff 0%, #eefbf3 50%, #b8ebd0 100%)",
          pillBg: "rgba(31, 157, 85, 0.16)",
          pillColor: "#16884a",
        },
        {
          id: 3,
          name: "兴趣体验",
          subtitle: "Hobby Time",
          pillText: "好玩体验",
          icon: "grid-fill",
          iconColor: "#E86A1A",
          iconHalo: "rgba(232, 106, 26, 0.34)",
          bgColor: "linear-gradient(160deg, #FFD194 0%, #FF8A3D 100%)",
          surfaceBg:
            "linear-gradient(145deg, #fffaf5 0%, #fff0e0 55%, #ffd4b0 100%)",
          pillBg: "rgba(232, 106, 26, 0.14)",
          pillColor: "#c45a12",
        },
        {
          id: 4,
          name: "交流互动",
          subtitle: "Neighbors",
          pillText: "邻里互动",
          icon: "chat-fill",
          iconColor: "#E84D6A",
          iconHalo: "rgba(232, 77, 106, 0.32)",
          bgColor: "linear-gradient(160deg, #FF9A9E 0%, #F06B8A 100%)",
          surfaceBg:
            "linear-gradient(145deg, #fff8f9 0%, #ffecef 55%, #ffc8d4 100%)",
          pillBg: "rgba(232, 77, 106, 0.14)",
          pillColor: "#c93d58",
        },
        {
          id: 5,
          name: "公益行动",
          subtitle: "Volunteer",
          pillText: "志愿参与",
          icon: "gift-fill",
          iconColor: "#7B4FD6",
          iconHalo: "rgba(123, 79, 214, 0.34)",
          bgColor: "linear-gradient(160deg, #D8B4FE 0%, #8B5CF6 100%)",
          surfaceBg:
            "linear-gradient(145deg, #faf7ff 0%, #f1eaff 55%, #dbc8ff 100%)",
          pillBg: "rgba(123, 79, 214, 0.14)",
          pillColor: "#6b3fc4",
        },
      ],
    };
  },
  computed: {
    weekly_display_list() {
      return getWeeklyRecommendDisplayList();
    },
    light_task_featured() {
      return (this.light_list || [])[0] || null;
    },
    active_list_preview() {
      return (this.active_list || []).slice(0, ACTIVE_LIST_PREVIEW_SIZE);
    },
    showActiveListMore() {
      const total = Number(this.active_total) || 0;
      if (total > ACTIVE_LIST_PREVIEW_SIZE) return true;
      return (this.active_list || []).length > ACTIVE_LIST_PREVIEW_SIZE;
    },
    themeHeroCate() {
      return (this.main_categories && this.main_categories[0]) || null;
    },
    themeSideCate() {
      return (this.main_categories && this.main_categories[1]) || null;
    },
    themeMiniCates() {
      return (this.main_categories || []).slice(2, 5);
    },
  },
  onLoad(options) {
    this.loadAllData();
  },
  onShow() {
    this.updateTabBarSelected(1);
    if (checkCommunity(this)) return;
    if (!this.isLogin) return;
  },
  methods: {
    weeklyCardCover(item) {
      if (!item) return "";
      return resolveHomeHotThumb(item.thumb) || item.thumb || "";
    },
    onWeeklyCardTap(wIdx) {
      const list = this.weekly_display_list || [];
      const item = list[wIdx];
      if (!item) return;
      if (item.entry_type) {
        navigateHomeHotSpotEntry(this, item);
        return;
      }
      if (!item.id) return;
      this.$u.route("/pages/activity/lsxf/details-new?id=" + item.id);
    },
    actEngagementCount(item) {
      if (!item) return 0;
      if (Number(item.is_date_pending) === 1) {
        return Number(item.interest_count) || 0;
      }
      return Number(item.active_count) || 0;
    },
    actEngagementSuffix(item) {
      return Number(item && item.is_date_pending) === 1 ? "人感兴趣" : "人参与";
    },
    weeklyCardDateLine(item) {
      const base = this.formatActiveDate(item);
      if (item && (item.isWeeklyPin || item.entry_type)) return base;
      if (Number(item.is_date_pending) === 1) return base;
      return base + " 结束";
    },
    formatActiveDate(item) {
      if (item && item.hint) {
        return item.hint;
      }
      if (item && item.isWeeklyPin) {
        return "选场景 · 摆桶 · 拍照";
      }
      if (Number(item.is_date_pending) === 1) {
        return "时间待定";
      }
      if (!item.date) {
        return "—";
      }
      return this.$u.timeFormat(item.date, "yy-mm-dd");
    },
    formatRecentCalDay(item) {
      if (Number(item.is_date_pending) === 1) return "待定";
      if (!item.date) return "—";
      return this.$u.timeFormat(item.date, "dd");
    },
    formatRecentCalMonth(item) {
      if (Number(item.is_date_pending) === 1) return "时间";
      if (!item.date) return "";
      return this.$u.timeFormat(item.date, "mm") + "月";
    },
    formatRecentMeta(item) {
      if (Number(item.is_date_pending) === 1) return "时间待定";
      if (!item.date) return "截止 —";
      return "截止 " + this.$u.timeFormat(item.date, "yy-mm-dd");
    },
    async loadAllData() {
      const startTime = Date.now();
      this.active_list = [];
      this.active_page = 1;
      this.active_total = 0;
      try {
        await Promise.all([
          this.fetchActivityTabHome({ page: 1 }),
        ]);
      } catch (e) {
        console.log("数据加载失败:", e);
      } finally {
        const elapsed = Date.now() - startTime;
        if (elapsed < 400) {
          setTimeout(() => {
            this.isLoading = false;
          }, 400 - elapsed);
        } else {
          this.isLoading = false;
        }
      }
    },

    /**
     * 活动 Tab 唯一数据口：首屏带 banner/标签/推荐区；分页或筛标签只增量列表字段
     */
    fetchActivityTabHome({ page = 1, tag = "" } = {}) {
      const params = { page };
      if (tag) params.tag = tag;
      return this.$u.api.get_activity_tab_home(params).then((res) => {
        const data = res.data || {};
        if (Object.prototype.hasOwnProperty.call(data, "banner_list")) {
          this.banner_list = data.banner_list || [];
        }
        if (Object.prototype.hasOwnProperty.call(data, "tag_list")) {
          this.tag_list = data.tag_list || [];
        }
        if (Object.prototype.hasOwnProperty.call(data, "weekly_list")) {
          this.weekly_list = data.weekly_list || [];
        }
        if (Object.prototype.hasOwnProperty.call(data, "light_list")) {
          this.light_list = data.light_list || [];
        }
        /* 近期活动 / 全部活动暂时下线，接口不再返回列表数据
        if (Object.prototype.hasOwnProperty.call(data, "recent_list")) {
          this.recent_list = data.recent_list || [];
        }
        const list = data.active_list || [];
        ...
        */
        this.recent_list = [];
        this.active_list = [];
        this.active_total = 0;
        this.active_page = page;
      });
    },

    // 轻活动"更多" → 全部活动列表（选中"轻活动"筛选）
    goMoreLight() {
      this.$u.route({
        url: "/pages/activity/active-list",
        params: { filter: "light" },
      });
    },

    // 近期活动"更多" → 全部活动列表（选中"线下活动"筛选）
    goMoreRecent() {
      this.$u.route({
        url: "/pages/activity/active-list",
        params: { filter: "all" },
      });
    },

    /** 全部活动预览 → 完整列表（跟随当前标签筛选） */
    goMoreAll() {
      const params = { filter: "all" };
      if (this.current_tab > 0) {
        const tag = this.tag_list[this.current_tab - 1];
        if (tag) {
          params.filter = "tag:" + tag;
        }
      }
      this.$u.route({
        url: "/pages/activity/active-list",
        params,
      });
    },

    loadActiveList() {
      if (this.active_loading) return;
      this.active_loading = true;

      let tag = "";
      if (this.current_tab > 0) {
        tag = this.tag_list[this.current_tab - 1] || "";
      }

      this.fetchActivityTabHome({ page: this.active_page, tag })
        .catch((err) => {
          this.$u.toast(err.msg || "加载失败");
        })
        .finally(() => {
          this.active_loading = false;
        });
    },

    // 切换标签
    switchTab(index) {
      if (this.current_tab === index) return;
      this.current_tab = index;
      this.active_list = [];
      this.active_page = 1;
      this.active_total = 0;
      this.loadActiveList();
    },

    // 点击分类 → 进入分类列表页
    gotoCateList(cate) {
      this.$u.route(
        `/pages/activity/cate-list?cate_id=${cate.id}&name=${encodeURIComponent(cate.name)}`
      );
    },

    gotoGarbageClassify() {
      this.$u.route("/pages/activity/garbage-classify");
    },

    // 搜索
    gotoSearch() {
      this.$u.route("/pages/activity/active-search");
    },

    // tabbar
    updateTabBarSelected(index) {
      if (
        typeof this.$mp.page.getTabBar === "function" &&
        this.$mp.page.getTabBar()
      ) {
        this.$mp.page.getTabBar().setSelected(index);
      }
    },
  },

  onReachBottom() {
    /* 全部活动区仅展示预览，不在 Tab 页触底加载 */
  },
};
</script>

<style lang="scss" scoped>
@import '@/static/styles/glass-ui.scss';

.container {
  position: relative;
  z-index: 0;
}

/* 活动 Tab 不要右上角固定高光（glass-ui 的 ::after） */
.container.glass-page::after {
  content: none;
  display: none;
}

.fb-press {
  opacity: 0.92;
  transform: scale(0.98);
}

/* ===== 自定义顶部导航 ===== */
.fb-navbar {
  position: sticky;
  top: 0;
  z-index: 30;
  padding: 16rpx 24rpx;
  padding-top: calc(env(safe-area-inset-top) + 16rpx);
}
.fb-navbar-inner {
  display: flex;
  align-items: center;
}
.fb-search-box {
  flex: 1;
  display: flex;
  align-items: center;
  height: 76rpx;
  padding: 0 10rpx 0 12rpx;
}
.fb-search-ico {
  width: 52rpx;
  height: 52rpx;
  border-radius: 50%;
  background: #e8f8ec;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.fb-search-ph {
  margin-left: 12rpx;
  font-size: 26rpx;
  color: #9aa39c;
}

/* ===== 活动 Tab 顶部 Banner（紧跟 care-pull-hint） ===== */
.fb-activity-top-banner {
  padding: 4rpx 24rpx 12rpx;
}
.fb-activity-top-banner-img {
  width: 100%;
  display: block;
  border-radius: 20rpx;
}

/* ===== 垃圾真分：Banner + 场景入口统一外框 ===== */
.fb-zhenfen-hub {
  padding: 8rpx 24rpx 16rpx;
  box-sizing: border-box;
}
.fb-zhenfen-hub-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16rpx;
  padding: 0 8rpx 14rpx;
}
.fb-zhenfen-hub-brand {
  display: flex;
  flex-direction: column;
  gap: 6rpx;
  min-width: 0;
}
.fb-zhenfen-hub-badge {
  align-self: flex-start;
  padding: 4rpx 16rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #1b7a4a 0%, #18af38 100%);
  box-shadow: 0 4rpx 12rpx rgba(24, 175, 56, 0.28);
}
.fb-zhenfen-hub-sub {
  font-size: 28rpx;
  font-weight: 700;
  color: $glass-text;
  letter-spacing: 0.5rpx;
}
.fb-zhenfen-hub-hint {
  flex-shrink: 0;
  font-size: 20rpx;
  color: #8a958c;
  text-align: right;
  max-width: 42%;
  line-height: 1.35;
}
.fb-zhenfen-hub-frame {
  @include glass-surface(0.72, 40px);
  border-radius: $glass-radius-xl;
  border: 2rpx solid rgba(24, 175, 56, 0.22);
  box-shadow:
    0 10rpx 32rpx rgba(16, 48, 24, 0.08),
    inset 0 0 0 1rpx rgba(255, 255, 255, 0.65);
  overflow: hidden;
  background: linear-gradient(
    165deg,
    rgba(232, 248, 236, 0.55) 0%,
    rgba(255, 255, 255, 0.72) 42%,
    rgba(255, 255, 255, 0.58) 100%
  );
}
.fb-zhenfen-hub-divider {
  height: 1rpx;
  margin: 0 20rpx;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(24, 175, 56, 0.35) 20%,
    rgba(24, 175, 56, 0.35) 80%,
    transparent 100%
  );
}
.fb-banner-wrap--inset {
  width: 100%;
  padding: 12rpx 12rpx 0;
  margin-bottom: 0;
  box-sizing: border-box;
}
.fb-banner-swiper--inset {
  width: 100%;
  height: 300rpx;
  border-radius: 20rpx;
  overflow: hidden;
  padding: 0;
  box-shadow: none;
  background: transparent;
  backdrop-filter: none;
  -webkit-backdrop-filter: none;
}
.fb-entry-stage--inset {
  padding: 8rpx 12rpx 12rpx;
}
.fb-entry-kicker--inset {
  padding: 4rpx 8rpx 10rpx;
}
.fb-entry-kicker--inset .fb-entry-kicker-text {
  font-size: 24rpx;
  font-weight: 600;
  color: #3d5245;
}

/* ===== Banner 轮播（独立使用时保留） ===== */
.fb-banner-wrap {
  width: 100%;
  padding: 16rpx 24rpx 0;
  margin-bottom: 8rpx;
  box-sizing: border-box;
}
.fb-banner-swiper {
  width: 100%;
  height: 330rpx;
  border-radius: $glass-radius-xl;
  overflow: hidden;
  @include glass-surface(0.35, 32px);
  padding: 8rpx;
  box-sizing: border-box;
}
.fb-banner-item {
  width: 100%;
  height: 100%;
}

/* ===== 主题活动：左主卡 + 右副卡 + 三小格 ===== */
.fb-theme-act {
  margin-bottom: 0;
}
.fb-theme-bento {
  display: flex;
  flex-direction: row;
  align-items: stretch;
  gap: 14rpx;
  min-height: 268rpx;
  margin: 0 24rpx 10rpx;
  box-sizing: border-box;
}
.fb-theme-card-bg {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 0;
  border-radius: inherit;
}
.fb-theme-card--press {
  transform: scale(0.98);
  opacity: 0.92;
}
.fb-theme-hero,
.fb-theme-side-card,
.fb-theme-mini {
  position: relative;
  overflow: hidden;
  border-radius: 22rpx;
  box-shadow: 0 10rpx 28rpx rgba(22, 48, 36, 0.08);
  border: 1rpx solid rgba(255, 255, 255, 0.92);
  box-sizing: border-box;
  transition: transform 0.18s ease, opacity 0.18s ease;
}
.fb-theme-hero {
  flex: 0 0 48%;
  min-width: 0;
  padding: 22rpx 20rpx 20rpx;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.fb-theme-hero-ribbon {
  position: absolute;
  top: 0;
  right: 0;
  z-index: 2;
  padding: 6rpx 18rpx 6rpx 22rpx;
  font-size: 20rpx;
  font-weight: 700;
  color: #fff;
  background: linear-gradient(135deg, #18af38 0%, #12962e 100%);
  border-radius: 0 22rpx 0 18rpx;
  box-shadow: 0 4rpx 12rpx rgba(24, 175, 56, 0.35);
}
.fb-theme-hero-copy {
  position: relative;
  z-index: 1;
  padding-right: 8rpx;
}
.fb-theme-card-title {
  display: block;
  font-size: 34rpx;
  font-weight: 800;
  color: #1a2420;
  line-height: 1.25;
  letter-spacing: 0.5rpx;
}
.fb-theme-card-title--sm {
  font-size: 28rpx;
}
.fb-theme-pill {
  display: inline-flex;
  align-items: center;
  margin-top: 12rpx;
  padding: 6rpx 16rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  font-weight: 600;
  line-height: 1.3;
}
.fb-theme-pill--sm {
  margin-top: 8rpx;
  padding: 4rpx 12rpx;
  font-size: 20rpx;
}
.fb-theme-hero-icon {
  position: relative;
  z-index: 1;
  display: flex;
  justify-content: flex-end;
  align-items: flex-end;
  margin-top: 8rpx;
}
.fb-theme-icon-disk {
  width: 88rpx;
  height: 88rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.92);
  border: 2rpx solid rgba(255, 255, 255, 0.98);
}
.fb-theme-icon-disk--lg {
  width: 104rpx;
  height: 104rpx;
}
.fb-theme-side {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 12rpx;
}
.fb-theme-side-card {
  flex: 1.05;
  min-height: 0;
  padding: 18rpx 16rpx;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
}
.fb-theme-side-copy {
  position: relative;
  z-index: 1;
  flex: 1;
  min-width: 0;
  padding-right: 8rpx;
}
.fb-theme-side-icon {
  position: relative;
  z-index: 1;
  flex-shrink: 0;
  width: 72rpx;
  height: 72rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.88);
  box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.06);
}
.fb-theme-mini-row {
  flex: 1;
  min-height: 0;
  display: flex;
  flex-direction: row;
  gap: 10rpx;
}
.fb-theme-mini {
  flex: 1;
  min-width: 0;
  padding: 12rpx 6rpx 10rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.fb-theme-mini-icon {
  position: relative;
  z-index: 1;
  width: 56rpx;
  height: 56rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.9);
  box-shadow: 0 6rpx 16rpx rgba(0, 0, 0, 0.06);
  margin-bottom: 8rpx;
}
.fb-theme-mini-name {
  position: relative;
  z-index: 1;
  font-size: 20rpx;
  font-weight: 700;
  color: #2a3530;
  text-align: center;
  line-height: 1.25;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
  word-break: break-all;
}

/* 备用：左大卡 + 右 2×2 样式（已弃用，恢复时取消注释）
.fb-cate-wrap {
	display: flex;
	gap: 16rpx;
	padding: 8rpx 24rpx 24rpx;
}
.fb-cate-big {
	flex: 0 0 210rpx;
	height: 260rpx;
	display: flex;
	flex-direction: column;
	align-items: flex-start;
	justify-content: flex-end;
	padding: 24rpx 28rpx 24rpx 24rpx;
	box-shadow: 0 6rpx 20rpx rgba(24, 119, 242, 0.15);
	color: #fff;
	position: relative;
	overflow: hidden;
	clip-path: polygon(0 0, 88% 0, 100% 50%, 88% 100%, 0 100%);
	transition: transform 0.2s;
}
.fb-cate-big:active {
	transform: scale(0.96);
	opacity: 0.9;
}
.fb-cate-big-emoji {
	position: absolute;
	top: 20rpx;
	right: 16rpx;
	font-size: 88rpx;
	opacity: 0.9;
	filter: drop-shadow(0 4rpx 8rpx rgba(0,0,0,0.2));
}
.fb-cate-big::before {
	content: '';
	position: absolute;
	left: 0;
	right: 0;
	top: 0;
	bottom: 0;
	pointer-events: none;
	background: repeating-linear-gradient(
		-32deg,
		rgba(255,255,255,0) 0,
		rgba(255,255,255,0) 12rpx,
		rgba(255,255,255,0.16) 12rpx,
		rgba(255,255,255,0.16) 14rpx
	);
}
.fb-cate-big::after {
	content: '';
	position: absolute;
	width: 28rpx;
	height: 180%;
	right: 36rpx;
	top: -40%;
	transform: rotate(18deg);
	background: rgba(255,255,255,0.12);
	pointer-events: none;
}
.fb-cate-big-name {
	font-size: 34rpx;
	font-weight: 700;
	color: #fff;
	text-shadow: 0 2rpx 6rpx rgba(0,0,0,0.2);
	position: relative;
	z-index: 1;
}
.fb-cate-big-sub {
	font-size: 20rpx;
	color: rgba(255,255,255,0.85);
	margin-top: 2rpx;
	letter-spacing: 1rpx;
	position: relative;
	z-index: 1;
}
.fb-cate-right {
	flex: 1;
	display: grid;
	grid-template-columns: 1fr 1fr;
	grid-template-rows: 1fr 1fr;
	gap: 12rpx;
	height: 260rpx;
}
.fb-cate-mini {
	display: flex;
	flex-direction: row;
	align-items: center;
	padding: 14rpx 16rpx;
	gap: 10rpx;
	box-shadow: 0 3rpx 10rpx rgba(0,0,0,0.06);
	overflow: hidden;
	position: relative;
	clip-path: polygon(10% 0, 100% 0, 100% 100%, 0 100%, 0 28%);
	transition: transform 0.2s;
}
.fb-cate-mini:active {
	transform: scale(0.95);
	opacity: 0.9;
}
.fb-cate-mini::before {
	content: '';
	position: absolute;
	left: 0;
	right: 0;
	top: 0;
	bottom: 0;
	pointer-events: none;
	background: repeating-linear-gradient(
		28deg,
		rgba(255,255,255,0) 0,
		rgba(255,255,255,0) 10rpx,
		rgba(255,255,255,0.22) 10rpx,
		rgba(255,255,255,0.22) 11rpx
	);
}
.fb-cate-mini::after {
	content: '';
	position: absolute;
	left: 0;
	top: 0;
	bottom: 0;
	width: 8rpx;
	background: rgba(255,255,255,0.55);
	pointer-events: none;
}
.fb-cate-mini-emoji {
	font-size: 44rpx;
	flex-shrink: 0;
	position: relative;
	z-index: 1;
	filter: drop-shadow(0 2rpx 4rpx rgba(0,0,0,0.1));
}
.fb-cate-mini-text {
	display: flex;
	flex-direction: column;
	flex: 1;
	min-width: 0;
	position: relative;
	z-index: 1;
}
.fb-cate-mini-name {
	font-size: 24rpx;
	font-weight: 600;
	color: #050505;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.fb-cate-mini-sub {
	font-size: 18rpx;
	color: #65676B;
	margin-top: 2rpx;
	letter-spacing: 0.5rpx;
}
*/

/* ===== 常去入口：对冲咬合 + 斜线流动 + 中间能量核，两卡互不重叠 ===== */
@keyframes fbEntryStripe {
  0% {
    background-position:
      0 0,
      0 0;
  }
  100% {
    background-position:
      40rpx 28rpx,
      0 0;
  }
}
@keyframes fbEntryShine {
  0% {
    transform: translateX(-130%) skewX(-20deg);
    opacity: 0;
  }
  18% {
    opacity: 0.9;
  }
  42% {
    opacity: 0;
  }
  100% {
    transform: translateX(240%) skewX(-20deg);
    opacity: 0;
  }
}
@keyframes fbEntryFloat {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-6rpx);
  }
}
@keyframes fbHubPulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.16);
    opacity: 0.7;
  }
}
@keyframes fbHubSpin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
.fb-entry-stage {
  padding: 12rpx 20rpx 4rpx;
}
.fb-entry-kicker {
  display: flex;
  align-items: center;
  padding: 0 12rpx 8rpx;
}
.fb-entry-kicker-dot {
  width: 12rpx;
  height: 12rpx;
  border-radius: 50%;
  background: #18af38;
  margin-right: 10rpx;
  box-shadow: 0 0 0 6rpx rgba(24, 175, 56, 0.18);
}
.fb-entry-kicker-text {
  font-size: 28rpx;
  font-weight: 600;
  color: $glass-text;
}
.fb-entry-kicker-hint {
  margin-left: 12rpx;
  font-size: 22rpx;
  color: #8a958c;
}
.fb-entry-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12rpx;
  padding: 4rpx 0 16rpx;
}

.fb-light-task {
  padding: 4rpx 20rpx 8rpx;
}
.fb-entry-row--triple {
  grid-template-columns: repeat(3, 1fr);
  padding-bottom: 4rpx;
}
.fb-zhenfen-hub-frame .fb-entry-row--triple {
  padding: 0 0 4rpx;
  gap: 10rpx;
}
.fb-zhenfen-hub-frame .fb-entry-card {
  background: rgba(255, 255, 255, 0.58);
  border: 1rpx solid rgba(255, 255, 255, 0.82);
  box-shadow: 0 8rpx 24rpx rgba(31, 54, 42, 0.06), inset 0 1rpx 0 rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(20rpx);
  -webkit-backdrop-filter: blur(20rpx);
}
.fb-entry-row--triple .fb-entry-card {
  padding: 14rpx 10rpx 12rpx;
}
.fb-entry-row--triple .fb-entry-main {
  gap: 6rpx;
}
.fb-entry-row--triple .fb-entry-emoji {
  font-size: 28rpx;
}
.fb-entry-row--triple .fb-entry-title {
  font-size: 22rpx;
  line-height: 1.25;
}
.fb-entry-row--triple .fb-entry-sub {
  font-size: 18rpx;
  -webkit-line-clamp: 1;
}
.fb-entry-row--triple .fb-entry-go {
  width: 30rpx;
  height: 30rpx;
}
.fb-entry-row--triple .fb-entry-arrow {
  font-size: 28rpx;
}
.fb-entry-shot {
  border-left: 4rpx solid #e86a3a;
  background: rgba(255, 255, 255, 0.58);
}
.fb-entry-quiz {
  border-left: 4rpx solid #3d7ee8;
  background: rgba(255, 255, 255, 0.58);
}
.fb-entry-know {
  border-left: 4rpx solid #18af38;
  background: rgba(255, 255, 255, 0.58);
}
.fb-entry-shot::after,
.fb-entry-quiz::after,
.fb-entry-know::after {
  content: "";
  position: absolute;
  top: -24rpx;
  right: -24rpx;
  width: 88rpx;
  height: 88rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.14);
  pointer-events: none;
}
.fb-entry-shot .fb-entry-title,
.fb-entry-quiz .fb-entry-title,
.fb-entry-know .fb-entry-title,
.fb-entry-shot .fb-entry-arrow,
.fb-entry-quiz .fb-entry-arrow,
.fb-entry-know .fb-entry-arrow,
.fb-entry-shot .fb-entry-mark,
.fb-entry-quiz .fb-entry-mark,
.fb-entry-know .fb-entry-mark {
  color: $glass-text;
}
.fb-entry-shot .fb-entry-sub,
.fb-entry-quiz .fb-entry-sub,
.fb-entry-know .fb-entry-sub {
  color: $glass-text-secondary;
}
.fb-entry-card {
  @include glass-surface(0.58, 36px);
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-start;
  gap: 8rpx;
  padding: 18rpx 16rpx 16rpx;
  min-height: 0;
  border-radius: $glass-radius-md;
  position: relative;
  overflow: hidden;
  color: $glass-text;
  box-shadow: none;
}
.fb-entry-home {
  border-left: 4rpx solid #d4a832;
  background: rgba(255, 255, 255, 0.52);
}
.fb-entry-xsd {
  border-left: 4rpx solid #e85d7a;
  background: rgba(255, 255, 255, 0.52);
}
.fb-entry-office {
  border-left: 4rpx solid #5c6b8a;
  background: rgba(255, 255, 255, 0.52);
}
.fb-entry-street {
  border-left: 4rpx solid #18af38;
  background: rgba(255, 255, 255, 0.52);
}
.fb-entry-home::after,
.fb-entry-xsd::after,
.fb-entry-office::after,
.fb-entry-street::after {
  content: "";
  position: absolute;
  top: -24rpx;
  right: -24rpx;
  width: 88rpx;
  height: 88rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.14);
  pointer-events: none;
}
.fb-entry-home .fb-entry-title,
.fb-entry-home .fb-entry-sub,
.fb-entry-home .fb-entry-arrow,
.fb-entry-home .fb-entry-mark,
.fb-entry-xsd .fb-entry-title,
.fb-entry-xsd .fb-entry-sub,
.fb-entry-xsd .fb-entry-arrow,
.fb-entry-xsd .fb-entry-mark,
.fb-entry-office .fb-entry-title,
.fb-entry-office .fb-entry-sub,
.fb-entry-office .fb-entry-arrow,
.fb-entry-office .fb-entry-mark,
.fb-entry-street .fb-entry-title,
.fb-entry-street .fb-entry-sub,
.fb-entry-street .fb-entry-arrow,
.fb-entry-street .fb-entry-mark {
  color: $glass-text;
}
.fb-entry-home .fb-entry-sub,
.fb-entry-xsd .fb-entry-sub,
.fb-entry-office .fb-entry-sub,
.fb-entry-street .fb-entry-sub {
  color: $glass-text-secondary;
}
.fb-entry-home .fb-entry-go--dark {
  background: rgba(0, 0, 0, 0.06);
}

/* ===== 垃圾分类工作台 · 活动页主推（COS 切图） ===== */
.fb-dtsh-spotlight {
  padding: 12rpx 20rpx 8rpx;
}
.fb-dtsh-spotlight-poster {
  border-radius: 20rpx;
  overflow: hidden;
  box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.08);
}
.fb-dtsh-spotlight-press {
  opacity: 0.92;
}
.fb-dtsh-spotlight-img {
  width: 100%;
  display: block;
  border-radius: 20rpx;
}

/* ===== 社区专题 · 四大平级入口 ===== */
.fb-spot-modules {
  padding-top: 8rpx;
}
.fb-spot-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16rpx;
  padding: 0 4rpx 8rpx;
}
.fb-spot-card--poster {
  border-radius: 20rpx;
  padding: 0;
  overflow: hidden;
  box-shadow: 0 12rpx 30rpx rgba(31, 54, 42, 0.1), 0 0 0 1rpx rgba(255, 255, 255, 0.72);
  background: transparent;
}
.fb-spot-poster-img {
  width: 100%;
  display: block;
  border-radius: 20rpx;
}

/* ===== 我和七贤岭 · 活动页独立主推位（已并入专题网格，样式保留供其它页引用） ===== */
.fb-qxl-spotlight {
  padding: 8rpx 20rpx 16rpx;
}
.fb-qxl-spotlight-card {
  @include glass-accent-card(rgba(24, 175, 56, 0.05));
  position: relative;
  overflow: hidden;
  padding: 32rpx 28rpx 28rpx;
}
.fb-qxl-spotlight-press {
  opacity: 0.92;
  transform: scale(0.992);
}
.fb-qxl-spotlight-glow {
  display: none;
}
.fb-qxl-spotlight-glow--a {
  width: 200rpx;
  height: 200rpx;
  top: -72rpx;
  right: -48rpx;
}
.fb-qxl-spotlight-glow--b {
  width: 140rpx;
  height: 140rpx;
  bottom: -56rpx;
  left: -32rpx;
  background: rgba(255, 255, 255, 0.08);
}
.fb-qxl-spotlight-inner {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 20rpx;
}
.fb-qxl-spotlight-copy {
  flex: 1;
  min-width: 0;
}
.fb-qxl-spotlight-badge-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 10rpx;
}
.fb-qxl-spotlight-badge {
  font-size: 18rpx;
  font-weight: 700;
  color: #0d4d3a;
  background: rgba(255, 255, 255, 0.92);
  padding: 4rpx 12rpx;
  border-radius: 999rpx;
  letter-spacing: 2rpx;
}
.fb-qxl-spotlight-kicker {
  font-size: 22rpx;
  color: $glass-text-tertiary;
  letter-spacing: 0.5rpx;
}
.fb-qxl-spotlight-title-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 6rpx;
}
.fb-qxl-spotlight-emoji {
  font-size: 40rpx;
  line-height: 1;
}
.fb-qxl-spotlight-title {
  font-size: 32rpx;
  font-weight: 600;
  color: $glass-text;
  letter-spacing: 0.2rpx;
}
.fb-qxl-spotlight-desc {
  display: block;
  font-size: 24rpx;
  color: $glass-text-secondary;
  line-height: 1.5;
  margin-bottom: 14rpx;
}
.fb-qxl-spotlight-tags {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 8rpx;
}
.fb-qxl-spotlight-tag {
  font-size: 20rpx;
  color: $glass-brand-dark;
  padding: 4rpx 14rpx;
  border-radius: 999rpx;
  border: 1rpx solid rgba(24, 175, 56, 0.12);
  background: $glass-brand-soft;
}
.fb-qxl-spotlight-cta {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4rpx;
  width: 104rpx;
  height: 104rpx;
  border-radius: 50%;
  background: #18af38;
  border: none;
  box-shadow: 0 8rpx 24rpx rgba(24, 175, 56, 0.28);
}
.fb-qxl-spotlight-cta-label {
  font-size: 22rpx;
  font-weight: 600;
  color: #fff;
  line-height: 1.2;
  text-align: center;
}
.fb-qxl-spotlight-cta-arrow {
  font-size: 32rpx;
  font-weight: 300;
  color: #fff;
  line-height: 1;
  margin-top: -4rpx;
}
.fb-entry-mark {
  font-size: 16rpx;
  letter-spacing: 1rpx;
  opacity: 0.82;
  position: relative;
  z-index: 1;
  white-space: nowrap;
}
.fb-entry-main {
  display: flex;
  flex-direction: row;
  align-items: center;
  width: 100%;
  gap: 10rpx;
  position: relative;
  z-index: 1;
}
.fb-entry-emoji {
  font-size: 32rpx;
  margin: 0;
  flex-shrink: 0;
  line-height: 1;
}
.fb-entry-text {
  display: flex;
  flex-direction: column;
  gap: 2rpx;
  flex: 1;
  min-width: 0;
}
.fb-entry-title {
  font-size: 26rpx;
  font-weight: 700;
  line-height: 1.3;
}
.fb-entry-sub {
  font-size: 20rpx;
  opacity: 0.9;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}
.fb-entry-go {
  flex-shrink: 0;
  width: 36rpx;
  height: 36rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.24);
  display: flex;
  align-items: center;
  justify-content: center;
}
.fb-entry-arrow {
  font-size: 28rpx;
  font-weight: 300;
  opacity: 1;
  line-height: 1;
}

/* ===== 标签栏 ===== */
.fb-tab-bar {
  @include glass-sticky-bar;
  position: sticky;
  top: 0;
  z-index: 20;
}
.fb-tab-scroll {
  white-space: nowrap;
  height: 80rpx;
}
.fb-tab-list {
  display: inline-flex;
  align-items: center;
  padding: 0 16rpx;
  height: 100%;
}
.fb-tab-item {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 56rpx;
  padding: 0 24rpx;
  margin-right: 8rpx;
  border-radius: 28rpx;
  background: rgba(255, 255, 255, 0.45);
  border: 1rpx solid rgba(255, 255, 255, 0.5);
  flex-shrink: 0;
  transition: all 0.2s;
}
.fb-tab-active {
  background: rgba(24, 175, 56, 0.14);
  border-color: rgba(24, 175, 56, 0.2);
}
.fb-tab-active .fb-tab-text {
  color: #18af38;
  font-weight: 600;
}
.fb-tab-text {
  font-size: 26rpx;
  color: #636366;
}

/* ===== 活动 Tab：公益行动入口整图 ===== */
.fb-special-bundle-poster-wrap {
  padding: 16rpx;
}
.fb-special-bundle-poster-wrap--compact {
  padding: 10rpx 24rpx 8rpx;
  margin-top: 2rpx;
  width: 100%;
  box-sizing: border-box;
}
.fb-special-bundle-poster-frame {
  overflow: hidden;
  width: 100%;
  height: 180rpx;
  border-radius: 20rpx;
  box-shadow: 0 6rpx 20rpx rgba(24, 80, 48, 0.1);
  background: #f4faf6;
  /* 裁切框内缘，避免素材白边贴边 */
  transform: translateZ(0);
}
.fb-special-bundle-poster-img {
  width: 100%;
  display: block;
  border-radius: 20rpx;
  box-shadow: 0 12rpx 32rpx rgba(106, 79, 158, 0.22);
}
.fb-special-bundle-poster-wrap--compact .fb-special-bundle-poster-img {
  /* 略放大并偏移，裁掉素材自带的上/左/下（及右）白边；框高不变 */
  width: 124%;
  max-width: none;
  margin-left: -12%;
  margin-top: -36rpx;
  border-radius: 0;
  box-shadow: none;
  vertical-align: top;
}

/* ===== 通用板块 ===== */
.fb-section {
  margin: 28rpx 0 8rpx;
}
.fb-task-section {
  margin: 18rpx 24rpx 8rpx;
  padding: 22rpx;
  border: 1rpx solid rgba(255, 255, 255, 0.85);
  border-radius: 24rpx;
  background: linear-gradient(130deg, #fff8e9 0%, #fff1dd 52%, #ffe5c5 100%);
  box-shadow: 0 12rpx 30rpx rgba(177, 95, 35, 0.13), inset 0 1rpx 0 #fff;
}
.fb-task-heading,
.fb-task-heading-main {
  display: flex;
  align-items: center;
}
.fb-task-heading {
  justify-content: space-between;
  margin-bottom: 16rpx;
}
.fb-task-title {
  font-size: 32rpx;
  font-weight: 800;
  color: #713a19;
}
.fb-task-label {
  margin-left: 12rpx;
  padding: 5rpx 12rpx;
  border-radius: 999rpx;
  background: #f7d5ad;
  color: #a45220;
  font-size: 20rpx;
  font-weight: 700;
}
.fb-task-more {
  display: flex;
  align-items: center;
  gap: 4rpx;
  color: #a45220;
  font-size: 22rpx;
}
.fb-task-card {
  min-height: 196rpx;
  display: flex;
  align-items: center;
  position: relative;
  overflow: hidden;
  padding: 24rpx;
  border-radius: 20rpx;
  background: linear-gradient(115deg, #c95d27 0%, #e98235 55%, #f6ad54 100%);
}
.fb-task-card--pressed {
  opacity: 0.88;
  transform: scale(0.99);
}
.fb-task-copy {
  position: relative;
  z-index: 2;
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding-right: 12rpx;
}
.fb-task-eyebrow {
  color: rgba(255, 255, 255, 0.84);
  font-size: 21rpx;
}
.fb-task-name {
  max-width: 100%;
  margin-top: 10rpx;
  color: #fff;
  font-size: 32rpx;
  font-weight: 800;
  line-height: 1.35;
  white-space: normal;
}
.fb-task-cta {
  display: flex;
  align-items: center;
  gap: 6rpx;
  margin-top: 16rpx;
  padding: 9rpx 16rpx;
  border-radius: 999rpx;
  background: rgba(117, 49, 20, 0.3);
  color: #fff;
  font-size: 22rpx;
  font-weight: 700;
}
.fb-task-image-wrap {
  position: relative;
  z-index: 1;
  flex-shrink: 0;
  width: 170rpx;
  height: 148rpx;
  overflow: hidden;
  border: 3rpx solid rgba(255, 255, 255, 0.55);
  border-radius: 18rpx;
  box-shadow: 0 8rpx 20rpx rgba(93, 43, 20, 0.2);
}
.fb-task-orb {
  position: absolute;
  right: 116rpx;
  top: -100rpx;
  width: 270rpx;
  height: 270rpx;
  border: 2rpx solid rgba(255, 255, 255, 0.18);
  border-radius: 50%;
  pointer-events: none;
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
.fb-hd-bar {
  width: 8rpx;
  height: 32rpx;
  border-radius: 8rpx;
  margin-right: 12rpx;
  flex-shrink: 0;
}
.fb-hd-bar--gold {
  background: linear-gradient(180deg, #f6d365 0%, #dda017 100%);
}
.fb-hd-bar--ink {
  background: linear-gradient(180deg, #5b6bff 0%, #3d4ec8 100%);
}
.fb-section-title {
  font-size: 34rpx;
  font-weight: 700;
  color: #1c2b1e;
  margin-right: 8rpx;
}
.fb-hd-chip {
  margin-left: 12rpx;
  padding: 4rpx 12rpx;
  border-radius: 999rpx;
}
.fb-hd-chip--gold {
  background: #fff4d8;
}
.fb-hd-chip-text {
  font-size: 20rpx;
  color: #b88912;
  font-weight: 600;
}
.fb-live-dot {
  width: 16rpx;
  height: 16rpx;
  border-radius: 50%;
  background: #18af38;
  margin-right: 12rpx;
  box-shadow: 0 0 0 6rpx rgba(24, 175, 56, 0.18);
  animation: fbHubPulse 1.6s ease-in-out infinite;
}
.fb-section-more {
  display: flex;
  align-items: center;
}
.fb-more-text {
  font-size: 24rpx;
  color: #18af38;
  font-weight: 600;
  margin-right: 4rpx;
}

/* ===== 本周推荐：大图海报 ===== */
.fb-weekly-scroll {
  white-space: nowrap;
  padding-bottom: 8rpx;
}
.fb-weekly-list {
  display: inline-flex;
  padding: 0 24rpx 8rpx;
}
.fb-weekly-card {
  position: relative;
  display: inline-block;
  width: 520rpx;
  height: 320rpx;
  margin-right: 20rpx;
  overflow: hidden;
  flex-shrink: 0;
  background: #dce8de;
  box-shadow: 0 14rpx 32rpx rgba(180, 130, 20, 0.16);
  clip-path: polygon(
    0 10%,
    6% 0,
    94% 0,
    100% 10%,
    100% 90%,
    94% 100%,
    6% 100%,
    0 90%
  );
}
.fb-weekly-card--pin {
  background: linear-gradient(145deg, #3d9b5c 0%, #2a7d48 55%, #1f6b3a 100%);
  box-shadow: 0 14rpx 32rpx rgba(42, 125, 72, 0.28);
}
.fb-weekly-pin-bg {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24rpx;
  box-sizing: border-box;
}
.fb-weekly-pin-emoji {
  font-size: 72rpx;
  line-height: 1;
}
.fb-weekly-pin-bg-t {
  margin-top: 16rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.92);
  letter-spacing: 2rpx;
}
.fb-weekly-hatch {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 1;
  pointer-events: none;
  background: repeating-linear-gradient(
    -28deg,
    rgba(255, 255, 255, 0) 0,
    rgba(255, 255, 255, 0) 14rpx,
    rgba(255, 255, 255, 0.14) 14rpx,
    rgba(255, 255, 255, 0.14) 16rpx
  );
}
.fb-weekly-hatch--soft {
  background: repeating-linear-gradient(
    -28deg,
    rgba(255, 255, 255, 0) 0,
    rgba(255, 255, 255, 0) 16rpx,
    rgba(255, 255, 255, 0.08) 16rpx,
    rgba(255, 255, 255, 0.08) 18rpx
  );
}
.fb-weekly-mask {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 180rpx;
  background: linear-gradient(
    180deg,
    rgba(0, 0, 0, 0) 0%,
    rgba(0, 0, 0, 0.72) 100%
  );
  pointer-events: none;
}
.fb-weekly-tag {
  position: absolute;
  top: 22rpx;
  left: 28rpx;
  z-index: 2;
  padding: 6rpx 16rpx;
  background: linear-gradient(135deg, #f6d365 0%, #dda017 100%);
  clip-path: polygon(0 0, 100% 0, 92% 100%, 0 100%);
}
.fb-weekly-tag-text {
  font-size: 20rpx;
  font-weight: 700;
  color: #fff;
}
.fb-weekly-badge {
  position: absolute;
  top: 16rpx;
  right: 16rpx;
  z-index: 2;
  padding: 6rpx 14rpx;
  background: rgba(0, 0, 0, 0.45);
  border-radius: 999rpx;
}
.fb-weekly-badge-text {
  color: #fff;
  font-size: 20rpx;
  font-weight: 500;
}
.fb-weekly-foot {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 2;
  padding: 20rpx 24rpx 24rpx;
}
.fb-weekly-name {
  font-size: 30rpx;
  font-weight: 700;
  color: #fff;
  line-height: 1.35;
  white-space: normal;
}
.fb-weekly-date {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.82);
}

/* ===== 现在就能参加：绿色行动宫格 ===== */
.fb-light-act {
  @include glass-sheet;
  margin: 24rpx 24rpx 8rpx;
  padding: 24rpx 0 12rpx;
}
.fb-light-act .fb-section-header {
  padding: 0 24rpx 16rpx;
}
.fb-light-grid {
  display: flex;
  flex-wrap: wrap;
  padding: 0 12rpx 12rpx;
}
.fb-light-card {
  width: 50%;
  box-sizing: border-box;
  padding: 0 12rpx 20rpx;
  display: flex;
  flex-direction: column;
}
.fb-light-cover {
  width: 100%;
  height: 168rpx;
  overflow: hidden;
  background: #dce8de;
  position: relative;
}
.fb-light-card:nth-child(odd) .fb-light-cover {
  clip-path: polygon(0 0, 100% 0, 100% 100%, 0 82%);
}
.fb-light-card:nth-child(even) .fb-light-cover {
  clip-path: polygon(0 0, 100% 0, 100% 82%, 0 100%);
}
.fb-light-info {
  @include glass-surface(0.68, 24px);
  padding: 16rpx 16rpx 8rpx;
  border: 1rpx solid rgba(255, 255, 255, 0.82);
  box-shadow: 0 10rpx 28rpx rgba(31, 54, 42, 0.07), inset 0 1rpx 0 rgba(255, 255, 255, 0.9);
  box-sizing: border-box;
}
.fb-light-name {
  font-size: 26rpx;
  font-weight: 700;
  color: #1c2b1e;
  line-height: 1.35;
  height: 70rpx;
  white-space: normal;
}
.fb-light-btn {
  background: #18af38;
  padding: 12rpx 0;
  text-align: center;
  clip-path: polygon(0 0, 100% 0, 92% 100%, 0 100%);
}
.fb-light-btn-text {
  font-size: 24rpx;
  color: #fff;
  font-weight: 700;
}

/* ===== 近期活动：台历信息流 ===== */
.fb-recent-list {
  padding: 0 24rpx;
  position: relative;
}
.fb-recent-list::before {
  content: "";
  position: absolute;
  left: 66rpx;
  top: 12rpx;
  bottom: 28rpx;
  border-left: 3rpx dashed rgba(61, 78, 200, 0.28);
  pointer-events: none;
}
.fb-recent-card {
  @include glass-surface(0.78, 18px);
  display: flex;
  align-items: center;
  padding: 16rpx 18rpx 16rpx 16rpx;
  margin-bottom: 16rpx;
  clip-path: polygon(0 0, 100% 0, 100% 100%, 3% 100%, 0 72%);
  position: relative;
}
.fb-recent-cal {
  width: 88rpx;
  height: 96rpx;
  clip-path: polygon(
    0 12%,
    12% 0,
    88% 0,
    100% 12%,
    100% 88%,
    88% 100%,
    12% 100%,
    0 88%
  );
  background-image:
    repeating-linear-gradient(
      -28deg,
      rgba(61, 78, 200, 0) 0,
      rgba(61, 78, 200, 0) 8rpx,
      rgba(61, 78, 200, 0.08) 8rpx,
      rgba(61, 78, 200, 0.08) 9rpx
    ),
    linear-gradient(180deg, #eef1ff 0%, #dfe4ff 100%);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-right: 14rpx;
  flex-shrink: 0;
}
.fb-recent-cal-d {
  font-size: 36rpx;
  font-weight: 700;
  color: #3d4ec8;
  line-height: 1;
}
.fb-recent-cal-m {
  margin-top: 6rpx;
  font-size: 20rpx;
  color: #6b78d4;
}
.fb-recent-cover {
  flex-shrink: 0;
  width: 120rpx;
  height: 96rpx;
  border-radius: 16rpx;
  overflow: hidden;
  background: #dce8de;
  margin-right: 16rpx;
}
.fb-recent-info {
  flex: 1;
  min-width: 0;
  margin-right: 8rpx;
}
.fb-recent-name {
  font-size: 28rpx;
  font-weight: 700;
  color: #1c2b1e;
  line-height: 1.35;
  white-space: normal;
}
.fb-recent-meta {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  color: #8a9b8d;
}

/* ===== 活动列表 ===== */
.fb-act-list {
  padding: 16rpx 24rpx calc(88rpx + env(safe-area-inset-bottom));
}
.fb-act-list-hd {
  padding-left: 0;
  padding-right: 0;
}
.fb-act-more-wrap {
  padding: 8rpx 0 24rpx;
}
.fb-act-more-btn {
  @include glass-surface(0.55, 36px);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8rpx;
  height: 88rpx;
  border-radius: $glass-radius-lg;
}
.fb-act-more-btn--on {
  opacity: 0.88;
  transform: scale(0.985);
}
.fb-act-more-text {
  font-size: 28rpx;
  font-weight: 600;
  color: $glass-brand-dark;
}
.fb-act-card {
  @include glass-surface(0.8, 20px);
  margin-bottom: 20rpx;
  overflow: hidden;
  border-radius: $glass-radius-lg;
}
.fb-act-card:active {
  opacity: 0.92;
}
.fb-act-cover {
  position: relative;
  width: 100%;
  height: 320rpx;
  background: #dce8de;
}
.fb-act-body {
  padding: 20rpx 24rpx 22rpx 32rpx;
  position: relative;
  background: transparent;
}
.fb-act-body::before {
  content: "";
  position: absolute;
  left: 0;
  top: 16rpx;
  bottom: 16rpx;
  width: 8rpx;
  background: linear-gradient(180deg, #1877f2 0%, #18af38 100%);
}
.fb-act-badge {
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
.fb-act-avatars {
  display: flex;
}
.fb-act-avatar {
  width: 24rpx;
  height: 24rpx;
  border-radius: 50%;
  margin-left: -6rpx;
  border: 2rpx solid #fff;
  background: #f0f2f5;
}
.fb-act-avatar:first-child {
  margin-left: 0;
}
.fb-act-badge-text {
  color: #fff;
  font-size: 20rpx;
  font-weight: 500;
  margin-left: 6rpx;
}
.fb-act-title {
  font-size: 30rpx;
  font-weight: 700;
  color: #1c2b1e;
  line-height: 1.4;
}
.fb-act-meta {
  display: flex;
  flex-wrap: wrap;
  margin-top: 12rpx;
}
.fb-meta-item {
  display: flex;
  align-items: center;
  min-width: 0;
  flex: 1;
  margin-right: 16rpx;
}
.fb-meta-item .u-icon {
  margin-right: 4rpx;
}
.fb-meta-text {
  font-size: 22rpx;
  color: #6a7b6e;
  flex: 1;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

/* ===== 空态/加载 ===== */
.fb-empty {
  text-align: center;
  padding: 100rpx 0;
}
.fb-empty-text {
  font-size: 28rpx;
  color: #65676b;
}
.fb-load-tip {
  text-align: center;
  padding: 30rpx 0;
  font-size: 24rpx;
  color: #65676b;
}

</style>
