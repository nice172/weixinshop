// pages/Login/Login.js
var http = require('../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    username:null,
    password:null
  },
  editusername: function (e) {
    console.log(e)
    this.setData({
      username: e.detail.value
    })
  }, 
  editpassword: function (e) {
    console.log(e)
    this.setData({
      password: e.detail.value
    })
  },
  Login:function(){
    var app = getApp()
    var page = this;
    if (!this.data.username) {
      wx.showToast({
        title: "请输入手机号!",
        icon: 'none',
        duration: 2000
      })
      return;
    }
    if (!this.data.password) {
      wx.showToast({
        title: "请输入密码!",
        icon: 'none',
        duration: 2000
      })
      return;
    }
    http.send({
      url: app.config.ApiUrl,
      data: {
        username: this.data.username,
        password: this.data.password,
        act: "act_login",
        back_act: "",
        submit: ""
      },
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      success: function (res) {
        wx.showToast({
          title: res.data.content,
          icon: 'none',
          duration: 2000
        });
        if (res.data.type == "info"){
          if (res.data.links['user_id']){
            wx.setStorageSync('parent_id', res.data.links['user_id']);
          }
          app.globalUserInfo = true;
          wx.navigateBack();
        }
      }
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