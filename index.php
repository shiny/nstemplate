<?php
$tpl_file = isset($_GET['lang']) ? 'index.zh-CN.tpl.htm' : 'index.en.tpl.htm';

include 'Library/ns_template.php';
$tpl = new ns_template();
$tpl
->set_compile_dir(dirname(__FILE__).DIRECTORY_SEPARATOR.'Cache')
->set_template_dir('Template')

->set_lifetime(0)
//default param is -1, update cache file when template modified
//0 will compile for each time
//other is expired seconds

->register_plugin_dir('Plugin')
->assign('var','Variable Value')
->assign('arr',array('key'=>'value'))
->display($tpl_file);
