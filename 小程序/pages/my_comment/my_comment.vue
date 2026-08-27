<template>
    <view>
        <view class="u-type-white-bg u-m-t-30 u-p-30" v-for="(item,index) in comment_list" :key="index">
            <view class="u-flex u-row-between">
                <view class="u-flex">
                    <u-avatar :src="item.avatar"></u-avatar>
                    <view class="u-type-info u-m-l-10">
                        <view class="">
                            {{item.nickname}}
                        </view>
                        <view class="u-font-24 u-m-t-10">
                            {{item.create_time|date('yy-mm-dd hh:MM')}}
                        </view>
                    </view>
                </view>
                <view class="">
                    <u-icon name="trash" label="删除"></u-icon>
                </view>
            </view>
            <view class="u-font-24 u-m-t-30">
                {{item.comment}}
            </view>
            <view class="u-m-t-30 u-p-10 u-type-info-light-bg u-radius-6 u-font-24 u-flex u-row-between"
                @click="$u.route('/pages/community/details?id='+item.forum_id)">
                <view class="u-flex">
                    <u-image width="70" height="70" :src="item.image[0]"></u-image>
                    <view class="u-line-2 u-m-l-10">
                        {{item.content}}
                    </view>
                </view>
                <view class="u-shrink-no">
                    <u-icon name="arrow-right" label="详情" label-color="#18AF38" color="#18AF38" label-size="22"
                        label-pos="left"></u-icon>
                </view>
            </view>
        </view>
        <u-empty margin-top="400" mode="list" v-if="!comment_list.length"></u-empty>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                comment_list: [],
                page: 1,
                total: 0
            };
        },
        onLoad() {
            this.my_comment_list()
        },
        methods: {
            my_comment_list() {
                this.$u.api.my_comment_list({
                    keyword: this.keyword,
                    page: this.page,
                }).then(res => {
                    this.comment_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.comment_list.length >= this.total) return
            this.page++
            this.my_comment_list()
        }
    }
</script>

<style lang="scss">

</style>