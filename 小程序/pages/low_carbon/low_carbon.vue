<template>
  <activity-tab-page-shell>
    <activity-tab-discover
      :filter="discover_filter"
      :categories="discover_categories"
      :month-theme="discover_month_theme"
      :list="discover_list"
      :loading="discover_loading"
      :has-more="discover_has_more"
      @search="gotoSearch"
      @filter="switchDiscoverFilter"
      @open-cate="gotoCateList"
      @open="openDiscoverActive"
      @more-filter="goMoreAllDiscover"
    />

    <!-- 原邻里 / 精选双 Tab（组件仍保留：neighbor-feed / featured-panel + legacy 样式） -->

    <page-guide page="low_carbon"></page-guide>
  </activity-tab-page-shell>
</template>

<script>
import ActivityTabPageShell from "@/components/activity-tab/activity-tab-page-shell.vue";
import ActivityTabDiscover from "@/components/activity-tab/activity-tab-discover.vue";
import activityDiscoverFeedMixin from "@/pages/low_carbon/mixins/activity-discover-feed.mixin.js";
import memberActiveFeedMixin from "@/pages/low_carbon/mixins/member-active-feed.mixin.js";
import activityTabPageMixin from "@/pages/low_carbon/mixins/activity-tab-page.mixin.js";

export default {
  components: {
    ActivityTabPageShell,
    ActivityTabDiscover,
  },
  mixins: [
    activityTabPageMixin,
    activityDiscoverFeedMixin,
    memberActiveFeedMixin,
  ],
};
</script>
