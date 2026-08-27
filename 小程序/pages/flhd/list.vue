<template>
    <view class="u-p-30">
        <view class="">
            <view class="u-font-24" style="color: #18AF38;">
                学习进度：{{waste.user_count}}/{{waste.waste_count}}
            </view>
            <view class="u-m-t-30">
                <u-line-progress active-color="#FF7E15" :percent="(waste.user_count/waste.waste_count*100)"
                    :show-percent="false" height="10"></u-line-progress>
            </view>
        </view>
        <view class="u-p-20 u-type-white-bg u-radius-6 u-m-t-30 u-flex" v-for="(item,index) in waste_list" :key="index">
            <u-image width="160" height="160" :src="item.thumb"></u-image>
            <view class="u-flex-1 u-m-l-20 u-flex u-row-between u-col-top u-flex-col u-h-160">
                <view class="u-font-bold">
                    {{item.name}}
                </view>
                <view class="u-font-24 u-type-info">
                    {{item.desc}}
                </view>
                <view class="u-text-right u-width-100">
                    <u-button type="primary" size="mini" hover-class="none"
                        :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                        @click="$u.route('/pages/flhd/details?id='+item.id)" v-if="item.is_study==0">开始学习</u-button>
                    <u-button type="primary" size="mini" hover-class="none" :customStyle="{background: '#999999'}"
                        @click="$u.route('/pages/flhd/details?id='+item.id)" v-if="item.is_study==1">开始学习</u-button>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                waste_list: [],
                page: 1,
                total: 0,
                waste: {}
            };
        },
        onShow() {
            this.waste_list = []
            this.page = 1
            this.get_waste_list()
        },
        methods: {
            get_waste_list() {
                this.$u.api.get_waste_list({
                    page: this.page
                }).then(res => {
                    this.waste = res.data
                    this.waste_list.push(...res.data.list.data)
                    this.total = res.data.list.total
                })
            }
        },
        onReachBottom() {
            if (this.waste_list.length >= this.total) return
            this.page++
            this.get_waste_list()
        }
    }
</script>

<style lang="scss">

</style>