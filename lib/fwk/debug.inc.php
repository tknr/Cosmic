<?php

//==========================================================
// デバッグ関連
//==========================================================

function debug__out($var) {
	echo '<pre>'.var_export($var,true).'</pre>';
}

function debug__halt($var) {
	debug__out($var);
	exit;
}


?>