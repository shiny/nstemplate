<?php
function func_utruncate($str,$len=10,$dot='...'){
    if(function_exists('iconv_strlen')){
        $strlen = 'iconv_strlen';
        $strcut = 'iconv_substr';
    } else if(function_exists('mb_strlen')){
        $strlen = 'mb_strlen';
        $strcut = 'mb_strcut';
    } else {
        throw new Exception('Extension mbstring or iconv NOT loaded');
    }
    if($strlen($str,'utf-8')>$len){
        return $strcut($str,0,$len,'utf-8').$dot;
    } else {
        return $str;
    }
}
