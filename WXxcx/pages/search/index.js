// pages/search/index.js
const app = getApp(),
	page = app.page,
	request = app.request,
	route = app.route
var that,keyword
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
		}
	})
}