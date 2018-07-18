// pages/My.mod/MyReferrals/MyReferrals.js
Page({

  /**
   * 页面的初始数据
   */
  data: {
    choseclass: 0,
    Lvonenum: 10,
    Lvtwonum: 2,
    list:[
      {
        icon:"/cache/head.png",
        name:"石头",
        time:"2018-06-08 13:50",
        monetary:"1000",
        Order:"10"
      },
      {
        icon: "/cache/head.png",
        name: "石头",
        time: "2018-06-08 13:50",
        monetary: "131021",
        Order: "102413"
      }
    ]
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    var index = 0;
    if (options["index"] != null) {
      index = options["index"];
    }
    this.setData({
      choseclass: index
    })
  },
  ChangeClass: function (e) {
    console.log(e)
    var classtype = e.target.dataset.class
    this.setData({
      choseclass: classtype
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
  refresh_list: function () {
    var app = getApp()
    var page = this;
    //获取热门推荐
    http.send({
      url: app.config.ApiUrl, //仅为示例，并非真实的接口地址
      data: {
        act: "account_log"
      },
      header: {
        'content-type': 'application/json' // 默认值
      },
      success: function (res) {
        console.log(res)
        page.setData({
          purchases: res.data
        })
      }
    })
  }
})