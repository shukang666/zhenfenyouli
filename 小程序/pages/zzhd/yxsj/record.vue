<template>
    <view>
        <view class="" v-if="!setting.app_test">
            <view class="u-type-white-bg u-p-30 u-m-b-30" v-for="(item,index) in forum_list" :key="index"
                @click="$u.route('/pages/zzhd/yxsj/details?id='+item.id+'&index='+index)">
                <view class="u-font-24 u-type-info">
                    {{item.create_time|date('yy年mm月dd日 hh:MM')}}
                </view>
                <view class="u-m-t-20 u-font-bold u-font-26">
                    {{item.title}}
                </view>
                <view class="u-m-t-20 u-font-bold u-font-26">
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
    </view>
</template>

<script>
    export default {
        data() {
            return {
                forum_list: [],
                page: 1,
                total: 0,
                type: 1,
                info: {}
            };
        },
        onLoad(option) {
            this.type = option.type
            this.get_forum_list()
        },
        methods: {
            // 分享
            share_brn(item) {
                this.info = item
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
                    type: this.type
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
                title: this.info.title,
                imageUrl: this.info.image.length ? this.info.image[0] : '',
                path: '/pages/zzhd/yxsj/details?id=' + this.info.id
            }
        },
        onShareTimeline() {
            return {
                title: this.info.title,
                imageUrl: this.info.image[0],
                path: '/pages/zzhd/yxsj/details?id=' + this.info.id
            }
        }
    }
</script>

<style lang="scss">

</style>