<template>
    <view>
        <view class="u-p-20">
            <u-parse :html="health.content"></u-parse>
        </view>
        <u-gap height="120"></u-gap>
        <view class="submit-btn u-p-30" v-if="!setting.app_test">
            <u-button type="primary" hover-class="none"
                @click="navigateTo('/pages/answer/list?id='+ (health.cate_id ? health.id:health.exam_id))"
                v-if="health.is_exam==1&&!health.is_answer">填写问卷</u-button>
            <u-button type="primary" hover-class="none" @click="navigateTo('/pages/healthcare/add?id='+health.id)"
                v-if="health.is_exam==2">立即预约</u-button>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                health: {}
            };
        },
        onLoad(option) {
            if (option.type == 1) {
                this.get_health_home_list(option.id)
            } else {
                this.find_exam_topic_list(option.id)
            }
        },
        methods: {
            // 详情
            get_health_home_list(id) {
                this.$u.api.get_health_home_list({
                    id
                }).then(res => {
                    this.health = res.data
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 详情
            find_exam_topic_list(id) {
                this.$u.api.find_exam_topic_list({
                    id
                }).then(res => {
                    this.health = res.data
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            //跳转
            navigateTo(url){
                uni.redirectTo({url})
            }
        }
    }
</script>

<style lang="scss">

</style>