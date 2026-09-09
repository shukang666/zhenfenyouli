<template>
    <view class="u-p-20">
        <u-parse :html="content"></u-parse>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                content: ''
            };
        },
        onLoad(option) {
            this.find_exam_topic_list(option.id)
        },
        methods: {
            //获取数据
            find_exam_topic_list(id) {
                this.$u.api.find_exam_topic_list({
                    id
                }).then(res => {
                    this.content = this.$u.func.formatRichText(res.data.secret);
                })
            },
        }
    }
</script>

<style lang="scss">

</style>