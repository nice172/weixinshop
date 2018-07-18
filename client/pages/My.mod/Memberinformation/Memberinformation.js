// pages/My.mod/Memberinformation/Memberinformation.js
var http = require('../../../request.js');
// var city = require('../../../city.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    userinfo: {},
    region: ['广东省', '广州市', '海珠区'],
    customItem: '全部',
    username: '',
    mobile_phone:'',
    wxuser: '',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    setTimeout(() => {
      wx.hideLoading();
    },30000);
    var app = getApp();
    var _this = this;
      http.send({
        url:app.config.ApiUrl + '?act=userinfo',
        success: function(result){
          wx.hideLoading();
          if(result.data.code==1){
            _this.setData({
              userinfo: result.data.userinfo,
              username: result.data.userinfo.user_name,
              mobile_phone: result.data.userinfo.mobile_phone,
              wxuser: result.data.userinfo.wxuser
            });
            var province = result.data.userinfo.province;
            var city = result.data.userinfo.city;
            var area = result.data.userinfo.area;
            if (province != '' && province != '0' && city != '' && city != '0' && area != '' && area != '0'){
                var currentArr = [province,city,area];
                _this.setData({
                  region: currentArr
                });
            }
          }else{
            wx.showToast({
              title: result.data.msg,
              icon: 'none',
              mask: true,
              success: function(res) {},
              fail: function(res) {},
              complete: function(res) {},
            });
          }
        }
      });
  },

  RedBtn: function(){
    if(this.data.username == ''){
      wx.showToast({
        title: '请输入用户名',
        icon: 'none',
        mask: true
      })
      return;
    }
    if (this.data.mobile_phone == '') {
      wx.showToast({
        title: '请输入手机号',
        icon: 'none',
        mask: true
      })
      return;
    }
    wx.showLoading({
      title: '提交中...',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    setTimeout(() => {
      wx.hideLoading();
    }, 30000);
    var app = getApp();
    var _this = this;
    http.send({
      url: app.config.ApiUrl+'?act=updateuser',
      method: 'POST',
      data:{
        user_name: this.data.username,
        mobile_phone: this.data.mobile_phone,
        wxuser: this.data.wxuser,
        birthday: this.data.userinfo.birthday,
        province: this.data.region[0],
        city: this.data.region[1],
        area: this.data.region[2]
      },
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
      }
    });
  },

  usernameInput: function(e){
      this.setData({
        username: e.detail.value
      });
  },
  phoneInput: function (e) {
    this.setData({
      mobile_phone: e.detail.value
    });
   },
  wxuserInput: function (e) { 
    this.setData({
      wxuser: e.detail.value
    });
  },

  bindDateChange: function(e){
    var userinfo = this.data.userinfo;
    userinfo.birthday = e.detail.value;
    this.setData({
      userinfo: userinfo
    });
  },
  bindRegionChange: function(e){
    this.setData({
      region: e.detail.value
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