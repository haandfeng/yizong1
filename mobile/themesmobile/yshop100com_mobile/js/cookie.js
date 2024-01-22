(function () {
  // cookie工具对象
  var cookieUtils = {};
  // cookie数据
  var data = {};
  /**
   * 设置cookie
   * @param key cookie的名称
   * @param value cookie的值
   * @param timeOfDay cookie的有效时长（单位是天）
   */
  cookieUtils.set = function (key, value, timeOfDay) {
    var date = new Date();
    date.setTime(date.getTime() + (timeOfDay * 24 * 60 * 60 * 1000));
    document.cookie = key + "=" + value + ";expires=" + date;
    // 更新cookie数据
    updateData(data)
  };
  /**
   * 获取指定cookie的值
   * @param key cookie的名称
   * @returns String cookie的值
   */
  cookieUtils.get = function(key){
    return data[key];
  }
  /**
   * 获取所有的cookie
   * @returns Object cookie数据对象
   */
  cookieUtils.getAll=function(){
    return data;
  }
  /**
   * 移除指定的cookie
   * @param key cookie的名称
   */
  cookieUtils.del = function (key) {
      var date = new Date();
      date.setTime(0);
      document.cookie = key + "=" + value + ";expires=" + date;
      updateData(data);
  }
  /**
   * 更新cookie数据对象
   * @param  obj 
   */
  function updateData(obj) {
    var cookieStr = document.cookie.replace(/\s/g,"");
    if (cookieStr.length != 0) {
      var cookies = cookieStr.split(";");
      cookies.forEach(function (element) {
        var arr = element.split("=");
        obj[arr[0]] = arr[1];
      });
    }
  }
  updateData(data);
  window.cookieUtils = cookieUtils;
 })();
 function alertmsg(msg){
		var msgboxval  = cookieUtils.get('msgbox');
		if(msgboxval==msg.length.toString()){
			
		}
		else{
			alert(msg);
			cookieUtils.set("msgbox",msg.length);
		}
 }