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
        <!-- 学完全部指导且尚未通过考试时，提供考试入口（考过后自动隐藏） -->
        <view class="u-m-t-30" v-if="learnAll && !is_answer">
            <view class="u-font-24 u-m-b-20" style="color: #18AF38;">已学完全部垃圾分类指导，参加答题考试通过后即可真分</view>
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="$u.route('/pages/answer/examine?id=1')">去答题考试</u-button>
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
                waste: {},
                // 是否已学完全部垃圾分类指导
                learnAll: false,
                // 是否已通过垃圾分类答题考试
                is_answer: false
            };
        },
        onShow() {
            this.waste_list = []
            this.page = 1
            this.learnAll = false
            this.is_answer = false
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
                    // 学完全部垃圾分类指导后，刷新考试状态，用于展示"去考试"入口
                    const total = Number(res.data.waste_count) || 0
                    const learned = Number(res.data.user_count) || 0
                    if (!this.learnAll && total > 0 && learned >= total) {
                        this.learnAll = true
                        this.getUserAnswerState()
                    }
                })
            },
            // 获取最新考试通过状态（考过且及格后后端 is_answer 为 1）
            getUserAnswerState() {
                this.$u.api.getUserInfo().then(res => {
                    const info = res.data || {}
                    this.is_answer = Number(info.is_answer) === 1
                    // 顺带刷新全局用户状态，保证首页快捷真分等入口判断最新状态
                    this.$u.vuex('userInfo', info)
                }).catch(e => {
                    console.log('获取考试状态失败:', e)
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