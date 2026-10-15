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

function persistLifeDataLogout() {
  try {
    const tmp = wx.getStorageSync('lifeData') || {}
    tmp.userInfo = {}
    tmp.isLogin = false
    wx.setStorageSync('lifeData', tmp)
  } catch (e) {}
  try {
    wx.removeStorageSync('vcode')
  } catch (e) {}
}

/** custom-tab-bar 与主包 Vue 实例隔离，需写 storage + 同步已打开页的 $vm */
function clearLocalSession() {
  persistLifeDataLogout()
  const app = getAppSafe()
  const rootVm = (app && (app.$vm || app)) || null
  if (rootVm && rootVm.$store) {
    rootVm.$store.commit('$uStore', { name: 'userInfo', value: {} })
    rootVm.$store.commit('$uStore', { name: 'isLogin', value: false })
  } else if (rootVm && rootVm.$u && rootVm.$u.vuex) {
    rootVm.$u.vuex('userInfo', {})
    rootVm.$u.vuex('isLogin', false)
  }
  try {
    const pages = getCurrentPages() || []
    pages.forEach((p) => {
      const vm = p.$vm
      if (!vm || !vm.$u || !vm.$u.vuex) return
      vm.$u.vuex('userInfo', {})
      vm.$u.vuex('isLogin', false)
    })
  } catch (e) {}
}

/** TabBar 与主包隔离，openid 以 lifeData 为准，再尝试 $vm.$store */
function getOpenid() {
  try {
    const life = wx.getStorageSync('lifeData') || {}
    const u = life.userInfo || {}
    if (u.openid) return String(u.openid)
  } catch (e) {}
  const app = getAppSafe()
  const rootVm = app && (app.$vm || app)
  const st = rootVm && rootVm.$store && rootVm.$store.state
  const u = (st && st.userInfo) || {}
  return u.openid ? String(u.openid) : ''
}

const ME_TAB_INDEX = 4
const DEV_GATE = '110'
const ME_LONG_PRESS_MS = 2000

function tabIndexFromEvent(e) {
  const raw = e.currentTarget.dataset.index
  const n = parseInt(raw, 10)
  return Number.isNaN(n) ? -1 : n
}

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
    },
    detached() {
      this._clearMeLongPressTimer()
    }
  },
  methods: {
    noop() {},
    _clearMeLongPressTimer() {
      if (this._meLongPressTimer) {
        clearTimeout(this._meLongPressTimer)
        this._meLongPressTimer = null
      }
    },
    onTabTouchStart(e) {
      const index = tabIndexFromEvent(e)
      if (index !== ME_TAB_INDEX) return
      this._meLongPressFired = false
      this._clearMeLongPressTimer()
      this._meLongPressTimer = setTimeout(() => {
        this._meLongPressTimer = null
        this._meLongPressFired = true
        try {
          wx.vibrateShort({ type: 'light' })
        } catch (err) {}
        this.setData({
          devPwdShow: true,
          devPwdInput: '',
          devPwdFocus: true
        })
      }, ME_LONG_PRESS_MS)
    },
    onTabTouchEnd(e) {
      const index = tabIndexFromEvent(e)
      if (index === ME_TAB_INDEX) {
        this._clearMeLongPressTimer()
      }
    },
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
      const index = tabIndexFromEvent(e)
      const path = e.currentTarget.dataset.path
      if (index === ME_TAB_INDEX && this._meLongPressFired) {
        this._meLongPressFired = false
        return
      }
      if (this.data.selected === index) return
      writeTabIndex(index)
      wx.switchTab({ url: path })
      this.setData({
        selected: index,
        animOn: this.data.selected >= 0
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
      wx.switchTab({ url: '/pages/user/index' })
    },
    doDeleteAccount() {
      const openid = getOpenid()
      if (!openid) {
        wx.showToast({ title: '未获取到登录态，无法删除', icon: 'none' })
        return
      }
      wx.showModal({
        title: '删除账户',
        content: '将永久删除当前小程序账户，再次微信登录将作为新用户。确定继续？',
        confirmColor: '#c1121f',
        success: (res) => {
          if (!res.confirm) return
          wx.showLoading({ title: '处理中…', mask: true })
          wx.request({
            url: API_BASE + '/wxapp/user/delete_account',
            method: 'POST',
            header: {
              'Content-Type': 'application/json;charset=UTF-8',
              openid: openid
            },
            data: { dev_gate: DEV_GATE, openid: openid },
            success: (resp) => {
              wx.hideLoading()
              let body = resp.data || {}
              if (typeof body === 'string') {
                try {
                  body = JSON.parse(body)
                } catch (e) {
                  body = {}
                }
              }
              if (body.code === 1) {
                clearLocalSession()
                this.setData({ devMenuShow: false })
                wx.showToast({ title: '已删除并退出', icon: 'none' })
                wx.switchTab({ url: '/pages/user/index' })
                return
              }
              wx.showToast({ title: body.msg || '删除失败', icon: 'none' })
            },
            fail: () => {
              wx.hideLoading()
              wx.showToast({ title: '网络错误', icon: 'none' })
            }
          })
        }
      })
    }
  }
})
