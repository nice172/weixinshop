// pages/My.mod/Mypurchase/Mypurchase.js
var http = require('../../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    purchases:[],
    show: true
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      show: true
    });
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    this.refresh_list();
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
    this.setData({
      show: true
    });
    // var _this = this;
    //   setInterval(function(){
    //     try {
    //       var cache = wx.getStorageSync('purchase_id');
    //       if (cache) {
    //         var purchases = _this.data.purchases;
    //         for (var i in purchases) {
    //           if (purchases[i]['purchase_id'] == cache.purchase_id) {
    //             var current = purchases[i];
    //             current.name = cache['name'];
    //             current.brand = cache['brand'];
    //             current.cate = cache['cate'];
    //             current.pur_num = cache['pur_num'];
    //             purchases[i] = current;
    //           }
    //         }
    //         _this.setData({
    //           purchases: purchases
    //         });
    //         wx.removeStorageSync('purchase_id');
    //       }
    //     } catch (e) {
          
    //     }
    //   },1000);
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    this.setData({
      show: false
    });
    var _this = this;
    setInterval(function () {
      var cache = wx.getStorageSync('purchase_id');
      if (cache) {
        wx.removeStorageSync('purchase_id');
        _this.refresh_list();
      }
    }, 1000);
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

  deleteFunc: function(event){
    wx.showLoading({
      title: '删除中...',
      mask: true,
      success: function (res) { },
      fail: function (res) { },
      complete: function (res) { },
    });
    setTimeout(function () {
      wx.hideLoading();
    }, 30000);
    var app = getApp();
    var index = event.target.dataset.index;
    var id = event.target.dataset.id;
    var _this = this;
    http.send({
      url: app.config.ApiUrl +"?act=purchase_delete",
      method:'GET',
      data:{id: id},
      success: function(response){
        wx.hideLoading();
        if(response.data.code == 1){
          var purchases = _this.data.purchases;
          var newpurchases = [];
          for (var i in purchases) {
            if (i != index) {
              newpurchases.push(purchases[i]);
            }
          }
          _this.setData({
            purchases: newpurchases
          });
        }
        wx.showToast({
          title: response.data.msg,
          icon: 'none',
          mask: true
        });
      }
    });
  },
  updateFunc: function (event){
    var index = event.target.dataset.index;
    var id = event.target.dataset.id;
    wx.navigateTo({
      url: '../../editpurchase/edit?id='+id,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
  },

  refresh_list:function(){
    setTimeout(function(){
      wx.hideLoading();
    },30000);
    var app = getApp()
    var page = this;
    //获取热门推荐
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "purchase_list"
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        
        if(page.data.show){
          wx.hideLoading();
        }
        if (res.data.code == 20001) {
          
          wx.showToast({
            title: '请先登录用户',
            icon: 'none',
            mask: true,
            success: function (res) { },
            fail: function (res) { },
            complete: function (res) { },
          });
          setTimeout(function () {
            wx.navigateTo({
              url: '../../Login/Login',
              success: function (res) { },
              fail: function (res) { },
              complete: function (res) { },
            });
          }, 1500);
          return;
        }
        page.setData({
          purchases:res.data
        })
      }
    })
  }

})