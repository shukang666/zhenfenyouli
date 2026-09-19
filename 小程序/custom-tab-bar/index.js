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

Component({
  options: {
    styleIsolation: 'shared'
  },
  data: {
    selected: -1,
    animOn: false,
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
    }
  }
})
