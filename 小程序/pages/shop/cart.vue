<template>
    <view>
        <block v-if="List.length">
            <view class="">
                <u-tabs :list="tabs_list" :is-scroll="false" :current="current" @change="tabs_change"
                    active-color="#18AF38"></u-tabs>
            </view>
            <checkbox-group>
                <view class="u-flex-col u-type-white-bg u-radius-10 u-p-30 u-m-t-30" v-for="(oitem,oindex) in List"
                    :key="oindex">
                    <view class="u-flex u-m-b-30" @click="checkboxChange('shop', oindex)">
                        <view class="u-checkbox-radius checkbox-box">
                            <checkbox :value="oitem.id" :checked="oitem.checked" />
                        </view>
                        <text class="u-font-30 u-font-bold u-m-l-10">{{oitem.shop_name}}</text>
                    </view>
                    <view class="u-flex" :class="index ? 'u-m-t-30' : ''" v-for="(item,index) in oitem.goods_list"
                        :key="index" @click="checkboxChange('good', oindex, index)">
                        <view class="u-checkbox-radius checkbox-box u-m-r-10">
                            <checkbox :value="item.id" :checked="item.checked" />
                        </view>
                        <view class="u-width-100 u-flex">
                            <view class="u-shrink-no u-m-r-20">
                                <u-image :src="item.thumb" width="145" height="145" border-radius="10"></u-image>
                            </view>
                            <view class="u-width-100 u-h-145 u-rela">
                                <view class="u-font-28 u-font-bold u-line-1">{{item.goods_name}}</view>
                                <view class="u-font-24 u-tips-color u-m-t-10" v-if="item.sku_name">{{item.sku_name}}
                                </view>
                                <view class="u-width-100 u-flex u-row-between u-abso u-abso-l-0 u-abso-b-0"
                                    style="margin-bottom: -8rpx;">
                                    <view class="u-type-error">
                                       <!-- <text class="u-font-24">&yen;</text>
                                        <text class="u-font-35 u-font-bold">{{item.price}}</text> -->
										<text v-if="Number(item.price)">{{item.price}}元</text>
										<text v-if="Number(item.price) && item.point"> + </text>
										<text v-if="item.point">{{item.point}}积分</text>
                                    </view>
                                    <view style="transform: scale(1);">
                                        <u-number-box v-model="item.count" :min="1" :max="item.stock" :index="index"
                                            disabled-input @minus="valChange($event,oindex)"
                                            @plus="valChange($event,oindex)"></u-number-box>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </checkbox-group>
        </block>
        <view class="empty-tip" v-if="!List.length">
            <u-empty text="购物车竟然是空的" mode="list">
                <navigator slot="bottom" class="u-text-center" hover-class="none" url="/pages/product/index">
                    <view class="u-font-24 u-tips-color">再累再忙，也要记的给自己买点什么犒劳自己～</view>
                </navigator>
            </u-empty>
        </view>

        <u-gap height="180"></u-gap>
        <view class="submit-btn safe-area-inset-bottom u-flex u-row-between" v-show="List.length">
            <view class="u-flex u-col-center u-m-l-30">
                <u-checkbox v-model="checkAll" shape="circle" active-color="#18AF38" size="36"
                    @change="selectAll">{{checkAll ? '全不选' : '全选'}}</u-checkbox>
                <text class="u-font-30 u-type-error u-m-l-10" v-if="selectList.length" @click="delBtn">删除</text>
            </view>
            <view class="u-flex u-m-r-30">
                <view class="u-text-right u-m-r-30">
                    <view class="u-type-error">
                        <text class="u-font-24">&yen;</text>
                        <text class="u-font-35 u-font-bold">{{total}}</text>
                    </view>
                    <view class="u-font-24 u-content-color">共{{selectList.length}}件商品</view>
                </view>
                <u-button type="error" shape="circle" hoverClass="none"
                    :custom-style="{margin:'20rpx 0',padding:'0 50rpx',background:'#18AF38'}"
                    @click="submitBtn">结算</u-button>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                List: [],
                page: 1,
                pageCount: 0,
                selectList: [], //已选择商品
                checkAll: false,
                total: 0,
                tabs_list: [{
                    name: '可邮寄',
                    type: 1
                }, {
                    name: '可自提',
                    type: 2
                }],
                current: 0
            };
        },
        onShow() {
            this.Reload(); //重载数据
            this.checkAll = false;

            this.List.forEach(item => {
                this.$set(item, 'checked', false);
                item.children.forEach(obj => {
                    this.$set(obj, 'checked', false);
                })
            })
        },
        methods: {
            // 切换
            tabs_change(index) {
                this.current = index;
                this.Reload();
            },
            //获取购物车列表
            async my_cart() {
                const res = await this.$u.api.my_cart({
                    page: this.page,
                    page_size: 10,
                    delivery_type: this.tabs_list[this.current].type
                });
                this.List = res.data.data
                this.List.forEach(item => {
                    this.$set(item, 'checked', false);
                    item.goods_list.forEach(obj => {
                        this.$set(obj, 'checked', false);
                    })
                })
                uni.stopPullDownRefresh();
            },
            //数量修改
            valChange(e, oindex) {
                this.List[oindex].goods_list[e.index].count = e.value;
                this.calculation();
                this.$u.api.set_cart({
                    id: this.List[oindex].goods_list[e.index].id,
                    count: e.value
                }).then(res => {
                    this.calculation();
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            //选择商品
            checkboxChange(type, oindex, index) {
                if (type == 'shop') {
                    this.List[oindex].checked = !this.List[oindex].checked;
                    this.List[oindex].goods_list.forEach(item => {
                        item.checked = this.List[oindex].checked ? true : false;
                    })
                }
                if (type == 'good') {
                    this.List[oindex].goods_list[index].checked = !this.List[oindex].goods_list[index].checked;
                    let choicArr = [];
                    this.List[oindex].goods_list.forEach(item => {
                        if (item.checked) choicArr.push(item);
                    })
                    this.List[oindex].checked = choicArr.length == this.List[oindex].goods_list.length ? true : false;
                }

                //处理商品是否全选
                let goodsArr = [],
                    newArr = [];
                this.List.forEach(item => {
                    item.goods_list.forEach(obj => {
                        goodsArr.push(obj);
                        if (obj.checked) newArr.push(obj);
                    })
                })
                this.checkAll = newArr.length == goodsArr.length ? true : false;
                this.calculation();
            },
            //全选
            selectAll() {
                this.checkAll = !this.checkAll;
                this.List.map(item => {
                    if (this.checkAll) {
                        item.checked = true;
                        item.goods_list.map(obj => {
                            obj.checked = true;
                        })
                    } else {
                        item.checked = false;
                        item.goods_list.map(obj => {
                            obj.checked = false;
                        })
                    }
                })
                this.calculation();
            },
            //删除确认
            delBtn() {
                let that = this,
                    delArr = [];
                that.selectList.forEach(item => {
                    delArr.push(item.id);
                })
                uni.showModal({
                    title: '删除提示',
                    content: '确认要删除此商品？',
                    success: function(res) {
                        if (res.confirm) {
                            that.$u.api.set_cart({
                                id: delArr,
                                delete: 1
                            }).then(res => {
                                that.$u.toast(res.msg, 'success');
                                setTimeout(() => {
                                    that.Reload();
                                }, 600)
                            }).catch(err => {
                                that.$u.toast(err.msg)
                            })
                        }
                    }
                });
            },
            //计算总金额
            calculation() {
                let selectArr = [],
                    total = 0;
                this.List.forEach(item => {
                    item.goods_list.forEach(obj => {
                        if (obj.checked) {
                            selectArr.push({
                                shop_id: item.id,
                                ...obj
                            });
                            total += obj.price * obj.count;
                        }
                    })
                })
                this.selectList = selectArr;
                this.total = this.$u.func.formatMoney(total, 2);
            },
            //提交订单
            submitBtn() {
                let shopData = [],
                    goodsData = [];
                this.List.forEach(item => {
                    this.selectList.forEach(obj => {
                        if (item.shop_id == obj.shop_id) {
                            shopData.push({
                                shop_id: item.shop_id,
                                shop_name: item.shop_name,
                                address: item.address,
                                children: []
                            })
                            goodsData.push({
                                shop_id: obj.shop_id,
                                id: obj.id,
                                goods_name: obj.goods_name,
                                thumb: obj.thumb,
                                attr_name: obj.sku || '',
                                count: obj.count,
                                price: obj.price
                            })
                        }
                    })
                })
                const uniqueShopData = [...new Map(shopData.map(item => [item.shop_id, item])).values()];
                uniqueShopData.forEach(shop => {
                    shop.children = goodsData.filter(goods => goods.shop_id === shop.shop_id);
                });
                if (!uniqueShopData.length) return this.$u.toast('请选择要结算的商品');
                uni.setStorageSync('goods', uniqueShopData)
                this.$u.route('/pages/shop/add_yj', {
                    type: this.current + 1,
                });
            },


            //重载数据
            Reload() {
                this.selectList = []
                this.total = 0
                this.page = 1;
                this.List = [];
                this.my_cart();
            }
        },
        onReachBottom() {
            if (this.List.length >= this.pageCount) return
            this.page++
            this.my_cart();
        },
        onPullDownRefresh() {
            this.Reload();
        }
    }
</script>

<style lang="scss">
    .checkbox-box {

        /*checkbox 选项框大小  */
        checkbox .wx-checkbox-input {
            width: 30rpx;
            height: 30rpx;
            border-radius: 50%;
        }

        /*checkbox选中后样式  */
        checkbox .wx-checkbox-input.wx-checkbox-input-checked {
            border: 1px #18AF38 solid;
            background: #18AF38;
        }

        /*checkbox选中后图标样式  */
        checkbox .wx-checkbox-input.wx-checkbox-input-checked::before {
            text-align: center;
            font-size: 22rpx;
            color: #FFF;
            background: transparent;
            transform: translate(-50%, -50%) scale(1);
            -webkit-transform: translate(-50%, -50%) scale(1);
        }
    }

    .submit-btn {
        background: #FFFFFF;
    }
</style>