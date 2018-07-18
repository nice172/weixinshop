// pages/My.mod/Fullorder/Fullorder.js
var http = require('../../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    choseclass: 0,
    status:{
      0:"待付款"
    },
    Orders: []
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    console.log(options)
    this.refresh_list()
    var index = 0;
    if (options["index"] != null) {
      index = options["index"];
    }
    this.setData({
      choseclass: index
    })
  },
  ChangeClass: function (e) {
    var classtype = e.target.dataset.class
    console.log(classtype)
    this.setData({
      choseclass: classtype
    })
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },
  refresh_list: function () {
    var app = getApp()
    var page = this;
    //获取热门推荐
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "order_list"
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        console.log(res)
        page.setData({
          Orders:res.data
        })
      }
    })
  }
})