// pages/My.mod/Receivingaddress/editaddress/EditReivingAddress.js
var http = require('../../../../request');
var city = require('../../../../city');
Page({

      /**
       * 页面的初始数据
       */
      data: {
        address_id: "",
        mobile: "", //手机号 必填
        address: "", //详细地址 必填
        consignee: "", //收件人
        province: "2", //省
        city: "37", //城市
        district: "403", //区
        email:"",
        zipcode:"",
        country:"1",
        tel:"",
        sign_building:"",
        best_time:"",
        ShowCitys: [city.shop_province_list, city.city_list[2], city.district_list[37]],
        CityText: ["北京", "北京市", "东城区"],
        options: [],
        oncity: 2
      },

      /**
       * 生命周期函数--监听页面加载
       */
      onLoad: function(options) {
        console.log(options)
        var app = getApp()
        var cache = app.config.AddressCache 
        if (options["id"] != null){
          var data = {};
          for(var key in this.data){
            if (cache[key]!= null){
              data[key] = cache[key];
            }
          }
          console.log(data)
          var incity = city.findName(cache.province, cache.city, cache.district);
          console.log(incity);
          data["ShowCitys"] = [city.shop_province_list, city.city_list[incity.province_id], city.district_list[incity.cityid_id]];
          data["City"] = [incity.province, incity.cityid, incity.district];
          this.setData(data)
        }
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
      add_new_address: function() {
        var app = getApp()
        var page = this;

        if (this.data.consignee == ''){
          wx.showToast({
            title: '请输入收件人',
            icon: 'none',
            mask: true,
            success: function(res) {},
            fail: function(res) {},
            complete: function(res) {},
          });
          return;
        }
        if (this.data.mobile == '') {
          wx.showToast({
            title: '请输入联系电话',
            icon: 'none',
            mask: true,
            success: function (res) { },
            fail: function (res) { },
            complete: function (res) { },
          });
          return;
        }
        if (this.data.address == '') {
          wx.showToast({
            title: '请输入详细地址',
            icon: 'none',
            mask: true,
            success: function (res) { },
            fail: function (res) { },
            complete: function (res) { },
          });
          return;
        }
        wx.showLoading({
          title: '保存中...',
          mask: true,
          success: function(res) {},
          fail: function(res) {},
          complete: function(res) {},
        });
        setTimeout(function(){
          wx.hideLoading();
        },30000);
        http.send({
          url: app.config.ApiUrl,
          data: {
            act: "act_edit_address",
            address_id: this.data.address_id,
            submit: "新增收货地址",
            best_time: this.data.best_time,
            sign_building: this.data.sign_building,
            tel: this.data.tel,
            mobile: this.data.mobile, //手机号 必填
            zipcode: this.data.zipcode,
            address: this.data.address, //详细地址 必填
            email: this.data.email,
            consignee: this.data.consignee, //收件人
            country: this.data.country, //省份
            province: this.data.province, //市
            city: this.data.city, //城市
            district: this.data.district //区
          },
          method: "POST",
          header: {
            "content-type": "application/x-www-form-urlencoded"
          },
          success: function(res) {
            wx.hideLoading();
            wx.showToast({
              title: res.data.content,
              icon: 'success',
              duration: 2000,
              success:function(){
                if (res.data.type == "info") {
                  wx.navigateBack();
                }
              }
            })
          }
        })
      },
      bindRegionChange: function(e) {
        var options = [];
        var cityvalue = [];
        var province= null;
        var cityid= null;
        var district= null;
        for (var index in e.detail.value){
          console.log(this.data.ShowCitys[index][e.detail.value[index]])
          var address_id = 0
          if (this.data.ShowCitys[index] == null || e.detail.value[index] == null || this.data.ShowCitys[index][e.detail.value[index]] == null || this.data.ShowCitys[index][e.detail.value[index]]["region_name"] == null){
            cityvalue[index] = "";
          }else{
          cityvalue[index] = this.data.ShowCitys[index][e.detail.value[index]]["region_name"];
          options[index] = e.detail.value[index];
          address_id = this.data.ShowCitys[index][e.detail.value[index]].region_id;
          }
          if(index == 0){
            province = address_id
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
            district = address_id;
          }
        }
        this.setData({
          options: options,
          CityText: cityvalue,
          province: province,
          district: district,
          city: cityid
        })
        //var addressids = city.findID(e.detail.value[0], e.detail.value[1], e.detail.value[2]);
        //console.log(addressids)
      },
      onchange: function(e) {
        console.log(e)
        var value = e.detail.value
        var name = e.target.dataset.name
        var data = {};
        data[name] = value
        this.setData(data)
      },
      bindcolumnchange: function(e) {
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
        }
      })