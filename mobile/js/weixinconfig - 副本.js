
var _data = { 
tokenUrl:location.href, 
t:Math.random() 
}; 
var _data2 = { 
goods:"{\"quick\":0,\"spec\":[],\"goods_id\":idstr,\"number\":\"1\",\"parent\":0}"
}; 
var _data1 = { 
goods:"{\"quick\":0,\"spec\":[],\"goods_id\":idstr,\"number\":\"1\",\"parent\":0}"
}; 
console.log(_data.tokenUrl);
var host='https://www.quickact.net/';
var _getWechatSignUrl = host+'sample/php/index.php'; 
var _gwcUrl=host+'mobile/flow.php?step=add_to_cart&luin=';
var luid=0;
$.ajax({ 
	url:_getWechatSignUrl, 
	data:_data, 
	dataType:"json", 
	success:function (o) {  
		console.log('获取数据：timestamp:'+o.timestamp+'nonceStr:'+o.nonceStr+'signature:'+o.signature+',url:'+o.url);  
		wxConfig(o.timestamp, o.nonceStr, o.signature,o.appId); 
	},
	err:function (err) {  
	} 
}); 
function wxConfig(_timestamp, _nonceStr, _signature,_appId) { 
	console.log('获取数据：' + _timestamp +'\n' + _nonceStr +'\n' + _signature); 
	wx.config({ 
	debug:false,
	appId: _appId,
	timestamp: _timestamp,
	nonceStr: _nonceStr,
	signature: _signature,
	jsApiList: ['checkJsApi','scanQRCode'] 
	}); 
} 

function scanCode(cd,uid) {
	if(uid>0)
	{
		luid=uid;
	}
  wx.scanQRCode({

  needResult:1,

  scanType: ["qrCode","barCode"],

  success:function (res) {

        console.log(res)  
        var result = res.resultStr; 
		if(cd=='3')
		{
			setTimeout(back1(result,uid),500);
		}
		else if(cd=='1')
		{
			setTimeout(backa(result,uid),500);
		}
		else
		{
			if(window.confirm('您确定要进购物车吗？\r\n点“取消”将查看商品详情')){ 
				setTimeout(backa(result,uid),500);
			}else{ 
				setTimeout(back1(result,uid),500);
			} 
		}
			
		//setTimeout(backb(result,uid),500);

   },

  error:function(res){ 
        alert(JSON.stringify(res))

        if(res.errMsg.indexOf('function_not_exist') >0){

        alert('版本过低请升级')

      }

    }
  });
}
 

function backb(res,uid){ 
	//document.location=res;
	var reg = new RegExp("(^|[?|&])"+ "id" +"=([^&]*)(&|$)");
     var r = res.substr(1).match(reg);
     if(r != null) {
     		id=unescape(r[2]);
			_data2.goods=_data1.goods.replace("idstr",id);
			var _gwcUrl2=_gwcUrl+uid;//'15602' 
			$.ajax({ 
				url:_gwcUrl2, 
				data:_data2,  
				method:'POST',
				header: {
				  "Content-Type": "application/x-www-form-urlencoded" 
				},
				success:function (o) {  
					document.location="/mobile/flow.php" + "?_r=" + Math.random();
				},
				err:function (err) {  
				alert(err);
				} 
			});  
     } else{
     		alert("扫描失败");
     } 
}


function backa(res,uid){ 
	//document.location=res;
	if(uid==0)
	{
		uid=luid;
	}
	var reg = new RegExp("(^|[?|&])"+ "id" +"=([^&]*)(&|$)");
     var r = res.substr(1).match(reg);
     if(r != null) {
     		id=unescape(r[2]);
			_data2.goods=_data1.goods.replace("idstr",id);
			var _gwcUrl2=_gwcUrl+uid;//'15602'  
			Ajax.call(_gwcUrl2, "goods={\"quick\":0,\"spec\":[],\"goods_id\":"+id+",\"number\":\"1\",\"parent\":0}", aloc, 'POST', 'string');
     } else{
     		alert("扫描失败");
     } 
}
function back1(res,uid){ 
	document.location=res; 
}
function aloc()
{
	document.location="/mobile/flow.php" + "?_r=" + Math.random();
}
 
