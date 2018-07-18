var http = require('../../../request');
var city = require('../../../city');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    province: "2", //省
    city: "37", //城市
    district: "403", //区
    ShowCitys: [city.shop_province_list, city.city_list[2], city.district_list[37]],
    CityText: ["北京", "北京市", "东城区"],
    options: [],
    oncity: 2,
    files: [],
    num:0,
    address:'',
    username:'',
    phone:'',
    images: []
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

  _chooseImage: function(e){
    var _this = this;
    var pics = this.data.files;
    wx.chooseImage({
      count: 9 - pics.length,
      sizeType: ['original', 'compressed'],
      sourceType: ['album', 'camera'],
      success: function(res) {
        var oldfiles = _this.data.files;
        var Newfiles = [];
        for(var i in res.tempFilePaths){
          Newfiles.push({
            ZoomUrl: res.tempFilePaths[i]
          });
        }
        if(oldfiles.length==0){
          _this.setData({
            files: Newfiles
          });
        }else{
          var count = 9;
          var len = count - oldfiles.length;
          for(var i=0; i<len; i++){
            if (res.tempFilePaths[i]){
              oldfiles.push({
                ZoomUrl: res.tempFilePaths[i]
              });
            }
          }
          _this.setData({
            files: oldfiles
          });
        }

      },
      fail: function(res) {},
      complete: function(res) {},
    });
  },

  //多张图片上传
  uploadimg: function (data){
    var that= this,
    i=data.i ? data.i : 0,//当前上传的哪张图片
    success=data.success ? data.success : 0,//上传成功的个数
    fail=data.fail ? data.fail : 0;//上传失败的个数
    wx.uploadFile({
      url: data.url,
      filePath: data.path[i].ZoomUrl,
      header: data.header,
      name: 'file',//这里根据自己的实际情况改
      formData: null,//这里是上传图片时一起上传的数据
      success: (resp) => {
        success++;//图片上传成功，图片上传成功的变量+1
        console.log(resp)
        console.log(i);
        var resdata = JSON.parse(resp.data);
        that.data.images.push(resdata.path);
      },
      fail: (res) => {
        fail++;//图片上传失败，图片上传失败的变量+1
        console.log('fail:' + i + "fail:" + fail);
      },
      complete: () => {
        console.log(i);
        i++;//这个图片执行完上传后，开始上传下一张
        if (i == data.path.length) {   //当图片传完时，停止调用          
          console.log('执行完毕');
          console.log('成功：' + success + " 失败：" + fail);
          var app = getApp();
          http.send({
            url: app.config.ApiUrl+"?act=quick",
            method: 'POST',
            data: {
              num: that.data.num,
              username: that.data.username,
              phone: that.data.phone,
              address: that.data.address,
              province: that.data.province,
              city: that.data.city,
              district: that.data.district,
              images: that.data.images
            },
            success: function(response){
                wx.hideLoading();
                wx.showToast({
                  title: response.data.msg,
                  icon: 'none',
                });
                if(response.data.code == 1){
                  setTimeout(() => {
                    wx.navigateBack({
                      delta: 1,
                    });
                  },1500);
                }
            }
          });

        } else {//若图片还没有传完，则继续调用函数
          console.log(i);
          data.i = i;
          data.success = success;
          data.fail = fail;
          that.uploadimg(data);
        }

      }
    });
  },

  numInput: function(e){
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

  sendBtn: function(){
      var app = getApp();
      if(this.data.files.length <= 0) {
        wx.showToast({
          title: '请上传图片',
          icon: 'none',
          mask: true
        });
        return;
      }
      if(this.data.num <= 0){
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
      this.setData({
        images: []
      });
      this.uploadimg({
        url: app.config.ApiUrl + "?act=upload&type=quick",
        header: header,
        path: this.data.files
      });

      console.log('send');

  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
  
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