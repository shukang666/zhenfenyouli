<template>
  <view class="fb-member-feed" id="guide-fb-member">
    <view class="fb-member-feed-top">
      <view class="fb-member-publish" hover-class="fb-press" @click="$emit('publish')">
        <u-icon name="plus" size="20" color="#fff"></u-icon>
        <text class="fb-member-publish-text">发布活动</text>
      </view>
    </view>
    <view class="fb-member-empty" v-if="!loading && listLength === 0">
      <text class="fb-member-empty-title">还没有邻里活动</text>
      <text class="fb-member-empty-sub">发起一场读书、徒步、公益小聚，邻居报名后现场签到即可</text>
      <view class="fb-member-empty-btn" @click="$emit('publish')">我来发布第一场</view>
    </view>
    <view class="fb-member-waterfall" v-else>
      <view class="fb-member-col">
        <view
          class="fb-member-card"
          v-for="item in colLeft"
          :key="'m-l-' + item.id"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="$u.func.openActiveDetail(item)"
        >
          <view class="fb-member-cover">
            <u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
            <view class="fb-member-tag">{{ item.theme_label || "社区活动" }}</view>
          </view>
          <view class="fb-member-body">
            <text class="fb-member-name u-line-2">{{ item.name }}</text>
            <text class="fb-member-meta u-line-1">{{ item.time_line }}</text>
            <view class="fb-member-foot">
              <text class="fb-member-pub u-line-1">{{ item.publisher_nickname || "邻居" }}</text>
              <text class="fb-member-count" v-if="engagementCount(item) > 0"
                >{{ engagementCount(item) }}人参与</text
              >
            </view>
          </view>
        </view>
      </view>
      <view class="fb-member-col">
        <view
          class="fb-member-card"
          v-for="item in colRight"
          :key="'m-r-' + item.id"
          hover-class="fb-press"
          :hover-stay-time="80"
          @click="$u.func.openActiveDetail(item)"
        >
          <view class="fb-member-cover fb-member-cover--tall">
            <u-image width="100%" height="100%" :src="item.thumb" mode="aspectFill"></u-image>
            <view class="fb-member-tag">{{ item.theme_label || "社区活动" }}</view>
          </view>
          <view class="fb-member-body">
            <text class="fb-member-name u-line-2">{{ item.name }}</text>
            <text class="fb-member-meta u-line-1">{{ item.time_line }}</text>
            <view class="fb-member-foot">
              <text class="fb-member-pub u-line-1">{{ item.publisher_nickname || "邻居" }}</text>
              <text class="fb-member-count" v-if="engagementCount(item) > 0"
                >{{ engagementCount(item) }}人参与</text
              >
            </view>
          </view>
        </view>
      </view>
    </view>
    <view class="fb-member-load" v-if="loading">
      <u-loading mode="circle" color="#18AF38"></u-loading>
      <text>加载中</text>
    </view>
    <view class="fb-member-load fb-member-load--muted" v-else-if="listLength > 0 && !hasMore">
      <text>— 已经到底啦 —</text>
    </view>
  </view>
</template>

<script>
export default {
  name: "ActivityTabNeighborFeed",
  props: {
    colLeft: { type: Array, default: () => [] },
    colRight: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    hasMore: { type: Boolean, default: true },
    listLength: { type: Number, default: 0 },
    engagementCount: {
      type: Function,
      default: () => 0,
    },
  },
};
</script>

<style lang="scss" scoped>
.fb-press {
  opacity: 0.92;
  transform: scale(0.98);
}

.fb-member-feed {
  padding: 8rpx 24rpx calc(140rpx + env(safe-area-inset-bottom));
}
.fb-member-feed-top {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12rpx;
}
.fb-member-feed .fb-member-waterfall {
  margin-top: 4rpx;
}
.fb-member-publish {
  display: flex;
  align-items: center;
  padding: 12rpx 22rpx;
  border-radius: 999rpx;
  background: linear-gradient(135deg, #1f9d55, #18af38);
  box-shadow: 0 8rpx 20rpx rgba(24, 175, 56, 0.28);
}
.fb-member-publish-text {
  margin-left: 6rpx;
  font-size: 24rpx;
  font-weight: 600;
  color: #fff;
}
.fb-member-empty {
  margin-top: 16rpx;
  padding: 48rpx 32rpx;
  border-radius: 24rpx;
  background: rgba(255, 255, 255, 0.92);
  text-align: center;
  box-shadow: 0 8rpx 28rpx rgba(22, 53, 36, 0.05);
}
.fb-member-empty-title {
  display: block;
  font-size: 30rpx;
  font-weight: 600;
  color: #1a2e22;
}
.fb-member-empty-sub {
  display: block;
  margin-top: 12rpx;
  font-size: 24rpx;
  color: #7a8a80;
  line-height: 1.55;
}
.fb-member-empty-btn {
  display: inline-block;
  margin-top: 28rpx;
  padding: 18rpx 40rpx;
  border-radius: 999rpx;
  background: #e8f7ec;
  color: #16864b;
  font-size: 26rpx;
  font-weight: 600;
}
.fb-member-waterfall {
  display: flex;
  align-items: flex-start;
  gap: 16rpx;
  margin-top: 16rpx;
}
.fb-member-col {
  flex: 1;
  min-width: 0;
}
.fb-member-card {
  margin-bottom: 16rpx;
  border-radius: 20rpx;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 10rpx 28rpx rgba(22, 53, 36, 0.07);
}
.fb-member-cover {
  position: relative;
  width: 100%;
  height: 220rpx;
  background: #dce8de;
}
.fb-member-cover--tall {
  height: 280rpx;
}
.fb-member-tag {
  position: absolute;
  left: 12rpx;
  top: 12rpx;
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  background: rgba(255, 255, 255, 0.92);
  font-size: 20rpx;
  color: #16864b;
  font-weight: 600;
}
.fb-member-body {
  padding: 16rpx 18rpx 18rpx;
}
.fb-member-name {
  font-size: 28rpx;
  font-weight: 600;
  color: #1a2e22;
  line-height: 1.35;
}
.fb-member-meta {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  color: #7a8a80;
}
.fb-member-foot {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 12rpx;
  gap: 8rpx;
}
.fb-member-pub {
  font-size: 22rpx;
  color: #95a59b;
  flex: 1;
}
.fb-member-count {
  font-size: 22rpx;
  color: #18af38;
  font-weight: 600;
  flex-shrink: 0;
}
.fb-member-load {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12rpx;
  padding: 32rpx 0 16rpx;
  font-size: 24rpx;
  color: #18af38;
}
.fb-member-load--muted {
  color: #a8b5ad;
}
</style>
