<?php

include_once('./config/site.inc.php');

$request__ix = $my_cgi->check_request('ix',cgi::CV_MD5  ,'');
$request__pg = $my_cgi->check_request('pg',cgi::CV_DIGIT,1);
$request__re = $my_cgi->check_request('re',cgi::CV_TEXT,'');

$my_html->set_value('head_additional',$my_html->mktag_script($my_html->get_theme_file_url('viewer.js')));
$my_html->set_value('ix',$request__ix);
$my_html->set_value('pg',$request__pg);
$my_html->set_value('re',$request__re);

$my_html->apply_template('viewer.html',html::REMOVE_UNDEF_TAGS,html::OUTPUT_HTML);

?>