<template>
    <view>
        <view class="u-p-30">
            <view class="u-type-white-bg u-p-30 u-radius-10 u-m-b-30" v-for="(item,index) in reservation_list"
                :key="index" @click="$u.route('/pages/my_reservation/details?id='+item.id)">
                <view class="u-font-32 u-font-bold">
                    {{item.name}}
                </view>
                <view class="u-flex u-row-between u-type-info u-m-t-10">
                    <view class="u-font-26">
                        提交时间：{{item.create_time|date('yy-mm-dd hh:MM')}}
                    </view>
                    <view class="">
                        <u-button type="success" size="mini" hover-class="none"
                            @click="$u.route('/pages/my_reservation/details?id='+item.id)">查看详情
                        </u-button>
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
                reservation_list: [],
                page: 1,
                total: 0
            };
        },
        onLoad() {
            this.get_reservation_list()
        },
        methods: {
            get_reservation_list() {
                this.$u.api.get_reservation_list({
                    keyword: this.keyword,
                    page: this.page,
                }).then(res => {
                    this.reservation_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.reservation_list.length >= this.total) return
            this.page++
            this.get_reservation_list()
        }
    }
</script>

<style lang="scss">

</style>