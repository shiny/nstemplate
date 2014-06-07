<?php
class tag_now extends ns_tag{
	/**
	 * 如果内容是时间敏感不能缓存的，需要动态调用
	 * 推荐封装在方法里然后在__toString里调用
	 * 否则维护不易
	 *
	 * 能不这么做就尽量不要这么做，for better performance
	 */
	function __toString(){
		return '{php echo tag_now::now()}';
	}
	static function now(){
		return date('Y-m-d H:i:s');
	}
}
