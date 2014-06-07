<?php
class ns_plugin{
    static $plugin_dirs=array();
    public static function load_plugin($plugin_name,$type_dir='') {
        foreach (self::$plugin_dirs as $dir) {
            $file = $dir . DIRECTORY_SEPARATOR .$type_dir. $plugin_name . '.php';
            if (is_file($file)){
                include_once($file);
                break;
            }
        }
    }
    public static function load_tag($tag_name){
        self::load_plugin($tag_name,'Tags'.DIRECTORY_SEPARATOR);
    }
    public static function load_func($func_name){
        self::load_plugin($func_name,'Funcs'.DIRECTORY_SEPARATOR);
    }
    public static function register_plugin_dir($dir){
        if(is_array($dir)){
            foreach($dir as $plugin_dir){
                self::register_plugin_dir($plugin_dir);
            }
        } else {
            if(!in_array($dir,self::$plugin_dirs)){
                self::$plugin_dirs[] = realpath($dir);
            }
        }
    }
    public static function is_plugin_file($file) {
        foreach (self::$plugin_dirs as $dir) {
            if (realpath(dirname($file)) == $dir)
                return TRUE;
        }
        return FALSE;
    }

    public static function get_load_plugin_code(){
        $files = get_included_files();
        $tmp = '<?php ';
        foreach ($files as $file) {
            if (self::is_plugin_file($file))
                $tmp .= 'include_once '.var_export($file,1).';';
        }
        return $tmp.'?>';
    }
}

