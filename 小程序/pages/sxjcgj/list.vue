<template>
    <view class="u-p-30 u-font-32">
        <block v-if="subject.length">
            <view class="u-flex u-row-between">
                <view class="">
                    {{subject_index+1}}/{{subject.length}}
                </view>
                <view class="">
                    <u-button size="mini" plain shape="circle" hover-class="none" @click="add_col"
                        :type="!subject[subject_index].is_collect?'':'primary'">
                        <u-icon :name="!subject[subject_index].is_collect?'star':'star-fill'" size="24"
                            :color="!subject[subject_index].is_collect?'':'#119AEB'"
                            :label="!subject[subject_index].is_collect?'收藏':'已收藏'"
                            :label-color="!subject[subject_index].is_collect?'':'#119AEB'" label-pos="left"
                            label-size="22"></u-icon>
                    </u-button>
                </view>
            </view>
            <view class="u-type-white-bg u-radius-10 u-p-30 u-m-t-30" v-for="(item,index) in subject" :key="index"
                v-if="index==subject_index">
                <view class="u-font-bold">
                    {{item.title}}
                </view>
                <view class="u-m-t-30">
                    <view class="u-type-info-light-bg u-p-30 u-radius-10 u-m-b-30"
                        :class="item.is_right==1&&item.user_answer.includes(item1.id)?'success_style':(item.is_right==2&&item.user_answer.includes(item1.id)?'error_style':'')"
                        v-for="(item1,index1) in item.answer_array" :key="index1">
                        {{item1.id}}. {{item1.value}}
                    </view>
                </view>
            </view>
            <view class="u-type-white-bg u-radius-10 u-p-30 u-m-t-30" v-if="subject[subject_index].is_right==2">
                <view class="">
                    正确答案：{{subject[subject_index].answer}}
                </view>
                <view class="u-m-t-30">
                    解析：{{subject[subject_index].analysis}}
                </view>
            </view>
            <u-gap height="120"></u-gap>
            <view class="submit-btn u-p-30" v-if="subject.length!=1">
                <view class="" v-if="!subject_index">
                    <u-button type="primary" @click="subject_index++">下一题</u-button>
                </view>
                <view class="u-flex u-row-around" v-else-if="subject_index&&subject.length-1!=subject_index">
                    <view class="">
                        <u-button type="primary" @click="subject_index--">上一题</u-button>
                    </view>
                    <view class="">
                        <u-button type="primary" @click="subject_index++">下一题</u-button>
                    </view>
                </view>
                <view class="" v-else>
                    <u-button type="primary" @click="subject_index--">上一题</u-button>
                </view>
            </view>
        </block>
        <block v-else>
            <u-empty text="暂无答题记录" mode="list" marginTop="400"></u-empty>
        </block>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                subject: [],
                subject_index: 0,
            };
        },
        onLoad(option) {
            this.subject_index = Number(option.index)
            this.get_user_activity_detail(option.id, option.is_wrong)
        },
        methods: {
            // 收藏
            add_col() {
                if (!this.isLogin) return this.$u.route('/pages/public/login')
                this.$u.api.post_subject_set({
                    subject_id: this.subject[this.subject_index].subject_id,
                }).then(res => {
                    if (!this.subject[this.subject_index].is_collect) {
                        this.subject[this.subject_index].is_collect = 1
                    } else {
                        this.subject[this.subject_index].is_collect = 0
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
            get_user_activity_detail(activity_id, is_wrong) {
                this.$u.api.get_user_activity_detail({
                    activity_log_id: activity_id,
                    is_wrong: is_wrong == 'true' ? true : false
                }).then(res => {
                    this.subject = res.data.list
                })
            }
        }
    }
</script>

<style lang="scss">
    .active_style {
        background: #F0F3FB;
        border: 1rpx solid #0E7CF7;
    }

    .success_style {
        background: #C3F2D0;
        border: 1rpx solid #48D971;
    }

    .error_style {
        background: #FCEDED;
        border: 1rpx solid #EA3E3E;
    }
</style>