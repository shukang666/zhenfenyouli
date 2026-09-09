<template>
    <view class="u-p-30">
        <view class="u-m-b-30 u-type-white-bg u-radius-10" v-for="(item,index) in cate_list" :key="index">
            <u-image width="100%" height="326" :src="item.thumb"></u-image>
            <view class="u-p-20 u-flex u-row-between">
                <view class="">
                    <view class="u-font-bold u-font-32">
                        {{item.name}}
                    </view>
                    <view class="u-flex u-m-t-10">
                        <view class="">
                            <u-icon name="clock" labelSize="24" size="24" :label="item.date+' '+item.day_type"></u-icon>
                        </view>
                        <view class="u-m-l-20">
                            <u-icon name="map" labelSize="24" size="24" :label="item.area_range"></u-icon>
                        </view>
                    </view>
                </view>
                <view class="">
                    <u-button type="primary" size="mini" hover-class="none"
                        :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                        @click="$u.route('/pages/low_carbon/lsxf/details?id='+item.id)">立即参与</u-button>
                </view>
            </view>
        </view>
        <u-empty margin-top="400" mode="list" v-if="!cate_list.length"></u-empty>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                cate_list: [],
                page: 1,
                total: 0,
            };
        },
        onLoad() {
            this.get_active_list()
        },
        methods: {
            // 环保活动
            get_active_list() {
                this.$u.api.get_active_list({
                    page: this.page,
                    is_my:1
                }).then(res => {
                    this.cate_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.cate_list.length >= this.total) return
            this.page++
            this.get_active_list()
        }
    }
</script>

<style lang="scss">

</style>