// pages/My.mod/Recharge/RechargeView.js
var http = require('../../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    momney:'0.00',
    Recharge: 0
  },

  Recharge: function(e){
      this.setData({
        Recharge: e.detail.value
      });
  },

  RedBtn: function(e){
    var Recharge = this.data.Recharge;
    if (Recharge <= 0){
      wx.showToast({
        title: '充值金额不正确',
        icon: 'none',
        mask: true,
        success: function(res) {},
        fail: function(res) {},
        complete: function(res) {},
      });
      return;
    }
    wx.showLoading({
      title: '请稍后...',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    var app = getApp()
    var page = this;
    wx.login({
      success: function (res) {
        if (res.code) {
          http.send({
            url: app.config.ApiUrl + '?act=recharge&code=' + res.code,
            data: {
              amount: Recharge
            },
            method: 'POST',
            header: {
              "content-type": "application/x-www-form-urlencoded"
            },
            success: function (response) {
              if (response.data.code == 1) {

                var order = response.data.order;
                wx.requestPayment({
                  timeStamp: order.timeStamp,
                  nonceStr: order.nonceStr,
                  package: order.package,
                  signType: order.signType,
                  paySign: order.paySign,
                  success: function (res) {
                    wx.hideLoading();
                    if (res.errMsg == 'requestPayment:ok') {
                      wx.showToast({
                        title: '支付成功',
                        icon: 'none',
                        mask: true
                      });
                      
                    }
                  },
                  fail: function (res) {
                    if (res.errMsg == 'requestPayment:fail cancel') {
                      wx.showToast({
                        title: '用户取消支付',
                        icon: 'none',
                        mask: true
                      })
                    } else {
                      wx.showToast({
                        title: '支付失败',
                        icon: 'none',
                        mask: true
                      })
                    }
                    
                  },
                  complete: function (res) {
                    console.log(res);
                  },
                })


              } else if (response.data.code == '20001'){
                wx.showToast({
                  title: '请先登录用户',
                  icon: 'none'
                });
                setTimeout(() => {
                  wx.navigateTo({
                    url: '../../Login/Login',
                  })
                }, 1000);
              } else {
                wx.showToast({
                  title: response.data.msg,
                  icon: 'none',
                  mask: true
                });
              }
            }
          });
        }

      },
      fail: function (res) { },
      complete: function (res) { },
    });
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var app = getApp()
    var page = this;
    http.send({
      url: app.config.ApiUrl + '?act=user_info',
      data: {
      },
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded",
      },
      success: function (res) {
        if (res.data["info"] != null) {
          var user = res.data["info"];
          page.setData({
            momney: user['user_money']
          })
        }
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