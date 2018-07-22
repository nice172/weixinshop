var http = require('../../request.js');

Page({

  /**
   * 页面的初始数据
   */
  data: {
  
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
  
  },

  test: function(event){
      var app = getApp();
      var _this = this;
      wx.login({
        success: function(res) {
          if(res.code){
            http.send({
              url: app.config.Url + '/wechat/wechat.php?code=' + res.code,
              method:'GET',
              success: function(response){
                if (response.data.code==1){

                  http.send({
                    url: app.config.Url + '/wechat/pay.php?openid=' + response.data.data.openid,
                    method: 'GET',
                    success: function(result){
                      if(result.data.code == 1){
                        var order = result.data.order;
                        wx.requestPayment({
                          timeStamp: order.timeStamp,
                          nonceStr: order.nonceStr,
                          package: order.package,
                          signType: order.signType,
                          paySign: order.paySign,
                          success: function(res) {
                            if (res.errMsg == 'requestPayment:ok'){
                              wx.showToast({
                                title: '支付成功',
                                icon: 'none',
                                mask: true
                              })
                            }
                          },
                          fail: function(res) {
                            if (res.errMsg == 'requestPayment:fail cancel') {
                              wx.showToast({
                                title: '用户取消支付',
                                icon: 'none',
                                mask: true
                              })
                            }else{
                              wx.showToast({
                                title: '支付失败',
                                icon: 'none',
                                mask: true
                              })
                            }
                          },
                          complete: function(res) {
                            console.log(res);
                          },
                        })
                      }
                    }
                  });
                }
              }
          });
          }

        },
        fail: function(res) {},
        complete: function(res) {},
      });

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