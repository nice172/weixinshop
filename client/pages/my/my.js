//index.js
var http = require('../../request');
Page({
  data: {
    user: {
      surplus: 0, gender: 0, integral:0,
      username: '***', avatarUrl:''
    },
    isLogin:false
  },
  onLoad: function () {
    var page = this;
    // 查看是否授权
    wx.getSetting({
      success: function (res) {
        if (res.authSetting['scope.userInfo']) {
          // 已经授权，可以直接调用 getUserInfo 获取头像昵称
          console.log('auth ok');
          wx.getUserInfo({
            success: function (res) {
              var user = page.data.user;
              user['avatarUrl'] = res.userInfo.avatarUrl;
              page.setData({
                user:user
              });
              page.GetUserinfo();
            }
          })
        }
      }
    })
  },
  GetUserinfo(){
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
        if(res.data["info"] != null){
          app.globalUserInfo = true;
          var avatarUrl = page.data.user.avatarUrl;
          var user = res.data["info"];
          wx.setStorageSync('parent_id', user['gender']);
          user['avatarUrl'] = avatarUrl;
          page.setData({
            isLogin:true,
            user: user
          })
        }
      }
    })
  },
  bindGetUserInfo: function (e) {
    console.log(e.detail.userInfo)
    this.setData({
      isLogin: true,
      user: e.detail.userInfo
    })
  },
  //事件处理函数
  toRecharge: function () {
    // wx.navigateTo({
    //   url: '../recharge/recharge'
    // });
  },
  toRechargeRecord: function () {
    wx.navigateTo({
      url: '../recharge.record/recharge.record'
    });
  },
  toMyRecommend: function () {
    wx.navigateTo({
      url: '../my.recommend/my.recommend'
    });
  },
  toUser: function () {
    wx.navigateTo({
      url: '../user/user'
    });
  },
  toTipRecord: function () {
    wx.navigateTo({
      url: '../tip.record/tip.record'
    });
  },
  toIntegration: function () {
    wx.navigateTo({
      url: '../my.integration/my.integration'
    });
  },
  toRecommendMe: function () {
    wx.navigateTo({
      url: '../recommend.me/recommend.me'
    });
  },
  toRecommendTo: function () {
    wx.navigateTo({
      url: '../recommend.to/recommend.to'
    });
  },
  onShow: function () {
    this.GetUserinfo();
  },
  onShareAppMessage: function (res) {
  }
})
