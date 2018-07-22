// pages/My.mod/MyProducts/My Products.js
var http = require('../../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    items: [],
    page:0,
    totalPage:0,
    goods_index: 0,
    goods_id: 0,
    org_shop_price: '0.00',
    org_goods_number: 0,
    shop_price: '0.00',
    goods_number: 0
  },
  updatePrice: function(e){
    this.setData({
      shop_price: e.detail.value
    });
  },
  updateNumber: function (e) {
    this.setData({
      goods_number: e.detail.value
    });
  },
  updateGoods: function(event){
      var goods_index = event.target.dataset.index;
      var shop_price = 0;
      var goods_number = 0;
      for(var i in this.data.items){
        if(goods_index == i){
          shop_price = this.data.items[i]['shop_price'];
          goods_number = this.data.items[i]['goods_number'];
          break;
        }
      }
      this.setData({
        org_shop_price: shop_price,
        org_goods_number: goods_number,
        shop_price: shop_price,
        goods_number: goods_number,
        goods_index: goods_index,
        goods_id: event.target.dataset.goods_id
      });
  },
  updateSave: function(event){
    var goods_id = this.data.goods_id;
    var goods_index = this.data.goods_index;
    if(this.data.org_shop_price == this.data.shop_price && this.data.goods_number == this.data.org_goods_number){
      this.setData({
        goods_index: goods_index,
        goods_id: 0,
      });
      return;
    }
    wx.showLoading({
      title: '更新中...',
      mask: true
    });
    setTimeout(() => {wx.hideLoading();},30000);
    var app = getApp();
    var _this = this;
    http.send({
      url: app.config.ApiUrl +'?act=update_goods',
      method: 'POST',
      data: {
        goods_id: goods_id,
        shop_price: this.data.shop_price,
        goods_number: this.data.goods_number
      },
      success: function(response){
          wx.hideLoading();
          wx.showToast({
            title: response.data.msg,
            icon: 'none',
            mask: true
          });
          if(response.data.code == 1){
            var list = _this.data.items;
            for (var i in list){
              if(goods_index == i){
                  list[i]['shop_price'] = _this.data.shop_price;
                  list[i]['goods_number'] = _this.data.goods_number;
                  break;
              }
            }
            _this.setData({
              items: list,
              goods_index: goods_index,
              goods_id: 0,
              shop_price: '0.00',
              goods_number: 0
            });
          }
      }
    });
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.refresh_list();
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
    console.log('loading');
    this.refresh_list();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {

  },

  deleteGoods: function(e){
    var app = getApp();
    var _this = this;
    var index = e.target.dataset.index;
    var goods_id = e.target.dataset.goods_id;
    wx.showModal({
      title: '提示',
      content: '确认删除吗？',
      showCancel: true,
      success: function(res) {
        if(res.confirm == true){
            wx.showLoading({
              title: '删除中...',
              mask: true,
              success: function(res) {},
              fail: function(res) {},
              complete: function(res) {},
            });
            http.send({
              url: app.config.ApiUrl + '?act=delete_goods&goods_id=' + goods_id,
              success: function(res){
                wx.hideLoading();
                wx.showToast({
                  title: res.data.msg,
                  icon: 'none',
                  mask: true
                });
                if(res.data.code == 1){
                  var items = _this.data.items;
                  var newlist = [];
                  for(var i in items){
                    if(index != i){
                      newlist.push(items[i]);
                    }
                  }
                  _this.setData({
                    items: newlist
                  });
                }
              }
            });
        }
      },
      fail: function(res) {},
      complete: function(res) {},
    })
  },

  refresh_list: function () {
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function (res) { },
      fail: function (res) { },
      complete: function (res) { },
    });
    setTimeout(() => { wx.hideLoading(); }, 30000);
    var page = this.data.page+1;
    if(page > this.totalPage) return;
    var app = getApp()
    var _this = this;
    //获取热门推荐
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "stock_list",
        page: page
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        wx.hideLoading();
        if (res.data.code == '20001') {
          wx.showToast({
            title: '请先登录用户',
            icon: 'none'
          });
          setTimeout(() => {
            wx.navigateTo({
              url: '../../Login/Login',
            })
          }, 1000);
          return;
        }
        if(page <= 1){
          _this.setData({
            page: page,
            items: res.data.goods
          });
        }else{
          var list = _this.data.items;
          for(var i in res.data.goods){
            list.push(res.data.goods[i]);
          }
          _this.setData({
            page: page,
            items: list
          });
        }
      }
    })
  }
})