<template>
    <view class="u-p-lr-30">
        <view class="u-p-tb-30 u-border-bottom u-flex u-row-between" v-for="(item,index) in msg_list" :key="index">
            <view class="u-flex">
                <u-avatar :src="item.avatar" size="110"></u-avatar>
                <view class="u-m-l-10">
                    <view class="u-font-bold">
                        {{item.nickname}}
                    </view>
                    <view class="u-font-26 u-type-info u-m-t-10" v-if="item.type==2">
                        评论了你：{{item.content}}
                    </view>
                    <view class="u-font-26 u-type-info u-m-t-10" v-if="item.type==1">
                        点赞了你的作品
                    </view>
                    <view class="u-type-info u-m-t-10">
                        {{item.create_time|date('yy-mm-dd hh:MM')}}
                    </view>
                </view>
            </view>
            <view class="u-m-l-20">
                <u-image width="180" height="180" :src="item.image"></u-image>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                msg_list: [],
                page: 1,
                total: 0
            };
        },
        onLoad() {
            this.get_msg_list()
        },
        methods: {
            get_msg_list() {
                this.$u.api.get_msg_list({
                    keyword: this.keyword,
                    page: this.page,
                }).then(res => {
                    this.msg_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.msg_list.length >= this.total) return
            this.page++
            this.get_msg_list()
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }
</style>