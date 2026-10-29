<template>
  <view
    class="activity-tab-page container glass-page"
    @touchstart="onCareTouchStart"
    @touchmove="onCareTouchMove"
    @touchend="onCareTouchEnd"
  >
    <view class="glass-page__ambient"></view>
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
    <care-pull-hint :visible="!showCareHint"></care-pull-hint>
    <slot />
  </view>
</template>

<script>
import careGesture from "@/common/care-gesture.mixin.js";

export default {
  name: "ActivityTabPageShell",
  mixins: [careGesture],
};
</script>

<style lang="scss" scoped>
@import "@/static/styles/glass-ui.scss";

.activity-tab-page {
  position: relative;
  z-index: 0;
}

/* 活动 Tab 不要右上角固定高光（glass-ui 的 ::after） */
.activity-tab-page.glass-page::after {
  content: none;
  display: none;
}
</style>
