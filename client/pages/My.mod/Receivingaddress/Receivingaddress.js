// pages/My.mod/Receivingaddress/Receivingaddress.js
var http = require('../../../request');
var city = require('../../../city');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    // address: [{
    //   consignee: "石头",
    //   mobile: "18000000000",
    //   address: "江苏省 无锡市 滨湖区 XXXX XXX XXX18号",
    //   isdefault: true
    // }]
    address:[]
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function(options) {

  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function() {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function() {
    var app = getApp()
    var page = this;
    http.send({
      url: app.config.ApiUrl + '?act=address_list',
      data: {},
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      success: function(res) {
        console.log(res)
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
        for (var key in res.data.consignee_list) {
          var consignee = res.data.consignee_list[key];
          var address = city.findName(consignee.province, consignee.city, consignee.district)
          consignee.addressStr = address.address + " " + consignee.address;
        }
        page.setData({
          address: res.data.consignee_list
        })
      }
    })
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

  },
  Btn_SetDefault: function(e) {
    console.log(e)
    var index = e.currentTarget.dataset.index;
    this.SetDefault(this.data.address[index])
  },
  SetDefault: function(city) {
    var app = getApp()
    var page = this;
    city["act"] = "act_edit_address";
    city["is_default"] = 1;
    city["submit"] = "确认修改";
    http.send({
      url: app.config.ApiUrl,
      data: city,
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      success: function(res) {
        console.log(res)
        wx.showToast({
          title: res.data.content,
          icon: 'success',
          duration: 2000
        })
        page.onShow();
      }
    })

  },
  Btn_DeleteAddress: function(e) {
    console.log(e)
    this.DeletAddress(e.currentTarget.dataset.id)
  },
  DeletAddress: function(id) {
    var app = getApp()
    var page = this;
    http.send({
      url: app.config.ApiUrl,
      data: {
        act: "drop_consignee",
        id: id
      },
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      success: function(res) {
        console.log(res)
        wx.showToast({
          title: res.data.content,
          icon: 'success',
          duration: 2000
        })
        page.onShow();
      }
    })

  },
  editAddress: function(e) {
      console.log(e)
      var app = getApp()
      app.config.AddressCache = e.currentTarget.dataset.item;
      wx.navigateTo({
        url: '/pages/My.mod/Receivingaddress/editaddress/EditReivingAddress?id=' + app.config.AddressCache.address_id
      })
  }
})