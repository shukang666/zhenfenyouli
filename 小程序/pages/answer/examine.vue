<template>
    <view class="u-p-30">
        <view class="">
            <u-image width="100%" height="330rpx"
                src="https://oss.ausite.cn/xcxkf052/admin/20250515/69c005b81e1c8a35677a63b478139729.png?watermark"></u-image>
        </view>
        <view class="u-type-white-bg u-p-30 u-rela" style="margin-top: -100rpx;z-index: 1;border-radius: 10rpx;">
            <view class="u-flex u-row-center" style="margin-top: -60rpx;z-index: 1;">
                <u-image width="376" height="78"
                    src="https://oss.ausite.cn/xcxkf052/admin/20250515/e34a943fbfe4de3ac703bb0816d598d6.png?watermark"></u-image>
            </view>
            <view class="u-flex u-col-top u-m-t-30">
                <view class="u-shrink-no">
                    <u-tag text="问" mode="dark" size="mini" bg-color="#18AF38" />
                </view>
                <view class="u-m-l-20 u-font-30">
                    {{question.title}}
                </view>
            </view>
            <view class="u-flex u-row-between" v-if="answer_status!=0">
                <view class="u-font-30">
                    答案：{{option}}
                </view>
                <view class="" v-if="answer_status==1">
                    <u-image width="128" height="128"
                        src="https://oss.ausite.cn/xcxkf052/admin/20250515/f752343199326c1e4e669b3da339c85d.png?watermark"></u-image>
                </view>
                <view class="" v-if="answer_status==2">
                    <u-image width="128" height="128"
                        src="https://oss.ausite.cn/xcxkf052/admin/20250515/8bee399a643027ecd5c604d1b69c616c.png?watermark"></u-image>
                </view>
            </view>
            <view class="u-m-t-50" v-if="answer_status==0">
                <view class="boxStyle u-m-b-30" :class="question_id == item.id?'active_style':''"
                    v-for="(item,index) in question.option_list" :key="index" @click="question_id = item.id">
                    {{item.letter}}.{{item.option}}
                </view>
            </view>
            <view class="u-flex u-col-top u-m-t-30 u-border-top u-p-t-30" v-if="answer_status==2">
                <view class="u-shrink-no">
                    <u-tag text="解析" mode="dark" size="mini" bg-color="#DB392A" />
                </view>
                <view class="u-m-l-20">
                    <u-parse :html="desc"></u-parse>
                </view>
            </view>
            <view class="u-m-t-50" v-if="answer_status==2">
                <view class="u-flex u-row-center">
                    <u-image width="164" height="164"
                        src="https://oss.ausite.cn/xcxkf052/admin/20250515/fca076eebbadcbba03fd6f9a12e89316.png?watermark"></u-image>
                </view>
                <view class="u-text-center u-font-24">
                    涨知识啦
                </view>
            </view>
        </view>
        <view class="u-p-30" v-if="next==0">
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="add_answer_one" v-if="answer_status==0">确认提交</u-button>
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="get_question_one" v-else>下一题</u-button>
        </view>
        <view class="u-p-30" v-else>
            <u-button type="primary" hover-class="none"
                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                @click="mask_show = true">完成答题</u-button>
        </view>
        <!-- 弹窗 -->
        <u-mask :show="mask_show">
            <view class="warp">
                <view class="">
                    <view class="u-flex u-row-center">
                        <u-image width="540" height="87"
                            src="https://oss.ausite.cn/xcxkf052/admin/20250515/1f421a47405e005f417777a7fc8779f7.png?watermark"></u-image>
                    </view>
                    <view class="u-w-540 u-radius-10 u-type-white-bg u-text-center">
                        <u-gap height="100"></u-gap>
                        <view class="u-flex u-row-center">
                            <u-image width="200" height="200"
                                src="https://oss.ausite.cn/xcxkf052/admin/20250516/f788a225dbb9a6bdff4d8a312e9fad3a.png?watermark"></u-image>
                        </view>
                        <view class="u-font-42 u-font-bold">
                            {{score}}分
                        </view>
                        <view class="u-m-t-60">
                            <u-button type="success" hover-class="none"
                                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                                shape="circle" size="medium" @click="navigateBack">我知道了</u-button>
                        </view>
                        <u-gap height="30"></u-gap>
                    </view>
                </view>
            </view>
        </u-mask>
        <!-- 弹窗 -->
        <u-mask :show="mask_show2">
            <view class="warp">
                <view class="">
                    <view class="u-flex u-row-center">
                        <u-image width="540" height="87"
                            src="https://oss.ausite.cn/xcxkf052/admin/20250515/0b56d1d84a8ce4646db26dc6801ffac3.png?watermark"></u-image>
                    </view>
                    <view class="u-w-540 u-radius-10 u-type-white-bg u-text-center">
                        <u-gap height="100"></u-gap>
                        <view class="u-flex u-row-center">
                            <u-image width="200" height="200"
                                src="https://oss.ausite.cn/xcxkf052/admin/20250516/26e0df0c97fcd40c195728a3e6a7560c.png?watermark"></u-image>
                        </view>
                        <view class="u-font-32 u-font-bold u-p-30">
                            恭喜已经激活垃圾分类打卡等功能，可以开启垃圾打卡环保之旅
                        </view>
                        <view class="u-m-t-30">
                            <u-button type="success" hover-class="none"
                                :customStyle="{background: 'linear-gradient( 141deg, #A4DB88 0%, #18AF38 100%)'}"
                                shape="circle" size="medium" @click="redirectTo">立即打卡</u-button>
                        </view>
                        <u-gap height="30"></u-gap>
                    </view>
                </view>
            </view>
        </u-mask>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                question: {},
                answer_id: 0,
                question_id: 0,
                desc: '',
                answer_status: 0,
                option: '',
                next: 0,
                mask_show: false,
                score: 0,
                mask_show2: false,
                pass: 0
            };
        },
        onLoad(option) {
            this.answer_id = option.id
            this.get_question_one()
        },
        methods: {
            // 打卡
            redirectTo(){
                uni.redirectTo({
                    url:'/pages/flhd/ljfldk'
                })
            },
            // 
            navigateBack() {
                if (this.pass) {
                    this.mask_show2 = true
                    this.pass = 0
                } else {
                    if (this.answer_id != 1) {
                        this.$u.func.prePage().get_problem_list(this.answer_id)
                    }
                    uni.navigateBack()
                }
            },
            // 答题
            add_answer_one() {
                this.$u.api.add_answer_one({
                    answer_id: this.question.id,
                    o_id: this.question_id
                }).then(res => {
                    this.next = res.data.next
                    this.answer_status = res.data.answer_status
                    this.desc = res.data.desc
                    this.option = res.data.option
                    this.score = res.data.score
                    this.pass = res.data.pass
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
            // 题目
            get_question_one() {
                this.$u.api.get_question_one({
                    id: this.answer_id
                }).then(res => {
                    this.question = res.data
                    this.answer_status = 0
                    this.desc = ''
                    this.option = ''
                })
            }
        }
    }
</script>

<style lang="scss">
    .warp {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }

    .boxStyle {
        border-radius: 48rpx 48rpx 48rpx 48rpx;
        border: 2rpx solid #999;
        padding: 25rpx;
        text-align: center;
    }

    .active_style {
        border: 2rpx solid #18AF38;
        color: #18AF38;
        background: rgba(24, 175, 56, 0.1);
    }
</style>