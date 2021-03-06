<?php
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'html_functions.php';
require_once CLASS_DIR . 'class_check.php';

$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
$lang = array_merge($lang, load_language('ad_mega_search'));
$stdhead = [
    'css' => [
        get_file('upload_css')
    ],
];

$msg_to_analyze = (isset($_POST['msg_to_analyze']) ? htmlsafechars($_POST['msg_to_analyze']) : '');
$invite_code = (isset($_POST['invite_code']) ? htmlsafechars($_POST['invite_code']) : '');
$user_names = (isset($_POST['user_names']) ? preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $_POST['user_names']) : '');
$HTMLOUT = $found = $not_found = $count = $no_matches_for_this_email = $matches_for_email = $no_matches_for_this_ip = $matches_for_ip = '';
$number = 0;
$HTMLOUT .= '
    <div class="container is-fluid portlet">
        <h1 style="text-align: center;">' . $lang['mega_heading'] . '</h1>
        <div class="has-text-centered size_4 has-text-white top10 bottom10">' . $lang['mega_analyze'] . '</div>
        <div class="bg-02 round5 bottom20 padding10">
            <form method="post" action="staffpanel.php?tool=mega_search&amp;action=mega_search">
                ' . bubble($lang['mega_text'], $lang['mega_text_1']) . '
                <textarea name="msg_to_analyze" rows="20" class="w-100">' . $msg_to_analyze . '</textarea>
                <div class="has-text-centered">
                    <input type="submit" class="button" value="' . $lang['mega_search_btn'] . '" />
                </div>
            </form>
        </div>
        <div class="bg-02 round5 bottom20 padding10">
            <form method="post" action="staffpanel.php?tool=mega_search&amp;action=mega_search">
                ' . bubble('<b>' . $lang['mega_invite'] . '</b>', $lang['mega_invite_1']) . '
                <input type="text" name="invite_code" class="w-100" value="' . $invite_code . '" />
                <div class="has-text-centered top10">
                    <input type="submit" class="button" value="' . $lang['mega_search_btn'] . '" />
                </div>
            </form>
        </div>
        <div class="bg-02 round5 bottom20 padding10">
            <form method="post" action="staffpanel.php?tool=mega_search&amp;action=mega_search">
                ' . bubble('<b>' . $lang['mega_names'] . '</b>', $lang['mega_names_1']) . '
                <textarea name="user_names" rows="4" class="w-100">' . $user_names . '</textarea>
                <div class="has-text-centered">
                    <input type="submit" class="button" value="' . $lang['mega_search_btn'] . '" />
                </div>
            </form>
        </div>';

