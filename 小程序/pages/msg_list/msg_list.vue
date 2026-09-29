<template>
    <view class="u-p-30">
        <view class="u-flex u-rela u-p-tb-30 u-border-bottom" @click="$u.route('/pages/msg_list/system')">
            <u-image width="80" height="80"
                src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250610/40edfdea00cbf3a787137969d0576688.png"></u-image>
            <view class="u-m-l-20">
                <view class="">
                    系统消息
                </view>
                <view class="u-type-info">
                    您有新的通知消息
                </view>
            </view>
            <u-badge type="error" :count="msg.sys_msg_num" :offset="[50, 0]"></u-badge>
        </view>
        <view class="u-flex u-rela u-p-tb-30 u-border-bottom" @click="$u.route('/pages/msg_list/interaction')">
            <u-image width="80" height="80"
                src="https://shukang-budget-1319203539.cos.ap-shanghai.myqcloud.com/xcxkf052/admin/20250610/4c1e107c4a5f141df56bfdef4214376e.png"></u-image>
            <view class="u-m-l-20">
                <view class="">
                    互动消息
                </view>
                <view class="u-type-info">
                    您有新的通知消息
                </view>
            </view>
            <u-badge type="error" :count="msg.forum_msg_num" :offset="[50, 0]"></u-badge>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                msg: {}
            };
        },
        onShow() {
            this.my_msg_num()
        },
        methods: {
            my_msg_num() {
                this.$u.api.my_msg_num().then(res => {
                    this.msg = res.data
                })
            }
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }
</style>