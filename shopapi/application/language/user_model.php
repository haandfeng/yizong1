<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
 * 用户表MODEL
 * @author ld66.com
 * 2015年3月31日 14:25:00
 */

class User_model extends CI_Model {
	
	//判断用户验证信息是否正确
	public function is_true ($tel,$pwd) {	
		$sql = 'SELECT * FROM db_user WHERE telnum = ? AND pwd = ? LIMIT 1';
		$data[] = $tel;
		$data[] = $pwd;
		
		$uObj = $this->db->query($sql,$data);
		
		if ($uObj->num_rows()){
			return 1;
		}else {
			return 0;
		}
	}
	
	//获取所有用户的简单信息
	public function getusers () {
		$sql = 'SELECT a.id AS userid,a.nickname,a.image,a.gender,b.hs,b.gs,b.profe,b.price,a.certification FROM db_user AS a LEFT JOIN db_user_ext AS b ON a.id = b.uid';
		$uObj = $this->db->query($sql);
		return $uObj->result_array();
	}
	
	//获取单一用户信息
	public function userinfo ($userid,$moreinfo = 0) {
		$fields = '';
		if ($moreinfo == 1){
			$fields = ',b.eb,b.experience,b.reward,b.goodat,b.bright';
		}
		$sql = 'SELECT a.id AS userid,a.nickname,a.realname,a.image,a.gender,b.hs,b.gs,b.profe,b.price,a.certification'.$fields.' FROM db_user AS a LEFT JOIN db_user_ext AS b ON a.id = b.uid WHERE a.id = ? LIMIT 1';
		$uObj = $this->db->query($sql,$userid);
		return $uObj->result_array();
	}
	
	//获取单一用户认证信息
	public function usercinfo ($userid){
		$sql = 'SELECT * FROM db_user_cert WHERE uid = ? LIMIT 1';
		$uObj = $this->db->query($sql,$userid);
		return $uObj->result_array();
	}
	
	//获取指定用户的时间管理列表
	public function timelist ($userid,$stime,$etime){
		$sql = 'SELECT * FROM db_user_time WHERE `uid` = ? AND `starttime` >= ? AND `endtime` <= ?';
		$data[] = $userid;
		$data[] = $stime;
		$data[] = $etime;
		$uObj = $this->db->query($sql,$data);
	
		$timeArr = $uObj->result_array();
		
		if (!empty($timeArr)){	
			return $timeArr;
		}else {
			return 0;
		}
	}
	
	//获取指定用户关注用户
	public function focus ($userid) {
		$sql = 'SELECT a.touid AS uid,a.status,b.nickname,b.certification,c.gs,c.profe FROM db_user_focus AS a LEFT JOIN db_user AS b ON a.touid = b.id LEFT JOIN db_user_ext AS c ON a.touid = c.uid WHERE a.fuid = ?';
		$uObj = $this->db->query($sql,$userid);
		$focusArr = $uObj->result_array();
		
		if (!empty($focusArr)){
			return $focusArr;
		}else {
			return 0;
		}
	}
	
	//获取指定用户关注用户
	public function fans ($userid) {
		$sql = 'SELECT a.fuid AS uid,a.status,b.nickname,b.certification,c.gs,c.profe FROM db_user_focus AS a LEFT JOIN db_user AS b ON a.fuid = b.id LEFT JOIN db_user_ext AS c ON a.fuid = c.uid WHERE a.touid = ?';
		$uObj = $this->db->query($sql,$userid);
		$focusArr = $uObj->result_array();
	
		if (!empty($focusArr)){
			return $focusArr;
		}else {
			return 0;
		}
	}
	
	//获取指定咨询师的评价平均值
	public function starave ($userid) {
		
		//判断是否是咨询师
		$sql = 'SELECT * FROM db_user WHERE id = ? AND certification = 1 LIMIT 1';
		$uObj = $this->db->query($sql,$userid);
		if (!$uObj->num_rows()){
			return 0;
		}

		//计算三个星的平均值
		$sql = 'SELECT AVG(kts) AS kts,AVG(pds) AS pds,AVG(pls) AS pls FROM db_comments WHERE cuid = ?';
		$sObj = $this->db->query($sql,$userid);
		$sArray = $sObj->result_array();
		return $sArray[0];
	}
	
