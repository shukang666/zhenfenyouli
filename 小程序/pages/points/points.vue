<template>
    <view class="u-p-30">
        <view class="white u-p-30 u-radius-10" style="background: linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%);"
            @click="handleCancel">
            <view class="u-m-t-30">
                可用积分
            </view>
            <view class="u-font-bold" style="font-size: 48rpx;">
                {{userInfo.point}}
            </view>
        </view>
        <view class="u-type-white-bg u-m-t-30">
            <view class="">
                <view class="u-flex u-row-between u-m-b-25">
                    <view class="u-font-32 u-font-bold">
                        积分明细
                    </view>
                    <view class="" @click="show=true">
                        <text v-if="start_time">
                            {{`${start_time}~${end_time}`}}
                        </text>
                        <text v-else>日期选择</text> <u-icon name="arrow-down-fill" size="25" class="u-m-l-5"></u-icon>
                    </view>
                </view>
                <view class="u-p-tb-30 u-border-bottom u-flex u-row-between u-type-white-bg" v-for="item in dataList"
                    :key="item.id">
                    <view class="">
                        <view class="">
                            {{item.type_name}}
                        </view>
                        <view class="$u-type-info u-font-26 u-m-t-10">
                            {{$u.timeFormat(item.create_time, 'yyyy-mm-dd hh:MM')}}
                        </view>
                    </view>
                    <view class="u-text-right">
                        <view class="">
                            {{item.change_amount}}
                        </view>
                    </view>
                </view>
            </view>
        </view>
        <!-- 选择时间 -->
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
            }
        },
        onLoad(option) {
            this.get_log_list()
        },
        methods: {
            // 提示
            handleCancel(item) {
                let that = this;
                uni.showModal({
                    title: '提示',
                    content: '请到商城兑换礼品',
                    success(str) {
                        if (str.confirm) {
                            uni.switchTab({
                                url: '/pages/mall/mall'
                            })
                        };
                    }
                })
            },
            // 选择日期
            change(e) {
                this.start_time = e.startDate
                this.end_time = e.endDate
                this.dataList = []
                this.page = 1
                this.get_log_list()
            },
            // 获取记录
            get_log_list() {
                this.$u.api.get_log_list({
                    account_type: 2,
                    page: this.page,
                    end_time: this.end_time,
                    begin_time: this.start_time
                }).then(res => {
                    this.dataList.push(...res.data.data);
                    this.total = res.data.total
                })
            },
        },
        onReachBottom() {
            if (this.dataList.length >= this.total) return
            this.page++
            this.get_log_list()
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }
</style>