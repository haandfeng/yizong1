<?php
class Express {
	private function getshipping($getcom)
	{
            switch ($getcom){
				case "EMS"://ecshop后台中显示的快递公司名称
                            $postcom = 'EMS';//快递公司代码
                            break;
                    case "中国邮政":
                            $postcom = 'EMS';
                            break;
                    case "申通快递":
                            $postcom = 'STO';
                            break;
                    case "圆通速递":
                            $postcom = 'YTO';
                            break;
                    case "顺丰速运":
                            $postcom = 'SF';
                            break;
                    case "天天快递":
                            $postcom = 'HHTT';
                            break;
                    case "韵达快递":
                            $postcom = 'YD';
                            break;
                    case "中通速递":
                            $postcom = 'ZTO';
                            break;
                    case "龙邦物流":
                            $postcom = 'LB';
                            break;
                    case "宅急送":
                            $postcom = 'ZJS';
                            break;
                    case "全一快递":
                            $postcom = 'UAPEX';
                            break; 
                    case "民航快递":
                            $postcom = 'MHKD';
                            break;	
                    case "亚风速递":
                            $postcom = 'YFSD';
                            break;	
                    case "快捷速递":
                            $postcom = 'DJKJWL';
                            break;	
                    case "华宇物流":
                            $postcom = 'HOAU';
                            break;	
                    case "中铁快运":
                            $postcom = 'ZTWL';
                            break;		
                            /* 修改 by www.yshop100.com start */
                    case "百世汇通":
                            $postcom = 'HTKY';
                            break; 
                    case "德邦":
                            $postcom = 'DBL';
                            break;
                            /* 修改 by www.yshop100.com end */
                    case "FedEx":
                            $postcom = 'FEDEX';
                            break;		
                    case "UPS":
                            $postcom = 'UPS';
                            break;		
                    case "DHL":
                            $postcom = 'DHL';
                            break;		
                    default:
                            $postcom = '';
                    /* case "EMS"://ecshop后台中显示的快递公司名称
                            $postcom = 'ems';//快递公司代码
                            break;
                    case "中国邮政":
                            $postcom = 'ems';
                            break;
                    case "申通快递":
                            $postcom = 'shentong';
                            break;
                    case "圆通速递":
                            $postcom = 'yuantong';
                            break;
                    case "顺丰速运":
                            $postcom = 'shunfeng';
                            break;
                    case "天天快递":
                            $postcom = 'tiantian';
                            break;
                    case "韵达快递":
                            $postcom = 'yunda';
                            break;
                    case "中通速递":
                            $postcom = 'zhongtong';
                            break;
                    case "龙邦物流":
                            $postcom = 'longbanwuliu';
                            break;
                    case "宅急送":
                            $postcom = 'zhaijisong';
                            break;
                    case "全一快递":
                            $postcom = 'quanyikuaidi';
                            break;
                    case "汇通速递":
                            $postcom = 'huitongkuaidi';
                            break;	
                    case "民航快递":
                            $postcom = 'minghangkuaidi';
                            break;	
                    case "亚风速递":
                            $postcom = 'yafengsudi';
                            break;	
                    case "快捷速递":
                            $postcom = 'kuaijiesudi';
                            break;	
                    case "华宇物流":
                            $postcom = 'tiandihuayu';
                            break;	
                    case "中铁快运":
                            $postcom = 'zhongtiewuliu';
                            break;		
                            /* 修改 by www.yshop100.com start */
                    /*case "百世汇通":
                            $postcom = 'huitongkuaidi';
                            break;
                    case "全峰快递":
                            $postcom = 'quanfengkuaidi';
                            break;
                    case "德邦":
                            $postcom = 'debangwuliu';
                            break;
                            /* 修改 by www.yshop100.com end */
                    /*case "FedEx":
                            $postcom = 'fedex';
                            break;		
                    case "UPS":
                            $postcom = 'ups';
                            break;		
                    case "DHL":
                            $postcom = 'dhl';
                            break;		
                    default:
                            $postcom = ''; */
                }
		return $postcom;
        }
	
    private function getcontent($url){
        if(function_exists("file_get_contents")){
            $file_contents = file_get_contents($url);
        }else{
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $file_contents = curl_exec($ch);
            curl_close($ch);    
        }
        return $file_contents;
    }
    
    
    
    private function json_array($json){
        if($json){
            foreach ((array)$json as $k=>$v){
                $data[$k] = !is_string($v)?$this->json_array($v):$v;
            }
            return $data;
        }
    }
    public function getorder($name,$order){
        $keywords = $this->getshipping($name);
		if ($keywords=='')$keywords='EMS';
		$url="http://www.chongdk.com/QT/kuaidiniao.aspx?type={$keywords}&postid={$order}";
		//$url="http://www.kuaidi100.com/query?type={$keywords}&postid={$order}";
		//$url="https://www.easebutech.com/mobile/kuaidi/KdApiSearchDemo.php";
		/* if($keywords=="shunfeng"){
			$sql = "SELECT mobile  FROM ". $GLOBALS['ecs']->table('delivery_order'). " WHERE invoice_no = '".$order."' AND shipping_name = '".$name."'";  
			$shunfengphone = $GLOBALS['db']->getOne($sql);
			$url=$url."&phone=".substr($shunfengphone,7,4);
		} */
        $result = $this->getcontent($url);  
		//$result = '';//htmlspecialchars_decode($result);
			//echo $result;
        /* 快递100 api 收费 使用快递网接口 */
//        $result = $this->getcontent("http://www.kuaidi.com/index-ajaxselectcourierinfo-$order-$keywords.html");
        //$result = '{ "LogisticCode" : "71581824088968", "ShipperCode" : "HTKY", "Traces" : [ { "AcceptStation" : "揭阳市【普宁一部】，【陈燕燕/13670599886】已揽收", "AcceptTime" : "2019-05-11 21:20:13" }, { "AcceptStation" : "揭阳市【普宁一部】，正发往【汕头转运中心】", "AcceptTime" : "2019-05-12 00:46:43" }, { "AcceptStation" : "到汕头市【汕头转运中心】", "AcceptTime" : "2019-05-12 03:09:02" }, { "AcceptStation" : "汕头市【汕头转运中心】，正发往【无锡转运中心】", "AcceptTime" : "2019-05-12 03:18:18" }, { "AcceptStation" : "汕头市【汕头转运中心】，正发往【无锡转运中心】", "AcceptTime" : "2019-05-13 03:54:56" } ], "State" : "2", "EBusinessID" : "1491648", "Success" : true }';
        $data = json_decode($result,true);
		
        //$data = $this->json_array($res);
        //if(!$data['success']){
            /* if($data['status']!=200){$data['data'][0] = array('time'=> local_date('Y-m-d H:m:s', gmtime()),'context'=>'查询失败,请检查网络是否正常');
        } */ 
		
		if(!$data['Success']){
			$data['Traces'][0] = array('AcceptTime'=> local_date('Y-m-d H:m:s', gmtime()),'AcceptStation'=>'查询失败,请检查网络是否正常');
        }
        return $data;
    }
}
?>