	//判断手机号是否存在
	public function h_tel ($num) {
		if (!is_numeric($num)){
			return 0;
		}
		
		//查询
		$sql = 'SELECT * FROM db_user WHERE telnum = ? LIMIT 1';
		$uObj = $this->db->query($sql,$num);
		
		if ($uObj->num_rows()){
			return 0;
		}else {
			return 1;
		}
	}
	
	//写入注册信息，并返回相应数组
	public function reg ($tel,$pwd){

		$sql = 'INSERT INTO db_user (`nickname`,`pwd`,`image`,`telnum`,`certification`) VALUES(?,?,"/uploads/user/default.jpg",?,0)';
		
		$data[] = 'DianBo_'.$tel;
		$data[] = $pwd;
		$data[] = $tel;
		
		//拼装返回值
		$rObj = $this->db->query($sql,$data);
		if ($this->db->affected_rows()){
			$return = array(
					'id'=>$this->db->insert_id(),
					'nickname'=>'DianBo_'.$tel,
					'image'=>'/uploads/user/head/default.jpg'
			);
			return $return;
		}else {
			return 0;
		}
		
	}
	
	//登陆查询
	public function login ($tel,$pwd){
		
		$sql = 'SELECT * FROM db_user WHERE telnum = ? AND pwd = ? LIMIT 1';
		
		$data[] = $tel;
		$data[] = $pwd;
		
		$lObj = $this->db->query($sql,$data);
		if ($lObj->num_rows()){
			$uArr = $lObj->result_array();			
			$return = array(
					'id'=>$uArr[0]['id'],
					'nickname'=>$uArr[0]['nickname'],
					'img'=>$uArr[0]['image'],
					'is_cerf'=>$uArr[0]['certification']
			);
			
			$sql = 'SELECT gs,profe FROM db_user_ext WHERE uid = ? LIMIT 1';
			$eObj = $this->db->query($sql,$uArr[0]['id']);
			$eArr = $eObj->result_array();
			$return['gs'] = $eArr[0]['gs'];
			$return['profe'] = $eArr[0]['profe'];
			
			return $return;
		}else {
			return 0;
		}
	}
	
	//判断相应用户是否存在
	public function is_user ($id) {
		
		$sql = 'SELECT * FROM db_user WHERE id = ? LIMIT 1';
		$uObj = $this->db->query($sql,$id);
		
		if ($uObj->num_rows()){
			return 1;			
		}else {
			return 0;
		}
	}
	
	//写入、修改个人信息，普通用户
	public function wsui ($arr) {
		
		if (!is_array($arr)){
			return 0;
		}
		
		$sql = 'UPDATE db_user SET nickname = ?,realname = ?,gender = ?,birthdate = ? WHERE id = ? LIMIT 1';
		
		$data[] = $arr['nickname'];
		$data[] = $arr['realname'];
		$data[] = $arr['gender'];
		$data[] = $arr['birthdate'];
		$data[] = $arr['id'];
		
		$uObj = $this->db->query($sql,$data);
		
		if ($this->db->affected_rows()){
			return 1;
		}else {
			return 0;
		}
		
	}
	
	//写入 认证信息
	public function wcert ($p) {
		
		if (!is_array($p)){
			return 0;
		}
		
		//判断用户是否认证
		$sql = 'SELECT * FROM db_user WHERE id = ? AND certification IN (0,3) LIMIT 1';
		$uObj = $this->db->query($sql,$p['uid']);
		
		if (!$uObj->num_rows()){
			return 0;
		}

		$sql = 'INSERT INTO db_user_cert (uid,dorf,icimage,received,degreenum,gcn,gci,certtime) VALUES (?,?,?,?,?,?,?,'.time().')';
		
		$data[] = $p['uid'];
		$data[] = $p['dorf'];
		$data[] = $p['icimage'];
		$data[] = $p['received'];
		$data[] = $p['degreenum'] ? $p['degreenum'] : 0;
		$data[] = $p['gcn'] ? $p['gcn'] : 0;
		$data[] = $p['gci'] ? $p['gci'] : 0;
		
		$this->db->query($sql,$data);
		
		if ($this->db->affected_rows()){
			
			//修改主表认证信息
			$sql = 'UPDATE db_user SET certification = 2 WHERE id = ? LIMIT 1';
			$this->db->query($sql,$p['uid']);
			
			if ($this->db->affected_rows()){
				return 1;
			}else {
				return 0;
			}
			
		}else {
			return 0;
		}
	} 
	
