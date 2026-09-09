<template>
    <view class="u-p-30">
        <view class="u-flex u-type-white-bg u-radius-10 u-m-b-30 u-p-30" v-for="(item,index) in news_list" :key="index"
            @click="$u.route('/pages/article/details?id='+item.id)">
            <u-image width="230" height="150" :src="item.thumb" border-radius="6"></u-image>
            <view class="u-h-150 u-m-l-20 u-flex u-row-between u-col-top u-flex-col">
                <view class="u-font-26 u-line-2">
                    {{item.title}}
                </view>
                <view class="u-font-24 u-type-info">
                    发布时间：{{item.create_time|date('yy-mm-dd')}}
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                news_list: [],
                page: 1,
                total: 0,
                type: 1
            };
        },
        onLoad(option) {
            this.type = option.type
            uni.setNavigationBarTitle({
                title: this.type == 1 ? '宣传手册' : '入户宣传'
            })
            this.get_news()
        },
        methods: {
            // 列表
            get_news() {
                this.$u.api.get_news({
                    page: this.page,
                    cate_id: this.type
                }).then(res => {
                    this.news_list.push(...res.data.data)
                    this.total = res.data.total
                })
            }
        },
        onReachBottom() {
            if (this.news_list.length >= this.total) return
            this.page++
            this.get_news()
        }
    }
</script>

<style lang="scss">

</style>