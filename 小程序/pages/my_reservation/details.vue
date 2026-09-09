<template>
    <view>
        <view class="u-type-white-bg u-p-30 u-m-t-30">
            <view class="">
                <view class="" style="color: #3974f2;">
                    姓名
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="reservation.name" placeholder="请输入" disabled />
                </view>
            </view>
            <view class="u-m-t-30">
                <view class="" style="color: #3974f2;">
                    联系电话
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="reservation.mobile" placeholder="请输入" disabled />
                </view>
            </view>
            <view class="u-m-t-30" v-if="reservation.demand">
                <view class="" style="color: #3974f2;">
                    具体诊疗需求
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="reservation.demand" type="textarea" height="250" maxlength="-1" placeholder="请输入"
                        disabled />
                </view>
            </view>
            <view class="u-m-t-30" v-if="reservation.institution">
                <view class="" style="color: #3974f2;">
                    请选择您常去的医疗机构
                </view>
                <view class="u-type-info-light-bg u-p-lr-10 u-m-t-30">
                    <u-input v-model="reservation.institution" placeholder="请选择" disabled />
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                reservation: {}
            }
        },
        onLoad(option) {
            this.get_reservation_list(option.id)
        },
        methods: {
            get_reservation_list(id) {
                this.$u.api.get_reservation_list({
                    id
                }).then(res => {
                    this.reservation = res.data
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        }
    }
</script>

<style>

</style>