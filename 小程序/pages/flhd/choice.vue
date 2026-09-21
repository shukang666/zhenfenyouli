<template>
    <view v-if="!setting.app_test">
        <u-tabs :list="list" :is-scroll="false" :current="current" @change="change" active-color="#18AF38"></u-tabs>
        <view class="u-type-white-bg" style="padding: 15rpx 30rpx;">
            <u-search placeholder="搜索信息内容" v-model="keyword" :show-action="false" @clear="search"
                @search="search"></u-search>
        </view>
        <view class="">
            <view class="u-type-white-bg u-p-30 u-m-b-30" v-for="(item,index) in forum_list" :key="index"
                @click="$u.route('/pages/flhd/dk_details?id='+item.id+'&index='+index)">
                <view class="u-flex u-row-between">
                    <view class="u-flex">
                        <u-avatar :src="item.avatar" size="94"></u-avatar>
                        <view class="u-m-l-10">
                            <view class="u-font-bold">
                                {{item.nickname}}
                            </view>
                            <view class="u-font-24 u-type-info u-m-t-10">
                                {{item.create_time|date('yy年mm月dd日 hh:MM')}}
                            </view>
                        </view>
                    </view>
                    <view class="u-type-success u-font-bold">
                        +{{item.point}}积分
                    </view>
                </view>
                <view class="u-m-t-20 u-font-bold u-font-26">
                    {{item.cate_name}}
                </view>
                <view class="u-m-t-20 u-font-bold u-font-26 content-text">
                    {{item.content}}
                </view>
                <view class="u-m-t-20">
                    <cl-upload v-model="item.image" :max="item.image?item.image.length:''" :remove="false"
                        :isPreviewImage="false">
                    </cl-upload>
                </view>
                <view class="u-m-t-20 u-flex u-row-between">
                    <view class="u-font-24 u-type-info">
                        已有{{item.view_num}}人围观
                    </view>
                    <view class="u-flex">
                        <view class="">
                            <u-icon :name="!item.is_like?'thumb-up':'thumb-up-fill'" size="36"
                                :color="!item.is_like?'#909399':'#3974f2'" :label="item.like_total"
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
        <u-gap height="40"></u-gap>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                forum_list: [],
                page: 1,
                total: 0,
                forum: {},
                list: [{
                    name: '精选打卡',
                    type: 0
                }],
                current: 0
            };
        },
        onLoad() {
            this.get_choice_list()
        },
        methods: {
            // 
            navigator(url) {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
                this.$u.route(url)
            },
            // 
            change(index) {
                this.current = index;
                this.search()
            },
            // 分享
            share_brn(item) {
                this.forum = item
            },
            search() {
                this.forum_list = []
                this.page = 1
                this.get_choice_list()
            },
            // 精选打卡列表
            get_choice_list() {
                this.$u.api.get_choice_list({
                    keyword: this.keyword,
                    page: this.page
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
            this.get_choice_list()
        },
        onShareAppMessage(res) {
            return {
                title: this.forum.title,
                imageUrl: this.forum.image ? this.forum.image[0] : '',
                path: '/pages/flhd/dk_details?id=' + this.forum.id
            }
        },
        onShareTimeline() {
            return {
                title: this.forum.title,
                imageUrl: this.forum.image ? this.forum.image[0] : '',
                path: '/pages/flhd/dk_details?id=' + this.forum.id
            }
        }
    }
</script>

<style lang="scss">
.content-text {
    white-space: pre-line;
    word-break: break-all;
}
</style>
