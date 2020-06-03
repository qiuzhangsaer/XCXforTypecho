// pages/search/index.js
const app = getApp(),
	page = app.page,
	request = app.request,
	route = app.route
var that,keyword;
var interstitialAd = null;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    keyword:''
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    that = this;
    keyword = options.kw;
		that.setData({
			keyword : keyword
		})
		if(wx.createInterstitialAd){
　　　//1.创建广告实例
      interstitialAd = wx.createInterstitialAd({ adUnitId: '你的插屏广告id' })
　　　//3.监听广告，广告显示出来以后你要做的操作
      interstitialAd.onLoad(() => {
        console.log('onLoad event emit')
      })
　　　//其实可以不用管这个onError，它的作用是如果广告拉取失败，就提示你
      interstitialAd.onError((err) => {
        console.log('onError event emit', err)
      })
　　　//广告关闭时，触发。
      interstitialAd.onClose((res) => {
        console.log('onClose event emit', res)
      })
    }
	},

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    getSLists(keyword);
  },
  
  toInfo(e) {
		e = e.currentTarget.dataset.id
		route({
			type: 'navigate',
			url: 'detail',
			params: {
				id: e
			}
		})
	},

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  }
})

function getSLists(keyword) {
	wx.showLoading({
		mask: true,
		title: '加载中...'
	})
	request({
		url: 'search',
		data: {
			keyword
		},
		success: r => {
      //设置数据
			that.setData({
				list: r,
			}, () => {
				wx.hideLoading()
			});
			if(r != 'none'){
				setTimeout( function () {
					getAds();
				},1000)
			}
		}
	})
}

function getAds() {
	interstitialAd.show().catch((err) => {
	  console.error(err)
	})
}