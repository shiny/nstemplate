<?php
include_once 'ns_plugin.php';
include_once 'ns_compiler.php';
include_once 'ns_tag.php';
/**
 * @version 1.0.1
 */
class ns_template{
    private $_var = array();
    private $compile_dir = '';
    private $template_dir = '';
    private $lifetime = -1;
    /**
     * assign a Variable or Variables
     * @param mixed $key string or array
     * @param mixed $value 
     * @return $this 
     */
    function assign($key,$value=FALSE){
        if(is_array($key)){
            $this->_var = array_merge($this->_var,$key);
        } else {
            $this->_var[$key] = $value;
        }
        return $this;
    }
    /**
     * set directory of template
     * @param string $dir
     * @return $this
     */
    function set_template_dir($dir){
        $this->template_dir = $dir;
        return $this;
    }
    /**
     * get template's directory
     * @param string tpl file path
     * @return string
     */
    function get_template_dir($file){
        return realpath($this->template_dir.DIRECTORY_SEPARATOR.$file);
    }
    /**
     * directory to store compiled file
     * @param string $dir
     * @return this
     */
    function set_compile_dir($dir){
        $this->compile_dir = $dir;
        return $this;
    }
    /**
     * set compiled file's life time 
     * 0 stand for always compile tpl,for tag debug;
     * default value -1 is compile file when modify tpl;
     * the others is seconds to expire

     * @param int $lifetime
     * @return $this
     */
    function set_lifetime($lifetime=-1){
        $this->lifetime = $lifetime;
        return $this;
    }
    /**
     * @return int lifetime
     */
    function get_lifetime(){
        return $this->lifetime;
    }
    /**
     * get compile file's path
     * @param string $file
     * @return string compile file path
     */
    function get_compile_dir($file=''){
        $file = $this->compile_dir.DIRECTORY_SEPARATOR.$file.'.cache.php';
        if(!is_dir(dirname($file))) mkdir(dirname($file),0700,true);
        return $file;
    }
    /**
     * display a template file
     * @param string $file
     */
    function display($file=FALSE){
        echo $this->fetch($file);
    }
    /**
     * fetch HTML of a rendered template
     * @param string $file
     * @param string HTML
     */
    function fetch($file=FALSE){
        $tpl_file = $this->get_template_dir($file);
        if(!file_exists($tpl_file)){
            Throw new Exception('Template File '.$file.' NOT Exists');
        }
        $php_file = $this->get_compile_dir($file);
        if(!file_exists($php_file)){
            $this->compile($file);
        }
        extract($this->_var);
        ob_start();
        $success = include $php_file;
        $html = ob_get_clean();
        //Expired
        if($success===FALSE){
            if(@unlink($php_file)===false){
                Throw new Exception('Permission Denied When Delete The Expired File '.$php_file);
                exit;
            }
            return $this->fetch($file);
        }
        return $html;
    }

    /**
     * Register a Plugin directory
     * @param string $dir
     * @return $this
     */
    function register_plugin_dir($dir){
        ns_plugin::register_plugin_dir($dir);
        return $this;
    }

    /**
     * Compile a file
     * @param string $file
     * @return bool whether is succeed
     */
    function compile($file){
        $php_file = $this->get_compile_dir($file);
        $content = file_get_contents($this->get_template_dir($file));
        $source = ns_compiler::compile($content);
        $source = ns_plugin::get_load_plugin_code().$source;
        if(file_put_contents($php_file, $this->get_flush_code($file).$source)===false){
            Throw new Exception('Permission Denied When Write Data to '.$php_file);
            return FALSE;
        }
        return TRUE;
    }

    /**
     * the Code to flush compiled files
     * 
     * @param string $file
     * @return string php code
     */
    function get_flush_code($file){
        $f = tmpfile();
        $fileinfo = fstat($f);
        /**
         filemtime() BIG than time() on SOME LINUX SYSTEM
         so we need get filemtime from system,NOT time()
         or it will compile and comile and compile... 
        **/
        return '<?php if($this->chk_refresh('.var_export($file,1).','.$fileinfo['mtime'].',__FILE__)) return FALSE; ?>';
    }
    /**
     * chk whether a template file need compile anain 
     *
     * @param string $file template file path
     * @param int compile time
     * @param string compiled file path
     * @return bool TRUE is need compile,FALSE will use cache file
     **/
    function chk_refresh($file,$time,$compiled_file){
        $lifetime = $this->lifetime;
        $tpl_file = $this->get_template_dir($file);
        if(filemtime($tpl_file) > $time){
            return TRUE;
        } else {
            if($lifetime==-1) {
                return FALSE;
            } elseif($lifetime==0) { //recommend for DEBUG only
                static $refreshed = array();
                if(isset($refreshed[$file])){
                    return FALSE;
                } else {
                    $refreshed[$file] = TRUE;
                    return TRUE;
                }
            } else { //chk expired
                return filemtime($compiled_file)+$lifetime < time();
            }
        }
    }
}


