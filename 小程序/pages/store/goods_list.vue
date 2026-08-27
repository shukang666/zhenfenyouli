<template>
    <view class="u-p-30">
        <view class="u-type-white-bg u-radius-10 u-p-30 u-m-b-30" v-for="(item,index) in goods_list" :key="index">
            <view class="u-flex u-col-top">
                <u-image width="140" height="140" :src="item.thumb"></u-image>
                <view class="u-m-l-30 u-h-140 u-flex u-row-between u-col-top u-flex-col u-width-100">
                    <view class="u-font-bold u-font-30">
                        {{item.goods_name}}
                    </view>
                    <view class="u-type-info u-font-24">
                        库存：{{item.stock}}
                    </view>
                    <view class="u-flex u-row-between u-width-100">
                        <view class="u-font-bold" style="color: #18AF38;">
                            {{item.price}}
                        </view>
                        <view class="u-flex u-row-right">
                            <view class="">
                                <u-button type="warning" size="mini" shape="circle" hover-class="none"
                                    :custom-style="{background: '#18AF38'}"
                                    @click="$u.route('/pages/store/add_goods?id='+item.id)">编
                                    辑</u-button>
                            </view>
                            <view class="u-m-l-20">
                                <u-button type="error" size="mini" shape="circle" @click="add_goods(item,2)"
                                    v-if="item.status==1">下 架</u-button>
                                <u-button type="warning" size="mini" shape="circle" @click="add_goods(item,1)"
                                    v-else>上 架</u-button>
                            </view>
                            <!-- <view class="u-m-l-20">
                                <u-button type="error" size="mini" shape="circle"
                                    @click="handleCancel(item,'delete',index)">删
                                    除</u-button>
                            </view> -->
                        </view>
                    </view>

                </view>
            </view>

        </view>
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30">
            <u-button type="warning" shape="circle" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="$u.route('/pages/store/add_goods')">添加商品</u-button>
        </view>
        <!--  -->
        <u-empty margin-top="350" mode="list" v-if="!goods_list.length"></u-empty>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                goods_list: [],
                page: 1,
                total: 0,
                shop_id: 0,
            };
        },
        onLoad(option) {
            this.get_shop_goods_list()
        },
        methods: {
            //取消订单
            handleCancel(item, index) {
                let that = this;
                uni.showModal({
                    title: '提示',
                    content: type == 'edit' ? '是否确认下架？' : '确认删除？',
                    success(str) {
                        if (str.confirm) {
                            that.add_goods(item, index)
                        };
                    }
                })

            },
            // 上下架
            add_goods(item, index) { 
                this.$u.api.add_goods({
                    id: item.id,
                    status: index,
                }).then(res => {
                    this.$u.func.showToast({
                        title: res.msg,
                    })
                    item.status = index
                }).catch(err => {
                    this.$u.func.showToast({
                        title: err.msg,
                    })
                })
            },
            get_shop_goods_list() {
                this.$u.api.get_shop_goods_list({
                    // id: this.shop_id,
                    page: this.page
                }).then(res => {
                    this.goods_list.push(...res.data.data)
                    this.total = res.data.total
                })
            }
        },
        onReachBottom() {
            if (this.goods_list.length >= this.total) return
            this.page++
            this.get_shop_goods_list()
        }
    }
</script>

<style lang="scss">

</style>