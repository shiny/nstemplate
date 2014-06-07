<?php
/**
 * NS Compiler
 * 
 * @version 1.0.0
 */
class ns_compiler{
    /*----------------------------------------------------------------------------
    |  解析的正则规则                                                             |
    -----------------------------------------------------------------------------*/
    private static $compiler_reg = array(
        #remove TABs
        "/([\n\r]+)\t+/s"
        =>"\\1",
        #remark {**}
        "/\{\*(.+?)\*\}/s"
        =>'',
        #tag {include}--------------
        "4"=>array(
            "/\{include\s+([\"'\s]*)([a-z0-9\-_:\/.]+)\\1[\s\/]*\}/is"
            => "<?php \$this->display('\\2'); ?>"
        ),
        #array------------------
        "/\{(\\\$[a-zA-Z0-9_\[\]\'\"\-\>\$\x7f-\xff]+)+\.([a-zA-Z0-9_\[\]\'\"\.\$\x7f-\xff]+)\}/ies"
        =>"'<?php echo \\1'.self::parse_array('\\2').'; ?>'",
        #modify arrays---------------
        "/\{(\\\$[a-zA-Z0-9_\[\]\'\"\-\>\$\x7f-\xff]+)+\.([a-zA-Z0-9_\[\]\'\"\.\$\x7f-\xff]+)\|(.+?)\}/ies"
        =>"self::parse_modify('\\1'.self::parse_array('\\2'),'\\3')",
        #echo {$var}
        "/\{(\\\$[a-zA-Z0-9_\[\]\'\"\\-\>$\.\x7f-\xff]+)\}/s"
        =>"<?php echo \\1; ?>",
        #support modify {$var}
        "/\{(\\\$[a-zA-Z0-9_\[\]\'\"\-\>\$\.\x7f-\xff]+)\|([^{]+?)\}/ies"
        =>"self::parse_modify('\\1','\\2')",
        #support tag {php}
        "/[\n\r\t]*\{php\s+(.+?)\}[\n\r\t]*/is"
        =>"<?php \\1; ?>",
        #{if}{else}{/if}
        '/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is'
        =>'\\1<?php } elseif(\\2) { ?>\\3'
        ,
        "/([\n\r\t]*)\{else\}([\n\r\t]*)/is"
        =>"\\1<?php } else { ?>\\2"
        ,
        "6"=>array(
            // {loop $source $item} => foreach( $source as $item)  loopelse
            "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{loopelse\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/is"
            =>"<?php \$ns_template_tmp_array = \\1; if(is_array(\$ns_template_tmp_array) && count(\$ns_template_tmp_array)>0){ foreach(\$ns_template_tmp_array as \\2) { ?>\\3<?php } } else { ?>\\4<?php } unset(\$ns_template_tmp_array); ?>",
            // {loop $source $item} => foreach( $source as $item)
            "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/is"
            =>"<?php \$ns_template_tmp_array = \\1; if(is_array(\$ns_template_tmp_array) && count(\$ns_template_tmp_array)>0){ foreach(\$ns_template_tmp_array as \\2) { ?>\\3<?php } } unset(\$ns_template_tmp_array); ?>",
            // {loop $source $key $value} => foreach ($source as $key=>$value) loopelse
            "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{loopelse\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/is"
            =>"<?php \$ns_template_tmp_array = \\1; if(is_array(\$ns_template_tmp_array) && count(\$ns_template_tmp_array)>0){ foreach(\\1 as \\2 => \\3) { ?>\\4<?php } } else {?>\\5<?php }unset(\$ns_template_tmp_array); ?>",
            // {loop $source $key $value} => foreach ($source as $key=>$value)
            "/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/is"
            =>"<?php \$ns_template_tmp_array = \\1; if(is_array(\$ns_template_tmp_array)){ foreach(\\1 as \\2 => \\3) { ?>\\4<?php } } unset(\$ns_template_tmp_array); ?>"
            ,//
            "/([\n\r\t]*)\{if\s+(.+?)\}([\n\r]*)(.+?)([\n\r]*)\{\/if\}([\n\r\t]*)/is"
            =>"\\1<?php if(\\2) { ?>\\3\\4\\5<?php } ?>\\6"
        ),
        //
        '/\{ns:([a-zA-Z0-9_]+)\s*(.*?)\/\}/ime'
        =>"self::parse_tag('\\1','\\2');",
        //support tags
        '/\{ns:([a-zA-Z0-9_]+)([^}.]*)\}(.*)\{\/ns:\\1\}/imes'
        =>"self::parse_tag('\\1','\\2','\\3',true);",
        //replace ? >< ?php to empty
        "/ \?\>[\n\r]*\<\?php /s"=>" ",
        //避免 url中的&为引用
        "/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e"=>"self::transamp('\\0')",
        //去除script脚本中&的引用
        "/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/ise"=>
        "self::stripscriptamp('\\1', '\\2')",
    );
    /**
     * Compile a Template
     * @param string $source tpl
     * @return string HTML
     */
    public static function compile($source) {
        foreach (self::$compiler_reg as $key => $value) {
            if (!is_array($value)) {
                $source = self::compile_single($key, $value, $source);
                continue;
            }
            for ($i = 0; $i < $key; $i++) {
                foreach ($value as $reg => $replace) {
                    $source = self::compile_single($reg, $replace, $source);
                }
            }
        }
        if (!self::syntax_check($source)){
            echo '<pre>',$source,'</pre>';
            throw new Exception('Incorrect Template Source');
        }
        return $source;
    }

