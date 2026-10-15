/** custom-tab-bar 独立上下文，不可 require 主包 common/settings.js */
const API_BASE = 'https://admin.circular-economy.cn/api'

function getAppSafe() {
  try {
    return getApp()
  } catch (e) {
    return null
  }
}

function readTabIndex() {
  const app = getAppSafe()
  const n = app && app.globalData && app.globalData.tabBarIndex
  return typeof n === 'number' ? n : 0
}

function writeTabIndex(index) {
  const app = getAppSafe()
  if (!app) return
  if (!app.globalData) app.globalData = {}
  app.globalData.tabBarIndex = index
}

function clearLocalSession() {
  const app = getAppSafe()
  if (app && app.$store) {
    app.$store.commit('$uStore', { name: 'userInfo', value: {} })
    app.$store.commit('$uStore', { name: 'isLogin', value: false })
  }
  try {
    wx.removeStorageSync('vcode')
  } catch (e) {}
}

function getOpenid() {
  const app = getAppSafe()
  const st = app && app.$store && app.$store.state
  const u = (st && st.userInfo) || {}
  return u.openid || ''
}

const ME_TAB_INDEX = 4
const DEV_GATE = '110'

Component({
  options: {
    styleIsolation: 'shared'
  },
  data: {
    selected: -1,
    animOn: false,
    devPwdShow: false,
    devPwdFocus: false,
    devPwdInput: '',
    devMenuShow: false,
    list: [
      {
        pagePath: '/pages/index/index',
        iconPath: '/static/tabbar/tab1.png',
        selectedIconPath: '/static/tabbar/tab1-active.png',
        text: '首页'
      },
      {
        pagePath: '/pages/low_carbon/low_carbon',
        iconPath: '/static/tabbar/tab2.png',
        selectedIconPath: '/static/tabbar/tab2-active.png',
        text: '活动'
      },
      {
        pagePath: '/pages/health/health',
        iconPath: '/static/tabbar/tab3.png',
        selectedIconPath: '/static/tabbar/tab3-active.png',
        text: '健康'
      },
      {
        pagePath: '/pages/mall/mall',
        iconPath: '/static/tabbar/tab4.png',
        selectedIconPath: '/static/tabbar/tab4-active.png',
        text: '积分权益'
      },
      {
        pagePath: '/pages/user/index',
        iconPath: '/static/tabbar/tab5.png',
        selectedIconPath: '/static/tabbar/tab5-active.png',
        text: '我的'
      }
    ]
  },
  lifetimes: {
    attached() {
      this._snapTo(readTabIndex())
    }
  },
  methods: {
    noop() {},
    _enableAnim() {
      if (this.data.animOn) return
      this.setData({ animOn: true })
    },
    _snapTo(index) {
      writeTabIndex(index)
      this.setData({ animOn: false, selected: index }, () => {
        setTimeout(() => this._enableAnim(), 60)
      })
    },
    setSelected(index) {
      writeTabIndex(index)
      if (this.data.selected < 0 || !this.data.animOn) {
        this._snapTo(index)
        return
      }
      if (this.data.selected === index) return
      this.setData({ selected: index })
    },
    switchTab(e) {
      const index = e.currentTarget.dataset.index
      const path = e.currentTarget.dataset.path
      if (this.data.selected === index) return
      writeTabIndex(index)
      wx.switchTab({ url: path })
      this.setData({
        selected: index,
        animOn: this.data.selected >= 0
      })
    },
    onTabLongPress(e) {
      const index = e.currentTarget.dataset.index
      if (index !== ME_TAB_INDEX) return
      this.setData({
        devPwdShow: true,
        devPwdInput: '',
        devPwdFocus: true
      })
    },
    onDevPwdInput(e) {
      this.setData({ devPwdInput: (e.detail && e.detail.value) || '' })
    },
    closeDevPwd() {
      this.setData({ devPwdShow: false, devPwdFocus: false, devPwdInput: '' })
    },
    confirmDevPwd() {
      if (String(this.data.devPwdInput).trim() !== DEV_GATE) {
        wx.showToast({ title: '密码错误', icon: 'none' })
        return
      }
      this.setData({
        devPwdShow: false,
        devPwdFocus: false,
        devPwdInput: '',
        devMenuShow: true
      })
    },
    closeDevMenu() {
      this.setData({ devMenuShow: false })
    },
    doLogout() {
      clearLocalSession()
      this.setData({ devMenuShow: false })
      wx.showToast({ title: '已退出登录', icon: 'none' })
    },
    doDeleteAccount() {
      const openid = getOpenid()
      if (!openid) {
        clearLocalSession()
        this.setData({ devMenuShow: false })
        wx.showToast({ title: '已退出（未登录）', icon: 'none' })
        return
      }
      wx.showModal({
        title: '删除账户',
        content: '将永久删除当前小程序账户，再次微信登录将作为新用户。确定继续？',
        confirmColor: '#c1121f',
        success: (res) => {
          if (!res.confirm) return
          wx.request({
            url: API_BASE + '/wxapp/user/delete_account',
            method: 'POST',
            header: {
              'Content-Type': 'application/json;charset=UTF-8',
              openid
            },
            data: { dev_gate: DEV_GATE },
            success: (resp) => {
              const body = resp.data || {}
              if (body.code === 1) {
                clearLocalSession()
                this.setData({ devMenuShow: false })
                wx.showToast({ title: '已删除并退出', icon: 'none' })
                return
              }
              wx.showToast({ title: body.msg || '删除失败', icon: 'none' })
            },
            fail: () => {
              wx.showToast({ title: '网络错误', icon: 'none' })
            }
          })
        }
      })
    }
  }
})
