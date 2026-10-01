<template>
  <view
    class="container"
    @touchstart="onCareTouchStart"
    @touchmove="onCareTouchMove"
    @touchend="onCareTouchEnd"
  >
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
    <view class="fb-navbar">
      <view class="fb-navbar-inner">
        <view class="fb-search-box" id="guide-fb-search" @click="gotoSearch">
          <view class="fb-search-ico">
            <u-icon name="search" size="28" color="#18AF38"></u-icon>
          </view>
          <text class="fb-search-ph">搜索活动名称 / 地点</text>
        </view>
      </view>
    </view>
    <care-pull-hint :visible="!showCareHint"></care-pull-hint>
    <skeleton-screen :show="isLoading" type="low_carbon"></skeleton-screen>

    <!-- 顶部 Banner 轮播 -->
    <view class="fb-banner-wrap" v-if="banner_list && banner_list.length">
      <swiper
        class="fb-banner-swiper"
        :indicator-dots="banner_list.length > 1"
        indicator-color="rgba(255,255,255,0.5)"
        indicator-active-color="#fff"
        autoplay
        circular
        :interval="4000"
        @change="onBannerChange"
      >
        <swiper-item
          v-for="(banner, idx) in banner_list"
          :key="idx"
          @click="handleBannerTap(banner)"
        >
          <view class="fb-banner-item">
            <u-image
              width="100%"
              height="100%"
              :src="banner.image"
              mode="aspectFill"
            ></u-image>
          </view>
        </swiper-item>
      </swiper>
    </view>

    <!-- 垃圾分类工作台：独立主推位（与场景快捷入口分离） -->
    <view class="fb-dtsh-spotlight" id="guide-fb-dtsh-hub">
      <view
        class="fb-dtsh-spotlight-card"
        hover-class="fb-dtsh-spotlight-press"
        :hover-stay-time="80"
        @click="gotoFlhd"
      >
        <view class="fb-dtsh-spotlight-glow fb-dtsh-spotlight-glow--a"></view>
        <view class="fb-dtsh-spotlight-glow fb-dtsh-spotlight-glow--b"></view>
        <view class="fb-dtsh-spotlight-inner">
          <view class="fb-dtsh-spotlight-copy">
            <view class="fb-dtsh-spotlight-badge-row">
              <text class="fb-dtsh-spotlight-badge">核心</text>
              <text class="fb-dtsh-spotlight-kicker">分类治理</text>
            </view>
            <view class="fb-dtsh-spotlight-title-row">
              <text class="fb-dtsh-spotlight-emoji">♻️</text>
              <text class="fb-dtsh-spotlight-title">垃圾分类工作台</text>
            </view>
            <text class="fb-dtsh-spotlight-desc">学习分类、拍照打卡、督导监测一站搞定</text>
            <view class="fb-dtsh-spotlight-tags">
              <text class="fb-dtsh-spotlight-tag">学习打卡</text>
              <text class="fb-dtsh-spotlight-tag">督导巡检</text>
              <text class="fb-dtsh-spotlight-tag">积分排行</text>
            </view>
          </view>
          <view class="fb-dtsh-spotlight-cta">
            <text class="fb-dtsh-spotlight-cta-label">进入工作台</text>
            <!-- <text class="fb-dtsh-spotlight-cta-arrow">›</text> -->
          </view>
        </view>
      </view>
    </view>

    <!-- 场景快捷入口：家庭 · 办公 · 沿街商铺 -->
    <view class="fb-entry-stage" id="guide-fb-entry">
      <view class="fb-entry-kicker">
        <view class="fb-entry-kicker-dot"></view>
        <text class="fb-entry-kicker-text">场景快捷入口</text>
      </view>
      <view class="fb-entry-row fb-entry-row--triple">
        <view
          class="fb-entry-card fb-entry-home"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="gotoHomeScene"
        >
          <text class="fb-entry-mark">01 家庭</text>
          <view class="fb-entry-main">
            <text class="fb-entry-emoji">🏠</text>
            <view class="fb-entry-text">
              <text class="fb-entry-title">家庭场景</text>
              <text class="fb-entry-sub">厨房客厅，全家日常投放</text>
            </view>
            <view class="fb-entry-go fb-entry-go--dark">
              <text class="fb-entry-arrow">›</text>
            </view>
          </view>
        </view>
        <view
          class="fb-entry-card fb-entry-office"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="gotoOfficeBuilding"
        >
          <text class="fb-entry-mark">02 办公</text>
          <view class="fb-entry-main">
            <text class="fb-entry-emoji">🏢</text>
            <view class="fb-entry-text">
              <text class="fb-entry-title">办公场景</text>
              <text class="fb-entry-sub">大厦真分角投放</text>
            </view>
            <view class="fb-entry-go">
              <text class="fb-entry-arrow">›</text>
            </view>
          </view>
        </view>
        <view
          class="fb-entry-card fb-entry-street"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="gotoStreetShop"
        >
          <text class="fb-entry-mark">03 沿街</text>
          <view class="fb-entry-main">
            <text class="fb-entry-emoji">🏪</text>
            <view class="fb-entry-text">
              <text class="fb-entry-title">沿街商铺</text>
              <text class="fb-entry-sub">路边门店分类投放</text>
            </view>
            <view class="fb-entry-go">
              <text class="fb-entry-arrow">›</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 我和七贤岭：独立主推位（非快捷入口，后续可扩展专题能力） -->
    <view class="fb-qxl-spotlight" id="guide-fb-qxl">
      <view
        class="fb-qxl-spotlight-card"
        hover-class="fb-qxl-spotlight-press"
        :hover-stay-time="80"
        @click="gotoQxl"
      >
        <view class="fb-qxl-spotlight-glow fb-qxl-spotlight-glow--a"></view>
        <view class="fb-qxl-spotlight-glow fb-qxl-spotlight-glow--b"></view>
        <view class="fb-qxl-spotlight-inner">
          <view class="fb-qxl-spotlight-copy">
            <view class="fb-qxl-spotlight-badge-row">
              <text class="fb-qxl-spotlight-badge">专题</text>
              <text class="fb-qxl-spotlight-kicker">山野社区</text>
            </view>
            <view class="fb-qxl-spotlight-title-row">
              <text class="fb-qxl-spotlight-emoji">⛰️</text>
              <text class="fb-qxl-spotlight-title">我和七贤岭</text>
            </view>
            <text class="fb-qxl-spotlight-desc">一起守护这片绿水青山</text>
            <view class="fb-qxl-spotlight-tags">
              <text class="fb-qxl-spotlight-tag">认识</text>
              <text class="fb-qxl-spotlight-tag">探索</text>
              <text class="fb-qxl-spotlight-tag">记录</text>
              <text class="fb-qxl-spotlight-tag">守护</text>
            </view>
          </view>
          <view class="fb-qxl-spotlight-cta">
            <text class="fb-qxl-spotlight-cta-label">进入专题</text>
            <!-- <text class="fb-qxl-spotlight-cta-arrow">›</text> -->
          </view>
        </view>
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

    <!-- 主题活动：5 大分类 -->
    <view class="fb-section fb-theme-act" id="guide-fb-theme">
      <view class="fb-section-header">
        <view class="fb-hd">
          <view class="fb-entry-kicker-dot"></view>
          <text class="fb-section-title">主题活动</text>
        </view>
      </view>
      <!-- 现行：一行五个圆钮 + 下方文字 -->
      <view class="fb-theme-row">
        <view
          class="fb-theme-item"
          v-for="cate in main_categories"
          :key="cate.id"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="gotoCateList(cate)"
        >
          <view class="fb-theme-circle" :style="{ background: cate.bgColor }">
            <text class="fb-theme-emoji">{{ cate.emoji }}</text>
          </view>
          <text class="fb-theme-label">{{ cate.name }}</text>
        </view>
      </view>
      <!-- 备用：左大卡 + 右 2×2（已弃用）
			<view class="fb-cate-wrap">
				<view class="fb-cate-big" :style="{background: main_categories[0].bgColor}"
					@click="gotoCateList(main_categories[0])">
					<text class="fb-cate-big-emoji">{{main_categories[0].emoji}}</text>
					<text class="fb-cate-big-name">{{main_categories[0].name}}</text>
					<text class="fb-cate-big-sub">Healthy Life</text>
				</view>
				<view class="fb-cate-right">
					<view class="fb-cate-mini" v-for="(cate, idx) in main_categories.slice(1)" :key="cate.id"
						:style="{background: cate.bgColor}" @click="gotoCateList(cate)">
						<text class="fb-cate-mini-emoji">{{cate.emoji}}</text>
						<view class="fb-cate-mini-text">
							<text class="fb-cate-mini-name">{{cate.name}}</text>
							<text class="fb-cate-mini-sub">{{cate.subtitle}}</text>
						</view>
					</view>
				</view>
			</view>
			-->
    </view>

    <!-- 本周推荐：大图海报横滑，和下面两块刻意做成不同版式 -->
    <view class="fb-section fb-weekly-rec" v-if="weekly_display_list.length > 0">
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
            <view v-if="item.isWeeklyPin" class="fb-weekly-pin-bg">
              <text class="fb-weekly-pin-emoji">♻️</text>
              <text class="fb-weekly-pin-bg-t">垃圾分类真分打卡</text>
            </view>
            <u-image
              v-else
              width="520"
              height="320"
              :src="item.thumb"
              mode="aspectFill"
            ></u-image>
            <view class="fb-weekly-hatch"></view>
            <view class="fb-weekly-mask"></view>
            <view class="fb-weekly-tag">
              <text class="fb-weekly-tag-text">{{ item.isWeeklyPin ? '真分打卡' : '本周精选' }}</text>
            </view>
            <view class="fb-weekly-badge" v-if="!item.isWeeklyPin && item.active_count > 0">
              <text class="fb-weekly-badge-text"
                >{{ item.active_count }}人参与</text
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

    <!-- 现在就能参加：绿色行动块，强调立刻点 -->
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
          @click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)"
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

    <!-- 近期活动：日期台历 + 信息流，不像海报也不像宫格 -->
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
          @click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)"
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

    <!-- 活动列表 -->
    <view class="fb-act-list">
      <view class="fb-section-header fb-act-list-hd">
        <view class="fb-hd">
          <view class="fb-entry-kicker-dot"></view>
          <text class="fb-section-title">全部活动</text>
          <text class="fb-entry-kicker-hint">继续往下看</text>
        </view>
      </view>
      <view
        class="fb-act-card"
        v-for="item in active_list"
        :key="item.id"
        @click="$u.route(`/pages/activity/lsxf/details-new?id=${item.id}`)"
      >
        <view class="fb-act-cover">
          <u-image
            width="100%"
            height="100%"
            :src="item.thumb"
            mode="aspectFill"
          ></u-image>
          <view class="fb-weekly-hatch fb-weekly-hatch--soft"></view>
          <view class="fb-act-badge" v-if="item.active_count > 0">
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
            <text class="fb-act-badge-text">{{ item.active_count }}人参与</text>
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

      <!-- 空态/加载中：均不显示 -->
      <view
        class="fb-load-tip"
        v-if="
          !active_loading &&
          active_list.length > 0 &&
          active_list.length >= active_total
        "
        >没有更多了</view
      >
    </view>
    <page-guide page="low_carbon"></page-guide>
  </view>
