<template>
    <view class="container">
        <view class="fb-list">
            <view class="fb-card" v-for="(item,index) in cate_list" :key="index"
                @click="$u.route('/pages/low_carbon/lsxf/details?id='+item.id)">
                <view class="fb-card-cover">
                    <u-image width="100%" height="320" :src="item.thumb" mode="aspectFill"></u-image>
                </view>
                <view class="fb-card-body">
                    <view class="fb-card-title u-line-1">{{item.name}}</view>
                    <view class="fb-card-meta">
                        <view class="fb-meta-item">
                            <u-icon name="clock" size="26" color="#65676B"></u-icon>
                            <text class="fb-meta-text">{{item.date}} {{item.day_type}}</text>
                        </view>
                        <view class="fb-meta-item" v-if="item.area_range">
                            <u-icon name="map" size="26" color="#65676B"></u-icon>
                            <text class="fb-meta-text u-line-1">{{item.area_range}}</text>
                        </view>
                    </view>
                    <view class="fb-card-footer">
                        <view class="fb-join-btn">立即参与</view>
                    </view>
                </view>
            </view>
            <u-empty margin-top="400" mode="list" v-if="!cate_list.length"></u-empty>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                cate_list: [],
                page: 1,
                total: 0,
            };
        },
        onLoad() {
            this.get_active_list()
        },
        methods: {
            // 环保活动
            get_active_list() {
                this.$u.api.get_active_list({
                    page: this.page,
                    is_my:1
                }).then(res => {
                    this.cate_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.cate_list.length >= this.total) return
            this.page++
            this.get_active_list()
        }
    }
</script>

<style lang="scss" scoped>
.container {
    min-height: 100vh;
    background: #F0F2F5;
    padding: 16rpx;
}
.fb-list {
    padding-bottom: 32rpx;
}
.fb-card {
    background: #fff;
    border-radius: 16rpx;
    margin-bottom: 16rpx;
    overflow: hidden;
    box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.08);
    border: 1rpx solid #E4E6EB;
}
.fb-card:active { opacity: 0.92; }
.fb-card-cover {
    width: 100%;
    height: 320rpx;
    background: #F0F2F5;
}
.fb-card-body {
    padding: 24rpx 24rpx 20rpx;
}
.fb-card-title {
    font-size: 32rpx;
    font-weight: 600;
    color: #050505;
    line-height: 1.4;
    margin-bottom: 14rpx;
}
.fb-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16rpx;
    margin-bottom: 16rpx;
}
.fb-meta-item {
    display: flex;
    align-items: center;
    min-width: 0;
    flex: 1;
}
.fb-meta-item .u-icon { margin-right: 6rpx; }
.fb-meta-text {
    font-size: 24rpx;
    color: #65676B;
    flex: 1;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.fb-card-footer {
    display: flex;
    justify-content: flex-end;
    padding-top: 16rpx;
    border-top: 1rpx solid #E4E6EB;
}
.fb-join-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 64rpx;
    padding: 0 36rpx;
    background: #E7F0FE;
    color: #1877F2;
    font-size: 26rpx;
    font-weight: 600;
    border-radius: 32rpx;
    transition: all 0.2s;
}
.fb-join-btn:active {
    background: #1877F2;
    color: #fff;
}
</style>
