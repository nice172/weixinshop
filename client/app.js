//app.js
var qcloud = require('./vendor/wafer2-client-sdk/index')
var config = require('./config')

App({
  config: config,
  onLaunch: function (options) {
    qcloud.setLoginUrl(config.service.loginUrl);
    if (options.query.userid) {
      wx.setStorageSync('parent_id', options.query.userid);
    }
  },
  /**
 * 当小程序启动，或从后台进入前台显示，会触发 onShow
 */
  onShow: function (options) {
    if (options.query.userid){
      wx.setStorageSync('parent_id', options.query.userid);
    }
  },

  globalUserInfo: null
});