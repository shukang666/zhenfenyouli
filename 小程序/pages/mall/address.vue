<template>
    <view class="u-p-30">
        <view class="u-p-30 u-m-b-30 u-type-white-bg u-flex u-row-between u-radius-10" v-for="(item,index) in list" :key="index"
            @click="navigator(item)">
            <view class="u-flex u-width-100">
                <u-image width="100" height="100" :src="item.thumb"></u-image>
                <view class="u-m-l-20 u-width-100">
                    <view class="u-font-bold">
                        {{item.name}}
                    </view>
                    <view class="u-m-t-20 u-font-22 u-line-1">
                        {{item.address}}
                    </view>
                </view>
            </view>
            <view class="">
                <u-icon name="arrow-right"></u-icon>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                list: [],
                page: 1,
                total: 0,
                type: 0
            }
        },
        onLoad(option) {
            this.type = option.type
            this.goods_position()
        },
        methods: {
            // 
            navigator(item) {
                if (this.type == 1) {
                    this.$u.func.prePage().position = item
                } else {
                    this.$u.func.prePage().position_name = item.address
                    this.$u.func.prePage().position_id = item.id
                }
                uni.navigateBack()
            },
            // 地提点
            goods_position() {
                this.$u.api.goods_position({
                    keyword: this.keyword,
                    page: this.page,
                }).then(res => {
                    this.list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.list.length >= this.total) return
            this.page++
            this.goods_position()
        }
    }
</script>

<style>

</style>