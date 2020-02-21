//app.js
const Towxml = require('/towxml/main');
App({
  Data: {
    userInfo: null
  },
  towxml: new Towxml(),
  onLaunch: function () {
    wx.getSystemInfo({
      success: e => {
        this.globalData.StatusBar = e.statusBarHeight;
        let custom = wx.getMenuButtonBoundingClientRect();
        this.globalData.Custom = custom;
        this.globalData.CustomBar = custom.bottom + custom.top - e.statusBarHeight;
      }
    })
  },
  globalData: {
    userInfo: null,
  },
  system: {},
  config: {
    url: 'https://xxx.xxx.xxx',
    apisec: 'xxx'
  },
  page: require('utils/page.js'),
  request: require('utils/tools.js').request,
  route: require('utils/route.js'),
  towxml: new Towxml()
})