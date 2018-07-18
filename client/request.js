var http ={
  send:function(json){
    var success= json.success;
    var header = json.header;
    //写入Cookie
    if(header == null){
      header = {};
      json.header = header;
    }
    var cookies =wx.getStorageSync("cookies");
    if(cookies == null){
      cookies = {};
    }
    var CookieStr = "";
    for (var name in cookies){
      CookieStr = CookieStr + name + "=" + cookies[name]+"; "
    }
    if(header.cookie != null){
      header.cookie = header.cookie + ";" + CookieStr ;
    }else{
      header.cookie = CookieStr;
    }
    //监听成功事件
    json.success = function(res){
      var setcookie = res.header["Set-Cookie"];
      if (setcookie != null){
        var save_cookie = wx.getStorageSync("cookies");
        if (save_cookie == null || typeof save_cookie != "object") {
          save_cookie = {};
        }
        var cookies = setcookie.split(";");
        for(var index in cookies){
          var cookie = cookies[index];
          cookie = cookie.trim();
          cookie = cookie.replace("path=/,","");
          var Cook_index= cookie.indexOf("=")
          var name = cookie.substr(0, Cook_index);
          var value = cookie.substr(Cook_index + 1, cookie.length);
          save_cookie[name] = value;
        }
        wx.setStorageSync("cookies", save_cookie);
      }
      success(res);
    }
    return wx.request(json)
  }
}
module.exports = http;