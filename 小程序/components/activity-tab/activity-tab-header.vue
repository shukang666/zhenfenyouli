<template>
  <view class="fb-navbar glass-nav">
    <view class="fb-navbar-inner">
      <view class="fb-search-box glass-search" id="guide-fb-search" @click="$emit('search')">
        <view class="fb-search-ico">
          <u-icon name="search" size="28" color="#18AF38"></u-icon>
        </view>
        <text class="fb-search-ph">搜索活动名称 / 地点</text>
      </view>
    </view>
    <view class="fb-page-tabs">
      <view
        class="fb-page-tab"
        :class="{ 'fb-page-tab--on': value === 'neighbor' }"
        @click="$emit('input', 'neighbor')"
      >
        <text>邻里活动</text>
      </view>
      <view
        class="fb-page-tab"
        :class="{ 'fb-page-tab--on': value === 'featured' }"
        @click="$emit('input', 'featured')"
      >
        <text>精选专区</text>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  name: "ActivityTabHeader",
  props: {
    value: {
      type: String,
      default: "neighbor",
    },
  },
};
</script>

<style lang="scss" scoped>
@import "@/static/styles/glass-ui.scss";

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

.fb-page-tabs {
  display: flex;
  gap: 12rpx;
  margin-top: 16rpx;
  padding: 6rpx;
  border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.55);
  border: 1rpx solid rgba(255, 255, 255, 0.65);
}
.fb-page-tab {
  flex: 1;
  height: 64rpx;
  border-radius: 999rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28rpx;
  font-weight: 600;
  color: #5f7166;
  transition: background 0.2s, color 0.2s;
}
.fb-page-tab--on {
  background: #fff;
  color: #16864b;
  box-shadow: 0 4rpx 16rpx rgba(22, 134, 75, 0.12);
}
</style>
