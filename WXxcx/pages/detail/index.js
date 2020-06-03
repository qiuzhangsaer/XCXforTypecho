const tools = require("../../utils/tools.js");
const app = getApp(),
	page = app.page,
	request = app.request,
	route = app.route
var g, id

page({
	data: {
		StatusBar: app.globalData.StatusBar,
		CustomBar: app.globalData.CustomBar,
		Custom: app.globalData.Custom
	},
	/**
	 * 用户点击右上角分享
	 */
	onShareAppMessage: function () {

	},
	onLoad: function (options) {
		g = this
		id = options.id
		g.setData({
			theme: wx.getStorageSync('theme') || 'light'
		})
	},
	onShow() {
		getDetail(id);
		getComment(id);
	},
	onPullDownRefresh: function () {
		// 页面下拉
	},
	theme() {
		const theme = g.data.article.theme == 'light' ? 'dark' : 'light'
		g.setData({
			'article.theme': theme,
			theme
		})
		wx.setStorageSync('theme', theme)
	}
})

function getDetail(cid) {
	wx.showLoading({
		mask: true,
		title: '加载中...'
	})
	request({
		url: 'posts',
		data: {
			cid
		},
		success: r => {
			r = r[0]
			//将markdown内容转换为towxml数据
			let data = app.towxml.toJson(
				r.text, 'markdown'
			);

			//设置文档显示主题，默认'light'
			data.theme = wx.getStorageSync('theme') || 'light';
			//设置数据
			g.setData({
				article: data,
				info: r,
			}, () => {
				wx.hideLoading()
			});
		}
	})
}

function getComment(cid) {
	request({
		url: 'getcomment',
		data: {
			cid
		},
		success: r => {
			for(let i = 0;i<r.length;i++){
				r[i].time = tools.formatTime(r[i].created);
				r[i].avatar = 'data:image/png;base64,'+wx.getFileSystemManager().readFileSync('static/images/cmtavatar/cmtavatar_'+Math.round(Math.random()*5+1)+'.png', "base64");
				if (r[i].replays) {
					for(let re = 0;re<r[i].replays.length;re++){
						r[i].replays[re].time = tools.formatTime(r[i].replays[re].created);
						r[i].replays[re].avatar = 'data:image/png;base64,'+wx.getFileSystemManager().readFileSync('static/images/cmtavatar/cmtavatar_'+Math.round(Math.random()*5+1)+'.png', "base64");
					}
				}
			}
			g.setData({
				comment: r
			});
		}
	})
}