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
            uni.setNavigationBarTitle({
                title: option.title
            })
            this.findSetting(option.name)
        },
        methods: {
            //获取数据
            findSetting(name) {
                this.$u.api.findSetting({name}).then(res => {
                    this.content = this.$u.func.formatRichText(res.data.content);
                })
            },
        }
    }
</script>

<style lang="scss">

</style>
