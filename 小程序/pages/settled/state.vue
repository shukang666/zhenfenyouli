<template>
    <view class="u-p-30">
        <view class="" v-if="shop.status==1">
            <u-gap height="120"></u-gap>
            <view class="u-flex u-row-center">
                <u-image width="432" height="440" src="/static/shz.png"></u-image>
            </view>
            <view class="u-font-bold u-m-t-30 u-text-center">
                申请已提交审核，请您耐心等待
            </view>
        </view>
        <view class="" v-else>
            <u-gap height="120"></u-gap>
            <view class="u-flex u-row-center">
                <u-image width="432" height="440" src="/static/shz.png"></u-image>
            </view>
            <view class="u-font-bold u-m-t-30 u-text-center">
                申请未通过
            </view>
            <view class="u-font-bold u-m-t-50 u-text-center u-type-error">
                {{shop.shop_msg}}
            </view>

            <u-gap height="120"></u-gap>
            <view class="u-p-60">
                <u-button type="success" hover-class="none"
                    :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}" shape="circle"
                    @click="$u.route('/pages/settled/settled')">重新申请</u-button>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                shop: {}
            };
        },
        onLoad() {
            this.get_shop()
        },
        methods: {
            // 详情
            get_shop() {
                this.$u.api.get_shop().then(res => {
                    this.shop = res.data
                }).catch(err => {})
            },
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }
</style>