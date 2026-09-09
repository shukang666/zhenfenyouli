<template>
    <view>
        <view class="" v-if="!setting.app_test">
            <view class="u-type-white-bg u-p-30 u-m-b-30" v-for="(item,index) in forum_list" :key="index"
                @click="$u.route('/pages/community/details?id='+item.id+'&index='+index)">
                <view class="u-flex u-row-between">
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
                    <view class="u-flex">
                        <view class="">
                            <u-icon name="edit-pen" label-size="24" label="编辑" label-color="#3974f2" color="#3974f2"
                                @click="$u.route('/pages/community/add?id='+item.id)"></u-icon>
                        </view>
                        <view class="u-m-l-20">
                            <u-icon name="trash" label-size="24" label="删除" label-color="#F0392C" color="#F0392C"
                                @click="del_forum(item,index)"></u-icon>
                        </view>
                    </view>
                </view>
                <view class="u-m-t-20 u-font-bold u-font-26">
                    {{item.title}}
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
                            <u-icon name="thumb-up" :label="item.like_total" color="#909399"
                                label-color="#909399"></u-icon>
                        </view>
                        <view class="u-m-l-20">
                            <u-icon name="chat" :label="item.comment_total" color="#909399"
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
        <u-empty margin-top="400" mode="list" v-if="!forum_list.length"></u-empty>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                forum_list: [],
                page: 1,
                total: 0,
                health: {}
            };
        },
        onLoad() {
            this.get_forum_list()
        },
        methods: {
            // 分享
            share_brn(item) {
                this.health = item
            },
            // 删除
            del_forum(item, index) {
                var that = this
                uni.showModal({
                    title: '提示',
                    content: '是否删除？',
                    success: function(res) {
                        if (res.confirm) {
                            that.$u.api.del_forum({
                                id: item.id,
                            }).then(res => {
                                that.$u.toast(res.msg)
                                that.forum_list.splice(index, 1)
                            }).catch(err => {
                                that.$u.toast(err.msg)
                            })
                        } else if (res.cancel) {
                            console.log('用户点击取消');
                        }
                    }
                });
            },
            // 列表
            get_forum_list() {
                this.$u.api.get_forum_list({
                    keyword: this.keyword,
                    page: this.page,
                    is_my: 1,
                    type: 1
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
            this.get_forum_list()
        },
        onShareAppMessage(res) {
            return {
                title: this.health.title,
                imageUrl: this.health.image[0],
                path: '/pages/community/details?id=' + this.health.id
            }
        },
        onShareTimeline() {
            return {
                title: this.health.title,
                imageUrl: this.health.image[0],
                path: '/pages/community/details?id=' + this.health.id
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