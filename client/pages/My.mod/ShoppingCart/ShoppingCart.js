// pages/My.mod/ShoppingCart/ShoppingCart.js
var http = require('../../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    allPrice: "0.00",
    num: 0,
    isChoseAll: false,
    carts: [
    ]
  },

  payment: function(e){
    //18223537801
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
          http.send({
            url: app.config.ApiUrl + '?act=get_address_default',
            data: {},
            method: "GET",
            header: {
              "content-type": "application/x-www-form-urlencoded"
            },
            success: function (res) {
                if(res.data.code == 0){
                    wx.showToast({
                      title: res.data.msg,
                      icon: 'none',
                      mask: true,
                      success: function(res) {},
                      fail: function(res) {},
                      complete: function(res) {},
                    });
                    setTimeout(() => {
                      wx.navigateTo({
                        url: '/pages/My.mod/Receivingaddress/Receivingaddress',
                        success: function(res) {},
                        fail: function(res) {},
                        complete: function(res) {},
                      })
                    },1500);
                }else{

                http.send({
                  url: app.config.ApiUrl + '?act=payment',
                  data:{},
                  method:'POST',
                  header: {
                    "content-type": "application/x-www-form-urlencoded"
                  },
                  success: function(response){
                    
                  }
                });


                }
            }
          });


        }else{
          wx.showToast({
            title: '请先登录用户',
            icon: 'none'
          });
          setTimeout(() => {
            wx.navigateTo({
              url: '../../Login/Login',
            })
          },1000);

        }
      }
    });
  },

  eidtNum:function(e){
    var app = getApp()
    var page = this;
    var num = e.detail.value *1;
    if(num  < 1){
      num = 1;
    }
    var onnum = num;
    console.log(e)
    var id = e.target.dataset.id;
    for (var name in page.data.carts) {
      var itemarg = page.data.carts[name].items;
      for (var index in itemarg) {
        var item = itemarg[index]
        if (item.goods_id == id) {
          var num = num -item.goods_number;
          var goods = JSON.stringify({ "quick": 1, "spec": [], "goods_id": id, "number": num, "parent": 0 })
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
                for (var name in page.data.carts) {
                  var itemarg = page.data.carts[name].items;
                  for (var index in itemarg) {
                    var item = itemarg[index]
                    if (item.goods_id == id) {
                      item.goods_number = onnum;
                      console.log(item)
                      page.setData({
                        carts: page.data.carts
                      })
                    }

                  }
                }
              }
            }
          })
        }

      }
    }
  },
  choseAll:function(){
    for (var name in this.data.carts) {
      var itemarg = this.data.carts[name].items;
      for (var index in itemarg) {
        var item = itemarg[index]
          item["isBuy"] = !this.data.isChoseAll;
      }
    }
    this.setData({
      carts: this.data.carts
    })
    this.refreshAll();
  },
  AddBuyCartItem:function(e){
    console.log(e);
    console.log(this.data.carts);
    var itemarg = this.data.carts[e.currentTarget.dataset.index].items;
    if (this.data.carts[e.currentTarget.dataset.index]["isChoseAll"] == null){
      this.data.carts[e.currentTarget.dataset.index]["isChoseAll"] = false;
    }
    for (var index in itemarg) {
      var item = itemarg[index]
      item["isBuy"] = !this.data.carts[e.currentTarget.dataset.index]["isChoseAll"];
    }
    this.setData({
      carts: this.data.carts
    })
    this.refreshAll();
  },
  ChangNum:function(e){
    console.log(e)
    var num = e.target.dataset.num;
    var id = e.target.dataset.id;
    var app = getApp()
    var page = this;
    var goods = JSON.stringify({ "quick": 1, "spec": [], "goods_id": id, "number": num, "parent": 0 })
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
        console.log(res)
        if (res.data.error == 0){
          for (var name in page.data.carts){
            var itemarg = page.data.carts[name].items;
            for (var index in itemarg){
              var item = itemarg[index]
              if (item.goods_id == id){
                item.goods_number = item.goods_number * 1 + num*1;
                console.log(item)
                page.setData({
                  carts: page.data.carts
                })
              }
             
            }
          }
        }
      }
    })
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var app = getApp()
    var page = this;
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "cart"
      },
      header: {
        'content-type': 'application/json', // 默认值,
        'cookie': wx.getStorageSync("sessionid")
      },
      success: function (res) {
        wx.setStorageSync("sessionid", res.header["Set-Cookie"])
        var goods_list = res.data.goods_list;
        var shops = {};
        for (var index in goods_list){
          var item = goods_list[index];
          var shopname = item.supplier;
          var shopary = shops[shopname];
          if (shopary == null){
            shopary = [];
            shops[shopname] = shopary;
          }
          item.goods_img = app.config.ImageRoot +  item.goods_img;
          shopary.push(item);
        }
        var prarmary = [];
        for (var name in shops){
            var shop = {
              shopname:name,
              items: shops[name]
            }
            prarmary.push(shop)
        }
        page.setData({
          carts: prarmary
        })
        console.log(goods_list)
        
      }
    })
  },
  refreshAll: function () {
    var num = 0;
    var price = 0;
    var isChoseAll = true;
    for (var cartkey in this.data.carts) {
      var cart = this.data.carts[cartkey]
      var items = cart.items;
      var isItemchose = true;
      for (var itemkey in items) {
        var cart_item = items[itemkey]
        if (cart_item["isBuy"] != null && cart_item["isBuy"]) {
          price = price + 1 * cart_item.goods_price * cart_item.goods_number;
          num++;
        }else{
          isChoseAll = false;
          isItemchose = false;
        }

      }
      cart.isChoseAll = isItemchose;
    }
    this.setData({
      allPrice: price.toFixed(2),
      num:num,
      carts:this.data.carts,
      isChoseAll: isChoseAll
    });
  },
  AddBuyItem: function (e) {
    console.log(e.currentTarget.dataset)
    var shopcart = this.data.carts[e.currentTarget.dataset.index]

    var items = shopcart.items;
    var cart_item = items[e.currentTarget.dataset.itemidx]
    console.log(cart_item)
    if (cart_item["isBuy"] != null && cart_item["isBuy"]) {
      cart_item["isBuy"] = false;
    } else {
      cart_item["isBuy"] = true
    }
    this.setData({
      carts: this.data.carts
    });
    this.refreshAll();
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