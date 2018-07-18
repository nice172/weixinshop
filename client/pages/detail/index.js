// pages/detail/index.js
var http = require('../../request');
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
      id: '',
      data:null
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
      var _this = this;
      wx.showLoading({
        title: '加载中...',
        mask: true,
        success: function(res) {},
        fail: function(res) {},
        complete: function(res) {},
      });
      setTimeout(() => {
        wx.hideLoading();
      },30000);
      console.log(options);
      this.setData({id: options.id});
      http.send({
        url: app.config.ApiUrl + '?act=detail',
        method:'GET',
        data: {id: this.data.id},
        success: function(response){
          wx.hideLoading();
           var result = response.data;
           if(result.code == 1){
             _this.setData({
               data: result.data
             });
           }else{
             wx.showToast({
               title: result.msg,
               icon: 'none',
             })
           }
        }
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