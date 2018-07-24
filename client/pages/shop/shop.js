// pages/shop/shop.js
var http = require('../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    head_img_url: "/cache/head.png",//商家Logo

    hotip_titles: [],//热点消息

    detail_shop_brand: 0,//品牌商家数量

    detail_shop_seller: 0,//库存商家数量

    detail_shop_inventory: 0,//库存商品数量

    detail_shop_price: 0,//实际交易金额

    detail_depot_showimg: ["/cache/showpic.png"],//仓库展示图片

    imageRoot: "",

    recom_items: [
    ]
  },


  OpenItem: function (event) {
    var data = event.currentTarget.dataset;
    wx.navigateTo({
      url: '/pages/item/item?id='+data.id+'&title=' + data.title + "&price=" + data.price + "&img=" + data.img
    })

  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var app = getApp()
    var page = this;
    wx.showLoading({
      title: '加载中...',
      mask: true
    });
    setTimeout(() => {
      wx.hideLoading();
    }, 30000);
    this.setData({
      imageRoot: app.config.ImageRoot
    });
    //获取热门推荐
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "hot"
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        wx.hideLoading();
        page.setData({
          recom_items: res.data
        });
      }
    })
    //获取滚动热点
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "orders_list"
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        page.setData({
          hotip_titles: res.data
        })
      }
    })
    //获取Head头商家数
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "home_shownum"
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        wx.hideLoading();
        page.setData({
          detail_shop_brand: res.data[0],
          detail_shop_seller: res.data[1],
          detail_shop_inventory: res.data[2],
          detail_shop_price: res.data[3],
        });

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
  onShareAppMessage: function (res) {
    if (res.from === 'button') {
      // 来自页面内转发按钮
      console.log(res.target);
    }
    wx.showShareMenu({
      withShareTicket: true
    });
    var parent_id = wx.getStorageSync('parent_id');
    if (parent_id == '') {
      return {
        title: '商城',
        path: '/pages/shop/shop'
      };
    }else{
      return {
        title: '商城',
        path: '/pages/shop/shop?userid=' + parent_id
      };
    }
  }
})