// pages/Login/Regeister.js
var http = require('../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    phone: '',
    resendtime: 0,
    isLookUser: false,
    verfy_code:null,
    password:null,
    repassword:null
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {


  },
  editPhone: function(e) {
    console.log(e)
    this.setData({
      phone: e.detail.value
    })
  },
  editPassword: function (e) {
    console.log(e)
    this.setData({
      password: e.detail.value
    })
  },
  editrePassword: function (e) {
    console.log(e)
    this.setData({
      repassword: e.detail.value
    })
  },
  editverfy_code: function (e) {
    console.log(e)
    this.setData({
      verfy_code: e.detail.value
    })
  },
  Regeister:function(){
    var app = getApp()
    var page = this;
    if (!this.data.isLookUser) {
      wx.showToast({
        title: "请详细阅读用户协议!",
        icon: 'none',
        duration: 2000
      })
      return ;
    }
    if (!this.data.phone) {
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
    if (!this.data.verfy_code) {
      wx.showToast({
        title: "请输入短信验证码!",
        icon: 'none',
        duration: 2000
      })
      return;
    }
    if (this.data.password != this.data.repassword) {
      wx.showToast({
        title: "两次密码不相同!",
        icon: 'none',
        duration: 2000
      })
      return;
    }
    var parent_id = wx.getStorageSync('parent_id');
    if(parent_id == ''){
        parent_id = 0;
    }
    
    http.send({
      url: app.config.ApiUrl +"?act=act_register" ,
      data: {
        username:this.data.phone,
        verfy_code: this.data.verfy_code,
        password: this.data.password ,
        parent_id: parent_id,
        email:"",
        confirm_password: this.data.repassword,
        agreement:1,
        act:"act_register",
        back_act:"",
        Submit:""
      },
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      success: function (res) {
        if(res.data.type == 'info'){
          wx.removeStorageSync('parent_id');
          wx.setStorageSync('parent_id', res.data.links['user_id']);
        }
        wx.showToast({
          title: res.data.content,
          icon: 'none',
          duration: 2000
        })
      }
    })
  },
  lookuser: function() {
    if (this.data.isLookUser) {
      this.setData({
        isLookUser: false
      })
    } else {
      this.setData({
        isLookUser: true
      })
    }
  },
  api_send_msg: function() {
    console.log(this)
    if (this.data.resendtime < 1) {
      var app = getApp()
      var page = this;
      var data = "mobile=" + page.data.phone + "&act=send_msg"
      //data = encodeURIComponent(data);
      var requset = http.send({
        url: app.config.Url + '/user.php?',
        data: data,
        method: "POST",
        header: {
          "content-type": "application/x-www-form-urlencoded"
        },
        success: function(res) {
          console.log(res)
          console.log(data)
          if (res.data.status == 200) {
            wx.showToast({
              title: res.data.msg,
              icon: 'success',
              duration: 2000
            })
            setTimeout(page.refreshTime, 1000)

            page.setData({
              resendtime: 60
            })
          } else {
            wx.showToast({
              title: res.data.msg,
              icon: 'none',
              duration: 2000
            })
          }
        }
      })
    }
  },
  refreshTime: function(page) {
    var resendtime = this.data.resendtime - 1
    this.setData({
      resendtime: resendtime
    })
    if (this.data.resendtime > 0) {
      setTimeout(this.refreshTime, 1000)
    }
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {},

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    console.log("onShow:" + this.data.resendtime)
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function() {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function() {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function() {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function() {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function() {

  }
})