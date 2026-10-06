<script>
    export default {
        globalData: {
            tabBarIndex: 0
        },
        onLaunch: function() {
            // this.$u.wx.wxOauth()
            this.findSetting(true)
            // #ifdef MP-WEIXIN
            // 小程序更新（仅注册一次，避免每次 onShow 重复绑定导致卡顿与重复弹窗）
            if (uni.getUpdateManager) {
                const updateManager = uni.getUpdateManager()
                updateManager.onCheckForUpdate(function() {})
                updateManager.onUpdateReady(function() {
                    uni.showModal({
                        title: '更新提示',
                        content: '新版本已经准备好，是否重启应用？',
                        success(res) {
                            if (res.confirm) {
                                updateManager.applyUpdate()
                            }
                        }
                    })
                })
                updateManager.onUpdateFailed(function() {
                    uni.showModal({
                        title: '已经有新版本了哟~',
                        content: '新版本已经上线啦~，请您删除当前小程序，重新搜索打开哟~',
                        showCancel: false
                    })
                })
            }
            // #endif
        },
        onShow: function(options) {
            this.findSetting(false)
            const path = (options && options.path) || ''
            if (/details-new/.test(path)) {
                uni.$emit('pause-new-user-guide')
            }
        },
        onHide: function() {},
        methods: {
            findSetting(force) {
                const now = Date.now()
                const last = this._settingFetchedAt || 0
                if (!force && last && now - last < 120000) return
                this._settingFetchedAt = now
                this.$u.api.findSetting().then(res => {
                    this.$u.vuex('setting', res.data)
                }).catch(() => {})
            },
        }
    }
</script>

<style lang="scss">
    page {
        background: #eef2ef;
    }

    @import 'uview-ui/index.scss';
    @import '@/static/styles/glass-ui.scss';
    /*每个页面公共css */
    @import '@/common/common.style.scss';
    /*toast字体图标*/
    @import '@/components/iconfont.css';
</style>