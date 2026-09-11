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
                </view>
                <view class="u-p-tb-30 u-border-bottom u-flex u-row-between u-type-white-bg" v-for="item in dataList"
                    :key="item.id">
                    <view class="u-flex-1 u-m-r-20">
                        <view class="u-font-30">
                            {{reasonText(item)}}
                        </view>
                        <view class="u-type-info u-font-26 u-m-t-10">
                            {{$u.timeFormat(item.create_time, 'yyyy-mm-dd hh:MM')}}
                        </view>
                    </view>
                    <view class="u-text-right">
                        <view class="u-font-bold" :class="item.change_amount >= 0 ? 'u-type-success' : 'u-type-error'">
                            {{item.change_amount >= 0 ? '+' : ''}}{{item.change_amount}}
                        </view>
                        <view class="u-font-24 u-m-t-10" :class="item.change_amount >= 0 ? 'u-type-success' : 'u-type-error'">
                            {{item.change_amount >= 0 ? '增加' : '减少'}}
                        </view>
                    </view>
                </view>
                <view class="u-p-tb-60 u-text-center u-type-info u-font-28" v-if="loaded && !dataList.length">
                    暂无积分明细
                </view>
                <view class="u-p-tb-30 u-text-center u-type-info u-font-26"
                    v-if="dataList.length && dataList.length >= total">
                    没有更多了
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                dataList: [],
                page: 1,
                total: 0,
                loading: false,
                loaded: false,
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
            // 明细原因
            reasonText(item) {
                let name = item.type_name || ''
                let remark = item.remark || ''
                if (name && remark && remark != name) return name + ' · ' + remark
                return name || remark || '积分变动'
            },
            // 获取记录
            get_log_list() {
                if (this.loading) return
                this.loading = true
                this.$u.api.get_log_list({
                    account_type: 2,
                    page: this.page,
                    page_size: 20
                }).then(res => {
                    this.dataList.push(...(res.data.data || []));
                    this.total = res.data.total || 0
                    this.loaded = true
                    this.loading = false
                }).catch(() => {
                    this.loaded = true
                    this.loading = false
                    if (this.page > 1) this.page--
                })
            },
        },
        onReachBottom() {
            if (this.loading) return
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