	//更新认证信息
	public function ucert ($p) {
		
		if (!is_array($p)){
			return 0;
		}
		
		
		//判断用户是否认证
		$sql = 'SELECT * FROM db_user WHERE id = ? AND certification = 1 LIMIT 1';
		$uObj = $this->db->query($sql,$p['uid']);
		
		if (!$uObj->num_rows()){
			return 0;
		}
		
		//$sql = 'INSERT INTO db_user_cert (uid,dorf,icimage,received,degreenum,gcn,gci,certtime) VALUES (?,?,?,?,?,?,?,'.time().')';
		$sql = 'UPDATE db_user_cert SET dorf = ?,icimage = ?,received = ?,degreenum = ?,gcn = ?,gci = ? WHERE uid = ? LIMIT 1';
		
		$data[] = $p['dorf'];
		$data[] = $p['icimage'];
		$data[] = $p['received'];
		$data[] = $p['degreenum'] ? $p['degreenum'] : 0;
		$data[] = $p['gcn'] ? $p['gcn'] : 0;
		$data[] = $p['gci'] ? $p['gci'] : 0;
		$data[] = $p['uid'];
		
		$this->db->query($sql,$data);
		
		if ($this->db->affected_rows()){
			return 1;
		}else {
			return 0;
		}
		
	}
	
	//加关注
	public function wfocus ($f,$t) {
		
		//判断是否已经关注
		$sql = 'SELECT * FROM db_user_focus WHERE fuid = ? AND touid = ? LIMIT 1';
		
		$data[] = $f;
		$data[] = $t;
		
		$uObj = $this->db->query($sql,$data);
		
		if ($uObj->num_rows()){
			return 0;
		}
		
		//判断对方的关注关系
		$data = array();
		$data[] = $t;
		$data[] = $f;
		
		$uObj = $this->db->query($sql,$data);
		
		if ($uObj->num_rows()){
			$status = 3;
		}else {
			$status = 1;	
		}

		$sql = 'INSERT INTO db_user_focus (`fuid`,`touid`,`status`,`time`) VALUES(?,?,?,'.time().')';
		$data = array();
		$data[] = $f;
		$data[] = $t;
		$data[] = $status;
		
		$this->db->query($sql,$data);
		
		if ($this->db->affected_rows()){
			
			if ($status == 3){
				$sql = 'UPDATE db_user_focus SET status = 3 WHERE fuid = '.$t.' AND touid = '.$f.' LIMIT 1';
				$this->db->query($sql);	
			}
			
			return 1;

		}else {
			return 0;
		}
	}
	
	//取消关注
	public function dfocus ($f,$t) {
		
		//判断是否关注，并拿到关注关系
		$sql = 'SELECT * FROM db_user_focus WHERE fuid = '.$f.' AND touid = '.$t.' LIMIT 1';
		$uObj = $this->db->query($sql);
		
		if ($uObj->num_rows()){
			$uArr = $uObj->result_array();
			$status = $uArr[0]['status'];
		}else {
			return 0;
		}

		//删除关注关系，并修改对方关注关系
		$sql = 'DELETE FROM db_user_focus WHERE fuid = '.$f.' AND touid = '.$t.' LIMIT 1 ';
		$uObj = $this->db->query($sql);
		if ($this->db->affected_rows()){
			if ($status == 3){
				$sql = 'UPDATE db_user_focus SET status = 1 WHERE fuid = '.$t.' AND touid = '.$f.' LIMIT 1';
				$this->db->query($sql);		
			}
			return 1;
		}else {
			return 0;
		}
	}
	
	//修改密码
	public function upwd($tel,$pwd){
		
		$sql = 'UPDATE db_user SET pwd = ? WHERE telnum = ? LIMIT 1';
		$data[] = $pwd;
		$data[] = $tel;
		$this->db->query($sql,$data);
		
		if ($this->db->affected_rows()){
			return 1;
		}else {
			return 0;
		}
	}
	