</template>

<script>
import { checkCommunity } from "@/common/communityCheck.mixin.js";
import {
  openSceneClockTheme,
  openSceneClockHub,
  parseBannerSceneClockEntry,
  openSceneClockFromBannerEntry,
} from "@/common/sceneClockEntry.js";
import careGesture from "@/common/care-gesture.mixin.js";
import SkeletonScreen from "@/components/skeleton-screen/index.vue";

/** 本周推荐横滑第一项：固定真分打卡（不走后台 is_month_recommend） */
const WEEKLY_ZHENFEN_PIN = {
  isWeeklyPin: true,
  name: "真分打卡",
  active_count: 0,
};

export default {
  components: { SkeletonScreen },
  mixins: [careGesture],
  data() {
    return {
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
          emoji: "🏃",
          bgColor: "linear-gradient(135deg, #E7F0FE 0%, #1877F2 100%)",
        },
        {
          id: 2,
          name: "学习成长",
          subtitle: "Growing Up",
          emoji: "🌟",
          bgColor: "linear-gradient(135deg, #D4FC79 0%, #96E6A1 100%)",
        },
        {
          id: 3,
          name: "兴趣体验",
          subtitle: "Hobby Time",
          emoji: "🎨",
          bgColor: "linear-gradient(135deg, #FFD194 0%, #FF9A56 100%)",
        },
        {
          id: 4,
          name: "交流互动",
          subtitle: "Neighbors",
          emoji: "🤝",
          bgColor: "linear-gradient(135deg, #FF9A9E 0%, #FECFEF 100%)",
        },
        {
          id: 5,
          name: "公益行动",
          subtitle: "Volunteer",
          emoji: "💚",
          bgColor: "linear-gradient(135deg, #A18CD1 0%, #D8B4FE 100%)",
        },
      ],
    };
  },
  computed: {
    weekly_display_list() {
      return [WEEKLY_ZHENFEN_PIN].concat(this.weekly_list || []);
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
    onWeeklyCardTap(wIdx) {
      const list = this.weekly_display_list || [];
      const item = list[wIdx];
      if (!item) return;
      if (item.isWeeklyPin) {
        openSceneClockTheme(this);
        return;
      }
      if (!item.id) return;
      this.$u.route("/pages/activity/lsxf/details-new?id=" + item.id);
    },
    weeklyCardDateLine(item) {
      const base = this.formatActiveDate(item);
      if (item && item.isWeeklyPin) return base;
      if (Number(item.is_date_pending) === 1) return base;
      return base + " 结束";
    },
    formatActiveDate(item) {
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
        await this.fetchActivityTabHome({ page: 1 });
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
      const params = { page, page_size: 10 };
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
        if (Object.prototype.hasOwnProperty.call(data, "recent_list")) {
          this.recent_list = data.recent_list || [];
        }
        const list = data.active_list || [];
        if (page === 1) {
          this.active_list = list;
        } else {
          this.active_list.push(...list);
        }
        this.active_total = data.active_total != null ? data.active_total : list.length;
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

    // Banner 点击处理
    handleBannerTap(banner) {
      const url = (banner.url || "").trim();
      const target = banner.target || "";
      console.log("[SCF-DEBUG] low_carbon handleBannerTap", {
        url,
        target,
        isLogin: this.isLogin,
        community_id: this.userInfo && this.userInfo.community_id,
      });
      if (!url) return;

      const sceneEntry = parseBannerSceneClockEntry(url);
      if (sceneEntry && openSceneClockFromBannerEntry(this, sceneEntry)) {
        return;
      }

      // 如果是完整页面路径，直接跳转
      if (url.startsWith("/pages/")) {
        if (target === "switchTab") {
          uni.switchTab({ url });
        } else {
          uni.navigateTo({ url });
        }
        return;
      }

      // 如果是数字，当作活动 ID
      if (/^\d+$/.test(url)) {
        this.$u.route(`/pages/activity/lsxf/details-new?id=${url}`);
        return;
      }

      // 如果是 active?id=xxx 格式
      const match = url.match(/id=(\d+)/);
      if (match) {
        this.$u.route(`/pages/activity/lsxf/details-new?id=${match[1]}`);
        return;
      }
    },

    // 点击分类 → 进入分类列表页
    gotoCateList(cate) {
      this.$u.route(
        `/pages/activity/cate-list?cate_id=${cate.id}&name=${encodeURIComponent(cate.name)}`,
      );
    },

    // 垃圾分类工作台 → 低碳生活二级页（督导、打卡等）
    gotoFlhd() {
      this.$u.route("/pages/dtsh/dtsh");
    },

    // 等同选场景页点「家庭」：与 Banner 总入口同一套 scene-clock-flow
    gotoHomeScene() {
      console.log("[SCF-DEBUG] low_carbon gotoHomeScene", {
        isLogin: this.isLogin,
        community_id: this.userInfo && this.userInfo.community_id,
      });
      openSceneClockHub(this, "home");
    },

    gotoDtshPage(url) {
      this.$u.route(url);
    },

    gotoOfficeBuilding() {
      console.log("[SCF-DEBUG] low_carbon gotoOfficeBuilding", { isLogin: this.isLogin });
      openSceneClockHub(this, "office");
    },

    gotoStreetShop() {
      console.log("[SCF-DEBUG] low_carbon gotoStreetShop", { isLogin: this.isLogin });
      openSceneClockHub(this, "street");
    },

    // 我和七贤岭入口
    gotoQxl() {
      this.$u.route("/pages/activity/love-qixianling");
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

    // Banner 切换（可选埋点）
    onBannerChange() {},
  },

  onReachBottom() {
    if (this.active_list.length >= this.active_total) return;
    if (this.active_loading) return;
    this.active_page++;
    this.loadActiveList();
  },
};
</script>

<style lang="scss" scoped>
.container {
  min-height: 100vh;
  background: #eef4f0;
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
  background: #fff;
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
  background: #f3f8f4;
  border-radius: 38rpx;
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

/* ===== Banner 轮播 ===== */
.fb-banner-wrap {
  width: 100%;
  padding: 16rpx 24rpx 0;
  margin-bottom: 8rpx;
  box-sizing: border-box;
}
.fb-banner-swiper {
  width: 100%;
  height: 330rpx;
  border-radius: 24rpx;
  overflow: hidden;
}
.fb-banner-item {
  width: 100%;
  height: 100%;
}

/* ===== 主题活动：一行五个圆钮 ===== */
.fb-theme-row {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: space-between;
  padding: 4rpx 20rpx 20rpx;
}
.fb-theme-item {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.fb-theme-circle {
  width: 104rpx;
  height: 104rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8rpx 20rpx rgba(16, 48, 24, 0.12);
  border: 4rpx solid rgba(255, 255, 255, 0.85);
  box-sizing: border-box;
}
.fb-theme-emoji {
  font-size: 44rpx;
  line-height: 1;
}
.fb-theme-label {
  margin-top: 14rpx;
  width: 100%;
  padding: 0 4rpx;
  box-sizing: border-box;
  font-size: 22rpx;
  font-weight: 600;
  color: #142018;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
  font-weight: 700;
  color: #142018;
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
  padding-bottom: 12rpx;
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
  background: linear-gradient(145deg, #ffb88a 0%, #e86a3a 55%, #d45a28 100%);
}
.fb-entry-quiz {
  background: linear-gradient(145deg, #7eb8ff 0%, #3d7ee8 55%, #2a5fc4 100%);
}
.fb-entry-know {
  background: linear-gradient(145deg, #6ee09a 0%, #22a85a 55%, #178f47 100%);
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
.fb-entry-shot .fb-entry-sub,
.fb-entry-shot .fb-entry-arrow,
.fb-entry-shot .fb-entry-mark,
.fb-entry-quiz .fb-entry-title,
.fb-entry-quiz .fb-entry-sub,
.fb-entry-quiz .fb-entry-arrow,
.fb-entry-quiz .fb-entry-mark,
.fb-entry-know .fb-entry-title,
.fb-entry-know .fb-entry-sub,
.fb-entry-know .fb-entry-arrow,
.fb-entry-know .fb-entry-mark {
  color: #fff;
}
.fb-entry-card {
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-start;
  gap: 8rpx;
  padding: 16rpx 14rpx 14rpx;
  min-height: 0;
  border-radius: 16rpx;
  box-shadow: 0 6rpx 18rpx rgba(16, 40, 24, 0.1);
  position: relative;
  overflow: hidden;
  color: #fff;
}
.fb-entry-home {
  background: linear-gradient(145deg, #f8ecd0 0%, #e8c56a 55%, #d4a832 100%);
  box-shadow: 0 6rpx 18rpx rgba(120, 90, 20, 0.14);
}
.fb-entry-xsd {
  background: linear-gradient(145deg, #f5a89e 0%, #e85d7a 55%, #d94f6a 100%);
}
.fb-entry-office {
  background: linear-gradient(145deg, #8b9dc3 0%, #5c6b8a 55%, #4a5568 100%);
}
.fb-entry-street {
  background: linear-gradient(145deg, #6fcf88 0%, #38b86a 55%, #1f9d52 100%);
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
.fb-entry-home .fb-entry-mark {
  color: #4a3d14;
}
.fb-entry-home .fb-entry-go--dark {
  background: rgba(74, 61, 20, 0.12);
}
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
  color: #fff;
}

/* ===== 垃圾分类工作台 · 活动页独立主推位 ===== */
.fb-dtsh-spotlight {
  padding: 12rpx 20rpx 8rpx;
}
.fb-dtsh-spotlight-card {
  position: relative;
  overflow: hidden;
  border-radius: 20rpx;
  padding: 28rpx 24rpx 26rpx;
  background: linear-gradient(
    128deg,
    #0a3d6e 0%,
    #1565b8 38%,
    #1a8fd4 72%,
    #5ec8ff 100%
  );
  box-shadow: 0 12rpx 32rpx rgba(10, 61, 110, 0.26),
    0 2rpx 0 rgba(255, 255, 255, 0.14) inset;
}
.fb-dtsh-spotlight-press {
  opacity: 0.92;
  transform: scale(0.992);
}
.fb-dtsh-spotlight-glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  background: rgba(255, 255, 255, 0.14);
}
.fb-dtsh-spotlight-glow--a {
  width: 200rpx;
  height: 200rpx;
  top: -72rpx;
  right: -48rpx;
}
.fb-dtsh-spotlight-glow--b {
  width: 140rpx;
  height: 140rpx;
  bottom: -56rpx;
  left: -32rpx;
  background: rgba(255, 255, 255, 0.08);
}
.fb-dtsh-spotlight-inner {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: space-between;
  gap: 20rpx;
}
.fb-dtsh-spotlight-copy {
  flex: 1;
  min-width: 0;
}
.fb-dtsh-spotlight-badge-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 10rpx;
}
.fb-dtsh-spotlight-badge {
  font-size: 18rpx;
  font-weight: 700;
  color: #0a3d6e;
  background: rgba(255, 255, 255, 0.92);
  padding: 4rpx 12rpx;
  border-radius: 999rpx;
  letter-spacing: 2rpx;
}
.fb-dtsh-spotlight-kicker {
  font-size: 22rpx;
  color: rgba(255, 255, 255, 0.82);
  letter-spacing: 1rpx;
}
.fb-dtsh-spotlight-title-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 6rpx;
}
.fb-dtsh-spotlight-emoji {
  font-size: 40rpx;
  line-height: 1;
}
.fb-dtsh-spotlight-title {
  font-size: 34rpx;
  font-weight: 800;
  color: #fff;
  letter-spacing: 1rpx;
}
.fb-dtsh-spotlight-desc {
  display: block;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.45;
  margin-bottom: 14rpx;
}
.fb-dtsh-spotlight-tags {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  gap: 8rpx;
}
.fb-dtsh-spotlight-tag {
  font-size: 20rpx;
  color: rgba(255, 255, 255, 0.95);
  padding: 4rpx 14rpx;
  border-radius: 999rpx;
  border: 1rpx solid rgba(255, 255, 255, 0.38);
  background: rgba(255, 255, 255, 0.12);
}
.fb-dtsh-spotlight-cta {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4rpx;
  width: 112rpx;
  height: 112rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.22);
  border: 2rpx solid rgba(255, 255, 255, 0.45);
  box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.1);
}
.fb-dtsh-spotlight-cta-label {
  font-size: 20rpx;
  font-weight: 700;
  color: #fff;
  line-height: 1.25;
  text-align: center;
  padding: 0 6rpx;
}
.fb-dtsh-spotlight-cta-arrow {
  font-size: 36rpx;
  font-weight: 300;
  color: #fff;
  line-height: 1;
  margin-top: -4rpx;
}

/* ===== 我和七贤岭 · 活动页独立主推位 ===== */
.fb-qxl-spotlight {
  padding: 8rpx 20rpx 16rpx;
}
.fb-qxl-spotlight-card {
  position: relative;
  overflow: hidden;
  border-radius: 20rpx;
  padding: 28rpx 24rpx 26rpx;
  background: linear-gradient(
    128deg,
    #0d4d3a 0%,
    #157a52 42%,
    #1fa864 78%,
    #3dd68c 100%
  );
  box-shadow: 0 12rpx 32rpx rgba(13, 77, 58, 0.28),
    0 2rpx 0 rgba(255, 255, 255, 0.12) inset;
}
.fb-qxl-spotlight-press {
  opacity: 0.92;
  transform: scale(0.992);
}
.fb-qxl-spotlight-glow {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  background: rgba(255, 255, 255, 0.12);
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
  color: rgba(255, 255, 255, 0.78);
  letter-spacing: 1rpx;
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
  font-size: 36rpx;
  font-weight: 800;
  color: #fff;
  letter-spacing: 1rpx;
}
.fb-qxl-spotlight-desc {
  display: block;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.88);
  line-height: 1.45;
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
  color: rgba(255, 255, 255, 0.95);
  padding: 4rpx 14rpx;
  border-radius: 999rpx;
  border: 1rpx solid rgba(255, 255, 255, 0.35);
  background: rgba(255, 255, 255, 0.12);
}
.fb-qxl-spotlight-cta {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4rpx;
  width: 112rpx;
  height: 112rpx;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.22);
  border: 2rpx solid rgba(255, 255, 255, 0.45);
  box-shadow: 0 8rpx 20rpx rgba(0, 0, 0, 0.12);
}
.fb-qxl-spotlight-cta-label {
  font-size: 22rpx;
  font-weight: 700;
  color: #fff;
  line-height: 1.2;
  text-align: center;
}
.fb-qxl-spotlight-cta-arrow {
  font-size: 36rpx;
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
  position: sticky;
  top: 0;
  z-index: 20;
  background: #fff;
  border-top: 1rpx solid #e4e6eb;
  border-bottom: 1rpx solid #e4e6eb;
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
  background: #f0f2f5;
  flex-shrink: 0;
  transition: all 0.2s;
}
.fb-tab-active {
  background: #e7f0fe;
}
.fb-tab-active .fb-tab-text {
  color: #1877f2;
  font-weight: 600;
}
.fb-tab-text {
  font-size: 26rpx;
  color: #65676b;
}

/* ===== 通用板块 ===== */
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
  margin: 24rpx 24rpx 8rpx;
  padding: 24rpx 0 8rpx;
  background-image:
    repeating-linear-gradient(
      -28deg,
      rgba(24, 175, 56, 0) 0,
      rgba(24, 175, 56, 0) 14rpx,
      rgba(24, 175, 56, 0.06) 14rpx,
      rgba(24, 175, 56, 0.06) 16rpx
    ),
    linear-gradient(180deg, #e7f8eb 0%, #f4fbf6 100%);
  clip-path: polygon(0 0, 100% 0, 100% 94%, 0 100%);
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
  background: #fff;
  padding: 16rpx 16rpx 8rpx;
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
  display: flex;
  align-items: center;
  background: #fff;
  padding: 16rpx 18rpx 16rpx 16rpx;
  margin-bottom: 16rpx;
  box-shadow: 0 8rpx 24rpx rgba(16, 48, 24, 0.06);
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
.fb-act-card {
  background: #fff;
  margin-bottom: 20rpx;
  overflow: hidden;
  box-shadow: 0 10rpx 28rpx rgba(16, 48, 24, 0.07);
  clip-path: polygon(0 0, 100% 0, 100% 94%, 0 100%);
}
.fb-act-card:active {
  opacity: 0.92;
}
.fb-act-cover {
  position: relative;
  width: 100%;
  height: 320rpx;
  background: #dce8de;
  clip-path: polygon(0 0, 100% 0, 100% 86%, 0 100%);
}
.fb-act-body {
  padding: 20rpx 24rpx 22rpx 32rpx;
  position: relative;
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

/* 关怀版下拉提示层 */
.care-pull-layer {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: linear-gradient(
    180deg,
    #e8f5ee 0%,
    rgba(232, 245, 238, 0.9) 100%
  );
  display: flex;
  align-items: flex-end;
  justify-content: center;
  padding-bottom: 16rpx;
  z-index: 9998;
  pointer-events: none;

  .care-pull-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8rpx;

    .care-pull-text {
      font-size: 28rpx;
      color: #18af38;
      font-weight: 500;
    }
  }
}
</style>
