// pages/quickbuy/qucikbuy.js
Page({

  /**
   * 页面的初始数据
   */
  data: {

  },
  OpenPrarmBuy: function () {
    wx.navigateTo({
      url: "/pages/qucikbuy.mod/parambuy/QuickParamBuy"
    })
  },
  OpenImageBuy: function () {
    wx.navigateTo({
      url: "/pages/qucikbuy.mod/imagebuy/QuickImageBuy"
    })
  },
  OpenVidioBuy: function () {
    wx.navigateTo({
      url: "/pages/qucikbuy.mod/VidioBuy/QuickVidioBuy"
    })
  },
  showMsg: function(){
    wx.showModal({
      title: '提示',
      content: '现在小程序无法上传文件',
      showCancel: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

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