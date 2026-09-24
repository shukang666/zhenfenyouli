<template>
    <view class="u-p-30">
        <u-navbar title-color="#fff" :is-back="false" background="transparent" :borderBottom="false">
            <view class="u-m-l-30">
                <u-image width="231" height="64" :src="setting.min_logo"></u-image>
            </view>
        </u-navbar>
        <view class="u-abso u-h-552"
            style="z-index: -1;top: 0;left: 0;right: 0;background: linear-gradient(to bottom,#2fb74c,#EFF9F1);">
            <!-- <u-image width="100%" height="527" src="/static/index-bg.png"></u-image> -->
        </view>
        <u-swiper :list="swiper_list" height="350" mode="none" border-radius="0"
            @click="adLink($event,swiper_list)"></u-swiper>
        <view class="u-flex u-row-between u-m-t-30">
            <u-image width="157" height="160"
                src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250509/c8ca1e5c0043e1828a6641eb0fe51818.png"
                @click="switchTab('/pages/low_carbon/low_carbon')"></u-image>
            <u-image width="157" height="160"
                src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250509/8477a04f91cff5567b2e79c18825d3c7.png"
                @click="switchTab('/pages/health/health')"></u-image>
            <u-image width="157" height="160"
                src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250509/62509808194abfc7bf235a9d24948203.png"
                @click="switchTab('/pages/mall/mall')"></u-image>
            <u-image width="157" height="160"
                src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250509/ba3f9579228e8e9c116e62240b43f073.png"
                @click="navigator('/pages/sjhd/sjhd')"></u-image>
        </view>
        <view class="u-m-t-30">
            <view class="u-flex u-row-between">
                <view class="u-flex">
                    <u-icon name="/static/index-5.png" size="32"></u-icon>
                    <text class="u-font-bold u-m-l-10">热门活动</text>
                    <text class="u-font-22 u-type-info u-m-l-10"> | 共同创造美好生态环境</text>
                </view>
                <!-- <view class="">
                    <u-icon name="arrow-right" label="更多" label-size="22" label-color="#999999" color="#999999"
                        label-pos="left" size="22"></u-icon>
                </view> -->
            </view>
            <view class="u-m-t-30 u-type-white-bg u-radius-10" v-for="(item,index) in cate_list" :key="index"
                @click="$u.route('/pages/activity/lsxf/details?id='+item.id)">
                <u-image width="100%" height="326" :src="item.thumb"></u-image>
                <view class="u-p-20 u-flex u-row-between">
                    <view class="">
                        <view class="u-font-bold u-font-32">
                            {{item.name}}
                        </view>
                        <view class="u-flex u-m-t-10">
                            <view class="">
                                <u-icon name="clock" labelSize="24" size="24"
                                    :label="item.date+' '+item.day_type"></u-icon>
                            </view>
                            <view class="u-m-l-20">
                                <u-icon name="map" labelSize="24" size="24" :label="item.area_range"></u-icon>
                            </view>
                        </view>
                    </view>
                    <view class="">
                        <u-button type="primary" size="mini" hover-class="none"
                            :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                            @click="$u.route('/pages/activity/lsxf/details?id='+item.id)">立即参与</u-button>
                    </view>
                </view>
            </view>
        </view>
        <view class="u-m-t-30" @click="$u.route('/pages/dtsh/dtsh')" v-if="setting.is_waste">
            <u-image width="100%" height="240"
                src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250606/68d14d845f207d15e52bdc15d735c387.png"></u-image>
        </view>
        <!--  -->
        <view class="u-type-white-bg u-radius-10 u-m-t-30 u-p-lr-20" @click="wj_btn(item)"
            v-for="(item,index) in health_list" :key="index">
            <view class="u-p-tb-20 u-border-bottom">
                <u-image width="100%" height="166" :src="item.thumb"></u-image>
                <view class="u-m-t-20">
                    <view class="">
                        <view class="u-font-bold u-font-32">
                            {{item.name}}
                        </view>
                        <view class="u-type-info u-m-t-10" v-html="$u.func.formatTextArea(item.desc)">

                        </view>
                    </view>
                </view>
                <view class="u-text-right u-m-t-20">
                    <u-button type="primary" size="mini" hover-class="none"
                        :customStyle="{background: 'linear-gradient( 145deg, #F0DF73 0%, #FFA320 100%)'}"
                        @click="wj_btn(item)">立即参与</u-button>
                </view>
            </view>
        </view>
        <view class="u-m-t-30">
            <view class="u-flex">
                <u-icon name="/static/index-6.png" size="32"></u-icon>
                <text class="u-font-bold u-m-l-10">最受欢迎兑换商品</text>
            </view>
            <view class="u-m-t-30 u-flex u-row-between u-flex-wrap">
                <view class="u-type-white-bg u-radius-10 u-w-334 u-m-b-30" v-for="(item,index) in goods_list"
                    :key="index" @click="$u.route('/pages/shop/details?id='+item.id)">
                    <u-image width="334" height="334" :src="item.thumb"></u-image>
                    <view class="u-p-10">
                        <view class="u-font-bold">
                            {{item.goods_name}}
                        </view>
                        <view class="u-m-t-10" style="color: #18AF38;">
                            {{item.price}}元
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <!-- 弹窗 -->
        <u-mask :show="mask_show" :zoom="false">
            <view class="warp">
                <view class="u-rela">
                    <u-image width="540" height="586" src="/static/gg-bg.png"></u-image>
                    <view class="u-w-540 u-p-30 u-abso u-abso-l-0 u-abso-r-0 u-abso-t-170">
                        <view class="u-font-32 u-font-bold u-line-1">
                            {{msg.name}}
                        </view>
                        <view class="u-m-t-30">
                            <scroll-view scroll-y="true" style="height: 280rpx;">
                                <view class="u-font-26 u-type-info">
                                    {{msg.content}}
                                </view>
                            </scroll-view>
                        </view>
                    </view>
                    <view class="u-m-t-30 u-text-center">
                        <u-icon name="close-circle" color="#fff" size="60" @click="mask_show = false"></u-icon>
                    </view>
                </view>
            </view>
        </u-mask>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                swiper_list: [],
                cate_list: [],
                health_list: [],
                goods_list: [],
                page: 1,
                total: 0,
                mask_show: true,
                msg: {}
            }
        },

        onLoad() {
            this.find_slide()
            this.get_active_list()
            this.get_health_home_list()
            this.get_goods_list()
            this.get_system_msg_list()
        },
        methods: {
            // 系统消息
            get_system_msg_list() {
                this.$u.api.get_system_msg_list({
                    type: 1
                }).then(res => {
                    this.msg = res.data.data[0]
                }).catch(err => {})
            },
            // 列表
            get_goods_list() {
                this.$u.api.get_goods_list({
                    is_recommend: 1,
                    page: this.page,
                }).then(res => {
                    this.goods_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },

            // 问卷调研按钮
            wj_btn(item) {
                // if (item.is_exam == 1) return this.$u.toast('您已经参加过问卷')
                this.$u.route('/pages/healthcare/participate?id=' + item.id)
            },
            // 
            switchTab(url) {
                uni.switchTab({
                    url: url
                })
            },
            //
            get_health_home_list(type) {
                this.$u.api.get_health_home_list({
                    type: 2,
                }).then(res => {
                    res.data.forEach(item => {
                        if (item.status == 1) {
                            this.health_list.push(item)
                        }
                    })
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 环保活动
            get_active_list() {
                this.$u.api.get_active_list({
                    is_put: 1,
                    type: 1,
                    page: this.page,
                    page_size: 99999
                }).then(res => {
                    this.cate_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 跳转页面
            navigator(url) {
                // if (!this.isLogin) return this.$u.route('/pages/public/login')
                this.$u.route(url)
            },
            // 轮播图跳转
            async adLink(e, item) {
                let data = item[e];
                if (!data.url) return
                if (data.target == 'switchTab') return uni.switchTab({
                    url: data.url
                });
                const openUrl = await this.$u.func.checkH5(data.url);
                this.$u.route(openUrl);
            },
            // 轮播图
            find_slide() {
                this.$u.api.find_slide({
                    id: 1
                }).then(res => {
                    this.swiper_list = res.data
                })
            },
        },
        onReachBottom() {
            if (this.goods_list.length >= this.total) return
            this.page++
            this.get_goods_list()
        }
    }
</script>

<style lang="scss">
    .warp {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }
</style>