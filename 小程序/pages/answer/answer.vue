<template>
    <view>
        <u-swiper :list="swiper_list" height="400" mode="none" border-radius="0"
            @click="adLink($event,swiper_list)"></u-swiper>
        <view class="u-p-30">
            <view class="u-font-bold u-font-34">
                请问您属于以下哪类人群？
            </view>
            <view class="u-m-t-20 u-font-26 u-type-info">
                同时属于以下2个或2个以上人群的，只选一类
            </view>
        </view>

        <view class="u-p-60">
            <view class="u-m-t-30 " v-for="(item,index) in exam_list" :key="index">
                <view :class="'confirm-btn style'+index"
                    @click="$u.route('/pages/answer/list?id='+item.id+'&health_id='+health_id)">{{item.name}}</view>
            </view>
        </view>

    </view>
</template>

<script>
    export default {
        data() {
            return {
                swiper_list: [],
                exam_list: [],
                health_id: 0
            };
        },
        onLoad(option) {
            this.health_id = option.id
            this.find_slide()
            this.find_exam_list()
        },
        methods: {
            find_exam_list() {
                this.$u.api.find_exam_list().then(res => {
                    this.exam_list = res.data
                })
            },
            // 轮播图跳转
            async adLink(e, item) {
                let data = item[e];
                if (!data.url) return
                if (data.target == 'switchTab') return uni.switchTab({
                    url: data.url
                });
                const openUrl = await this.$u.func.checkH5(data.url);
                this.$u.route(openUrl);
            },
            // 轮播图
            find_slide() {
                this.$u.api.find_slide({
                    id: 1
                }).then(res => {
                    this.swiper_list = res.data
                })
            },
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }

    .confirm-btn {
        height: 84rpx;
        line-height: 84rpx;
        border-radius: 25px;
        background: #fff;
        text-align: center;

        &.style0 {
            color: #18AF38;
            border: 1rpx solid #18AF38;
        }

        &.style1 {
            color: #c47b75;
            border: 1rpx solid #c47b75;
        }

        &.style2 {
            color: #5375b1;
            border: 1rpx solid #5375b1;
        }

        &.style3 {
            color: #dca65f;
            border: 1rpx solid #dca65f;
        }

        &.style4 {
            color: #855c34;
            border: 1rpx solid #855c34;
        }

        &.style5 {
            color: #384661;
            border: 1rpx solid #384661;
        }
    }
</style>