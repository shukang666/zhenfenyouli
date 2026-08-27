<template>
    <view class="u-p-30">
        <view class="u-m-b-30 u-type-white-bg u-radius-10" v-for="(item,index) in cate_list" :key="index">
            <u-image width="100%" height="326" :src="item.thumb"></u-image>
            <view class="u-p-20 u-flex u-row-between">
                <view class="">
                    <view class="u-font-bold u-font-32">
                        {{item.name}}
                    </view>
                    <view class="u-flex u-m-t-10" v-if="item.type!=2">
                        <view class="">
                            <u-icon name="clock" labelSize="24" size="24" :label="item.date+' '+item.day_type"></u-icon>
                        </view>
                        <view class="u-m-l-20">
                            <u-icon name="map" labelSize="24" size="24" :label="item.area_range"></u-icon>
                        </view>
                    </view>
                    <view class="u-m-t-10 u-font-24 u-type-info" v-else>
                        发布时间：{{item.create_time|date('yy-mm-dd')}}
                    </view>
                </view>
                <view class="">
                    <u-button type="primary" size="mini" hover-class="none"
                        :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                        @click="$u.route('/pages/sjhd/details?id='+item.id)" v-if="item.status==0">立即参与</u-button>
                    <u-button type="primary" size="mini" hover-class="none"
                        :customStyle="{background: 'linear-gradient( 145deg, #F0DF73 0%, #FFA320 100%)'}"
                        @click="$u.route('/pages/sjhd/details?id='+item.id)" v-else>已完成</u-button>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                cate_list: [],
                page: 1,
                total: 0,
                type: 1
            };
        },
        onLoad(option) {
            this.type = option.type
            uni.setNavigationBarTitle({
                title: this.type == 2 ? '课程培训' : '社区知识竞赛'
            })
            // this.get_problem_list()
        },
        onShow() {
            this.cate_list = []
            this.page=1
            this.get_problem_list()
        },
        methods: {
            // 环保活动
            get_problem_list() {
                this.$u.api.get_problem_list({
                    page: this.page,
                    type: this.type
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
            this.get_problem_list()
        }
    }
</script>

<style lang="scss">

</style>