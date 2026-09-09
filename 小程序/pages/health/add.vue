<template>
    <view>
        <view class="u-type-white-bg u-p-30 u-m-t-30">
            <view class="">
                <view class="" style="color: #3974f2;">
                    姓名
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="name" placeholder="请输入" />
                </view>
            </view>
            <view class="u-m-t-30">
                <view class="" style="color: #3974f2;">
                    联系电话
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="mobile" placeholder="请输入" disabled />
                </view>
            </view>
            <view class="u-m-t-30" v-if="health.is_demand==1&&health.cate_id==6">
                <view class="" style="color: #3974f2;">
                    具体诊疗需求
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="demand" type="textarea" height="250" maxlength="-1" placeholder="请输入" />
                </view>
            </view>
            <view class="u-m-t-30" v-if="health.cate_id==6">
                <view class="" style="color: #3974f2;">
                    请选择您常去的医疗机构
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="institution" placeholder="请选择" type="select" @click="select_show = true" />
                </view>
            </view>
            <view class="u-p-60">
                <u-button type="primary" hover-class="none" @click="add_reservation">马上提交</u-button>
            </view>
        </view>
        <!-- 选择机构 -->
        <u-select mode="mutil-column-auto" v-model="select_show" value-name="id" label-name="name" child-name="child"
            :list="select_list" @confirm="confirm"></u-select>
        
    </view>
</template>

<script>
    export default {
        data() {
            return {
                health: {},
                select_list: [],
                select_show: false,
                name: '',
                mobile: '',
                demand: '',
                institution: '',
            };
        },
        onLoad(option) {
            this.mobile = this.userInfo.mobile || '';
            this.find_exam_topic_list(option.id)
            this.get_select_list()
        },
        methods: {
            // 提交
            add_reservation() {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                this.$u.api.add_reservation({
                    name: this.name,
                    mobile: this.mobile,
                    demand: this.demand,
                    institution: this.institution,
                    health_id: this.health.id
                }).then(res => {
                    this.$u.func.showToast({
                        title: res.msg,
                        icon: 'success',
                        back: true
                    })
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            confirm(e) {
                this.institution = e[0].label + '-' + e[1].label;
            },
            // 选择机构
            get_select_list() {
                this.$u.api.get_select_list().then(res => {
                    this.select_list = res.data
                })
            },
            find_exam_topic_list(id) {
                this.$u.api.find_exam_topic_list({
                    id
                }).then(res => {
                    this.health = res.data
                    uni.setNavigationBarTitle({
                        title: this.health.name
                    })
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        }
    }
</script>

<style lang="scss">
    
</style>