// pages/My.mod/Applicationsupplier.js
var http = require('../../../request');
var city = require('../../../city');
Page({

  /**
   * 页面的初始数据
   */
  data: {
      logoImg:"",
      idcardImg: "",
      region: ['广东省', '广州市', '海珠区'],
      customItem: '全部',

      suppliers_name: '',
      suppliers_desc: '',
      company_name: '',
      company_address: '',
      company_contactor: '',
      company_phone: '',
      sendLogoImg: "",
      sendIdcardImg: "",

      province: "2", //省
      city: "37", //城市
      district: "403", //区
      ShowCitys: [city.shop_province_list, city.city_list[2], city.district_list[37]],
      CityText: ["北京", "北京市", "东城区"],
      options:[],
      oncity: 2
  },

  bindRegionChange: function (e) {
    var cityvalue = [];
    var options = [];
    var province = null;
    var cityid = null;
    var district = null;
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
         if(address_id == 0){
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
  // onchange: function (e) {
  //   var value = e.detail.value
  //   var name = e.target.dataset.name
  //   var data = {};
  //   data[name] = value
  //   this.setData(data)
  // },
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
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
      
  },

  suppliers_name: function(e){
    this.setData({
      suppliers_name: e.detail.value
    });
  },
  suppliers_desc: function (e) {
    this.setData({
      suppliers_desc: e.detail.value
    });
  },
  company_name: function (e) {
    this.setData({
      company_name: e.detail.value
    });
  },
  company_address: function (e) {
    this.setData({
      company_address: e.detail.value
    });
  },
  company_contactor: function (e) {
    this.setData({
      company_contactor: e.detail.value
    });
  },
  company_phone: function (e) {
    this.setData({
      company_phone: e.detail.value
    });
  },
//提交
  confirmBtn: function(e){

    if (this.data.suppliers_name == ''){
      wx.showToast({
        title: '请输入供应商名称',
        icon: 'none',
        mask: true,
        success: function(res) {},
        fail: function(res) {},
        complete: function(res) {},
      });
      return;
    }
    if (this.data.company_name == ''){
        wx.showToast({
          title: '请输入你的个人/公司名称',
          icon: 'none',
          mask: true,
          success: function(res) {},
          fail: function(res) {},
          complete: function(res) {},
        })
      return;
    }
    if (this.data.sendLogoImg == '') {
      wx.showToast({
        title: '请上传公司Logo',
        icon: 'none',
        mask: true,
        success: function (res) { },
        fail: function (res) { },
        complete: function (res) { },
      })
      return;
    }
    if (this.data.sendIdcardImg == '') {
      wx.showToast({
        title: '请上传营业执照/身份证照片',
        icon: 'none',
        mask: true,
        success: function (res) { },
        fail: function (res) { },
        complete: function (res) { },
      })
      return;
    }
    if (this.data.company_address == '') {
      wx.showToast({
        title: '请输入详细地址',
        icon: 'none',
        mask: true,
        success: function (res) { },
        fail: function (res) { },
        complete: function (res) { },
      })
      return;
    }
    if (this.data.company_contactor == '') {
      wx.showToast({
        title: '请输入联系人',
        icon: 'none',
        mask: true,
        success: function (res) { },
        fail: function (res) { },
        complete: function (res) { },
      })
      return;
    }
    if (this.data.company_phone == '') {
      wx.showToast({
        title: '请输入联系电话',
        icon: 'none',
        mask: true,
        success: function (res) { },
        fail: function (res) { },
        complete: function (res) { },
      })
      return;
    }
    wx.showLoading({
      title: '正在提交中...',
      mask: true,
    });
    var app = getApp();
    http.send({
      url: app.config.ApiUrl + '?act=apply',
      method: 'POST',
      data: {
        city: this.data.city,
        province: this.data.province,
        district: this.data.district,
        sendIdcardImg: this.data.sendIdcardImg,
        sendLogoImg: this.data.sendLogoImg,
        suppliers_desc: this.data.suppliers_desc,
        suppliers_name: this.data.suppliers_name,
        company_address: this.data.company_address,
        company_contactor: this.data.company_contactor,
        company_phone: this.data.company_phone,
        company_name: this.data.company_name
      },
      success: function (res){
          wx.hideLoading();
          if (typeof res.data != 'object'){
            var data = JSON.parse(res.data);
          }else{
            var data = res.data;
          }
          if(data.code == 1){
              wx.showToast({
                title: data.msg,
                icon: 'none',
                mask: true,
                success: function(res) {
                    setTimeout(function(){
                      wx.navigateBack({
                        delta: 1,
                      });
                    },1500);
                }
              })
            return;
          }
          wx.showToast({
            title: data.msg,
            icon: 'none',
            mask: true,
            duration:2000,
            success: function(res) {},
            fail: function(res) {},
            complete: function(res) {},
          })
      }
    });

  },

  ChoseLogo: function () {
    var page = this;
    var app = getApp();
    wx.chooseImage({
      count: 1,
      success: function (res) {
        if (res["tempFilePaths"] != null) {
          var file = res.tempFilePaths[0];
          page.setData({
            logoImg: file
          });
          //写入Cookie
          var header = { "content-type": "multipart/form-data"};
          var cookies = wx.getStorageSync("cookies");
          if (cookies == null) {
            cookies = {};
          }
          var CookieStr = "";
          for (var name in cookies) {
            CookieStr = CookieStr + name + "=" + cookies[name] + "; "
          }
          if (header.cookie != null) {
            header.cookie = header.cookie + ";" + CookieStr;
          } else {
            header.cookie = CookieStr;
          }
          wx.uploadFile({
            url: app.config.ApiUrl+"?act=upload&type=logo",
            filePath: file,
            name: 'file',
            header: header,
            success: function(response){
              var data = JSON.parse(response.data);
              if(data.code == 0){
                  wx.showToast({
                    title: data.msg,
                    icon: 'none',
                    mask: true
                  })
              }else{
                page.setData({
                  sendLogoImg: data.path
                });
              }
            }
          });

        }
      }

    })
  },
  idcardImgClick: function(e){
    var page = this;
    var app = getApp();
    wx.chooseImage({
      count: 1,
      success: function (res) {
        if (res["tempFilePaths"] != null) {

          var file = res.tempFilePaths[0];
          page.setData({
            idcardImg: file
          });
          //写入Cookie
          var header = { "content-type": "multipart/form-data" };
          var cookies = wx.getStorageSync("cookies");
          if (cookies == null) {
            cookies = {};
          }
          var CookieStr = "";
          for (var name in cookies) {
            CookieStr = CookieStr + name + "=" + cookies[name] + "; "
          }
          if (header.cookie != null) {
            header.cookie = header.cookie + ";" + CookieStr;
          } else {
            header.cookie = CookieStr;
          }
          wx.uploadFile({
            url: app.config.ApiUrl + "?act=upload&type=idcard",
            filePath: file,
            name: 'file',
            header: header,
            success: function (response) {
              var data = JSON.parse(response.data);
              if (data.code == 0) {
                wx.showToast({
                  title: data.msg,
                  icon: 'none',
                  mask: true
                })
              } else {
                page.setData({
                  sendIdcardImg: data.path
                });
              }
            }
          });

        }
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
  onShareAppMessage: function () {

  }
})