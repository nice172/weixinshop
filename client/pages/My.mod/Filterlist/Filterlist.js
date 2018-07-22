// pages/Filterlist/Filterlist.js
var http = require('../../../request.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    choseclass: 0,
    Lvonenum: 0,
    Lvtwonum: 0,
    list: []
  },

  mingdanList: function(show){
    var app = getApp();
    var _this = this;
    http.send({
      url: app.config.ApiUrl + '?act=mingdanlist',
      method: 'GET',
      success: function (response) {
        if(show){
          wx.hideLoading();
        }
        var list = [];
        list.push(response.data.white);
        list.push(response.data.black);
        _this.setData({
          Lvonenum: response.data.whiteTotal,
          Lvtwonum: response.data.blackTotal,
          list: list
        });
      }
    });
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var index = 0;
    if (options["index"] != null) {
      index = options["index"];
    }
    this.setData({
      choseclass: index
    });

    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    setTimeout(function(){
      wx.hideLoading();
    },30000);
    
    this.mingdanList(true);

  },
  ChangeClass: function (e) {
    var classtype = e.target.dataset.class
    this.setData({
      choseclass: classtype
    })
  },
  del: function (event){

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
    var index = event.currentTarget.dataset.index;
    var id = event.currentTarget.dataset.id;

    var _this = this;
    http.send({
      url: app.config.ApiUrl + "?act=deletemingdan",
      method: 'GET',
      data: { id: id },
      success: function (response) {
        wx.hideLoading();
        if (response.data.code == 1) {
          var list = _this.data.list[_this.data.choseclass];
          var newlist = [];
          for (var i in list) {
            if (i != index) {
              newlist.push(list[i]);
            }
          }
          var Lvonenum = _this.data.Lvonenum;
          var Lvtwonum = _this.data.Lvtwonum;
          if (_this.data.choseclass){
            Lvtwonum--;
          }else{
            Lvonenum--;
          }
          var All = _this.data.list;
          All[_this.data.choseclass] = newlist;
          _this.setData({
            list: All,
            Lvonenum: Lvonenum,
            Lvtwonum: Lvtwonum
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
  addBtn: function(){
    wx.navigateTo({
      url: '../../mingdan/index?type=' + this.data.choseclass,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
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
    var _this = this;
    setInterval(function () {
      var cache = wx.getStorageSync('update_mingdan');
      if (cache) {
        wx.removeStorageSync('update_mingdan');
        _this.mingdanList(false);
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
  
  }
})