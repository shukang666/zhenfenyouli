<template>
    <view>
        <u-image width="100%" height="215"
            src="https://oss.ausite.cn/xcxkf052/admin/20250521/6e81c810b05addfb2bc42bb3fbdf567a.png?watermark"></u-image>
        <view class="u-p-30 u-type-white-bg u-rela"
            style="margin-top: -30rpx;z-index: 9;border-radius: 20rpx 20rpx 0 0;">
            <view class="u-flex u-row-between">
                <view class="u-flex">
                    <view class="">
                        <u-tag text="销量排序" :bg-color="type==1?'#18AF38':'#999999'" color="#fff"
                            border-color="transparent" @click="sx_btn(1)" />
                    </view>
                    <view class="u-m-l-30">
                        <u-tag text="点赞排序" :bg-color="type==2?'#18AF38':'#999999'" color="#fff"
                            border-color="transparent" @click="sx_btn(2)" />
                    </view>
                </view>
                <!-- <view class="u-font-22 u-type-info-light-bg u-radius-4" style="padding: 10rpx 20rpx;"
                    @click="show = true">
                    {{startDate||'开始时间'}} - {{endDate||'结束时间'}}
                </view> -->
            </view>
            <view class="u-type-info-light-bg u-p-20 u-flex u-m-t-30 u-radius-10 u-rela"
                v-for="(item,index) in goods_list" :key="index" @click="$u.route('/pages/mall/details?id='+item.id)">
                <view class="u-abso" style="top: 0;left: 0;z-index: 9;">
                    <u-image width="40" height="40"
                        src="https://oss.ausite.cn/xcxkf052/admin/20250521/6d343cc15dfcd9365578b3138a41cb30.png?watermark"
                        v-if="index==0"></u-image>
                    <u-image width="40" height="40"
                        src="https://oss.ausite.cn/xcxkf052/admin/20250521/02ebe49c185514c655d9821008f1961a.png?watermark"
                        v-if="index==1"></u-image>
                    <u-image width="40" height="40"
                        src="https://oss.ausite.cn/xcxkf052/admin/20250521/523680336b8ae0fdc718e7635c2ebbbb.png?watermark"
                        v-if="index==2"></u-image>
                </view>
                <u-image width="160" height="160" :src="item.thumb"></u-image>
                <view class="u-m-l-20 u-flex u-row-between u-col-top u-flex-col u-h-160">
                    <view class="u-font-bold">
                        {{item.goods_name}}
                    </view>
                    <view class="u-type-error-light-bg u-radius-10" style="padding: 0rpx 20rpx;">
                        <u-icon
                            name="https://oss.ausite.cn/xcxkf052/admin/20250521/9220f62fdfb0c76f5d42b175582f52a6.png?watermark"
                            :label="'已兑换'+item.sell_count+'件'" label-size="22" size="24" label-pos="left"
                            label-color="#F32927"></u-icon>
                    </view>
                    <view class="u-type-error">
                        ￥
                        <text class="u-font-bold u-font-32">{{item.price}}</text>
                        <!-- <text class="u-font-22">+20000积分</text> -->
                    </view>
                </view>
            </view>
        </view>
        <!-- 选择时间 -->
        <u-calendar v-model="show" mode="range" @change="change"></u-calendar>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                type: 1,
                show: false,
                startDate: '',
                endDate: '',
                goods_list: [],
                page: 1,
                total: 0
            };
        },
        onLoad() {
            this.get_goods_ranking_list()
        },
        methods: {
            // 筛选
            sx_btn(type) {
                this.type = type
                this.goods_list = []
                this.page = 1
                this.get_goods_ranking_list()
            },
            // 选择日期
            change(e) {
                this.startDate = e.startDate
                this.endDate = e.endDate
            },
            get_goods_ranking_list() {
                this.$u.api.get_goods_ranking_list({
                    keyword: this.keyword,
                    page: this.page,
                    type: this.type
                }).then(res => {
                    this.goods_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.goods_list.length >= this.total) return
            this.page++
            this.get_goods_ranking_list()
        }
    }
</script>

<style lang="scss">

</style>