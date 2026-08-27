<template>
    <view class="">
        <view class="u-p-30">
            <u-form :model="form" ref="uForm" :error-type="['border-bottom','toast']">
                <view class="u-type-white-bg u-radius-10 u-p-lr-30">
                    <u-form-item label="商品照片" labelWidth="300" labelPosition="bottom">
                        <cl-upload v-model="form.photos" :action="uploadUrl" @onSuccess="onSuccess($event,1)"
                            fileType="image">
                            <template v-slot:addImg>
                                <u-image width="138" height="138" src="/static/upload.png"></u-image>
                            </template>
                        </cl-upload>
                    </u-form-item>
                    <u-form-item label="商品名称" prop="goods_name" labelWidth="300">
                        <u-input v-model="form.goods_name" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="商品价格(元)" prop="price" labelWidth="300">
                        <u-input v-model="form.price" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="抵扣比例(%)" prop="discount" labelWidth="300">
                        <u-input v-model="form.discount" input-align="right" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="是否自提" prop="desc" labelWidth="300">
                        <view class="" slot="right">
                            <u-radio-group v-model="form.is_take" active-color="#18af38">
                                <u-radio v-for="(item, index) in radio_list" :key="index" :name="item.type">
                                    {{item.name}}
                                </u-radio>
                            </u-radio-group>
                        </view>
                    </u-form-item>
                    <u-form-item label="是否邮寄" prop="desc" labelWidth="300">
                        <view class="" slot="right">
                            <u-radio-group v-model="form.is_express" active-color="#18af38">
                                <u-radio v-for="(item, index) in radio_list" :key="index" :name="item.type">
                                    {{item.name}}
                                </u-radio>
                            </u-radio-group>
                        </view>
                    </u-form-item>
                    <u-form-item label="商品库存" prop="stock" labelWidth="300">
                        <u-input v-model="form.stock" placeholder="请填写" input-align="right" />
                    </u-form-item>
                    <u-form-item label="商品类别" prop="cate_name" labelWidth="300">
                        <u-input v-model="form.cate_name" input-align="right" placeholder="请选择" type="select"
                            @click="select_show = true" />
                    </u-form-item>
                    <u-form-item label="商品详情" prop="content" labelWidth="300" labelPosition="bottom">
                        <u-input v-model="form.content" type="textarea" height="200" maxlength="-1" placeholder="请填写" />
                    </u-form-item>
                    <u-form-item label="详情图片" labelWidth="300" labelPosition="bottom">
                        <cl-upload v-model="form.image" :action="uploadUrl" @onSuccess="onSuccess($event,2)"
                            fileType="image">
                            <template v-slot:addImg>
                                <u-image width="138" height="138" src="/static/upload.png"></u-image>
                            </template>
                        </cl-upload>
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
        <u-select v-model="select_show" :list="select_list" label-name="cate_name" value-name="id"
            @confirm="confirm"></u-select>
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
                    id: 0,
                    photos: [],
                    goods_name: '',
                    price: '',
                    discount: '',
                    stock: '',
                    cate_name: '',
                    first_cate: '',
                    content: '',
                    image: [],
                    is_express: 1,
                    is_take: 1
                },
                rules: {
                    cate_name: [{
                        required: true,
                        message: '请选择商品类别',
                        trigger: ['change', 'blur'],
                    }],
                    stock: [{
                        required: true,
                        message: '请输入商品库存',
                        trigger: ['change', 'blur'],
                        type: 'number'
                    }],
                    discount: [{
                        required: true,
                        message: '请输入折扣比例',
                        trigger: ['change', 'blur'],
                    }],
                    price: [{
                        required: true,
                        message: '请输入商品价格',
                        trigger: ['change', 'blur'],
                    }],
                    goods_name: [{
                        required: true,
                        message: '请输入商品名称',
                        trigger: ['change', 'blur'],
                    }],
                },
                select_show: false,
                select_index: 0,
                select_list: [],
                radio_list: [{
                        name: '是',
                        type: 1
                    },
                    {
                        name: '否',
                        type: 2
                    }
                ],
            };
        },
        onLoad(option) {
            if(option.id){
                this.get_shop_goods_list(option.id)
            }
            this.get_category()
        },
        methods: {
            // 
            confirm(e) {
                this.form.first_cate = e[0].value
                this.form.cate_name = e[0].label
            },
            // 
            get_category() {
                this.$u.api.get_category().then(res => {
                    this.select_list = res.data
                })
            },
            // 上传图片
            onSuccess(e, index) {
                if (index == 1) {
                    this.form.photos.push(e.data.url)
                } else {
                    this.form.image.push(e.data.url)
                }
            },
            // 详情
            get_shop_goods_list(id) {
                this.$u.api.get_shop_goods_list({
                    id
                }).then(res => {
                    if (!res.data) return
                    for (var key in this.form) {
                        this.form[key] = res.data[key]
                    }
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 提交
            submit() {
                this.$refs.uForm.validate(valid => {
                    if (valid) {
                        if (!this.form.photos.length) return this.$u.toast('请先上传商品照片')
                        this.$u.api.add_goods(this.form).then(res => {
                            this.$u.func.showToast({
                                title: res.msg,
                                icon: 'success',
                                back: true
                            })
                            this.$u.func.prePage().goods_list = []
                            this.$u.func.prePage().page = 1
                            this.$u.func.prePage().get_shop_goods_list()
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