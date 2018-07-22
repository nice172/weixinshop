// pages/purchase/AddNewNeed/AddNewNeed.js
var http = require('../../request');
var city = require('../../city');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    types: [],
    tyeps_index: 0,
    ShowCitys: [city.shop_province_list, city.city_list[2], city.district_list[37]],
    CityText: ["北京", "北京市", "东城区"],
    options: [],
    oncity: 2,
    name:"",
    pur_num:"",
    brand:"",
    cate:"",
    country:"1",
    province:"2",
    city:"37",
    district:"403",
    address:"",
    content:"",
    status:"1",
    submit:"确认修改",
    purchase_id:null,
    data:{},
    model: [],
    model_index: 0
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.setData({
      purchase_id: options.id
    });
    var app = getApp();
    var _this = this;
    wx.showLoading({
      title: '加载中...',
      mask: true,
      success: function (res) { },
      fail: function (res) { },
      complete: function (res) { },
    });
    setTimeout(() => {
      wx.hideLoading();
    }, 30000);
    http.send({
      url: app.config.ApiUrl+'?act=getpurchase',
      method:'GET',
      data:{id: options.id},
      success: function(response){
        if(response.data.code == 1){
          var provinceText = '';
          for (var i in city.shop_province_list){
            if (city.shop_province_list[i]['region_id'] == response.data.data.province){
              provinceText = city.shop_province_list[i]['region_name'];
              break;
            }
          }
          var city_list = city.city_list[response.data.data.province];
          var cityText = '';
          for (var i in city_list) {
            if (city_list[i]['region_id'] == response.data.data.city) {
              cityText = city_list[i]['region_name'];
              break;
            }
          }
          var district_list = city.district_list[response.data.data.city];
          var districtText = '';
          for (var i in district_list) {
            if (district_list[i]['region_id'] == response.data.data.district) {
              districtText = district_list[i]['region_name'];
              break;
            }
          }
          _this.setData({
            province: response.data.data.province,
            city: response.data.data.city,
            district: response.data.data.district,
            data: response.data.data,
            name: response.data.data.name,
            pur_num: response.data.data.pur_num,
            cate: response.data.data.cate,
            address: response.data.data.address,
            content: response.data.data.content,
            brand: response.data.data.brand,
            CityText: [provinceText, cityText, districtText]
          });
        }else{
          wx.showToast({
            title: response.data.msg,
            icon: 'none',
            mask: true,
            success: function(res) {},
            fail: function(res) {},
            complete: function(res) {},
          });
        }
      }
    });
    this.GetBrand_List();
  },
  bindRegionChange:function(e){
    console.log(e)
    this.setData({
      City: e.detail.value
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

  changeModel: function (e) {
    var index = e.detail.value;
    var cate = '';
    for (var i in this.data.model) {
      if (index == i) {
        cate = this.data.model[i]['goods_attr_val'];
        break;
      }
    }
    this.setData({
      model_index: index,
      cate: cate
    });
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
  GetBrand_List:function(){
    var app = getApp()
    var page = this;
    http.send({
      url: app.config.ApiUrl,
      data: {
        act: "brand_list"
      },
      success: function (res) {
        wx.hideLoading();
        var type_index = 0;
        for (var i in res.data.brand_list){
          if (res.data.brand_list[i]['brand_name'] == page.data.brand){
            type_index = i;
            break;
          }
        }

        var model_index = 0;
        for (var i in res.data.model) {
          if (res.data.model[i]['goods_attr_val'] == page.data.cate) {
            model_index = i;
            break;
          }
        }

        page.setData({
          tyeps_index: type_index,
          types: res.data.brand_list,
          model: res.data.model,
          model_index: model_index
        });
      }
    })
  },
  onchange: function (e) {
    console.log(e)
    var value = e.detail.value
    var typename = e.currentTarget.dataset.typename
    var data = {};
    data[typename] = value
    console.log(data)
    this.setData(data)
  },
  bindcolumnchange: function (e) {
    console.log(e)
    if (e.detail.column == 0) {
      this.setData({
        ShowCitys: [city.shop_province_list, city.city_list[city.shop_province_list[e.detail.value].region_id], city.district_list[city.city_list[city.shop_province_list[e.detail.value].region_id][0].region_id]],
        oncity: city.shop_province_list[e.detail.value].region_id
      })
    }
    if (e.detail.column == 1) {
      this.setData({
        ShowCitys: [city.shop_province_list, city.city_list[this.data.oncity], city.district_list[city.city_list[this.data.oncity][e.detail.value].region_id]]
      })
    }
  },
  bindRegionChangeAddress: function (e) {
    var cityvalue = [];
    var options = [];
    var province = null;
    var cityid = null;
    var district = null;
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
  addNewNeed:function(){
    wx.showLoading({
      title: '正在提交中...',
      mask: true
    });
    var app = getApp()
    var page = this;
    http.send({
      url: app.config.ApiUrl, 
      method: "POST",
      header: {
        "content-type": "application/x-www-form-urlencoded"
      },
      data: {
        act: "act_edit_purchase",
        name: this.data.name,
        pur_num: this.data.pur_num,
        brand: this.data.types[this.data.tyeps_index].brand_name,
        cate: this.data.cate,
        country: this.data.country,
        province: this.data.province,
        city: this.data.city,
        district: this.data.district,
        address: this.data.address,
        content: this.data.content,
        status: this.data.status,
        submit: this.data.submit,
        purchase_id: this.data.purchase_id
      },
      success: function (res) {
        wx.hideLoading();
        if (res.data.code == 0) {
          wx.showToast({
            title: res.data.msg,
            icon: 'none',
            duration: 2000
          })
          return;
        }
        if(res.data.type == "error"){
          wx.showToast({
            title: res.data.content,
            icon: 'none',
            duration: 2000
          })
        }
        if (res.data.type == "info") {
          wx.showToast({
            title: res.data.content,
            icon: 'success',
            duration: 2000
          });
          wx.setStorageSync('purchase_id', {
            purchase_id: page.data.purchase_id,
            name: page.data.name,
            brand: page.data.brand,
            pur_num: page.data.pur_num,
            cate: page.data.cate
          });
          setTimeout(() => {
            wx.navigateBack({
              delta: 1,
            });
          },2000);
        }
      }
    })
  }
})