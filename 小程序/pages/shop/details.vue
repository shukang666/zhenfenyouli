<template>
    <view>
        <u-swiper :list="goods.photos" height="750" border-radius="0"></u-swiper>
        <view class="u-p-30 u-type-white-bg">
            <view class="u-flex u-row-between">
                <view class="u-font-bold u-type-error u-font-40">
                    <text v-if="Number(goods.price)">{{goods.price}}元</text>
                    <text v-if="Number(goods.price) && goods.point"> + </text>
                    <text v-if="goods.point">{{goods.point}}积分</text>
                </view>
                <view class="u-font-24 u-type-info">
                    {{goods.sell_count}}人兑换
                </view>
            </view>
            <view class="u-font-bold u-font-32 u-m-t-30">
                {{goods.goods_name}}
            </view>
            <view class="u-m-t-20 u-font-24" style="color: #8B6914;" v-if="goods.second_name">
                地理产区：{{goods.second_name}}
            </view>
            <view class="sign-require-bar u-m-t-20" v-if="goods.require_active_id > 0">
                <view class="u-font-24 sign-require-text">
                    <text v-if="signRequireMet">已满足签到条件：{{ signRequireActiveName }}</text>
                    <text v-else>{{ signRequireHint }}</text>
                </view>
                <view class="u-m-t-15" v-if="!signRequireMet">
                    <u-button type="primary" size="mini" hover-class="none" :customStyle="{background: '#18AF38'}"
                        @click="goSignActive">去签到</u-button>
                </view>
            </view>
        </view>
        <view class="u-p-30 u-type-white-bg u-m-t-30 u-flex u-row-between">
            <view class="u-flex">
                <u-image width="100" height="100" :src="goods.shop_info.avatar"></u-image>
                <view class="u-m-l-20">
                    <view class="u-font-bold">
                        {{goods.shop_info.shop_name}}
                    </view>
                    <view class="u-m-t-20">
                        <u-rate v-model="goods.shop_info.point" disabled active-color="#18AF38"></u-rate>
                    </view>
                </view>
            </view>
            <view class="">
                <u-button type="primary" size="mini" hover-class="none" :customStyle="{background: '#18AF38'}"
                    @click="$u.route('/pages/shop/merchant?id='+goods.shop_info.id+'&goods_id='+goods.id)">进店逛逛</u-button>
            </view>
        </view>
        <view class="u-p-30 u-type-white-bg u-m-t-30" v-if="comment_list.length">
            <view class="u-m-b-30">
                <u-section :title="'用户评价（'+comment_list.length+'）'" :show-line="false" :right="false"></u-section>
            </view>
            <scroll-view scroll-x="true">
                <view class="u-flex">
                    <view class="u-m-r-20 u-type-info-light-bg u-p-15 u-w-450 u-shrink-no u-radius-6"
                        v-for="(item,index) in comment_list" :key="index" @click="cate_btn(item.type)">
                        <view class="u-flex u-row-between">
                            <view class="u-flex">
                                <u-avatar :src="item.avatar" size="40"></u-avatar>
                                <view class="u-font-22 u-m-l-10">
                                    {{item.nickname}}
                                </view>
                            </view>
                            <view class="u-font-22 u-type-info">
                                {{item.create_time | date('yy-mm-dd hh:MM')}}
                            </view>
                        </view>
                        <view class="u-font-24 u-type-info u-m-t-10 u-line-2">
                            {{item.content}}
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="u-p-30 u-type-white-bg u-m-t-30">
            <view class="u-flex u-row-center">
                <u-image width="447" height="28"
                    src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250521/b5fb7df0b75ce7d4f0f8a7ba800f9d66.png"></u-image>
            </view>
            <view class="u-m-t-30">
                <u-parse :html="goods.content"></u-parse>
            </view>
            <view class="">
                <u-image width="100%" mode="widthFix" :src="item" v-for="(item,index) in goods.image"
                    :key="index"></u-image>
            </view>
        </view>
        <u-gap height="130"></u-gap>
        <view class="submit-btn safe-area-inset-bottom">
            <view class="u-flex u-shrink-no u-m-lr-30">
                <view class="">
                    <button class="u-reset-button" open-type="share">
                        <u-icon name="zhuanfa" size="42" color="#808080" label="转发" label-pos="bottom"
                            label-size="22"></u-icon>
                    </button>
                </view>
                <view class="u-m-l-30">
                    <u-icon :name="goods.is_collect ? 'thumb-up-fill' : 'thumb-up'" size="42"
                        :color="goods.is_collect ? '#18AF38' : '#808080'" label="点赞" label-pos="bottom" label-size="22"
                        @click="col_goods(goods)"></u-icon>
                </view>
            </view>
            <view class="u-width-100 u-flex u-m-tb-20">
                <view class="btn-item">
                    <u-button type="warning" hoverClass="none" :custom-style="{background:'#FF7E15',borderRadius:0}"
                        @click="handleBuy('cart')">加入购物车</u-button>
                </view>
                <view class="btn-item">
                    <u-button type="error" hoverClass="none" :custom-style="{background:'#18AF38',borderRadius:0}"
                        @click="handleBuy('buy')">立即购买</u-button>
                </view>
            </view>
        </view>
        <!-- 选择规格 -->
        <sku-select ref="sku" @change="submitOrder"></sku-select>
    </view>
