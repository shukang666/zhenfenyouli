<template>
    <view>
        <view class="u-p-30">
            <view class="u-p-30 u-radius-10 u-flex white u-text-center"
                style="background: linear-gradient( 135deg, #A4DB88 0%, #18AF38 100%);">
                <view class="u-flex-1">
                    <view class="u-font-32  u-font-bold">
                        {{tj.sell_amount}}
                    </view>
                    <view class="u-m-t-30">
                        销售额
                    </view>
                </view>
                <view class="u-flex-1">
                    <view class="u-font-32  u-font-bold ">
                        {{tj.sell_count}}
                    </view>
                    <view class="u-m-t-30">
                        累计销量
                    </view>
                </view>
            </view>
            <view class="u-m-t-25 u-radius-5">
                <view class="u-flex u-row-between">
                    <view class="u-font-32 u-font-bold">
                        数据统计
                    </view>
                    <view class="" @click="show=true">
                        <text v-if="start_time">
                            {{`${start_time}~${end_time}`}}
                        </text>
                        <text v-else>日期选择</text> <u-icon name="arrow-down-fill" size="25" class="u-m-l-5"></u-icon>
                    </view>
                </view>
                <view class="u-p-30 u-type-white-bg u-m-t-30 u-radius-10" v-for="item in dataList" :key="item.id">
                    <view class="u-flex u-border-bottom u-p-b-20">
                        <u-avatar :src="item.thumb" size="58"></u-avatar>
                        <view class="u-font-bold u-m-l-10">
                            {{item.goods_name}}
                        </view>
                    </view>
                    <view class="u-flex u-m-t-20 u-text-center">
                        <view class="u-flex-1">
                            <view class="u-font-32  u-font-bold">
                                {{item.goods_price}}
                            </view>
                            <view class="u-m-t-30">
                                销售额
                            </view>
                        </view>
                        <view class="" style="color: #999999;">
                            |
                        </view>
                        <view class="u-flex-1">
                            <view class="u-font-32  u-font-bold ">
                                {{item.goods_num}}
                            </view>
                            <view class="u-m-t-30">
                                累计销量
                            </view>
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <u-calendar v-model="show" :mode="rang" @change="change"></u-calendar>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                dataList: [],
                page: 1,
                total: 0,
                end_time: '',
                start_time: '',
                show: false,
                account_type: 1,
                tj:{}
            }
        },
        onLoad(option) {
            this.get_shop_goods_total()
        },
        methods: {
            // 选择日期
            change(e) {
                this.start_time = e.startDate
                this.end_time = e.endDate
                this.dataList = []
                this.page = 1
                this.get_shop_goods_total()
            },
            // 获取记录
            get_shop_goods_total() {
                this.$u.api.get_shop_goods_total({
                    page: this.page,
                    end_time: this.end_time,
                    start_time: this.start_time
                }).then(res => {
                    this.tj = res.data
                    this.dataList.push(...res.data.list.data);
                    this.total = res.data.list.total
                })
            },
        },
        onReachBottom() {
            if (this.dataList.length >= this.total) return
            this.page++
            this.get_shop_goods_total()
        }
    }
</script>

<style lang="scss">

</style>