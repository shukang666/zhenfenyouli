<template>
  <view class="activity-tab-featured">
    <view class="fb-activity-top-banner" v-if="topBanner">
      <image
        class="fb-activity-top-banner-img"
        :src="topBanner"
        mode="widthFix"
        show-menu-by-longpress="false"
      />
    </view>

    <skeleton-screen :show="isLoading" type="low_carbon"></skeleton-screen>

    <view class="fb-task-section" v-if="lightList && lightList.length">
      <view class="fb-task-heading">
        <view class="fb-task-heading-main">
          <text class="fb-task-title">轻任务</text>
          <text class="fb-task-label">线上活动</text>
        </view>
        <view class="fb-task-more" @click="$emit('more-light')">
          <text>更多任务</text>
          <u-icon name="arrow-right" size="22" color="#b65b22"></u-icon>
        </view>
      </view>
      <view
        class="fb-task-card"
        v-if="lightFeatured"
        hover-class="fb-task-card--pressed"
        :hover-stay-time="90"
        @click="$u.func.openActiveDetail(lightFeatured)"
      >
        <view class="fb-task-copy">
          <text class="fb-task-eyebrow">轻松参与 · 线上完成</text>
          <text class="fb-task-name u-line-2">{{ lightFeatured.name }}</text>
          <view class="fb-task-cta">
            <text>立即参与</text>
            <u-icon name="arrow-right" size="22" color="#fff"></u-icon>
          </view>
        </view>
        <view class="fb-task-image-wrap" v-if="lightFeatured.thumb">
          <u-image width="100%" height="100%" :src="lightFeatured.thumb" mode="aspectFill"></u-image>
        </view>
        <view class="fb-task-orb"></view>
      </view>
    </view>

    <view class="fb-section fb-theme-act" id="guide-fb-theme">
      <view class="fb-section-header">
        <view class="fb-hd">
          <view class="fb-entry-kicker-dot"></view>
          <text class="fb-section-title">主题活动</text>
        </view>
      </view>
      <view class="fb-theme-bento" v-if="themeHero">
        <view
          class="fb-theme-hero"
          hover-class="fb-theme-card--press"
          :hover-stay-time="80"
          @click="$emit('open-cate', themeHero)"
        >
          <view class="fb-theme-card-bg" :style="{ background: themeHero.surfaceBg }"></view>
          <text class="fb-theme-hero-ribbon">精选</text>
          <view class="fb-theme-hero-copy">
            <text class="fb-theme-card-title">{{ themeHero.name }}</text>
            <view
              class="fb-theme-pill"
              :style="{ background: themeHero.pillBg, color: themeHero.pillColor }"
            >
              <text>{{ themeHero.pillText }}</text>
            </view>
          </view>
          <view class="fb-theme-hero-icon">
            <view
              class="fb-theme-icon-disk fb-theme-icon-disk--lg"
              :style="{ boxShadow: '0 12rpx 28rpx ' + themeHero.iconHalo }"
            >
              <u-icon :name="themeHero.icon" :color="themeHero.iconColor" size="64"></u-icon>
            </view>
          </view>
        </view>
        <view class="fb-theme-side">
          <view
            v-if="themeSide"
            class="fb-theme-side-card"
            hover-class="fb-theme-card--press"
            :hover-stay-time="80"
            @click="$emit('open-cate', themeSide)"
          >
            <view class="fb-theme-card-bg" :style="{ background: themeSide.surfaceBg }"></view>
            <view class="fb-theme-side-copy">
              <text class="fb-theme-card-title fb-theme-card-title--sm">{{ themeSide.name }}</text>
              <view
                class="fb-theme-pill fb-theme-pill--sm"
                :style="{ background: themeSide.pillBg, color: themeSide.pillColor }"
              >
                <text>{{ themeSide.pillText }}</text>
              </view>
            </view>
            <view class="fb-theme-side-icon">
              <u-icon :name="themeSide.icon" :color="themeSide.iconColor" size="44"></u-icon>
            </view>
          </view>
          <view class="fb-theme-mini-row">
            <view
              v-for="cate in themeMinis"
              :key="cate.id"
              class="fb-theme-mini"
              hover-class="fb-theme-card--press"
              :hover-stay-time="80"
              @click="$emit('open-cate', cate)"
            >
              <view class="fb-theme-card-bg" :style="{ background: cate.surfaceBg }"></view>
              <view class="fb-theme-mini-icon">
                <u-icon :name="cate.icon" :color="cate.iconColor" size="34"></u-icon>
              </view>
              <text class="fb-theme-mini-name">{{ cate.name }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>

    <view
      class="fb-special-bundle-poster-wrap fb-special-bundle-poster-wrap--compact"
      @click="$emit('garbage-classify')"
    >
      <view class="fb-special-bundle-poster-frame">
        <image
          class="fb-special-bundle-poster-img"
          :src="entryBanner"
          mode="widthFix"
          show-menu-by-longpress="false"
        />
      </view>
    </view>

    <!-- 已下线区块的模板备份见同目录 FEATURED_ARCHIVED.md -->
  </view>
</template>

<script>
import SkeletonScreen from "@/components/skeleton-screen/index.vue";

export default {
  name: "ActivityTabFeaturedPanel",
  components: { SkeletonScreen },
  props: {
    topBanner: { type: String, default: "" },
    entryBanner: { type: String, default: "" },
    isLoading: { type: Boolean, default: false },
    lightList: { type: Array, default: () => [] },
    lightFeatured: { type: Object, default: null },
    themeHero: { type: Object, default: null },
    themeSide: { type: Object, default: null },
    themeMinis: { type: Array, default: () => [] },
  },
};
</script>

<style lang="scss" scoped>
@import "@/pages/low_carbon/styles/activity-tab-featured.scss";
</style>
