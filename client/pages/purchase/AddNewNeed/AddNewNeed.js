// pages/purchase/AddNewNeed/AddNewNeed.js
var http = require('../../../request');
var city = require('../../../city');
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
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    this.GetBrand_List()
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
        page.setData({
          types: res.data.brand_list
        })
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
          })
        }
      }
    })
  }
})