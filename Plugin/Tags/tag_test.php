<?php
/**
 * Demo Tag
 */
class tag_test extends ns_tag{
	function __toString(){
        //return 'x';
		$str = 'Params:{php $params = '.var_export($this->params,1).'}';
		//We Can Return a Template Syntax,also.
		$str .= '{$params|var_dump}';
		$str .= 'Contents:'.$this->contents;
		return $str;
	}
}
