<?php

/**
 * Copyright (c) 2012, ScaleXtreme, Inc.
 * All rights reserved.
 *
 * This file is part of the ScaleXtreme Cloud Portal V1.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * Redistributions of source code must retain the above copyright notice, this list
 * of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this
 * list of conditions and the following disclaimer in the documentation and/or other
 * materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

class httpz {
    protected $ch = null;
    protected $request_headers=null;
    public $response_code=0;
    public $last_request_time=0;
    public $headers;
    public $body;
    public $cache_dir=null;

    public function init() {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADER, 1);
        curl_setopt($this->ch, CURLOPT_ENCODING, '');
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 1);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, 16);

        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 300);

        if (function_exists('cg') && $interface=cg('network_interface')) {
            curl_setopt($this->ch, CURLOPT_INTERFACE, $interface);
        }

        $headers = array(
            'Expect:',
        );
        $this->request_headers = $headers;
    }
    public function get($url, $cached_time=0) {
        if($this->ch == null){
            $this->init();
        }
        curl_setopt($this->ch, CURLOPT_HTTPGET, true); 
        curl_setopt($this->ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
        return $this->doHttpRequest($url, $cached_time, $url);
    }
    public function postWithFiles($url, $post_data=array(), $files=array(), $cached_time=0) {
    }
    public function post($url, $post_data=array(), $cached_time=0) {
        if($this->ch == null){
            $this->init();
        }
        curl_setopt($this->ch, CURLOPT_POST, true); 
        curl_setopt($this->ch, CURLOPT_TIMECONDITION, 0);
        $post_str='';
        if (!empty($post_data)) {
            if (is_array($post_data)) {
                foreach ($post_data as $k=>$v) {
                    $post_str.=urlencode($k).'='.urlencode($v).'&';
                }
                if ($post_str) {
                    $post_str=substr($post_str, 0, -1);
                }
            } else {
                $post_str=$post_data;
            }
        }
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_str); 
        return $this->doHttpRequest($url, $cached_time, 'post::'.$url.serialize($post_data));
    }
    public function enableTest() {
        if($this->ch == null){
            $this->init();
        }
        curl_setopt($this->ch, CURLOPT_VERBOSE, true);
    }
    public function disableTest() {
        if($this->ch == null){
            $this->init();
        }
        curl_setopt($this->ch, CURLOPT_VERBOSE, false);
    }
    public function enableCookie($filename=null) {
        if($this->ch == null){
            $this->init();
        }
        if (!$filename) {
            $filename = sys_get_temp_dir().'/RemoteFileReader-cookie';
        }
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, $filename);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, $filename);
    }
    public function clearCookie($filename=null) {
        if (!$filename) {
            $filename = sys_get_temp_dir().'/RemoteFileReader-cookie';
        }
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    public function setHeader($key, $value=null) {
        if($this->ch == null){
            $this->init();
        }
        if (is_null($value)) {
            $this->request_headers[] = $key;
        } else {
            $this->request_headers[] = $key.': '.$value;
        }
    }
    private function doHttpRequest($url, $cached_time, $cache_key) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request_headers);
        curl_setopt($this->ch, CURLOPT_VERBOSE, true);
 
        if ($cached_time>0 && $cache_data=$this->getCache($cache_key, $cached_time)) {
            $this->response_code=0;
            return $cache_data;
        }
        $meta=$this->getCacheMeta($cache_key);
        if (!empty($meta['expires']) && $meta['expires']>time()) {
            if (empty($cache_data)) {
                $cache_data=$this->getCache($cache_key, -1);
            }
            if ($cache_data) {
                return $cache_data;
            }
        }
        if (!empty($meta['last_request_time'])) {
            curl_setopt($this->ch, CURLOPT_TIMEVALUE, $meta['last_request_time']);
        } else {
            curl_setopt($this->ch, CURLOPT_TIMECONDITION, 0);
            curl_setopt($this->ch, CURLOPT_TIMEVALUE, null);
        }
        $this->last_request_time=time();
        $response=curl_exec($this->ch);
        $info=curl_getinfo($this->ch);
        $this->response_code=$info['http_code'];

        if ($this->response_code==304) {
            if (empty($cache_data)) {
                $cache_data=$this->getCache($cache_key, -1);
            }
            return $cache_data;
        }

        if ($this->response_code!=200 || empty($response)) {
            return false;
        }

        if (strpos($response,"\r\n\r\n")!==false) {
            list($header, $this->body)=explode("\r\n\r\n",$response,2);
        } else {
            $header='';
            $this->body=$response;
        }

        $this->headers=array();
        foreach (explode("\n",$header) as $line) {
            if (strpos($line,':')===false) {
                continue;
            }
            list($k,$v)=explode(':',$line,2);
            $this->headers[trim($k)]=trim($v);
        }


        if (!empty($this->body) && $this->getLastCode()==200) {
            $this->saveCache($cache_key);
        }
        return $this->body;
    }
    public function getCacheMeta($url) {
        $path=$this->getCacheDir().'/'.md5($url).'.meta';
        if (file_exists($path)) {
            $content=file_get_contents($path);
            return json_decode($content, true);
        }
        return false;
    }
    public function getCache($url, $cached_time) {
        if ($cached_time==0) {
            return false;
        }
        $path=$this->getCacheDir().'/'.md5($url);
        if (file_exists($path) 
        && ((filemtime($path)+$cached_time)>time() || $cached_time<0)) {
            $content=file_get_contents($path);
            $content=gzuncompress($content);
            return $content;
        }
        return false;
    }
    public function saveCache($url) {
        $path=$this->getCacheDir().'/'.md5($url);
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path),0777,true);
        }

        if (!empty($this->headers['Expires'])) {
            $expires=$this->headers['Expires'];
            $expires=strtotime($expires);
        } else {
            $expires=0;
        }

        $meta=array(
            'last_request_time'=>$this->last_request_time,
            'expires'=>$expires,
        );
        $meta=json_encode($meta);
        $content=gzcompress($this->body);

        file_put_contents($path,$content);
        file_put_contents($path.'.meta',$meta);
    }
    public function getLastCode() {
        return $this->response_code;
    }
    public function deleteCache($url) {
        $path=$this->getCacheDir().'/'.md5($url);
        if (file_exists($path)) {
            unlink($path);
        }
    }
    private function getCacheDir() {
        if ($this->cache_dir) {
            return $this->cache_dir;
        } else {
            return sys_get_temp_dir().'/fcache2';
        }
    }
    public function setCacheDir($dir) {
        $this->cache_dir = $dir;
    }
    
    public function setTimeout($secs) {
        if ($this->ch == null){
            $this->init();
        }
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $secs);
    }
}

if ( !function_exists('my_get_temp_dir')) {
    function my_get_temp_dir() {
        if (file_exists('/tmp')) {
            return '/tmp';
        }
        if (function_exists('sys_get_temp_dir')) {
            return sys_get_temp_dir();
        }
        if( $temp=getenv('TMP') )        return $temp;
        if( $temp=getenv('TEMP') )        return $temp;
        if( $temp=getenv('TMPDIR') )    return $temp;
        $temp=tempnam(__FILE__,'');
        if (file_exists($temp)) {
            unlink($temp);
            return dirname($temp);
        }
        return '/tmp';
    }
}
