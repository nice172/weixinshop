// pages/purchase/purchase.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    parchase:[
    ]
  },

  detail: function(e){
    wx.navigateTo({
      url: '../detail/index?id='+e.target.dataset.id,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var app = getApp()
    var page = this;
    //获取热门推荐
    wx.request({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "purchase"
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        console.log(res)
        page.setData({
          parchase:res.data
        })
      }
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
  
  }
})