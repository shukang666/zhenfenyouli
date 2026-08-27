<template>
    <view>
        <u-parse :html="active.content"></u-parse>
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30" v-if="active.type!=3&&active.status!=1">
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="$u.route('/pages/answer/examine?id='+active.id)">参加考核</u-button>
        </view>
        <view class="submit-btn u-p-30 u-flex" v-if="active.type==3">
            <view class="u-flex-1">
                <u-button type="primary" hover-class="none"
                    :customStyle="{background: 'linear-gradient( 141deg, #F0DF73 0%, #FFA320 100%)'}"
                    @click="$u.route('/pages/sjhd/ranking?id='+active.id)">查看排名</u-button>
            </view>
            <view class="u-flex-1 u-m-l-30" v-if="active.status!=1">
                <u-button type="primary" hover-class="none"
                    :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                    @click="navigator('/pages/answer/examine?id='+active.id)">参加考核</u-button>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                active: {}
            };
        },
        onLoad(option) {
            this.get_problem_list(option.id)
        },
        methods: {
            // 跳转页面
            navigator(url) {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                this.$u.route(url)
            },
            // 环保活动
            get_problem_list(id) {
                this.$u.api.get_problem_list({
                    id
                }).then(res => {
                    this.active = res.data
                })
            },
        }

    }
</script>

<style lang="scss">

</style>