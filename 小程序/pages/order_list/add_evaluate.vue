<template>
    <view class="u-p-30">
        <view class="u-type-white-bg u-radius-10 u-p-30">
            <view class="u-font-bold u-p-b-30 u-font-32">
                您对商家/商品满意吗?
            </view>
            <view class="u-flex u-col-top u-m-b-30" v-for="(item,index) in detail.goods_list" :key="index">
                <view class="u-m-r-20">
                    <u-image :src="item.thumb" width="160" height="160" border-radius="8"></u-image>
                </view>
                <view class="u-width-100">
                    <view class="u-font-28 u-font-bold u-line-1">{{item.goods_name}}</view>
                    <view class=" u-type-info u-m-t-20" v-if="item.sku_name">
                        {{item.sku_name}}
                    </view>
                    <view class="u-flex u-row-between u-m-tb-30">
                        <view class="">
                            <text class="u-font-24">&yen;</text>
                            <text class="u-font-32 u-font-bold">{{item.one_price}}</text>
                        </view>
                        <text>x {{item.count}}</text>
                    </view>
                </view>
            </view>
            <view class="u-m-t-30">
                <view class="u-flex u-row-between">
                    <view class="u-font-bold">
                        总体评价
                    </view>
                    <u-rate :count="5" v-model="point" active-color="#FF7117" size="40"></u-rate>
                </view>
                <!-- <view class="u-flex u-row-between u-m-t-30">
                    <view class="">
                        包装
                    </view>
                    <u-rate :count="5" v-model="point" active-color="#FF7117" size="40"></u-rate>
                </view> -->
            </view>
            <view class="u-type-info-light-bg u-radius-10 u-p-20 u-m-t-30">
                <u-input v-model="content" type="textarea" height="200" placeholder="请输入您对该商品的评价" />
                <view class="u-m-t-20">
                    <cl-upload v-model="images" :action="uploadUrl" @onSuccess="onSuccess" fileType="image"
                        :listStyle="{columns: 3,height:'170rpx'}">>
                        <template v-slot:addImg>
                            <u-image width="170" height="170" src="/static/upload.png"></u-image>
                        </template>
                    </cl-upload>
                </view>
            </view>
        </view>

        <view class="submit-btn u-p-30">
            <u-button type="primary" hoverClass="none" :custom-style="{background: '#18AF38'}" shape="circle"
                @click="add_comment">确认提交
            </u-button>
        </view>
    </view>
</template>

<script>
    import {
        uploadUrl
    } from "@/common/settings.js";
    export default {
        data() {
            return {
                uploadUrl,
                detail: {},
                id: '',
                point: 4,
                content: '',
                images: []
            }
        },
        onLoad(option) {
            this.id = option.id
            this.order_detail()
        },
        methods: {
            // 上传图片
            onSuccess(reslut) {
                this.images.push(reslut.data.url)
            },
            add_comment() {
                if (!this.content) return this.$u.toast('请填写评价')
                this.$u.api.add_order_comment({
                    order_num: this.detail.order_num,
                    point: this.point,
                    content: this.content,
                    photos: this.images
                }).then(res => {
                    this.$u.func.showToast({
                        title: res.msg,
                        back: true
                    })
                    this.$u.func.prePage().order_detail(this.detail.order_num);
                    this.$u.func.prePage(3).Reload();
                }).catch(e => {
                    this.$u.toast(e.msg)

                })
            },
            // 获取详情
            async order_detail() {
                const {
                    data
                } = await this.$u.api.order_detail({
                    order_num: this.id
                })
                this.detail = data
            }
        }
    }
</script>

<style lang="scss">
    .sort {
        min-width: 94rpx;
        height: 34rpx;
        background: #DFE8FF;
        border-radius: 17rpx;
        padding: 0 10rpx;
        font-size: 22rpx;
        color: #3182FF;
        text-align: center;
    }
</style>