if (isset($_POST['user_names'])) {
    $searched_users = explode(' ', preg_replace('/\s+/', ' ', $user_names));
    $found = '';
    foreach ($searched_users as $search_users) {
        $search_users = trim($search_users);
        $sql = "SELECT id, username, ip, added, last_access, email FROM users WHERE username LIKE '%{$search_users}%'";
        $res_search_usernames = sql_query($sql) or sqlerr(__FILE__, __LINE__);
        if (mysqli_num_rows($res_search_usernames) >= 0) {
            while ($arr = mysqli_fetch_array($res_search_usernames)) {
                $found .= '
            <tr>
                <td>' . $search_users . '</td>
                <td>' . format_username($arr['id']) . '</td>
                <td>' . htmlsafechars($arr['email']) . '</td>
                <td>
                    <span class="tooltipper text-blue" title="added">' . get_date($arr['added'], '') . '</span><br>
                    <span class="tooltipper has-text-success" title="last access">' . get_date($arr['last_access'], '') . '</span>
                </td>
                <td>
                    <img src="./images/up.png" alt="' . $lang['mega_up'] . '" class="tooltipper" title="' . $lang['mega_uploaded'] . '" /> 
                    <span class="has-text-success">' . mksize($arr['uploaded']) . '</span>
                    ' . ($site_config['ratio_free'] ? '
                </td>' : '<br>
                    <img class="tooltipper" src="./images/dl.png" alt="' . $lang['mega_down'] . '" title="' . $lang['mega_downloaded'] . '" />  
                    <span class="text-red">' . mksize($arr['downloaded']) . '</span>
                </td>') . '
                <td>' . member_ratio($arr['uploaded'], $site_config['ratio_free'] ? '0' : $arr['downloaded']) . '</td>
                <td>' . make_nice_address($arr['ip']) . '</td>
            </tr>';
            }
        }
    }
    if (empty($found)) {
        $found = "<td colspan='7'><span class='size_4 text-red text-shadow'>Not Found: " . implode(', ', $searched_users) . "</span></td>";
    }
    $HTMLOUT .= " 
        <table class='table table-bordered table-striped top20'>
            <thead>
                <tr>
                    <th>{$lang['mega_searched']}</th>
                    <th>{$lang['mega_member']}</th>
                    <th>{$lang['mega_email']}</th>
                    <th>{$lang['mega_registered']}<br>{$lang['mega_last_acc']}</th>
                    <th>{$lang['mega_stats']}</th>
                    <th>{$lang['mega_ratio']}</th>
                    <th>{$lang['mega_ip']}</th>
                </tr>
            </thead>
            <tbody>
                {$found}
            </tbody>
        </table>";
}
if (isset($_POST['msg_to_analyze'])) {
    $email_search = $_POST['msg_to_analyze'];
    $regex = '/[\._a-zA-Z0-9-]+@[\._a-zA-Z0-9-]+/i';
    $email_to_test = [];
    $number_of_matches = preg_match_all($regex, $email_search, $email_to_test);
    $matches_for_email .= '<h1>' . $lang['mega_emails'] . '</h1>';
    foreach ($email_to_test[0] as $tested_email) {
        $res_search_others = sql_query('SELECT id, email, ip, added, last_access, invitedby FROM users WHERE email LIKE \'' . $tested_email . '\'');
        if (mysqli_num_rows($res_search_others) == 0) {
            $no_matches_for_this_email .= '<span class="text-blue">' . $lang['mega_no_exact'] . ' ' . $tested_email . '</span>';
        } else {
            $number = 1;
            while ($arr = mysqli_fetch_array($res_search_others)) {
                if ($arr['id'] !== '') {
                    if ($arr['invitedby'] > 0) {
                        $inviter = format_username($arr['invitedby']);
                    } else {
                        $inviter = $lang['mega_open'];
                    }
                    $matches_for_email .= '
            <tr>
                <td>' . format_username($arr['id']) . '</td>
                <td>' . htmlsafechars($arr['email']) . '</td>
                <td>
                    <span class="tooltipper text-blue" title="added">' . get_date($arr['added'], '') . '</span><br>
                    <span class="tooltipper has-text-success" title="last access">' . get_date($arr['last_access'], '') . '</span>
                </td>
                <td>
                    <img src="./images/up.png" alt="' . $lang['mega_up'] . '" class="tooltipper" title="' . $lang['mega_uploaded'] . '" /> 
                    <span class="has-text-success">' . mksize($arr['uploaded']) . '</span>
                    ' . ($site_config['ratio_free'] ? '
                </td>' : '<br>
                    <img src="./images/dl.png" alt="' . $lang['mega_down'] . '" class="tooltipper" title="' . $lang['mega_downloaded'] . '" />  
                    <span class="tooltipper text-red">' . mksize($arr['downloaded']) . '</span>
                </td>') . '
                <td>' . member_ratio($arr['uploaded'], $site_config['ratio_free'] ? '0' : $arr['downloaded']) . '</td>
                <td>' . make_nice_address($arr['ip']) . '</td>
                <td>' . $inviter . '</td>
            </tr>';
                }
            }
        }
    } //=== end email search
    $print_if_any_matches = ($number > 0 ? '<table width="100%" border="1" cellspacing="0" cellpadding="5">
   <tr>
   <th>' . $lang['mega_member'] . '</td>
   <th>' . $lang['mega_matched_email'] . '</td>
   <th>' . $lang['mega_registered'] . '<br>' . $lang['mega_last_acc'] . '</td>
   <th>' . $lang['mega_stats'] . '</td>
   <th>' . $lang['mega_ratio'] . '</td>
   <th>' . $lang['mega_ip'] . '</td>
   <th>' . $lang['mega_invited_by'] . '</td>
   </tr>' . $matches_for_email : '') . '</table><br>';
    $HTMLOUT .= $print_if_any_matches . ($no_matches_for_this_email !== '' ? '<table width="100%" border="1" cellspacing="0" cellpadding="5">
   <tr><th><h1>' . $lang['mega_not_found_email'] . '</h1></td></tr>
   <tr><td>' . $no_matches_for_this_email . '</td></tr></table>' : '');
    //=== now let's search for emails that are similar...
    $regex = '/[\._a-zA-Z0-9-]+@/i';
    $email_to_test_like = [];
    $number_of_matches_like = preg_match_all($regex, $email_search, $email_to_test_like);
    $number = 0;
    $similar_emails = 0;
    foreach ($email_to_test_like[0] as $tested_email_like) {
        $res_search_others_like = sql_query('SELECT id, username, class, donor, suspended, leechwarn, chatpost, pirate, king, warned, enabled, email FROM users WHERE email LIKE \'%' . $tested_email_like . '%\'');
        if (mysqli_num_rows($res_search_others_like) > 0) {
            $email = preg_replace('/[^a-zA-Z0-9_-\s]/', '', $tested_email_like);
            $similar_emails .= '<h1>' . $lang['mega_email_using'] . ' "' . $email . '" </h1>';
            $number = 1;
            while ($arr = mysqli_fetch_array($res_search_others_like)) {
                $similar_emails .= str_ireplace($email, '<span style="color: red; font-weight: bold;">' . $email . '</span>', $arr['email']) . $lang['mega_used_by'] . format_username($arr) . '<br>';
            }
        }
    } //=== end emails like XXX
    $HTMLOUT .= ($number === 1 ? '<br><table width="100%" border="1" cellspacing="0" cellpadding="5">
    <tr><th><h1>' . $lang['mega_search_sim'] . '</h1></td></tr>
    <tr><td>' . $similar_emails . '</td></tr></table><br>' : '');
    //=== now let's do the IP search!
    $ip_history = $_POST['msg_to_analyze'];
    $regex = '/([\d]{1,3}\.){3}[\d]{1,3}/';
    $ip_to_test = [];
    $number_of_matches = preg_match_all($regex, $ip_history, $ip_to_test);
    foreach ($ip_to_test[0] as $tested_ip) {
        $res_search_others = sql_query('SELECT id, username, class, donor, suspended, leechwarn, chatpost, pirate, king, warned, enabled, uploaded, downloaded, invitedby, email, ip, added, last_access FROM users WHERE ip LIKE \'%' . $tested_ip . '%\'');
        if (mysqli_num_rows($res_search_others) == 0) {
            $no_matches_for_this_ip .= '<span style="color: blue;">No matches for IP: ' . $tested_ip . '</span><br>';
        } else {
            $matches_for_ip .= '<h1>' . $lang['mega_used_ip'] . ' ' . $tested_ip . '</h1>
    <table width="100%" border="1" cellspacing="0" cellpadding="5">
    <tr>
    <th>' . $lang['mega_member'] . '</td>
    <th>' . $lang['mega_matched_ip'] . '</td>
    <th>' . $lang['mega_email'] . '</td>
    <th>' . $lang['mega_registered'] . '<br>' . $lang['mega_last_acc'] . '</td>
    <th>' . $lang['mega_stats'] . '</td>
    <th>' . $lang['mega_ratio'] . '</td>
    <th>' . $lang['mega_ip'] . '</td>
    <th>' . $lang['mega_invited_by'] . '</td>
    </tr>';
            while ($arr = mysqli_fetch_array($res_search_others)) {
                if ($arr['username'] !== '') {
                    //=== get inviter
                    if ($arr['invitedby'] > 0) {
                        $res_inviter = sql_query('SELECT id, username, class, donor, suspended, leechwarn, chatpost, pirate, king, warned, enabled FROM users WHERE id = ' . sqlesc($arr['invitedby']));
                        $arr_inviter = mysqli_fetch_array($res_inviter);
                        $inviter = ($arr_inviter['username'] !== '' ? format_username($arr_inviter) : $lang['mega_open']);
                    } else {
                        $inviter = $lang['mega_open'];
                    }
                    //=== highlight the matched IP in the ip history \\o\o/o//
                    //$ip_history = nl2br($arr['ip_history']);
                    $matches_for_ip .= '<tr>
                            <td>' . format_username($arr) . '</td>
                            <td><span style="color: red; font-weight: bold;">' . $tested_ip . ' </span></td>
                            <td>' . htmlsafechars($arr['email']) . '</td>
                            <td>
                            <span style="color: blue;" title="added">' . get_date($arr['added'], '') . '</span><br>
                            <span style="color: green;" title="last access">' . get_date($arr['last_access'], '') . '</span>
                            </td>
                            <td>
                            <img src="./images/up.png" alt="' . $lang['mega_up'] . '" title="' . $lang['mega_uploaded'] . '" /> 
                            <span style="color: green;">' . mksize($arr['uploaded']) . '</span>
                            ' . ($site_config['ratio_free'] ? '' : '<br>
                            <img src="./images/dl.png" alt="' . $lang['mega_down'] . '" title="' . $lang['mega_downloaded'] . '" />  
                            <span style="color: red;">' . mksize($arr['downloaded']) . '</span></td>') . '
                            <td>' . member_ratio($arr['uploaded'], $site_config['ratio_free'] ? '0' : $arr['downloaded']) . '</td>
                            <td>' . make_nice_address($arr['ip']) . '<br>
                            </td>
                            <td>' . $inviter . '</td>
                              </tr>';
                }
            }
            $matches_for_ip .= '</td></tr></table><br>';
        }
    }
    $HTMLOUT .= (($matches_for_ip != '' || $no_matches_for_this_ip !== '') ? '<h1>' . $lang['mega_searched_ip'] . '</h1>' : '') . $matches_for_ip . ($no_matches_for_this_ip !== '' ? '<table width="100%" border="1" cellspacing="0" cellpadding="5">
                                                    <tr><th><h1>' . $lang['mega_no_ips'] . '</h1></td></tr>
                                                    <tr><td>' . $no_matches_for_this_ip . '</td></tr></table>' : '');
} //=== end search IP and email
if (isset($_POST['invite_code'])) {
    if (strlen($invite_code) != 32) {
        stderr($lang['mega_error'], $lang['mega_bad_invite']);
    } else {
        $inviter = sql_query('SELECT u.id, u.username, u.ip, u.last_access, u.email, u.added, u.class, u.leechwarn, u.chatpost, u.pirate, u.king, u.uploaded, u.downloaded, u.donor, u.enabled, u.warned, u.suspended, u.invitedby, i.id AS invite_id, i.added AS invite_added FROM users AS u LEFT JOIN invites AS i ON u.id = i.sender WHERE  i.code = ' . sqlesc($invite_code));
        $user = mysqli_fetch_array($inviter);
        if ($user['username'] == '') {
            $HTMLOUT .= stdmsg($lang['mega_error'], $lang['mega_invite_gone']);
        } else {
            $u1 = sql_query('SELECT id, username, donor, class, enabled, leechwarn, chatpost, pirate, king, warned, suspended FROM users WHERE  id=' . sqlesc($user['invitedby']));
            $user1 = mysqli_fetch_array($u1);
            $HTMLOUT .= '<h1>' . format_username($user) . $lang['mega_made'] . $invite_code . '  (' . get_date($user['invite_added'], '') . ')</h1>
                <table width="90%">
                <tr>
                <th>' . $lang['mega_invited'] . '</td>
                <th>' . $lang['mega_email'] . '</td>
                <th>' . $lang['mega_ip'] . '</td>
                <th>' . $lang['mega_last_acc'] . '</td>
                <th>' . $lang['mega_joined'] . '</td>
                <th>' . $lang['mega_ud'] . '</td>
                <th>' . $lang['mega_ratio'] . '</td>
                <th>' . $lang['mega_invited_by'] . '</td>
                </tr>
                <tr>
                <td>' . format_username($user) . '</td>
                <td>' . htmlsafechars($user['email']) . '</td>
                <td>' . htmlsafechars($user['ip']) . '</td>
                <td>' . get_date($user['last_access'], '') . '</td>
                <td>' . get_date($user['added'], '') . '</td>
                <td><img src="./images/up.png" alt="' . $lang['mega_up'] . '" title="' . $lang['mega_uploaded'] . '" /> <span style="color: green;">' . mksize($user['uploaded']) . '</span>
                ' . ($site_config['ratio_free'] ? '' : '<br>
                <img src="./images/dl.png" alt="' . $lang['mega_down'] . '" title="' . $lang['mega_downloaded'] . '" />  
                <span style="color: red;">' . mksize($user['downloaded']) . '</span></td>') . '
                <td>' . member_ratio($user['uploaded'], $site_config['ratio_free'] ? '0' : $user['downloaded']) . '</td>
                <td>' . ($user['invitedby'] == 0 ? $lang['mega_open'] : format_username($user1)) . '</td>
                </tr>
                </table>';
        }
        $invited = sql_query('SELECT u.id, u.username, u.ip, u.last_access, u.email, u.added, u.leechwarn, u.chatpost, u.pirate, u.king, u.class, u.uploaded, u.downloaded, u.donor, u.enabled, u.warned, u.suspended, u.invitedby, i.id AS invite_id FROM users AS u LEFT JOIN invites AS i ON u.id = i.receiver WHERE  i.code = ' . sqlesc($invite_code));
        $user_invited = mysqli_fetch_array($invited);
        if ($user_invited['username'] == '') {
            $HTMLOUT .= stdmsg($lang['mega_error'], $lang['mega_not_used']);
        } else {
            $u2 = sql_query('SELECT id, username, donor, class, enabled, warned, leechwarn, chatpost, pirate, king, suspended FROM users WHERE id=' . sqlesc($user_invited['invitedby']));
            $user2 = mysqli_fetch_array($u2);
            $HTMLOUT .= '<h1>' . format_username($user_invited) . $lang['mega_used_from'] . format_username($user) . '</h1>
                <table width="90%">
                <tr>
                <th>' . $lang['mega_invited'] . '</td>
                <th>' . $lang['mega_email'] . '</td>
                <th>' . $lang['mega_ip'] . '</td>
                <th>' . $lang['mega_last_acc'] . '</td>
                <th>' . $lang['mega_joined'] . '</td>
                <th>' . $lang['mega_ud'] . '</td>
                <th>' . $lang['mega_ratio'] . '</td>
                <th>' . $lang['mega_invited_by'] . '</td>
                </tr>
                <tr>
                <td>' . format_username($user_invited) . '</td>
                
                <td>' . htmlsafechars($user_invited['email']) . '</td>
                <td>' . htmlsafechars($user_invited['ip']) . '</td>
                <td>' . get_date($user_invited['last_access'], '') . '</td>
                <td>' . get_date($user_invited['added'], '') . '</td>
                <td><img src="./images/up.png" alt="' . $lang['mega_up'] . '" title="' . $lang['mega_uploaded'] . '" /> <span style="color: green;">' . mksize($user_invited['uploaded']) . '</span>
                ' . ($site_config['ratio_free'] ? '' : '<br>
                <img src="./images/dl.png" alt="' . $lang['mega_down'] . '" title="' . $lang['mega_downloaded'] . '" />  
                <span style="color: red;">' . mksize($user_invited['downloaded']) . '</span></td>') . '
                <td>' . member_ratio($user_invited['uploaded'], $site_config['ratio_free'] ? '0' : $user_invited['downloaded']) . '</td>
                <td>' . ($user_invited['invitedby'] == 0 ? $lang['mega_open'] : format_username($user2)) . '</td>
                </tr>
                </table>';
        }
    }
    $HTMLOUT .= '</td></tr></table>';
}

$HTMLOUT .= "</div>";
echo stdhead($lang['mega_stdhead'], true, $stdhead) . $HTMLOUT . stdfoot();
