<template>
    <view>
        <u-swiper :list="swiper_list" height="400" mode="none" border-radius="0"
            @click="adLink($event,swiper_list)"></u-swiper>
        <view class="u-p-lr-30">
            <view class="" v-for="(item,index) in disease_list" :key="index">
                <view class="u-font-bold u-m-t-30">
                    {{item.name}}
                </view>
                <view class="u-flex u-flex-wrap">
                    <view class="u-text-center u-type-info-light-bg u-p-tb-20 u-w-218 boxStyle"
                        v-for="(item1,index1) in item.child" :key="index1"
                        @click="$u.route('/pages/sxjk/details?id='+item1.id)">
                        <view class="u-flex u-row-center">
                            <u-image width="60" height="60" :src="item1.thumb"></u-image>
                        </view>
                        <view class="u-font-24 u-m-t-10">
                            {{item1.name}}
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
                swiper_list: [],
                disease_list: [],
                page: 1,
                total: 0
            };
        },
        onLoad() {
            this.find_slide()
            this.get_disease_list()
        },
        methods: {
            // 
            get_disease_list() {
                this.$u.api.get_disease_list({
                    keyword: this.keyword,
                    page: this.page,
                }).then(res => {
                    this.disease_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
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
                    id: 4
                }).then(res => {
                    this.swiper_list = res.data
                })
            },
        },
        onReachBottom() {
            if (this.disease_list.length >= this.total) return
            this.page++
            this.get_disease_list()
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;

        .boxStyle {
            margin: 30rpx 20rpx 0 0;
        }

        .boxStyle:nth-child(3n+3) {
            margin-right: 0 !important;
        }
    }
</style>