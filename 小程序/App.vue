<script>
    export default {
        onLaunch: function() {
            // this.$u.wx.wxOauth()
        },
        onShow: function() {
            this.findSetting()
            // #ifdef MP-WEIXIN
            //小程序更新
            if (uni.getUpdateManager) {
                const updateManager = uni.getUpdateManager();
                updateManager.onCheckForUpdate(function(res) {
                    // 请求完新版本信息的回调
                    console.log(res.hasUpdate);
                });
                updateManager.onUpdateReady(function(res) {
                    uni.showModal({
                        title: '更新提示',
                        content: '新版本已经准备好，是否重启应用？',
                        success(res) {
                            if (res.confirm) {
                                // 新的版本已经下载好，调用 applyUpdate 应用新版本并重启
                                updateManager.applyUpdate();
                            }
                        }
                    });
                });
                updateManager.onUpdateFailed(function(res) {
                    // 新的版本下载失败
                    uni.showModal({
                        title: '已经有新版本了哟~',
                        content: '新版本已经上线啦~，请您删除当前小程序，重新搜索打开哟~',
                        showCancel: false
                    });
                });
            }
            // #endif
        },
        onHide: function() {},
        methods: {
            findSetting() {
                this.$u.api.findSetting().then(res => {
                    this.$u.vuex('setting', res.data)
                })
            },
        }
    }
</script>

<style lang="scss">
    page {
        background: #EFF9F1
    }

    @import 'uview-ui/index.scss';
    /*每个页面公共css */
    @import '@/common/common.style.scss';
    /*toast字体图标*/
    @import '@/components/iconfont.css';
</style>