</template>

<script>
    import skuSelect from './components/sku-select.vue'
    export default {
        components: {
            skuSelect
        },
        data() {
            return {
                goodsId: '',
                goods: {},
                comment_list: []
            };
        },
        computed: {
            signRequireActiveName() {
                const name = this.goods && this.goods.require_active_name
                return name && String(name).trim() ? String(name).trim() : '指定活动'
            },
            signRequireMet() {
                return Number(this.goods && this.goods.require_sign_met) === 1
            },
            signRequireHint() {
                const tip = this.goods && this.goods.require_sign_tip
                if (tip && String(tip).trim() && String(tip).indexOf('undefined') < 0) {
                    return String(tip).trim()
                }
                return '需先完成「' + this.signRequireActiveName + '」签到后再兑换'
            },
        },
        onLoad(option) {
            this.goodsId = option.id
            this.get_goods_detail(option.id)
        },
        onShow() {
            if (!this.goodsId || !this.isLogin) return
            this.$u.api.get_goods_detail({ id: this.goodsId }).then(res => {
                this.goods = res.data
            })
        },
        methods: {
            //加入购物车/购买(addcart加入购物车 buy立即购买)
            handleBuy(type) {
                if (!this.isLogin) return this.$u.func.checkLogin();
                if (this.goods.require_active_id > 0 && !this.signRequireMet) {
                    this.$u.toast(this.signRequireHint);
                    return;
                }
                this.$refs.sku.init(type, this.goods);
            },
            goSignActive() {
                if (!this.goods.require_active_id) return;
                this.$u.route('/pages/activity/lsxf/details-new', {
                    id: this.goods.require_active_id
                });
            },
            //确定购买
            submitOrder(data) {
                console.log(data)
                if (data.type == 'addcart') {
                    // 加入购物车
                    this.$u.api.add_cart({
                        goods_id: data.goods_id,
                        count: data.count,
                        // sku_id: data.sku_name || ''
                    }).then(res => {
                        this.$u.toast(res.msg)
                    }).catch(err => {
                        this.$u.toast(err.msg)
                    })
                } else {
                    uni.setStorageSync('goods', [data])
                    this.$u.route('/pages/shop/add_order', {
                        type: 'buy'
                    })
                }
            },
            // 收藏
            col_goods(item) {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                this.$u.api.col_goods({
                    id: item.id,
                }).then(res => {
                    if (!this.goods.is_collect) {
                        this.goods.is_collect = 1
                    } else {
                        this.goods.is_collect = 0
                    }
                    this.$u.func.showToast({
                        title: res.msg,
                        icon: 'success',
                    })
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 详情
            get_goods_detail(id) {
                this.$u.api.get_goods_detail({
                    id
                }).then(res => {
                    this.goods = res.data
                    this.get_goods_comment()
                })
            },
            get_goods_comment() {
                this.$u.api.get_goods_comment({
                    keyword: this.keyword,
                    page: this.page,
					id: this.goods.id
                }).then(res => {
                    this.comment_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onShareAppMessage(res) {
            return {
                title: this.goods.goods_name,
                imageUrl: this.goods.photos[0],
                path: '/pages/shop/details?id=' + this.goods.id
            }
        },
        onShareTimeline() {
            return {
                title: this.goods.goods_name,
                imageUrl: this.goods.photos[0],
                path: '/pages/shop/details?id=' + this.goods.id
            }
        }
    }
</script>

<style lang="scss">
    .sign-require-bar {
        padding: 20rpx 24rpx;
        background: #fff8e6;
        border-radius: 12rpx;
        border: 1rpx solid #ffe0a3;
    }

    .sign-require-text {
        color: #8B6914;
        line-height: 1.5;
    }

    .submit-btn {
        background: #FFFFFF;
        display: flex;

        .u-reset-button {
            border-radius: 0 !important;
        }

        .btn-item {
            width: 100%;
            border-radius: 25px 0 0 25px;
            overflow: hidden;

            &:last-child {
                border-radius: 0 25px 25px 0;
                margin-right: 30rpx;
            }
        }
    }
</style>