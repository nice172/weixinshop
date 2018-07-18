// pages/SearchView/SearchView.js
var http = require('../../request');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    alltypes: ['分类'],
    attrValueAll: [],
    attrValue: [],
    leftMenu: [],
    categoryList: [],
    BrandList: [],
    selected: 0,
    chosetype: "分类",
    allvalues: {
      "分类": []
    },
    chosevalue: {},
    cateIsShow: true,
    brandIsShow: false,
    attrIsShow: false,
    catid: 695,
    selectedCategory:'',
    selectedBrand: '',
    selectedAttr: [],
    cateIndex: 0,
    BrandIndex: 0,
    attrIndex: 0,
    keywords: ''
  },

  clickCate: function(e){
      this.setData({
        cateIndex: e.target.dataset.index,
        selectedCategory: e.target.dataset.cid
      });
  },
  clickBrand: function(e){
      this.setData({
        BrandIndex: e.target.dataset.index,
        selectedBrand: e.target.dataset.brand_id
      });
  },
  clickAttr: function (e){
    var selectedAttr = this.data.selectedAttr;
    var index = e.target.dataset.attrid;
    var flag = true;
    for(var i in selectedAttr){
      if(selectedAttr[i] == index) {
        flag = false;
        break;
      }
    }
    if(!flag) return;
    selectedAttr.push(index);
    this.setData({
      attrIndex: e.target.dataset.index,
      selectedAttr: selectedAttr
    });
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.refresh_categories();
  },
  chosetype: function (e) {
    console.log(this.data)
    this.setData({
      selected: e.target.dataset.index
    })
  },

  //切换分类
  changeMenu: function(event){
    var index = event.target.dataset.index;
    this.setData({selected: index});
    if (index == 0){
        this.setData({
          cateIsShow: true,
          brandIsShow: false,
          attrIsShow: false,
        });
    }else if(index == 1){
      this.setData({
        cateIsShow: false,
        brandIsShow: true,
        attrIsShow: false,
      });
    }else{
      var valueList = this.data.attrValueAll[index];
      var selectedAttr = this.data.selectedAttr;
      var currentIndex = 0;
      for (var j in selectedAttr){
        var flag = false;
        for(var idx in valueList){
          if (idx == selectedAttr[j]){
              flag = true;
              currentIndex = idx;
              break;
            }
        }
        if(flag) break;
      }
      this.setData({
        cateIsShow: false,
        brandIsShow: false,
        attrIsShow: true,
        attrIndex: currentIndex,
        attrValue: valueList
      });
    }
  },

  getKeywords: function(e){
    //console.log(e);
    var keywords = e.detail.value;
    this.setData({
      keywords: keywords
    });
  },

  searchClick: function(e){
    // console.log(this.data);
    if (!this.data.selectedCategory){
      wx.showToast({
        title: '请至少选择一个分类',
        icon:'none',
      });
      return;
    }
    var selectedAttr = this.data.selectedAttr;
    var filter_attr = '';
    for (var i = 0; i < selectedAttr.length; i++){
      filter_attr += selectedAttr[i]+'.';
    }
    if (filter_attr){
      filter_attr = filter_attr.substring(0, filter_attr.length - 2);
    }
    wx.navigateTo({
      url: '../List/list?cid=' + this.data.selectedCategory + '&brand=' + this.data.selectedBrand + '&filter_attr=' + filter_attr + '&keywords=' + this.data.keywords,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    
  },

  chosevaule: function (e) {
    console.log(e)
    this.data.chosevalue[this.data.chosetype] = e.target.dataset.index;
    this.setData({
      chosevalue: this.data.chosevalue
    })
    if (this.data.chosetype == "分类") {
      this.setData({
        catid: e.target.dataset.index
      })
      this.refresh_categories()
    }
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

  },
  refresh_categories: function () {
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function(res) {},
      fail: function(res) {},
      complete: function(res) {},
    });
    setTimeout(() => {
      wx.hideLoading();
    }, 30000);
    var app = getApp()
    var page = this;
    http.send({
      url: app.config.ApiUrl + '?act=get_cat_info&id=' + this.data.catid,
      data: {},
      method: "GET",
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      success: function (res) {
        wx.hideLoading();
        var data = res.data;
        //console.log(data);
        var leftMenu = [];
        var attrValue = [];
        var attrValueAll = [];
        var categoryList = [] ,BrandList = [];
        for(var i in data){
           leftMenu.push(data[i]['attr_name']);
          //  if(i == 0){
          //    attrValue.push(data[i]['attrs']);
          //  }
           if(data[i]['type'] && data[i]['type'] == 'cate'){
             categoryList.push(data[i]['attrs']);
           }else if(data[i]['type'] && data[i]['type'] == 'brand'){
             BrandList.push(data[i]['attrs']);
           }
           attrValueAll.push(data[i]['attrs']);
        }
        page.setData({
          leftMenu: leftMenu,
          //attrValue: attrValue,
          categoryList: categoryList,
          BrandList: BrandList,
          attrValueAll: attrValueAll
        });
      }
    })
  }
})