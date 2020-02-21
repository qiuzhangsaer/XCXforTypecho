//获取应用实例
const app = getApp()

var videoAd = null;

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
  //广告支持
  showSupport() {
    videoAd = qq.createRewardedVideoAd({
      adUnitId: '你的激励视频广告id'
    });
    videoAd.onError(function (res) {
      console.log('videoAd onError', res)
    }),
      videoAd.onLoad(function (res) {
        console.log('videoAd onLoad', res)
      }),
      videoAd.onClose(function (res) {
        console.log('videoAd onClose', res)
      }),
      videoAd.load().then(() => {
        console.log('激励视频加载成功');
        videoAd.show().then(() => {
          console.log('激励视频 广告显示成功')
        }).catch(err => {
          console.log('激励视频 广告显示失败')
        })
      }).catch(err => {
        console.log('激励视频加载失败');
      })
  },

})
