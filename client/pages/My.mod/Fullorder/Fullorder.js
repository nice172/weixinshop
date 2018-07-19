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
    Orders: [],
    is_show: false,
    show: []
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
    var classtype = e.target.dataset.class
    console.log(classtype)
    this.setData({
      choseclass: classtype
    });
    var typetext = 'all';
    if(classtype == 1){
      typetext = 'not_pay';
    }else if(classtype == 2){
      typetext = 'shipping_status';
    }else if(classtype == 3){
      typetext = 'order_status';
    }else if(classtype == 4){
      typetext = 'order_done';
    }else if(classtype == 5){
      typetext = 'refund_goods';
    }
    var app = getApp()
    var page = this;
    //获取热门推荐
    http.send({
      url: app.config.ApiUrl + '?act=order_list&' + typetext+'=1', //仅为示例，并非真实的接口地址
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        wx.hideLoading();
        var show = [];
        for(var i in res.data){
            show.push({
              'is_show': true
            });
        }
        page.setData({
          show: show,
          Orders: res.data
        });
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

  },
  refresh_list: function () {
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function (res) { },
      fail: function (res) { },
      complete: function (res) { },
    });
    setTimeout(function () {
      wx.hideLoading();
    }, 30000);
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
        wx.hideLoading();
        if(res.data.code == 20001){
          wx.showToast({
            title: '请先登录用户',
            icon: 'none',
            mask: true,
            success: function(res) {},
            fail: function(res) {},
            complete: function(res) {},
          });
          setTimeout(function(){
            wx.navigateTo({
              url: '../../Login/Login',
              success: function(res) {},
              fail: function(res) {},
              complete: function(res) {},
            });
          },1500);
          return;
        }
        var show = [];
        for (var i in res.data) {
          show.push({
            'is_show': true
          });
        };
        page.setData({
          show: show,
          Orders:res.data
        })
      }
    })
  },

  payment: function(event){
      wx.showToast({
        title: '调试中',
        icon: 'none',
        mask: true,
        success: function(res) {},
        fail: function(res) {},
        complete: function(res) {},
      });

      var order_id = event.target.dataset.order_id;
      console.log(order_id);

  },

  cancel: function (event){
      var app = getApp();
      var _this = this;
      var order_id = event.target.dataset.order_id;
      var index = event.target.dataset.index;
      wx.showModal({
        title: '提示',
        content: '确认取消吗?',
        success: function (res) {
          if (res.confirm) {
            wx.showLoading({
              title: '取消中...',
              mask: true,
              success: function(res) {},
              fail: function(res) {},
              complete: function(res) {},
            });
            setTimeout(() => {wx.hideLoading()},30000);
            http.send({

              url: app.config.ApiUrl + '?act=cancelorder&order_id=' + order_id,
              success: function(response){
                
                wx.hideLoading();
                wx.showToast({
                  title: response.data.msg,
                  icon: 'none',
                  mask: true,
                  success: function(res) {},
                  fail: function(res) {},
                  complete: function(res) {},
                });
                if(response.data.code == 1){
                  var show = _this.data.show;
                  show[index]['is_show'] = false;
                  _this.setData({
                    show: show
                  });
                }

              }

            });

          } else if (res.cancel) {
            console.log('用户点击取消')
          }
        }
      })
  },

  confirm: function (event){
    var app = getApp();
    var _this = this;
    var order_id = event.target.dataset.order_id;
    var index = event.target.dataset.index;
    wx.showModal({
      title: '提示',
      content: '确认收到货物了吗?',
      success: function (res) {
        if (res.confirm) {
          wx.showLoading({
            title: '确认中...',
            mask: true,
            success: function (res) { },
            fail: function (res) { },
            complete: function (res) { },
          });
          setTimeout(() => { wx.hideLoading() }, 30000);
          http.send({

            url: app.config.ApiUrl + '?act=confirm&order_id=' + order_id,
            success: function (response) {

              wx.hideLoading();
              wx.showToast({
                title: response.data.msg,
                icon: 'none',
                mask: true,
                success: function (res) { },
                fail: function (res) { },
                complete: function (res) { },
              });
              if (response.data.code == 1) {
                var show = _this.data.show;
                show[index]['is_show'] = false;
                _this.setData({
                  show: show
                });
              }

            }

          });

        } else if (res.cancel) {
          console.log('用户点击取消')
        }
      }
    })
  },

  refund: function (event){
    var app = getApp();
    var _this = this;
    var order_id = event.target.dataset.order_id;
    var index = event.target.dataset.index;
    wx.showModal({
      title: '提示',
      content: '确认退货吗?',
      success: function (res) {
        if (res.confirm) {
          wx.showLoading({
            title: '退货中...',
            mask: true,
            success: function (res) { },
            fail: function (res) { },
            complete: function (res) { },
          });
          setTimeout(() => { wx.hideLoading() }, 30000);
          http.send({

            url: app.config.ApiUrl + '?act=refundgoods&order_id=' + order_id,
            success: function (response) {

              wx.hideLoading();
              wx.showToast({
                title: response.data.msg,
                icon: 'none',
                mask: true,
                success: function (res) { },
                fail: function (res) { },
                complete: function (res) { },
              });
              if (response.data.code == 1) {
                var show = _this.data.show;
                show[index]['is_show'] = false;
                _this.setData({
                  show: show
                });
              }

            }

          });

        } else if (res.cancel) {
          console.log('用户点击取消')
        }
      }
    })
  }

})