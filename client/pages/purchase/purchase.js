// pages/purchase/purchase.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    parchase:[],
    page: 0,
    totalPage: 0,
    firstLoading: false
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
    this.loadingList(1);
  },

  parchase_uppage: function(){
    var page = this.data.page - 1;
    if(page <= 0) {
      wx.showToast({
        title: '没有上一页了',
        icon: 'none',
        mask: true
      });
      return;
    }
    this.loadingList(page);
  },

  parchase_downpage: function(){
    var page = this.data.page + 1;
    if (page > this.data.totalPage) {
      wx.showToast({
        title: '没有下一页了',
        icon: 'none',
        mask: true
      });
      return;
    }
    this.loadingList(page);
  },

  loadingList: function(page) {
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function (res) { },
      fail: function (res) { },
      complete: function (res) { },
    });
    setTimeout(() => { wx.hideLoading(); }, 30000);
    var app = getApp()
    var _this = this;
    //获取热门推荐
    wx.request({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "purchase",
        page: page
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        wx.hideLoading();
        _this.setData({
          page: page,
          parchase: res.data.list,
          totalPage: res.data.totalPage
        });
        // if(page <= 1){
        // _this.setData({
        //   page: page,
        //   parchase: res.data
        // });
        // }else{
        //   var list = _this.data.parchase;
        //   for (var i in res.data) {
        //     list.push(res.data[i]);
        //   }
        //   _this.setData({
        //     page: page,
        //     parchase: list
        //   });
        // }
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