// pages/qucikbuy.mod/parambuy/QuickParamBuy.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    types:["FR4","FR1","CEM-1","CEM-3","HB"],
    tyeps_index:0
  },
  onchange:function(e){
    console.log(e)
    var value = e.detail.value
    var typename = e.currentTarget.dataset.typename
    var  data = {};
    data[typename] = value
    this.setData(data)
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