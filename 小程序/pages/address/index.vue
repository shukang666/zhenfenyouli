<template>
    <view>
        <view class="white-box" v-for="(item,index) in List" :key="index">
            <u-radio-group v-model="isDefault" size="26" @change="radioGroupChange($event,item,index)">
                <view class="u-flex u-row-between u-col-center u-width-100" @click="choiceAddress(item)">
                    <view class="u-flex u-m-r-30">
                        <view class="icon">
                            <u-icon name="map-fill" color="#FFF" size="28"></u-icon>
                        </view>
                        <view class="u-m-l-20 u-font-24 u-tips-color">
                            <view>
                                <text class="u-font-28 u-font-bold u-content-color">{{item.username}}</text>
                                <text class="u-m-l-20">{{item.phone}}</text>
                            </view>
                            <view class="u-m-t-5">{{item.province+item.city+item.county}}{{item.address}}</view>
                        </view>
                    </view>
                    <u-icon name="edit-pen" color="#999" size="35" @tap.stop="operation('edit',item)"></u-icon>
                </view>
                <view class="operation-box u-flex u-row-between u-width-100">
                    <u-radio active-color="#18AF38" label-size="24" :name="item.id">默认地址</u-radio>
                    <view class="u-tips-color" @click="deleteBtn(item,index)">删除</view>
                </view>
            </u-radio-group>
        </view>

        <view class="submit-btn safe-area-inset-bottom">
            <view class="btn" @click="operation('add','')">添加收货地址</view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                source: '',
                List: [],
                isDefault: '',
                type: 1
            }
        },
        onLoad(option) {
            this.source = option.source;
            this.type = option.type
        },
        onShow() {
            this.get_address_info(); //获取用户收货地址
        },
        methods: {
            //获取用户收货地址
            async get_address_info() {
                const res = await this.$u.api.get_address_info({
                    select_type: this.type
                })
                this.List = res.data;
                res.data.forEach(item => {
                    if (item.is_default == 1) {
                        this.isDefault = item.id;
                    }
                })
            },
            //默认地址
            radioGroupChange(value, item, index) {
                this.isDefault = value;
                this.postAddressData('default', item, index);
            },
            //添加/编辑收货地址
            operation(type, item) {
                this.$u.route('/pages/address/edit', {
                    type: type,
                    id: item.id,
                    type_1: this.type
                });
            },
            //删除收货地址
            deleteBtn(item, index) {
                let that = this;
                uni.showModal({
                    title: '确认要删除收货地址？',
                    success: function(res) {
                        if (res.confirm) {
                            that.postAddressData('delete', item, index);
                        }
                    }
                });
            },
            //设置默认/删除收货地址
            postAddressData(type, item, index) {
                let data = {};
                if (type == 'default') {
                    data.is_default = 1;
                }
                this.$u.api.post_address_data({
                    type: type,
                    id: item.id,
                    ...data
                }).then(res => {
                    this.$u.toast(res.msg, 'success');
                    if (type == 'default') {
                        this.List[index].is_default = 1;
                        this.$forceUpdate();
                    }
                    if (type == 'delete') {
                        this.List.splice(index, 1);
                    }
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            //选择收货地址
            choiceAddress(item) {
                if (this.source == 1) {
                    this.$u.func.prePage().address = item;
                    // this.$u.func.prePage().show = true
                    uni.navigateBack()
                }
            }
        }
    }
</script>

<style lang="scss">
    page {
        background: #F7F7F7;
    }

    .white-box {
        background: #FFFFFF;
        border-radius: 10px;
        margin: 30rpx;
        padding: 20rpx 30rpx;
    }

    .icon {
        width: 42rpx;
        height: 42rpx;
        line-height: 42rpx;
        background: #18AF38;
        border-radius: 50%;
        text-align: center;
        flex-shrink: 0;
    }

    .operation-box {
        border-top: 1px #F5F5F5 solid;
        margin-top: 20rpx;
        padding-top: 20rpx;
        font-size: 24rpx;
    }

    .submit-btn {
        background: #F7F7F7;

        .btn {
            background: linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%);
            border-radius: 20px;
            font-size: 30rpx;
            color: #fff;
            text-align: center;
            padding: 15rpx 0;
            margin: 30rpx;
        }
    }
</style>
