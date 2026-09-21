<template>
    <view class="" v-if="!setting.app_test">
        <view class="u-p-30">
            <u-form :model="form" ref="uForm" :error-type="['toast']">
                <view class="u-m-t-30 u-type-white-bg u-p-lr-30 u-radius-10">
                    <view class="u-m-t-30">
                        <view class="u-flex u-row-between">
                            <view class="u-font-32 u-font-bold">
                                上报类型
                            </view>
                            <view class="u-type-success u-font-24" @click="previewCate">
                                查看示例图
                            </view>
                        </view>
                        <view class="u-font-24 u-type-info u-m-t-10" v-if="sceneName">
                            本次场景：{{ sceneName }}
                        </view>
                        <view class="u-flex u-flex-wrap">
                            <view class="u-m-r-20 u-p-t-20" v-for="(item,index) in cate_list" :key="item.id"
                                @click="pickCate(index)">
                                <u-tag :text="item.name" mode="dark" :type="form.cate_id == item.id?'success':'info'" />
                            </view>
                        </view>
                    </view>
                    <u-form-item labelWidth="160" labelPosition="bottom" :border-bottom="false">
                        <view class="u-type-info-light-bg u-radius-6 u-p-20">
                            <u-input v-model="form.content" type="textarea" height="200" maxlength="-1"
                                placeholder="请输入垃圾分类描述" />
                        </view>
                    </u-form-item>
                    <view class="u-p-tb-30">
                        <cl-upload v-model="form.image" :action="uploadUrl" @onSuccess="onSuccess" fileType="image">
                            <template v-slot:addImg>
                                <u-image width="138" height="138" src="https://admin.circular-economy.cn/static/upload.png"></u-image>
                            </template>
                        </cl-upload>
                    </view>
                </view>
            </u-form>
        </view>
        <!--  -->
        <view class="u-p-60">
            <u-button type="success" hover-class="none" @click="submit()">确认提交</u-button>
        </view>
        <!-- <view class="u-text-center u-type-success" @click="$u.route('/pages/flhd/record')">
            拍照记录
        </view> -->
        <!-- 弹窗 -->
        <u-mask :show="mask_show">
            <view class="warp">
                <view class="">
                    <view class="u-flex u-row-center">
                        <u-image width="540" height="87"
                            src="https://oss.ausite.cn/xcxkf052/admin/20250514/9eb22ce3d967570fe1884290e5e10fa8.png?watermark"></u-image>
                    </view>
                    <view class="u-w-540 u-radius-10 u-type-white-bg u-text-center">
                        <u-gap height="80"></u-gap>
                        <view class="u-flex u-row-center">
                            <u-image width="200" height="200"
                                src="https://oss.ausite.cn/xcxkf052/admin/20250514/d4c1251c3c10fc8f0c4d912bf81cb167.png?watermark"></u-image>
                        </view>
                        <view class="u-font-32 u-font-bold">
                            {{msg}}
                        </view>
                        <view class="u-type-error u-font-24 u-m-t-30">
                            请不要忘记正确投递哦~
                        </view>
                        <view class="u-m-t-30 u-flex u-p-lr-30">
                            <view class="u-flex-1">
                                <u-button type="success" hover-class="none" :customStyle="{background: '#3974f2'}"
                                    shape="circle" @click="switchTab">兑换礼物</u-button>
                            </view>
                            <view class="u-flex-1 u-m-l-30">
                                <u-button type="warning" shape="circle" @click="navigateBack">后退</u-button>
                            </view>
                        </view>
                        <view class="u-p-lr-30 u-m-t-30">
                            <u-button type="success" hover-class="none" shape="circle"
                                @click="closeMask">继续打卡</u-button>
                        </view>
                        <u-gap height="30"></u-gap>
                    </view>
                </view>
            </view>
        </u-mask>
    </view>
</template>

<script>
    import {
        uploadUrl
    } from '@/common/settings'
    export default {
        data() {
            return {
                uploadUrl,
                form: {
                    type: 2,
                    cate_id: 0,
                    content: '',
                    image: []
                },
                rules: {
                    content: [{
                        required: true,
                        message: '请输入垃圾分类描述',
                        trigger: ['change', 'blur'],
                    }],
                },
                cate_list: [],
                cate_index: 0,
                pendingCateId: 0,
                sceneName: '',
                mask_show: false,
                msg: ''
            };
        },
        onLoad(option) {
            this.pendingCateId = option.cate_id ? parseInt(option.cate_id, 10) : 0
            this.sceneName = option.scene ? decodeURIComponent(option.scene) : ''
            this.get_select_list()
        },
        methods: {
            switchTab() {
                uni.switchTab({
                    url: '/pages/mall/mall'
                })
            },
            // 
            closeMask() {
                this.mask_show = false
            },
            navigateBack() {
                this.mask_show = false
                uni.navigateBack()
            },
            // 预览图片
            previewImage(url) {
                uni.previewImage({
                    urls: url,
                });
            },
            // 分类
            get_select_list() {
                this.$u.api.get_select_list({
                    type: 2
                }).then(res => {
                    this.cate_list = res.data || []
                    this.applyPendingCate()
                })
            },
            applyPendingCate() {
                const list = this.cate_list
                if (!list.length) return
                let idx = 0
                const pending = this.pendingCateId
                if (pending) {
                    for (let i = 0; i < list.length; i++) {
                        if (Number(list[i].id) === Number(pending)) {
                            idx = i
                            break
                        }
                    }
                }
                this.pickCate(idx)
            },
            pickCate(index) {
                const item = this.cate_list[index]
                if (!item) return
                this.cate_index = index
                this.form.cate_id = item.id
            },
            previewCate() {
                const item = this.cate_list[this.cate_index]
                if (!item || !item.waste_thumb) return this.$u.toast('暂无示例图')
                this.previewImage([item.waste_thumb])
            },
            // 上传图片
            onSuccess(reslut) {
                this.form.image.push(reslut.data.url)
            },
            // 提交
            submit() {
                var that = this
                if (!that.form.image.length) return that.$u.toast('请先上传照片')
                uni.showModal({
                    title: '提示',
                    content: '照片需要拍清楚垃圾的内容，保持分类正确，如果错误分类可能造成环境的危害和相应的撤销积分',
                    success: function(res) {
                        if (res.confirm) {
                            that.$u.api.add_forum(that.form).then(res => {
                                that.msg = res.msg
                                that.mask_show = true
                                that.form.content = ''
                                that.form.image = []
                                that.$refs.uUpload.clear
                                const prev = that.$u.func.prePage()
                                if (prev && typeof prev.get_forum_list === 'function') {
                                    prev.forum_list = []
                                    prev.page = 1
                                    prev.get_forum_list()
                                }
                            }).catch(res => {
                                that.$u.toast(res.msg);
                            })
                        } else if (res.cancel) {
                            console.log('用户点击取消');
                        }
                    }
                });
            },
        },
    }
</script>

<style lang="scss" scoped>
    .warp {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }
</style>