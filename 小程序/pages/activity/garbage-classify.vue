<template>
  <view class="container glass-page">
    <view class="glass-page__ambient"></view>

    <!-- 垃圾真分：Banner + 三场景入口统一外框（从活动页搬来） -->
    <view class="fb-zhenfen-hub" id="gc-zhenfen-hub">
      <view class="fb-zhenfen-hub-head">
        <view class="fb-zhenfen-hub-brand">
          <text class="fb-zhenfen-hub-badge">垃圾真分</text>
          <text class="fb-zhenfen-hub-sub">选场景 · 拍照真分</text>
        </view>
        <text class="fb-zhenfen-hub-hint">看海报 · 选场景 · 马上真分</text>
      </view>
      <view class="fb-zhenfen-hub-frame">
        <view class="fb-banner-wrap fb-banner-wrap--inset" v-if="banner_list && banner_list.length">
          <swiper
            class="fb-banner-swiper fb-banner-swiper--inset"
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
        <view class="fb-zhenfen-hub-divider" v-if="banner_list && banner_list.length"></view>
        <view class="fb-entry-stage fb-entry-stage--inset" id="gc-entry">
          <view class="fb-entry-kicker fb-entry-kicker--inset">
            <view class="fb-entry-kicker-dot"></view>
            <text class="fb-entry-kicker-text">三大场景快捷入口</text>
          </view>
          <view class="fb-entry-row fb-entry-row--triple">
            <view
              class="fb-entry-card fb-entry-home"
              hover-class="fb-press"
              :hover-stay-time="80"
              @click="gotoHomeScene"
            >
              <text class="fb-entry-mark">01</text>
              <view class="fb-entry-main">
                <text class="fb-entry-emoji">🏠</text>
                <view class="fb-entry-text">
                  <text class="fb-entry-title">家庭场景</text>
                </view>
              </view>
            </view>
            <view
              class="fb-entry-card fb-entry-office"
              hover-class="fb-press"
              :hover-stay-time="80"
              @click="gotoOfficeBuilding"
            >
              <text class="fb-entry-mark">02</text>
              <view class="fb-entry-main">
                <text class="fb-entry-emoji">🏢</text>
                <view class="fb-entry-text">
                  <text class="fb-entry-title">办公场景</text>
                </view>
              </view>
            </view>
            <view
              class="fb-entry-card fb-entry-street"
              hover-class="fb-press"
              :hover-stay-time="80"
              @click="gotoStreetShop"
            >
              <text class="fb-entry-mark">03</text>
              <view class="fb-entry-main">
                <text class="fb-entry-emoji">🏪</text>
                <view class="fb-entry-text">
                  <text class="fb-entry-title">沿街商铺</text>
                </view>
              </view>
            </view>
          </view>
        </view>
      </view>
    </view>

    <!-- 垃圾分类工作台入口大图卡片 -->
    <view class="fb-dtsh-spotlight" id="gc-entry-dtsh">
      <view
        class="fb-dtsh-spotlight-poster"
        hover-class="fb-dtsh-spotlight-press"
        :hover-stay-time="80"
        @click="gotoFlhd"
      >
        <image
          class="fb-dtsh-spotlight-img"
          :src="dtshSpotlightPoster"
          mode="widthFix"
        />
      </view>
    </view>
  </view>
</template>

<script>
import { DTSH_WORKBENCH_BANNER_URL } from "@/common/activityTabScreenshot.js";
import {
  openSceneClockHub,
  parseBannerSceneClockEntry,
  openSceneClockFromBannerEntry,
} from "@/common/sceneClockEntry.js";

export default {
  data() {
    return {
      banner_list: [],
      dtshSpotlightPoster: DTSH_WORKBENCH_BANNER_URL,
    };
  },
  onLoad() {
    this.fetchBanner();
  },
  methods: {
    /** 拉取垃圾真分 Banner（复用活动页接口，只取 banner_list） */
    fetchBanner() {
      this.$u.api.get_activity_tab_home({ page: 1 }).then((res) => {
        const data = res.data || {};
        if (Object.prototype.hasOwnProperty.call(data, "banner_list")) {
          this.banner_list = data.banner_list || [];
        }
      }).catch(() => {});
    },

    handleBannerTap(banner) {
      const url = (banner.url || "").trim();
      const target = banner.target || "";
      if (!url) return;

      const sceneEntry = parseBannerSceneClockEntry(url);
      if (sceneEntry && openSceneClockFromBannerEntry(this, sceneEntry)) {
        return;
      }

      if (url.startsWith("/pages/")) {
        if (target === "switchTab") {
          uni.switchTab({ url });
        } else {
          uni.navigateTo({ url });
        }
        return;
      }

      if (/^\d+$/.test(url)) {
        this.$u.route(`/pages/activity/lsxf/details-new?id=${url}`);
        return;
      }

      const match = url.match(/id=(\d+)/);
      if (match) {
        this.$u.route(`/pages/activity/lsxf/details-new?id=${match[1]}`);
        return;
      }
    },
    onBannerChange() {},

    gotoHomeScene() {
      openSceneClockHub(this, "home");
    },
    gotoOfficeBuilding() {
      openSceneClockHub(this, "office");
    },
    gotoStreetShop() {
      openSceneClockHub(this, "street");
    },

    gotoFlhd() {
      this.$u.route("/pages/dtsh/dtsh");
    },
  },
};
</script>

<style lang="scss" scoped>
@import "@/static/styles/glass-ui.scss";

.container {
  position: relative;
  z-index: 0;
  padding-bottom: calc(40rpx + env(safe-area-inset-bottom));
}

.container.glass-page::after {
  content: none;
  display: none;
}

.fb-press {
  opacity: 0.92;
  transform: scale(0.98);
}

/* ===== 垃圾真分 Hub（从活动页搬来） ===== */
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

/* Banner 轮播 */
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

/* ===== 三大场景快捷入口 ===== */
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
.fb-entry-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12rpx;
  padding: 4rpx 0 16rpx;
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
.fb-entry-office {
  border-left: 4rpx solid #5c6b8a;
  background: rgba(255, 255, 255, 0.52);
}
.fb-entry-street {
  border-left: 4rpx solid #18af38;
  background: rgba(255, 255, 255, 0.52);
}
.fb-entry-home::after,
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

/* ===== 分类工作台大图卡片 ===== */
.fb-dtsh-spotlight {
  padding: 16rpx 20rpx 8rpx;
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
</style>
