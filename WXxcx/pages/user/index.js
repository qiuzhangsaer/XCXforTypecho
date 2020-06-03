//获取应用实例
const app = getApp(),
	page = app.page,
	request = app.request,
	route = app.route

Page({
  data: {
    canIUse: wx.canIUse('button.open-type.getUserInfo'),
  },
  onLoad: function () {},
  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },
  //赞赏支持
  showSupport() {
    wx.previewImage({
      urls: ['https://wx.xvkes.cn/paycode.jpg'],
      current: 'https://wx.xvkes.cn/paycode.jpg' // 当前显示图片的https链接      
    })
  },

})