//获取应用实例
const app = getApp()

Page({
  data: {
    canIUse: wx.canIUse('button.open-type.getUserInfo'),
  },
  onLoad: function () {
  },
  /**
 * 用户点击右上角分享
 */
  onShareAppMessage: function () {

  },
  //赞赏支持
  showSupport() {
    wx.previewImage({
      urls: ['https://www.xvkes.cn/imgwechat/paycode.jpg'],
      current: 'https://www.xvkes.cn/imgwechat/paycode.jpg' // 当前显示图片的http链接      
    })
  },

})
