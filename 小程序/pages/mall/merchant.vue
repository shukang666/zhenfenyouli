<template>
    <view>
        <view class="u-p-30 u-type-white-bg u-flex u-row-between">
            <view class="u-flex">
                <u-image width="100" height="100" :src="shop.avatar"></u-image>
                <view class="u-m-l-20">
                    <view class="u-font-bold">
                        {{shop.shop_name}}
                    </view>
                    <view class="u-m-t-20">
                        <u-rate v-model="shop.point" disabled active-color="#18AF38"></u-rate>
                    </view>
                </view>
            </view>
            <view class="">
                <u-button type="primary" size="mini" hover-class="none" :customStyle="{background: '#18AF38'}"
                    @click="makePhoneCall(shop.mobile)">联系商家</u-button>
            </view>
        </view>
        <view class="u-p-30">
            <view class="">
                <u-search placeholder="搜索商品名称" v-model="keyword" border-color="#18AF38" search-icon-color="#18AF38"
                    @search="search" @custom="search"></u-search>
            </view>

            <view class="u-m-t-30 u-flex u-row-between u-flex-wrap">
                <view class="u-type-white-bg u-radius-10 u-w-334 u-m-b-30" v-for="(item,index) in goods_list"
                    :key="index" @click="$u.route('/pages/mall/details?id='+item.id)">
                    <u-image width="334" height="334" :src="item.thumb"></u-image>
                    <view class="u-p-10">
                        <view class="u-font-bold">
                            {{item.goods_name}}
                        </view>
                        <view class="u-m-t-10" style="color: #18AF38;">
                            {{item.price}}元
                        </view>
                    </view>
                </view>
            </view>
        </view>

    </view>
</template>

<script>
    export default {
        data() {
            return {
                shop_id: 0,
                keyword: '',
                goods_list: [],
                page: 1,
                total: 0,
                shop: {}
            };
        },
        onLoad(option) {
            this.shop_id = option.id
            this.get_shop_goods_list()
            this.get_shop()
        },
        methods: {
            // 拨打电话
            makePhoneCall(mobile) {
                uni.makePhoneCall({
                    phoneNumber: mobile
                });
            },
            // 详情
            get_shop(id) {
                this.$u.api.get_shop({
                    id: this.shop_id
                }).then(res => {
                    this.shop = res.data
                })
            },
            // 搜索
            search() {
                this.goods_list = []
                this.page = 1
                this.get_shop_goods_list()
            },
            // 商品列表
            get_shop_goods_list() {
                this.$u.api.get_shop_goods_list({
                    keyword: this.keyword,
                    page: this.page,
                    shop_id: this.shop_id
                }).then(res => {
                    this.goods_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        }
    }
</script>

<style lang="scss">

</style>