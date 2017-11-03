<?php

include_once('./config/site.inc.php');

$request__ix = $my_cgi->check_request('ix',cgi::CV_MD5,'');
$request__q = $my_cgi->check_request('q',cgi::CV_TEXT,'');


$path = file_get_contents(PATH_CACHE_IX.'/'.$request__ix.CACHE_IX_EXT_ZIP);
$hash__cx = file_get_contents(PATH_CACHE_IX.'/'.$request__ix.CACHE_IX_EXT_CX);

$fid_cx_list = PATH_CACHE_CX.'/'.$hash__cx.CACHE_CX_EXT_LIST;

$entry_list = unserialize(file_get_contents($fid_cx_list));


$json = array();

$json['ix'] = $request__ix;
$json['path'] = $path;

if (strpos($request__q,'list')!==false) {

	$json['list'] = $entry_list;

}

if (strpos($request__q,'page')!==false) {

	$json['page']['max'] = count($entry_list);

}

echo json_encode($json);

?>