<?php exit;?>a:3:{s:8:"template";a:1:{i:0;s:82:"D:/phpstudy_pro/WWW/yizong1/mobile/themesmobile/yshop100com_mobile/searchindex.dwt";}s:7:"expires";i:1705945356;s:8:"maketime";i:1705941756;}<!DOCTYPE html >
<html>
<head>
<meta name="Generator" content="ECSHOP v2.7.3" />
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width">
  <title>香港漢方杏林堂  </title>
  <meta name="Keywords" content="" />
  <meta name="Description" content="" />
  <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
  <link rel="stylesheet" type="text/css" href="themesmobile/yshop100com_mobile/css/public.css"/>
<link rel="stylesheet" type="text/css" href="themesmobile/yshop100com_mobile/css/index.css"/>
  <script type="text/javascript" src="themesmobile/yshop100com_mobile/js/jquery.js"></script>
  <script type="text/javascript" src="themesmobile/yshop100com_mobile/js/jweixin-1.0.0.js"></script>
	<script type="text/javascript" src="js/jquery.json.js"></script><script type="text/javascript" src="js/transport.js"></script><body>
<div id="search_hide" class="search_hide">
<!--  <h2> <span class="close"><a href="index.php"><img src="themesmobile/yshop100com_mobile/images/close.png"></a></span>关键搜索</h2> -->
 <div id="mallSearch" class="search_mid">
        <div id="search_tips" style="display:none;"></div>
          <!-- <ul class="search-type">
          	<li  class="cur"  num="0">宝贝</li>
          	<li  num="1">店铺</li>
          </ul>	 -->
          <div class="searchdotm"> 
          <form class="mallSearch-form" method="get" name="searchForm" id="searchForm" action="search.php" onSubmit="return checkSearchForm()">
	   		<input type='hidden' name='type' id="searchtype" value="0" >
              <div class="mallSearch-input">
                <div id="s-combobox-135">
                    <span class="search-icon"></span> 
                    <input aria-haspopup="true" role="combobox" class="s-combobox-input" name="keywords" id="keyword" tabindex="9" accesskey="s" onkeyup="STip(this.value, event);" autocomplete="off"  value="商品/店铺搜索" onFocus="if(this.value=='请输入关键词'){this.value='';}else{this.value=this.value;}" onBlur="if(this.value=='')this.value='请输入关键词'" type="text">
                    <input type="submit" value="搜索" class="button"  >
                </div>
              </div>
             
            
          </form>
         </div> 
        </div>
 <!--
                            <div class="search_body">
                                <div class="search_box">
                                    <form action="search.php" method="post" id="searchForm" name="searchForm" onSubmit="return checkSearchForm()">
                                        <div>
											<select id='search_type' name="search_type" style="width:15%;">
												<option value='search'>宝贝</option>
												<option value='stores'>店铺</option>
											</select>
                                            <input class="text" type="search" name="keywords" id="keywordBox" autofocus>
                                            <button type="submit" value="搜 索"></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            -->
<div id="search_goods">
<section class="mix_recently_search"><h3><i class="search_h1"></i><span>历史搜索</span><a href="javascript:del_session_search('search_goods')" class="Delete" style="color:#999;"></a> </h3>
<ul>
</ul>
  </section>  
</div>                              
<section class="hot_search"><h3>热门搜索 <a href="javascript:del_session_search('search_goods')" class="Refresh" style="color:#999;"></a> </h3></h3>
<ul>
     <ul>
       <li>
    <a href="search.php?keywords=%E4%BF%9D%E6%B5%8E%E4%B8%B8">保济丸</a>
   </li>
      <li>
    <a href="search.php?keywords=%E7%BB%B4%E5%96%9C">维喜</a>
   </li>
      <li>
    <a href="search.php?keywords=%E6%B4%BB%E7%BB%9C%E6%B2%B9">活络油</a>
   </li>
      <li>
    <a href="search.php?keywords=%E9%99%A4%E7%96%A4%E8%86%8F">除疤膏</a>
   </li>
      <li>
    <a href="search.php?keywords=Swisse">Swisse</a>
   </li>
      <li>
    <a href="search.php?keywords=%E7%AB%A5%E5%B9%B4%E6%97%B6%E5%85%89">童年时光</a>
   </li>
      <li>
    <a href="search.php?keywords=%E6%97%A5%E6%9C%AC%E8%B5%84%E7%94%9F%E5%A0%82">日本资生堂</a>
   </li>
      <li>
    <a href="search.php?keywords=%E9%BE%99%E8%A7%92%E6%95%A3">龙角散</a>
   </li>
      <li>
    <a href="search.php?keywords=%E8%9A%AC%E5%A3%B3%E8%83%83%E6%95%A3">蚬壳胃散</a>
   </li>
      </ul>
        </section>
</div>
                        
<script>
$('#scanQRCode').click(function() {
    document.location.href="./php/sample.php";
});
$('.search-type li').click(function() {
    $(this).addClass('cur').siblings().removeClass('cur');
    $('#searchtype').val($(this).attr('num'));
});
$('#searchtype').val($(this).attr('0'));
window.onload = function(){ 
    Ajax.call('search.php?is_ajax=1&act=get_session_search_goods', '', session_search_goods , 'POST', 'JSON');
}
function session_search_goods(request){
        $("#search_goods ul").html(request);
    }
function search_key(key){
     document.getElementById('keyword').value = key;     
     document.getElementById('searchForm').submit();
}
function del_session_search(name){
     Ajax.call('search.php?is_ajax=1&act=del_session_search','name='+name, delSearchResponse, 'GET', 'JSON');
}
function delSearchResponse(result){
    if(result>0){
       $("#search_goods ul").html("");
    }
}
</script>
</body>
</html>