<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'user_functions.php';
check_user_status();
$res = sql_query('SELECT COUNT(*) FROM posts WHERE user_id=' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
$arr3 = mysqli_fetch_row($res);
$forumposts = $arr3['0'];
sql_query('UPDATE usersachiev SET forumposts = ' . sqlesc($forumposts) . ' WHERE userid = ' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
header("Location: {$site_config['baseurl']}/index.php");
