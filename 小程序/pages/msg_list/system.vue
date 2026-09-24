<template>
    <view>
        <u-tabs :list="list" :is-scroll="false" :current="current" @change="change" active-color="#18AF38"></u-tabs>
        <view class="u-p-30">
            <view class=" u-m-b-30" v-for="(item,index) in msg_list" :key="index">
                <view class="u-text-center u-type-info">
                    {{item.create_time|date('yy-mm-dd hh:MM')}}
                </view>
                <view class="u-type-white-bg u-radius-10 u-p-30 u-m-t-30">
                    <view class="u-flex u-border-bottom u-p-b-30">
                        <u-image width="50" height="50"
                            src="https://admin.circular-economy.cn/upload/xcxkf052/admin/20250610/f1ebcb3105f3b204b4f2c24f2e1272d8.png"></u-image>
                        <view class="u-m-l-10">
                            {{item.name}}
                        </view>
                    </view>
                    <view class="u-m-t-30">
                        {{item.content}}
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
                    name: '系统消息',
                    type: 1
                }, {
                    name: '活动提醒',
                    type: 2
                }, {
                    name: '积分变动',
                    type: 3
                }],
                current: 0,
                msg_list: [],
                page: 1,
                total: 0
            };
        },
        onLoad() {
            this.get_system_msg_list()
        },
        methods: {
            change(index) {
                this.current = index;
                this.msg_list = []
                this.page = 1
                this.get_system_msg_list()
            },
            get_system_msg_list() {
                this.$u.api.get_system_msg_list({
                    keyword: this.keyword,
                    page: this.page,
                    type: this.list[this.current].type
                }).then(res => {
                    this.msg_list.push(...res.data.data)
                    this.total = res.data.total
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        },
        onReachBottom() {
            if (this.msg_list.length >= this.total) return
            this.page++
            this.get_system_msg_list()
        }
    }
</script>

<style lang="scss">

</style>