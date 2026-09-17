<template>
    <view>
        <u-parse :html="content"></u-parse>
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30">
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="navigator('/pages/zzhd/add')" v-if="!Object.keys(check).length">申请文明标兵</u-button>
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                v-if="check.status==1">申请已提交，请等待审核</u-button>
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                v-if="check.status==2">申请已通过</u-button>
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="navigator('/pages/zzhd/add')" v-if="check.status==3">申请未通过,请重新提交</u-button>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                content: '',
                check: {}
            };
        },
        onLoad() {
            this.findSetting()
        },
        onShow() {
            this.get_check()
        },
        methods: {
            //
            navigator(url) {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                if (!this.userInfo.community_id) return this.$u.route('/pages/member/info')
                this.$u.route(url)
            },
            //获取数据
            findSetting(name) {
                this.$u.api.findSetting({
                    name: 'civilized'
                }).then(res => {
                    this.content = this.$u.func.formatRichText(res.data.content);
                })
            },
            get_check() {
                this.$u.api.get_check().then(res => {
                    this.check = res.data
                })
            }
        }
    }
</script>

<style lang="scss">

</style>