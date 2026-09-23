<template>
    <view>
        <view class="invite-progress u-m-b-30 u-p-20 u-radius-6" v-if="invite.need_days && invite.clock_days < invite.need_days">
            <text class="u-font-26">已累计垃圾打卡 {{ invite.clock_days }}/{{ invite.need_days }} 天（不必连续），满 {{ invite.need_days }} 天可申请督导员。</text>
        </view>
        <u-parse :html="content"></u-parse>
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30">
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="navigator('/pages/zzhd/add')" v-if="!Object.keys(check).length && inviteCanApply">申请督导员</u-button>
            <u-button type="primary" hover-class="none" disabled
                :customStyle="{background: '#c5ccc7'}"
                v-if="!Object.keys(check).length && !inviteCanApply && invite.need_days">打卡满 {{ invite.need_days }} 天后可申请</u-button>
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
        computed: {
            inviteCanApply() {
                return Number(this.invite.can_apply) === 1
            },
        },
        data() {
            return {
                content: '',
                check: {},
                invite: {},
            };
        },
        onLoad() {
            this.findSetting()
        },
        onShow() {
            this.get_check()
            this.loadInvite()
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
                }).catch(() => {
                    this.check = {}
                })
            },
            loadInvite() {
                if (!this.isLogin) return
                this.$u.api.getUserInfo().then(res => {
                    const info = res.data || {}
                    this.invite = info.supervisor_invite || {}
                    this.$u.vuex('userInfo', info)
                }).catch(() => {})
            },
        }
    }
</script>

<style lang="scss">
.invite-progress {
    background: #e6fffb;
    color: #006d75;
}
</style>