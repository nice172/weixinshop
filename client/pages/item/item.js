// pages/item/item.js
var http = require('../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
      current:0,
      isDetail:"tabs_item_on",
      isBuyRecord:"",
      id:0,
      title:"未获取",
      loadimg:"/images/defaultpic.jpeg",
      imgs:null,
      price:"未获取",
      prarm:{//商品参数
      品牌:"建涛",
      型号:"X-1",
      板厚:"14mm"
      }
  },

  home: function(){
      wx.navigateBack({
        delta: 1
      });
  },

  add_to_cart:function(){
    var app = getApp()
    var page = this;
    var goods = JSON.stringify({ "quick": 1, "spec": [], "goods_id": this.data.id, "number": "1", "parent": 0 })
    http.send({
      url: app.config.ApiUrl + '?act=add_to_cart',
      data: {
        goods: goods
      },
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded",
      },
      success: function (res) {
        console.log(res)
        wx.setStorageSync("sessionid", res.header["Set-Cookie"])
        if (res.data.error == 0) {
          wx.showToast({
            title: "加入购物车成功",
            icon: 'success',
            duration: 2000
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none',
            duration: 2000
          })
        }
      }
    })
  },

  Buy_to_cart: function () {
    var app = getApp()
    var page = this;
    var goods = JSON.stringify({ "quick": 1, "spec": [], "goods_id": this.data.id, "number": "1", "parent": 0 })
    http.send({
      url: app.config.ApiUrl + '?act=add_to_cart',
      data: {
        goods: goods
      },
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      success: function (res) {
        if (res.data.error == 0) {
          wx.navigateTo({
            url: '/pages/My.mod/ShoppingCart/ShoppingCart',
          })
        } else {
          wx.showToast({
            title: res.data.message,
            icon: 'none',
            duration: 2000
          })
        }
      }
    })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    console.log(options);
    var app = getApp()
    var page = this;
    
    var id = options.id;
    console.log(id)
    this.setData({
      title: options.title,
      loadimg: options.img,
      price: options.price,
      id: options.id
    })
    //获取Head头商家数
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "good",
        id: options.id
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        console.log(res);
        var specification = res.data.specification["属性"]
        var prarm = {};
        //对属性进行转换
        for (var key in specification){
          var obj = specification[key]
          prarm[obj.name] = obj.value
        }
        page.setData({
          prarm: prarm,
          loadimg: app.config.ImageRoot + res.data.goods.goods_img
        });

      }
    })
  },
  TabsChangeD:function(e){
    this.setData({
      current:0
    })
  },
  TabsChangeB: function (e) {
    this.setData({
      current:1
    })
  },
  setTabsChange:function(e){
    var current = e.detail.current
    console.log(e)
    if (current == 0){
      this.setData({
        isDetail: "tabs_item_on",
        isBuyRecord: ""
      })
    }else{
      this.setData({
        isDetail: "",
        isBuyRecord: "tabs_item_on"
      })
    }
  },
  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

    wx.setTopBarText({
      text: this.data.title
    })
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