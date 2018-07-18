// pages/List/list.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    list:{},
    querystring: '',
    uploadTime: 'DESC',
    updateTime:'DESC',
    price: 'ASC',
    page:1,
    max_page:0,
    sort:'',
    order:''
  },

  detail: function(e){
    var app = getApp();
    var goods_id = e.target.dataset.goods_id;
    var title = e.target.dataset.title;
    var price = e.target.dataset.price;
    var img = app.config.ImageRoot + e.target.dataset.img;
    if(!goods_id){
      wx.showToast({
        title: '获取商品详情失败',
        icon: 'none',
        mask: true,
        success: function(res) {},
        fail: function(res) {},
        complete: function(res) {},
      })
      return;
    }
    wx.navigateTo({
      url: '../item/item?id=' + goods_id + '&title=' + title + "&price=" + price + "&img=" + img,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    })
  },

  parchase_uppage: function(){
      var max_page = this.data.max_page;
      var sort = this.data.sort;
      var order = this.data.order;
      var query = this.data.querystring;
      if(sort){
        query += '&sort=' + sort;
      }
      if(order){
        query += '&order=' + order;
      }
      var page = this.data.page;
      if(page-1 <= 0){
        wx.showToast({
          title: '没有上一页了',
          icon: 'none',
          mask: true,
          success: function(res) {},
          fail: function(res) {},
          complete: function(res) {},
        });
        return;
      }
      if(page <= 0 || page == 1){
        page = 1;
      }else{
        page--;
      }
      this.setData({
        page:page
      });
      if(page){
        query += '&page='+page;
      }
      this.goodsList(query);
  },
  parchase_downpage: function(){
    var max_page = this.data.max_page;
    var sort = this.data.sort;
    var order = this.data.order;
    var query = this.data.querystring;
    if (sort) {
      query += '&sort=' + sort;
    }
    if (order) {
      query += '&order=' + order;
    }
    var page = this.data.page;
    if (page + 1 > max_page) {
      wx.showToast({
        title: '没有下一页了',
        icon: 'none',
        mask: true,
        success: function (res) { },
        fail: function (res) { },
        complete: function (res) { },
      });
      return;
    }
    if (page >= max_page) {
      page = max_page;
    } else {
      page++;
    }
    this.setData({
      page: page
    });
    if (page) {
      query += '&page=' + page;
    }
    this.goodsList(query);
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
      var query = '?act=search&id=' + options.cid + '&brand=' + options.brand + '&filter_attr=' + options.filter_attr + '&keywords=' + options.keywords;
      this.setData({
        querystring: query
      });
      this.goodsList(query);
  },

  goodsList: function (query){
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function (res) { },
      fail: function (res) { },
      complete: function (res) { },
    });
    setTimeout(() => {
      wx.hideLoading();
    },30000);
    var app = getApp();
    var _this = this;
    wx.request({
      url: app.config.ApiUrl + query,
      method: 'GET',
      success: function (response) {
        wx.hideLoading();
        if (response.data.code == 1) {
          //console.log(_this.data);
          _this.setData({
            list: response.data.goodsList,
            max_page: response.data.max_page
          });
        } else {
          wx.showToast({
            title: response.data.msg,
            icon: 'none',
            mask: true,
            success: function (res) { },
            fail: function (res) { },
            complete: function (res) { },
          })
        }
      }
    });
  },

//sort=goods_id&order=DESC

  uploadTimeFunc:function(){
    var order = this.data.uploadTime;
    var query = this.data.querystring;
    if(order == 'DESC'){
      query += '&sort=goods_id&order=ASC';
      this.setData({
        sort:'goods_id',
        order:'ASC',
        uploadTime: 'ASC',
        updateTime:'DESC',
        price:'ASC'
      });
    }else{
      query += '&sort=goods_id&order=DESC';
      this.setData({
        sort: 'goods_id',
        order: 'DESC',
        uploadTime: 'DESC',
        updateTime: 'DESC',
        price: 'ASC'
      });
    }
    this.goodsList(query);
  },
  //sort=shop_price&order=ASC
  priceFunc: function(){
    var order = this.data.price;
    var query = this.data.querystring;
    if (order == 'DESC') {
      query += '&sort=shop_price&order=ASC';
      this.setData({
        sort: 'shop_price',
        order: 'ASC',
        price: 'ASC',
        uploadTime: 'DESC',
        updateTime: 'DESC',
      });
    } else {
      query += '&sort=shop_price&order=DESC';
      this.setData({
        sort: 'shop_price',
        order: 'DESC',
        price: 'DESC',
        uploadTime: 'DESC',
        updateTime: 'DESC',
      });
    }
    this.goodsList(query);
  },
  //sort=last_update&order=DESC
  updateTimeFunc: function(){
    var order = this.data.updateTime;
    var query = this.data.querystring;
    if (order == 'DESC') {
      query += '&sort=last_update&order=ASC';
      this.setData({
        sort: 'last_update',
        order: 'ASC',
        price: 'ASC',
        uploadTime: 'DESC',
        updateTime: 'ASC',
      });
    } else {
      query += '&sort=last_update&order=DESC';
      this.setData({
        sort: 'last_update',
        order: 'DESC',
        updateTime: 'DESC',
        uploadTime: 'DESC',
        price: 'ASC'
      });
    }
    this.goodsList(query);
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