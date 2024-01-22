<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title></title>
<meta name="description" content="" />
<link rel="stylesheet" href="<?php echo $url;?>data/css/index.css">
<link rel="stylesheet" href="<?php echo $url;?>data/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo $url;?>data/css/amazeui.two.css"/>
<link rel="stylesheet" href="<?php echo $url;?>data/css/two.css">

</head>
<body>
<div class="swipe">
    <ul id="slider">
        <?php foreach ($ads as $item):?>
			<li onclick=""><img width="100%" src="<?php echo $item['url'];?>" alt="" /></li>
		<?php endforeach;?>
    </ul>
    <div id="pagenavi">
        <a href="javascript:void(0);" class="active">1</a>
        <a href="javascript:void(0);">2</a>
        <a href="javascript:void(0);">3</a>
    </div>
</div>
<div class="clear"></div>
<div class="title-top">
    <div class="title-top-col">
        <span>新发现</span>
        <a href="type/new">更多最新上线商品></a>
    </div>
</div>
<div class="am-img-top">
    <ul class="am-avg-sm-2 am-avg-md-4 am-avg-lg-6 am-margin gallery-list">        
        <?php foreach ($new as $item):?>
			<li onclick="chs()">
            <div class="am-img-border">
                <a href="<?php echo $item['goods_id'];?>">
                	<input type="hidden" id = "<?php echo $item['goods_id'];?>" value="<?php echo $item['goods_id'];?>">
                    <div class="img-bg"><img class="am-img-thumbnail am-img-bdrs" src="<?php echo $item['goods_thumb']?>" alt=""/></div>
                    <div class="gallery-title"><?php echo $item['goods_name']?></div>
                    <div class="gallery-desc"><span style="color:#c8265d"><?php echo $item['shop_price']?></span></div>
                </a>
            </div>
        	</li>
		<?php endforeach;?>
    </ul>
</div>
<div class="clear"></div>
<div class="title-top">
    <div class="title-top-col">
        <span>热销产品</span>
        <a href="type/hot">更多热销产品></a>
    </div>
</div>
<div class="am-img-top">
    <ul class="am-avg-sm-2 am-avg-md-4 am-avg-lg-6 am-margin gallery-list">        
        <?php foreach ($hot as $item):?>
			<li id = 'goods_id'>
            <div class="am-img-border">
                <a href="<?php echo $item['goods_id'];?>">
                	<input type="hidden" id = "<?php echo $item['goods_id'];?>" value="<?php echo $item['goods_id'];?>">
                    <div class="img-bg"><img class="am-img-thumbnail am-img-bdrs" src="<?php echo $item['goods_thumb']?>" alt=""/></div>
                    <div class="gallery-title"><?php echo $item['goods_name']?></div>
                    <div class="gallery-desc"><span style="color:#c8265d"><?php echo $item['shop_price']?></span></div>
                </a>
            </div>
        	</li>
		<?php endforeach;?>
    </ul>
</div>
<div class="clear"></div>
<div class="title-top">
    <div class="title-top-col">
        <span>店长推荐</span>
        <a href="type/best">更多店长推荐产品></a>
    </div>
</div>
<div class="am-img-top">
    <ul class="am-avg-sm-2 am-avg-md-4 am-avg-lg-6 am-margin gallery-list">        
        <?php foreach ($best as $item):?>
			<li id = 'goods_id'>
            <div class="am-img-border">
                <a href="<?php echo $item['goods_id'];?>">
                	<input type="hidden" id = "id" value="<?php echo $item['goods_id'];?>">
                    <div class="img-bg"><img class="am-img-thumbnail am-img-bdrs" src="<?php echo $item['goods_thumb']?>" alt=""/></div>
                    <div class="gallery-title"><?php echo $item['goods_name']?></div>
                    <div class="gallery-desc"><span style="color:#c8265d"><?php echo $item['shop_price']?></span></div>
                </a>
            </div>
        	</li>
		<?php endforeach;?>
    </ul>
</div>

<script type="text/javascript" src="<?php echo $url;?>data/js/touchScroll.js"></script>
<script type="text/javascript" src="<?php echo $url;?>data/js/touchslider.dev.js"></script>
<script type="text/javascript">
var active=0,
	as=document.getElementById('pagenavi').getElementsByTagName('a');
	
for(var i=0;i<as.length;i++){
	(function(){
		var j=i;
		as[i].onclick=function(){
			t2.slide(j);
			return false;
		}
	})();
}

var t1=new TouchScroll({id:'wrapper','width':5,'opacity':0.7,color:'#555',minLength:20});

var t2=new TouchSlider({id:'slider', speed:600, timeout:6000, before:function(index){
		as[active].className='';
		active=index;
		as[active].className='active';
	}});

function chs(){
//	goods_id = document.getElementBy("id").innerHTML;
	/* goods_id = document.getElementsByName("id").innerHTML;
	alert(goods_id);
	return goods_id; */
	var ssss = getElementsByTagName('input');
	alert(ssss);
}
</script>
</body>
</html>