    /**
     * Check Syntax of Generated PHP Code
     *
     * @param string $code Compiled PHP Code
     * @return bool 
     */
    public static function syntax_check($code) {
        return eval("if(0){ ?>".$code.'<?php } return TRUE;');
    }
    /**
     * Compile a single Regular expression
     */
    public static function compile_single($key, $value, $source) {
        $tmp = preg_replace($key, $value, $source);
        //An Error Occurred
        if (is_null($tmp)){
            $error_code = preg_last_error();
            $error_table = array(
                PREG_NO_ERROR=>'PREG_NO_ERROR',
                PREG_INTERNAL_ERROR=>'PREG_INTERNAL_ERROR',
                PREG_BACKTRACK_LIMIT_ERROR=>'PREG_BACKTRACK_LIMIT_ERROR',
                PREG_RECURSION_LIMIT_ERROR=>'PREG_RECURSION_LIMIT_ERROR',
                PREG_BAD_UTF8_ERROR=>'PREG_BAD_UTF8_ERROR',
                PREG_BAD_UTF8_OFFSET_ERROR=>'PREG_BAD_UTF8_OFFSET_ERROR',
            );
            if(isset($error_table[$error_code])){
                echo $error_table[$error_code];
            } else {
                echo 'UNKNOW ERROR';
            }
            //Help for Debug
            echo '{key:',$key,'}';
            echo '{value:',$value,'}';
            exit;
        }
        return $tmp;
    }
    //转换&避免&以引用方式执行..
    private static function transamp($str) {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }

    /**
     * String to params
     * @param string $params params  string
     * @return array raw params
     */
    private static function parse_params($params) {
        $tmp = array();
        if (preg_match_all("/\s*([a-zA-Z0-9_]+)=([\"'])(.+?)\\2/", stripslashes($params), $matches)) {
            for ($i = 0; $i < count($matches[1]); $i++) {
                $tmp[$matches[1][$i]] = $matches[2][$i].$matches[3][$i].$matches[2][$i];
            }
        }
        return $tmp;
    }

    /**
     * parse tags
     * @param string $tag_name
     * @param string $params tag params
     * @param string $content content in tag
     * @return string compiled HTML
     */
    static function parse_tag($tag_name, $params='', $content='', $mutitag = false) {
        $ext_content = $mutitag ? stripslashes(self::muti_level_tag($tag_name,$content)) : '';
        $params = self::parse_params($params);
        $content = stripslashes($content);
        $tag_name = 'tag_'.$tag_name;
        if (!class_exists($tag_name, 0)) {
            ns_plugin::load_tag($tag_name);
        }
        if(!class_exists($tag_name, 0)){
            Throw new Exception('TAG:'.$tag_name.' NOT FOUND');
            exit;
        } else {
            return self::compile(strval(new $tag_name($params, $content)).$ext_content);
        }
    }
    /**
     * Muti Level tags
     * 
     * @param string $tag_name
     * @param string $content
     * @param int $endpos Offset Recursive
     * @return string
     */
    static function muti_level_tag($tag_name,&$content,$endpos=0){
        $start_tag = '{ns:'.$tag_name;
        $end_tag = '{/ns:'.$tag_name.'}';
        $tag_length = strlen($end_tag);

        $start = $end = array();

        $pos = 0;
        while($pos!==false){
            $pos = stripos($content,$start_tag,$pos);
            $start[]=$pos;
            if($pos!==false)
                $pos++;
        }
        $pos = 0;
        while($pos!==false){
            $pos = stripos($content,$end_tag,$pos);
            $end[]=$pos;
            if($pos!==false)
                $pos++;
        }
        if(count($start)!==count($end)){
            Throw new Exception("Tag NOT Closed Correctly");
            Exit;
        }
        $i = 0;
        while($start[$i]!==false){
            if($start[$i]>$end[$i]){
                $ext_content = substr($content,$end[$i]+$tag_length).$end_tag;
                $content = substr($content,0,$end[$i]);
                return $ext_content;
                break;
            }
            $i++;
        }
        return "";
    }
    /**
     * tpl arrays 
     * @param string $content
     * @return string
     */
    private static function parse_array($content){
        $content = explode('.',$content);
        $arr = '';
        foreach($content as $item){
            $arr .= '[\''.$item.'\']';
        }
        return $arr;
    }

    /**
     * modify
     * @param string $var
     * @param string $modify  strtoupper:@me|substr:1:1
     * @return string PHP Code
     */
    public static function parse_modify($var, $modify) {
        foreach (explode('|', $modify) as $func) {
            $var = self::parse_single_modify($var, $func);
        }
        return '<?php echo ' . $var . '; ?>';
    }

    /**
     * single modify
     * @param string $var
     * @param string $func tpl strtoupper:@me
     * @return string PHP Code
     */
    private static function parse_single_modify($var, $func) {
        $params = explode(':', $func);
        $func_name = array_shift($params);
        if (!function_exists($func_name)) {
            $func_name = 'func_'.$func_name;
            ns_plugin::load_func($func_name);
        }
        if (strpos($func, '@me') === FALSE) {
            $param_string = empty($params) ? '' : ',' . implode(",", $params);
            return '@' . $func_name . '(' . $var . $param_string . ')';
        } else {
            return '@' . $func_name . '(' . str_replace('@me', $var, implode(",", $params)) . ')';
        }
    }

    //转换脚本中的 & 避免引用
    private static function stripscriptamp($s, $extra) {
        $extra = str_replace('\\"', '"', $extra);
        $s = str_replace('&amp;', '&', $s);
        return "<script src=\"$s\" type=\"text/javascript\"$extra></script>";
    }
}

