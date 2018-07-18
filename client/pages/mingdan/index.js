// pages/mingdan/index.js
var http = require('../../request.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
      type:0,
      time: '15:05',
      brandList:[],
      index: 0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    this.setData({
      type: options.type
    });
    wx.setNavigationBarTitle({
      title: options.type==1?'黑名单':'白名单'
    });
    var app = getApp();
    var _this = this;
    http.send({
      url: app.config.ApiUrl +'?act=mingdanbrand',
      success: function(res){
        wx.hideLoading();
        _this.setData({
          brandList: res.data.bandlist
        });
        //console.log(_this.data.brandList);
      }
    });
  },

  btn:function(e){
    wx.showLoading({
      title: '正在提交',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    var app = getApp();
    var _this = this;
      http.send({
        url: app.config.ApiUrl + '?act=mingdansend',
        method:'POST',
        data: {
          brand_id: this.data.brandList[this.data.index]['brand_id'],
          brand_name: this.data.brandList[this.data.index]['brand_name'],
          type: this.data.type
        },
        success: function(response){
            wx.hideLoading();
            wx.showToast({
              title: response.data.msg,
              icon: 'none',
              mask: true,
              success: function(res) {},
              fail: function(res) {},
              complete: function(res) {},
            })
        }
      });
  },

  bindchange: function(e){
      this.setData({
          index: e.detail.value
      });
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
  
  }
})