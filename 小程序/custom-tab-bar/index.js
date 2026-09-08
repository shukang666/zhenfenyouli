Component({
  data: {
    selected: 0,
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
  methods: {
    setSelected(index) {
      this.setData({ selected: index })
    },
    switchTab(e) {
      const index = e.currentTarget.dataset.index
      const path = e.currentTarget.dataset.path
      if (this.data.selected === index) return
      wx.switchTab({ url: path })
    }
  }
})
