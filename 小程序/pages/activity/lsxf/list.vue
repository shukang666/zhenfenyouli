<template>
    <view>
        <u-tabs :list="list" :is-scroll="false" :current="current" @change="change" active-color="#1877F2"></u-tabs>
        <view class="" v-if="!setting.app_test">
            <view class="u-type-white-bg u-p-30 u-m-b-30" v-for="(item,index) in forum_list" :key="index"
                @click="$u.route('/pages/activity/lsxf/list_details?id='+item.id+'&index='+index)">
                <view class="u-flex">
                    <u-avatar :src="item.avatar" size="94"></u-avatar>
                    <view class="u-m-l-10">
                        <view class="u-font-bold">
                            {{item.nickname}}
                        </view>
                        <view class="u-font-24 u-type-info u-m-t-20">
                            {{item.create_time|date('yy年mm月dd日 hh:MM')}}
                        </view>
                    </view>
                </view>
                <view class="u-m-t-20">
                    <cl-upload v-model="item.image" :max="item.image?item.image.length:''" :remove="false">
                    </cl-upload>
                </view>
                <view class="u-m-t-20 u-font-26">
                    <text style="color: #1877F2;">作品介绍：</text> {{item.content}}
                </view>
                <view class="u-m-t-20 u-flex u-row-between">
                    <view class="u-font-24 u-type-info">
                        已有{{item.view_num}}人围观
                    </view>
                    <view class="u-flex">
                        <view class="">
                            <u-icon name="thumb-up" :label="item.like_total" size="36" color="#909399"
                                label-color="#909399"></u-icon>
                        </view>
                        <view class="u-m-l-20">
                            <u-icon name="chat" :label="item.comment_total" size="38" color="#909399"
                                label-color="#909399"></u-icon>
                        </view>
                        <view class="u-m-l-20">
                            <button class="u-reset-button" open-type="share" @click.stop="share_brn(item)">
                                <u-icon name="zhuanfa" label="分享" size="34" color="#909399"
                                    label-color="#909399"></u-icon>
                            </button>
                        </view>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                forum_list: [],
                page: 1,
                total: 0,
                id: 0,
                info: {},
                list: [{
                    name: '全部作品',
                    type: 0
                }, {
                    name: '我的作品',
                    type: 1
                }],
                current: 0
            };
        },
        onLoad(option) {
            this.id = option.id
            this.get_active_user_list()
        },
        methods: {
            //
            change(index) {
                this.current = index;
                this.forum_list=[]
                this.page=1
                this.get_active_user_list()
            },
            // 分享
            share_brn(item) {
                this.info = item
            },
            // 列表
            get_active_user_list() {
                this.$u.api.get_active_user_list({
                    active_id: this.id,
                    page: this.page,
                    user_type:this.list[this.current].type
                }).then(res => {
                    this.forum_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.forum_list.length >= this.total) return
            this.page++
            this.get_active_user_list()
        },
        onShareAppMessage(res) {
            return {
                title: this.info.title,
                imageUrl: this.info.image[0] || '',
                path: '/pages/activity/lsxf/list_details?id=' + this.info.id
            }
        },
        onShareTimeline() {
            return {
                title: this.info.name,
                path: '/pages/activity/lsxf/details?id=' + this.info.id,
                imageUrl: this.info.thumb
            }
        }
    }
</script>

<style lang="scss">

</style>