// pages/qucikbuy.mod/parambuy/QuickParamBuy.js
var http = require('../../../request');
var city = require('../../../city');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    types:["FR4","FR1","CEM-1","CEM-3","HB"],
    tyeps_index:0,


    brandList: [],
    brandIndex: 0,

    categoryList: [],
    cateIndex: 0,

    tongList: [],
    tongIndex: 0,

    banList: [],
    banIndex: 0,

    sizeList: [],
    sizeIndex: 0,

    modelList: [],
    modelIndex: 0,

    province: "2", //省
    city: "37", //城市
    district: "403", //区
    ShowCitys: [city.shop_province_list, city.city_list[2], city.district_list[37]],
    CityText: ["北京", "北京市", "东城区"],
    options: [],
    oncity: 2,
    num: 0,
    address: '',
    username: '',
    phone: '',
  },
  onchange:function(e){
    console.log(e)
    var value = e.detail.value
    var typename = e.currentTarget.dataset.typename
    var  data = {};
    data[typename] = value
    this.setData(data)
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function (res) { },
      fail: function (res) { },
      complete: function (res) { },
    });
    setTimeout(function(){
      wx.hideLoading();
    },30000);
    var app = getApp();
    var _this = this;
    http.send({
      url: app.config.ApiUrl + '?act=quickcate',
      success: function (res) {
        wx.hideLoading();
        _this.setData({
          brandList: res.data.bandlist,
          categoryList: res.data.catelist,
          tongList: res.data.tonghou,
          banList: res.data.banhou,
          modelList: res.data.model,
          sizeList: res.data.size
        });
      }
    });

  },

  bindchange: function (event) {
    // console.log(event);
    var _this = this;
    var app = getApp();
    var typeText = event.target.dataset.type;
    var index = event.detail.value;
    switch(typeText){
      case 'cate':
        _this.setData({
          cateIndex: index
        });
      break;
      case 'brand':
        _this.setData({
          brandIndex: index
        });
      break;
      case 'model':
        _this.setData({
          modelIndex: index
        });
      break;
      case 'tong':
        _this.setData({
          tongIndex: index
        });
      break;
      case 'ban':
        _this.setData({
          banIndex: index
        });
      break;
      case 'size':
        _this.setData({
          sizeIndex: index
        });
      break;
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


  bindRegionChange: function (e) {
    var cityvalue = [];
    var options = [];
    var province = null;
    var cityid = null;
    var district = null;
    if (e.detail.value[0] == null) {
      e.detail.value[0] = 0;
    }
    if (e.detail.value[1] == null) {
      e.detail.value[1] = 0;
    }
    if (e.detail.value[2] == null) {
      e.detail.value[2] = 0;
    }
    for (var index in e.detail.value) {
      var address_id = 0
      if (this.data.ShowCitys[index] == null ||
        e.detail.value[index] == null ||
        this.data.ShowCitys[index][e.detail.value[index]] == null ||
        this.data.ShowCitys[index][e.detail.value[index]]["region_name"] == null) {
        cityvalue[index] = "";
      } else {
        cityvalue[index] = this.data.ShowCitys[index][e.detail.value[index]]["region_name"]
        options[index] = e.detail.value[index];
        address_id = this.data.ShowCitys[index][e.detail.value[index]].region_id;
      }
      if (index == 0) {
        var provinceList = city.shop_province_list[e.detail.value[index]];
        province = provinceList.region_id;
      }
      if (index == 1) {
        cityid = address_id
      }
      if (index == 2) {
        if (address_id == 0) {
          var current = city.district_list[cityid][0];
          address_id = current.region_id;
          cityvalue[index] = current.region_name;
          options[index] = 0;
        }
        district = address_id
      }
    }
    this.setData({
      options: options,
      CityText: cityvalue,
      province: province,
      district: district,
      city: cityid
    });

  },
  numInput: function (e) {
    this.setData({
      num: e.detail.value
    });
  },
  addressInput: function (e) {
    this.setData({
      address: e.detail.value
    });
  },
  usernameInput: function (e) {
    this.setData({
      username: e.detail.value
    });
  },
  phoneInput: function (e) {
    this.setData({
      phone: e.detail.value
    });
  },

  sendBtn: function () {
    var app = getApp();
    if (this.data.num <= 0) {
      wx.showToast({
        title: '请输入数量',
        icon: 'none',
        mask: true
      });
      return;
    }
    if (this.data.address == '') {
      wx.showToast({
        title: '请输入详细地址',
        icon: 'none',
        mask: true
      });
      return;
    }
    if (this.data.username == '') {
      wx.showToast({
        title: '请输入联系人',
        icon: 'none',
        mask: true
      });
      return;
    }
    if (this.data.phone == '') {
      wx.showToast({
        title: '请输入联系电话',
        icon: 'none',
        mask: true
      });
      return;
    }
    wx.showLoading({
      title: '正在提交中...',
      mask: true,
    });
    setTimeout(() => {
      wx.hideLoading();
    }, 30000);
    http.send({
      url: app.config.ApiUrl + "?act=quick&type=params",
      method: 'POST',
      data: {
        num: this.data.num,
        username: this.data.username,
        phone: this.data.phone,
        address: this.data.address,
        province: this.data.province,
        city: this.data.city,
        district: this.data.district,
        cate_name: this.data.categoryList[this.data.cateIndex]['name'],
        brand_name: this.data.brandList[this.data.brandIndex]['brand_name'],
        model: this.data.modelList[this.data.modelIndex]['goods_attr_val'],
        tong: this.data.tongList[this.data.tongIndex]['goods_attr_val'],
        ban: this.data.banList[this.data.banIndex]['goods_attr_val'],
        size: this.data.sizeList[this.data.sizeIndex]['goods_attr_val'],
      },
      success: function (response){
        wx.showToast({
          title: response.data.msg,
          icon: 'none',
        });
        if (response.data.code == 1) {
          setTimeout(() => {
            wx.navigateBack({
              delta: 1,
            });
          }, 1500);
        }
      }
    });

  },

  bindcolumnchange: function (e) {
    if (e.detail.column == 0) {
      this.setData({
        ShowCitys: [city.shop_province_list,
        city.city_list[city.shop_province_list[e.detail.value].region_id],
        city.district_list[city.city_list[city.shop_province_list[e.detail.value].region_id][0].region_id]],
        oncity: city.shop_province_list[e.detail.value].region_id
      })
    }
    if (e.detail.column == 1) {
      this.setData({
        ShowCitys: [city.shop_province_list, city.city_list[this.data.oncity], city.district_list[city.city_list[this.data.oncity][e.detail.value].region_id]]
      })
    }
  }

})