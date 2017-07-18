<?php
if ($INSTALLER09['staffmsg_alert'] && $CURUSER['class'] >= UC_STAFF) {
    if (($answeredby = $mc1->get_value('staff_mess_')) === false) {
        $res1 = sql_query("SELECT count(id) FROM staffmessages WHERE answeredby = 0");
        list($answeredby) = mysqli_fetch_row($res1);
        $mc1->cache_value('staff_mess_', $answeredby, $INSTALLER09['expires']['alerts']);
    }
    if ($answeredby > 0) {
        $htmlout.= "<li>
    <a class='tooltip' href='staffbox.php'><b class='btn btn-warning btn-small'>" . ($answeredby > 1 ? $lang['gl_staff_messages'] . $lang['gl_staff_message_news'] : $lang['gl_staff_message'] . $lang['gl_newmess']) . "</b>
	<span class='custom info alert alert-warning'><em>" . ($answeredby > 1 ? $lang['gl_staff_messages'] . $lang['gl_staff_message_news'] : $lang['gl_staff_message'] . $lang['gl_staff_message_news']) . "</em>
   <b>{$lang['gl_hey']} {$CURUSER['username']}!<br /> " . sprintf($lang['gl_staff_message_alert'], $answeredby) . ($answeredby > 1 ? $lang['gl_staff_message_alerts'] : "") . "{$lang['gl_staff_message_for']}</b>
   {$lang['gl_staff_message_click']}
   </span></a></li>";
    }
}
//==End
// End Class
// End File
