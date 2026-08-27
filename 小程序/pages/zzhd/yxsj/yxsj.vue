<template>
    <view>
        <view class="u-type-white-bg" style="padding: 15rpx 30rpx;">
            <u-search placeholder="搜索信息内容" v-model="keyword" :show-action="false" @clear="search"
                @search="search"></u-search>
        </view>
        <u-tabs :list="list" :is-scroll="false" :current="current" @change="change" active-color="#18AF38"></u-tabs>
        <view class="" v-if="!setting.app_test">
            <view class="u-type-white-bg u-p-30 u-m-b-30" v-for="(item,index) in forum_list" :key="index"
                @click="$u.route('/pages/zzhd/yxsj/details?id='+item.id+'&index='+index)">
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
            @click="navigator('/pages/zzhd/yxsj/add?type='+type)">
            <u-image width="100" height="100"
                src="https://oss.ausite.cn/xcxkf052/admin/20250514/b080e0cca2b8055339bf87e066485ce4.png?watermark"></u-image>
        </view> -->
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30">
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="navigator('/pages/zzhd/yxsj/add?type='+type)">{{type==7?'添加论坛':(type==5?'添加互助':'添加事迹')}}</u-button>
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
                info: {},
                list: [{
                    name: '全部',
                    type: 0
                }, {
                    name: '我的',
                    type: 1
                }],
                current: 0
            };
        },
        onLoad(option) {
            this.type = option.type
            uni.setNavigationBarTitle({
                title: this.type == 6 ? '优秀事迹' : (this.type == 7 ? '环保论坛' : '互助平台')
            })
            this.get_forum_list()
        },
        methods: {
            //
            change(index) {
                this.current = index;
                this.search()
            },
            // 跳转页面
            navigator(url) {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                this.$u.route(url)
            },
            // 分享
            share_brn(item) {
                this.info = item
            },
            // 切换分类
            search() {
                this.page = 1
                this.forum_list = []
                this.get_forum_list()
            },
            // 列表
            get_forum_list() {
                this.$u.api.get_forum_list({
                    keyword: this.keyword,
                    page: this.page,
                    type: this.type,
                    is_my: this.list[this.current].type,
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