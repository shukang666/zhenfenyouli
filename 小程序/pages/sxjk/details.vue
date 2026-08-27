<template>
    <view class="u-p-30">
        <u-parse :html="disease"></u-parse>
    </view>
</template>

<script>
    export default {
        data() {
            return {
                disease: {}
            };
        },
        onLoad(option) {
            this.get_disease_list(option.id)
        },
        methods: {
            //
            get_disease_list(id) {
                this.$u.api.get_disease_list({
                    id
                }).then(res => {
                    this.disease = this.$u.func.formatRichText(res.data.content)
                    uni.setNavigationBarTitle({
                        title: res.data.name
                    })
                }).catch(err => {
                    this.$u.toast(err.msg)
                })
            },
        }
    }
</script>

<style lang="scss">
    page {
        background: #fff;
    }
</style>