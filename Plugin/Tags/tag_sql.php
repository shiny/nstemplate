<?php
define('MYSQL_HOST','127.0.0.1');
define('MYSQL_USER','root');
define('MYSQL_PASSWORD','');
define('MYSQL_DB','');

class tag_sql extends ns_tag{
    function __toString(){
	    $sql = $this->rawparams['sql'];
		$notfound = isset($this->rawparams['notfound']) ? $this->rawparams['notfound'] : "'Records Not Found.'";
        $field_name = isset($this->params['name']) ? $this->params['name'] : 'field'; 
		$code = '<?php
            $tmp_'.$field_name.' = sql::fetch_all('.$sql.');
            if(empty($tmp_'.$field_name.')):
                echo '.$notfound.';
            else:
                foreach($tmp_'.$field_name.' as $'.$field_name.'):
        ?>';
        $code .= $this->contents;
        $code .= '<?php
                endforeach;
            endif;?>';
        return $code;
	}
	
	
}
class sql{
    static function fetch_all($sql){
        if(!@mysql_connect(MYSQL_HOST,MYSQL_USER,MYSQL_PASSWORD)){
            trigger_error('Change Your MySQL Server Info First:'.__FILE__);
            return array();
        }
        if(defined('MYSQL_DB') && MYSQL_DB)
            mysql_select_db(MYSQL_DB);
        $results = array();
        if($res = mysql_query($sql)){
            while($results[] = mysql_fetch_assoc($res)){}
            array_pop($results);
        }
	    return $results;
	}	
}