	//写入时间管理
	/*public function wtime($uid,$list,$stime){
		
		//判断用户是否是咨询师
		$sql = 'SELECT * FROM db_user WHERE id = ? AND certification = 1 LIMIT 1';
		$uObj = $this->db->query($sql,$uid);
		if (!$uObj->num_rows()){
			return 0;
		}
		
		//写入操作
		$jj = 0;
		for ($i=0,$j=count($list);$i<$j;$i++){
			
			$sql = 'INSERT INTO db_user_time (`uid`,`starttime`,`endtime`,`sittime`) VALUES(?,?,?,'.time().')';
			$data = array();
			$data[] = $uid;
			$data[] = $list[$i]['starttime'];
			$data[] = $list[$i]['endtime'];
			
			$this->db->query($sql,$data);
			if ($this->db->affected_rows()){
				$jj++;
			}
		}
		
		if ($jj == $j){
			return 1;
		}else {
			return 0;
		}
	}*/
	//写入时间管理
	public function wtime($uid,$list,$stime){
		
		//判断用户是否是咨询师
		$sql = 'SELECT * FROM db_user WHERE id = ? AND certification = 1 LIMIT 1';
		$uObj = $this->db->query($sql,$uid);
		if (!$uObj->num_rows()){
			return 0;
		}
		
		//写入操作
		$jj = 0;
		for ($i=0,$j=count($list);$i<$j;$i++){
			
			$sql = 'INSERT INTO db_user_time (`uid`,`starttime`,`endtime`,`sittime`) VALUES(?,?,?,'.time().')';
			$data = array();
			$data[] = $uid;
			$data[] = $list[$i]['start'];
			$data[] = $list[$i]['end'];
			
			$this->db->query($sql,$data);
			if ($this->db->affected_rows()){
				$jj++;
			}
		}
		
		if ($jj == $j){
			return 1;
		}else {
			return 0;
		}
	}
	
	
	//写入详细信息
	public function wcui ($arr) {
		
		if (!is_array($arr)){
			return 0;
		}

		$sql = 'INSERT INTO db_user_ext (uid,hs,stype,gs,profe,eb,experience,reward,goodat,bright,price) VALUES(?,?,?,?,?,?,?,?,?,?,0)';
		
		$ppp['uid'] = $arr['uid'];
		$ppp['hs'] = $arr['hs'];
		$ppp['stype'] = $arr['hs'];
		$ppp['gs'] = $arr['gs'];
		$ppp['profe'] = $arr['profe'];
		$ppp['eb'] = $arr['eb'];
		$ppp['experience'] = $arr['experience'];
		$ppp['reward'] = $arr['reward'];
		$ppp['goodat'] = $arr['goodat'];
		$ppp['bright'] = $arr['bright'];
		
		$this->db->query($sql,$ppp);
		
		if ($this->db->affected_rows()){
			return 1;
		}else {
			return 0;
		}
		
	}
	
	//修改详细信息
	public function ucui ($arr) {
	
		if (!is_array($arr)){
			return 0;
		}
	
		//$sql = 'INSERT INTO db_user_ext (uid,hs,stype,gs,profe,eb,experience,reward,goodat,bright,price) VALUES(?,?,?,?,?,?,?,?,?,?,0)';
		$sql = 'UPDATE db_user_ext SET hs = ?,stype = ?,gs=?,profe=?,eb=?,experience=?,reward=?,goodat=?,bright=? WHERE uid = ? LIMIT 1';
		
		$ppp['hs'] = $arr['hs'];
		$ppp['stype'] = $arr['hs'];
		$ppp['gs'] = $arr['gs'];
		$ppp['profe'] = $arr['profe'];
		$ppp['eb'] = $arr['eb'];
		$ppp['experience'] = $arr['experience'];
		$ppp['reward'] = $arr['reward'];
		$ppp['goodat'] = $arr['goodat'];
		$ppp['bright'] = $arr['bright'];
		$ppp['uid'] = $arr['uid'];
	
		$this->db->query($sql,$ppp);
	
		if ($this->db->affected_rows()){
			return 1;
		}else {
			return 0;
		}
	}
	
	//咨询师搜索
	public function sc ($p) {
		
		if ($p['sname']){	
			$where = ' b.gs LIKE "%'.$p['sname'].'%"';
		}
		
		if ($p['stype']){
			$where .= ' AND b.stype = '.$p['stype'];
		}
		
		if ($p['prof']){
			$where .= ' AND b.profe = "'.$p['prof'].'"';
		}
		
		if ($p['rfc']){
			$where .= ' AND b.hs = "'.$p['rfc'].'"';
		}
		
		$sql = 'SELECT a.nickname,b.gs,b.profe,b.price FROM db_user AS a LEFT JOIN db_user_ext AS b ON a.id = b.uid WHERE '.$where;
		$uObj = $this->db->query($sql);
		
		if ($uObj->num_rows()){
			$uArr = $uObj->result_array();
			return $uArr[0];
		}
		
		
	}
	
	
}