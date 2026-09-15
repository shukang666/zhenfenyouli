<template>
    <view>
        <block v-if="id==6">
            <view class="u-p-30" style="background: #3974f2;">
                <view class="u-font-36 u-font-bold u-type-error u-text-center">
                    {{setting.health_title}}
                </view>
                <view class="u-m-t-30 u-p-20 white u-radius-10" style="background: #84a9f7;">
                    {{setting.health_notice}}
                </view>
            </view>
            <view class="u-type-white-bg">
                <u-grid :col="4" :border="false">
                    <u-grid-item v-for="(item,index) in home_list" :key="index"
                        @click="navigator('/pages/healthcare/details?type=1&id='+item.id)">
                        <u-icon :name="item.thumb" :size="60"></u-icon>
                        <view class="u-font-24 u-w-120 u-text-center u-m-t-10">{{item.name}}</view>
                    </u-grid-item>
                </u-grid>
            </view>
        </block>

        <view class="u-p-30">
            <view class="u-m-b-30" v-if="id==6">
                <u-subsection :list="list" mode="subsection" :current="current" bg-color="transparent"
                    active-color="#ffaa4d" @change="change"></u-subsection>
            </view>
            <view class="u-p-20 u-m-b-30 u-radius-10 u-type-white-bg" v-for="(item,index) in health_list" :key="index">
                <block v-if="item.is_exam == 1">
                    <view @click="wj_btn(item)" class="u-width-100" v-if="!setting.app_test">
                        <view class="u-m-b-20" v-if="item.thumb">
                            <u-image width="100%" height="167" :src="item.thumb"></u-image>
                        </view>
                        <view class="u-font-32 u-font-bold">
                            {{item.name}}
                        </view>
                        <view class="u-m-t-10" v-if="item.desc">
                            {{item.desc}}
                        </view>
                    </view>
                    <view v-else>
                        <view class="u-m-b-20" v-if="item.thumb">
                            <u-image width="100%" height="167" :src="item.thumb"></u-image>
                        </view>
                        <view class="u-font-32 u-font-bold">
                            {{item.name}}
                        </view>
                        <view class="u-m-t-10" v-if="item.desc">
                            {{item.desc}}
                        </view>
                    </view>
                    <view class="u-flex u-row-right u-m-t-20">
                        <view class="">
                            <u-button type="warning" plain size="mini"
                                @click.shop="navigator('/pages/healthcare/details?id='+item.id)">查看详情</u-button>
                        </view>
                        <!-- <view class="u-m-l-30">
                            <u-button type="primary" size="mini" hover-class="none"
                                @click="$u.route('/pages/answer/list?id='+item.id)">填写问卷</u-button>
                        </view> -->
                    </view>
                </block>
                <block v-if="item.is_exam == 2">
                    <view class="u-m-b-20" v-if="item.thumb">
                        <u-image width="100%" height="167" :src="item.thumb"></u-image>
                    </view>
                    <view class="u-font-32 u-font-bold">
                        {{item.name}}
                    </view>
                    <view class="u-m-t-10" v-if="item.desc">
                        {{item.desc}}
                    </view>
                    <view class="u-flex u-row-right u-m-t-20">
                        <view class="">
                            <u-button type="warning" plain size="mini"
                                @click="navigator('/pages/healthcare/details?id='+item.id)">查看详情</u-button>
                        </view>
                        <view class="u-m-l-30">
                            <u-button type="primary" size="mini" hover-class="none"
                                @click="navigator('/pages/healthcare/add?id='+item.id)">立即预约</u-button>
                        </view>
                    </view>
                </block>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                home_list: [],
                health_list: [],
                page: 1,
                total: 0,
                id: 0,
                list: [{
                        name: '问卷调查',
                        type: 1
                    },
                    {
                        name: '预约',
                        type: 2
                    }
                ],
                current: 0
            };
        },
        onLoad(option) {
            this.id = option.id
            this.get_health_home_list(1)
        },
        methods: {
            // 问卷调研按钮
            wj_btn(item) {
                if (item.is_exam == 1&&item.is_answer&&this.id==6) return this.$u.toast('您已经参加过问卷')
                this.$u.route('/pages/answer/list?id=' + item.id)
            },
            // 
            change(e) {
                this.current = e
                this.health_list = []
                this.page = 1
                this.find_exam_topic_list()
            },
            // 跳转页面
            navigator(url) {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                this.$u.route(url)
            },
            // 
            get_health_home_list(type) {
                this.$u.api.get_health_home_list({
                    type: 1,
                    page: this.page
                }).then(res => {
                    this.home_list = res.data
                    this.find_exam_topic_list()
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            //
            find_exam_topic_list() {
                this.$u.api.find_exam_topic_list({
                    cate_id: this.id,
                    page: this.page,
                    is_exam: this.list[this.current].type
                }).then(res => {
                    this.health_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },


        },
        onReachBottom() {
            if (this.health_list.length >= this.total) return
            this.page++
            this.find_exam_topic_list()
        }
    }
</script>

<style lang="scss">

</style>