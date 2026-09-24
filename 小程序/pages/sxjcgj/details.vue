<template>
    <view>
        <u-image width="100%" height="204"
            src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250509/5f9ec3ddd75fb73ccc51eaf56bd07f37.png"></u-image>
        <view class="u-p-30">
            <view class="u-type-primary-light-bg u-p-30 u-radius-10">
                <view class="u-flex u-row-center">
                    <u-image width="206" height="26"
                        src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250509/e63420e89eb4d96d813e60390c0dad51.png"></u-image>
                </view>
                <view class="u-flex u-text-center u-m-t-30">
                    <view class="u-flex-1 u-m-r-30">
                        <view class="u-font-bold">
                            20题
                        </view>
                        <view class="u-font-24 u-type-info u-m-t-20">
                            题目数量
                        </view>
                    </view>
                    <view class="u-font-40" style="color: #D9D9D9;">
                        |
                    </view>
                    <view class="u-flex-1 u-m-l-30">
                        <view class="u-font-bold">
                            20145人
                        </view>
                        <view class="u-font-24 u-type-info u-m-t-20">
                            测评人数
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <view class="u-m-t-30">
            <u-parse :html="content"></u-parse>
        </view>

        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30">
            <u-button type="primary" @click="$u.route('')">开始测评</u-button>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {

            };
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }
</style>