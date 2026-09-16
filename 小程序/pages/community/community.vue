<template>
    <view>
        <view class="u-type-white-bg" style="padding: 15rpx 30rpx;">
            <u-search placeholder="搜索信息内容" v-model="keyword" :show-action="false" @clear="cate_btn(cate_index)"
                @search="cate_btn(cate_index)"></u-search>
        </view>
        <view class="u-p-30">
            <scroll-view scroll-x="true">
                <view class="u-flex u-flex-wrap">
                    <view class="u-m-r-20" v-for="(item,index) in cate_list" :key="index" @click="cate_btn(index)">
                        <u-tag :text="item.name" shape="circle" :bg-color="cate_index==index?'#3974f2':'#fff'"
                            :color="cate_index==index?'#fff':'#666666'" border-color="transparent" />
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="" v-if="!setting.app_test">
            <view class="u-type-white-bg u-p-30 u-m-b-30" v-for="(item,index) in forum_list" :key="index"
                @click="$u.route('/pages/community/details?id='+item.id+'&index='+index)">
                <view class="u-flex u-row-between">
                    <view class="u-flex">
                        <u-avatar :src="item.avatar" size="94"></u-avatar>
                        <view class="u-m-l-10 u-font-bold">
                            {{item.nickname}}
                        </view>
                    </view>
                    <view class="u-font-24 u-type-info">
                        {{item.create_time|date('yy年mm月dd日 hh:MM')}}
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
        <!-- <view class="" style="position: fixed;right: 30rpx;bottom: 10%;z-index: 1;"
            @click="$u.route('/pages/community/add')">
            <u-image width="100" height="100" src="https://admin.circular-economy.cn/static/add.png"></u-image>
        </view> -->
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30">
            <u-button type="primary" hover-class="none" :customStyle="{background: '#3974f2'}"
                @click="$u.route('/pages/community/add')">立即发布</u-button>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                cate_list: [],
                cate_index: 0,
                forum_list: [],
                page: 1,
                total: 0,
                health: {}
            };
        },
        onLoad() {
            this.get_cate_list()
        },
        methods: {
            // 分享
            share_brn(item) {
                this.health = item
            },
            // 新闻分类
            get_cate_list() {
                this.$u.api.get_cate_list().then(res => {
                    this.cate_list = res.data
                    this.get_forum_list()
                })
            },
            // 切换分类
            cate_btn(index) {
                this.cate_index = index
                this.page = 1
                this.forum_list = []
                this.get_forum_list()
            },
            // 列表
            get_forum_list() {
                this.$u.api.get_forum_list({
                    keyword: this.keyword,
                    cate_id: this.cate_list[this.cate_index].id,
                    page: this.page,
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