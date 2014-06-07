<?php
class tag_test extends ns_tag{
	/* 模板会默认调用此方法并把返回内容编译到模板结果 */
	function __toString(){
		$str = '参数：{php $params = '.var_export($this->params,1).'}';
		//返回的结果也可以是模板
		$str .= '{$params|var_dump}';
		$str .= '内容：'.$this->contents;
		return $str;
	}
}
