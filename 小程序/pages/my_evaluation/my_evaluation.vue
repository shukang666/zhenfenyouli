<template>
    <view>
        <u-tabs :list="list" :is-scroll="false" :current="current" @change="change" active-color="#18AF38"></u-tabs>
        <view class="u-p-30">
            <view class="u-type-white-bg u-p-30 u-radius-10 u-m-b-30" v-for="(item,index) in 3" :key="index">
                <view class="u-font-32 u-font-bold">
                    孤独症行为评定量表
                </view>
                <view class="u-m-t-10 u-font-26 u-type-info">
                    量表类型：儿童诊断量表
                </view>
                <view class="u-flex u-row-between u-type-info">
                    <view class="u-font-26">
                        提交时间：2025.04.11 12:00
                    </view>
                    <view class="">
                        <u-button type="success" size="mini" hover-class="none" @click="add_comment()">查看详情
                        </u-button>
                    </view>
                </view>
            </view>
        </view>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                list: [{
                    name: '专业诊断量表'
                }, {
                    name: '身体检测工具'
                }],
                current: 0
            };
        },
        methods: {
            change(index) {
                this.current = index;
            }
        }
    }
</script>

<style lang="scss">

</style>