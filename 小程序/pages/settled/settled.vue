<template>
    <view class="">
        <view class="u-p-30">
            <u-form :model="form" ref="uForm" :error-type="['border-bottom','toast']">
                <view class="u-type-white-bg u-radius-10 u-p-lr-30">
                    <u-form-item label="姓名" prop="name" labelWidth="300">
                        <u-input v-model="form.name" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="联系电话" prop="mobile" labelWidth="300">
                        <u-input v-model="form.mobile" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="门店名称" prop="shop_name" labelWidth="300">
                        <u-input v-model="form.shop_name" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="门店位置" prop="address" labelWidth="300">
                        <u-input v-model="form.address" input-align="right" placeholder="请选择" type="select"
                            @click="chooseLocation" />
                    </u-form-item>
                    <u-form-item label="门店介绍" prop="desc" labelWidth="300" label-position="bottom">
                        <u-input v-model="form.desc" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="门店照片" labelWidth="300" labelPosition="bottom">
                        <u-upload :action="uploadUrl" :file-list="fileList" maxCount="1" @on-success="success($event,1)"
                            @on-remove="remove($event,1)" @on-choose-complete="disabled = true"
                            @on-uploaded="disabled = false">
                        </u-upload>
                    </u-form-item>
                    <u-form-item label="营业执照" labelWidth="300" labelPosition="bottom">
                        <u-upload :action="uploadUrl" :file-list="fileList2" maxCount="1"
                            @on-success="success($event,2)" @on-remove="remove($event,2)"
                            @on-choose-complete="disabled = true" @on-uploaded="disabled = false">
                        </u-upload>
                    </u-form-item>
                </view>
            </u-form>
            <u-gap height="120"></u-gap>
            <view class="submit-btn u-p-30">
                <u-button type="primary" shape="circle" hover-class="none"
                    :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                    :disabled="disabled" @click="submit">确认提交</u-button>
            </view>
        </view>
        <!-- 选择时间 -->
        <u-select v-model="select_show" :mode="mode" :list="select_list" @confirm="confirm"></u-select>
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
                    name: '',
                    mobile: '',
                    shop_name: '',
                    address: '',
                    lng: '',
                    lat: '',
                    desc: '',
                    avatar: '',
                    license: ''
                },
                rules: {
                    desc: [{
                        required: true,
                        message: '请输入门店介绍',
                        trigger: ['change', 'blur'],
                    }],
                    address: [{
                        required: true,
                        message: '请选择门店位置',
                        trigger: ['change', 'blur'],
                    }],
                    shop_name: [{
                        required: true,
                        message: '请输入门店名称',
                        trigger: ['change', 'blur'],
                    }],
                    name: [{
                        required: true,
                        message: '请输入联系人',
                        trigger: ['change', 'blur'],
                    }],
                    mobile: [{
                        required: true,
                        message: '请输入联系电话',
                        trigger: ['change', 'blur'],
                    }],
                },
                disabled: false,
                fileList: [],
                fileList2: [],
                select_show: false,
                select_index: 0,
                mode: 'single-column'
            };
        },
        onLoad(option) {
            if(this.userInfo.shop_status!=0){
                this.get_shop()
            }
        },
        methods: {
            // 选择位置
            chooseLocation() {
                var that = this
                uni.chooseLocation({
                    success: function(res) {
                        that.form.address = res.address
                        that.form.lng = res.longitude
                        that.form.lat = res.latitude
                        // console.log('位置名称：' + res.name);
                        // console.log('详细地址：' + res.address);
                        // console.log('纬度：' + res.latitude);
                        // console.log('经度：' + res.longitude);
                    }
                });
            },
            // 详情
            get_shop() {
                this.$u.api.get_shop().then(res => {
                    if (!res.data) return
                    this.form = res.data
                    if (this.form.avatar) {
                        this.fileList.push({
                            url: this.form.avatar
                        })
                    }
                    if (this.form.license) {
                        this.fileList2.push({
                            url: this.form.license
                        })
                    }
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 添加图片
            success(e, index) {
                if (index == 1) {
                    this.form.avatar = e.data.filepath
                } else {
                    this.form.license = e.data.filepath
                }
            },
            // 移除图片
            remove(e, index) {
                if (index == 1) {
                    this.form.avatar = ''
                } else {
                    this.form.license = ''
                }
            },
            // 提交
            submit() {
                this.$refs.uForm.validate(valid => {
                    if (valid) {
                        if (!this.form.avatar) return this.$u.toast('请先上传门店照片')
                        if (!this.form.license) return this.$u.toast('请先上传营业执照')
                        this.$u.api.add_shop(this.form).then(res => {
                            this.$u.func.showToast({
                                title: res.msg,
                                icon: 'success',
                                back: true
                            })
                        }).catch(err => {
                            this.$u.toast(err.msg)
                        })
                    } else {
                        console.log('验证失败');
                    }
                });
            },
        },
        // 必须要在onReady生命周期，因为onLoad生命周期组件可能尚未创建完毕
        onReady() {
            this.$refs.uForm.setRules(this.rules);
        }
    }
</script>

<style lang="scss">

</style>