<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'password_functions.php';
require_once INCL_DIR . 'bbcode_functions.php';
require_once INCL_DIR . 'function_bemail.php';
dbconn();
global $CURUSER, $INSTALLER09;
if (!$CURUSER) {
    get_template();
}

$ip = getip();
if (!$INSTALLER09['openreg']) {
    stderr('Sorry', 'Invite only - Signups are closed presently if you have an invite code click <a href="' . $INSTALLER09['baseurl'] . '/invite_signup.php"><b> Here</b></a>');
}
$res = sql_query('SELECT COUNT(id) FROM users') or sqlerr(__FILE__, __LINE__);
$arr = mysqli_fetch_row($res);
if ($arr[0] >= $INSTALLER09['maxusers']) {
    stderr($lang['takesignup_error'], $lang['takesignup_limit']);
}
$lang = array_merge(load_language('global'), load_language('takesignup'));
if (!mkglobal('wantusername:wantpassword:passagain:email' . ($INSTALLER09['captcha_on'] ? ':captchaSelection:' : ':') . 'submitme:passhint:hintanswer:country')) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_form_data']);
}
if ($submitme != 'X') {
    stderr('Ha Ha', 'You Missed, You plonker !');
}
if ($INSTALLER09['captcha_on']) {
    if (empty($captchaSelection) || getSessionVar('simpleCaptchaAnswer') != $captchaSelection) {
        header('Location: signup.php');
        exit();
    }
}
function validusername($username)
{
    global $lang;
    if ($username == '') {
        return false;
    }
    $namelength = strlen($username);
    if (($namelength < 3) or ($namelength > 64)) {
        stderr($lang['takesignup_user_error'], $lang['takesignup_username_length']);
    }
    // The following characters are allowed in user names
    $allowedchars = $lang['takesignup_allowed_chars'];
    for ($i = 0; $i < $namelength; ++$i) {
        if (strpos($allowedchars, $username[$i]) === false) {
            return false;
        }
    }

    return true;
}

if (empty($wantusername) || empty($wantpassword) || empty($email) || empty($passhint) || empty($hintanswer) || empty($country)) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_blank']);
}
if (!blacklist($wantusername)) {
    stderr($lang['takesignup_user_error'], sprintf($lang['takesignup_badusername'], htmlsafechars($wantusername)));
}
if ($wantpassword != $passagain) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_nomatch']);
}
if (strlen($wantpassword) < 6) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_pass_short']);
}
if (strlen($wantpassword) > 100) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_pass_long']);
}
if ($wantpassword == $wantusername) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_same']);
}
if (!validemail($email)) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_validemail']);
}
if (!validusername($wantusername)) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_invalidname']);
}
if (!(isset($_POST['day']) || isset($_POST['month']) || isset($_POST['year']))) {
    stderr('Error', 'You have to fill in your birthday.');
}
if (checkdate($_POST['month'], $_POST['day'], $_POST['year'])) {
    $birthday = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];
} else {
    stderr('Error', 'You have to fill in your birthday correctly.');
}
if ((date('Y') - $_POST['year']) < 17) {
    stderr('Error', 'You must be at least 18 years old to register.');
}
if (!(isset($_POST['country']))) {
    stderr('Error', 'You have to set your country.');
}
$country = (((isset($_POST['country']) && is_valid_id($_POST['country'])) ? intval($_POST['country']) : 0));
$gender = isset($_POST['gender']) && isset($_POST['gender']) ? htmlsafechars($_POST['gender']) : '';
// make sure user agrees to everything...
if ($_POST['rulesverify'] != 'yes' || $_POST['faqverify'] != 'yes' || $_POST['ageverify'] != 'yes') {
    stderr($lang['takesignup_failed'], $lang['takesignup_qualify']);
}
// check if email addy is already in use
$a = (mysqli_fetch_row(sql_query('SELECT COUNT(id) FROM users WHERE email=' . sqlesc($email)))) or sqlerr(__FILE__, __LINE__);
if ($a[0] != 0) {
    stderr($lang['takesignup_user_error'], $lang['takesignup_email_used']);
}
//=== check if ip addy is already in use
if ($INSTALLER09['dupeip_check_on']) {
    $c = (mysqli_fetch_row(sql_query('SELECT COUNT(id) FROM users WHERE ip=' . sqlesc($ip)))) or sqlerr(__FILE__, __LINE__);
    if ($c[0] != 0) {
        stderr('Error', 'The ip ' . htmlsafechars($ip) . ' is already in use. We only allow one account per ip address.');
    }
}
// TIMEZONE STUFF
if (isset($_POST['user_timezone']) && preg_match('#^\-?\d{1,2}(?:\.\d{1,2})?$#', $_POST['user_timezone'])) {
    $time_offset = (int)$_POST['user_timezone'];
} else {
    $time_offset = isset($INSTALLER09['time_offset']) ? (int)$INSTALLER09['time_offset'] : 0;
}

// have a stab at getting dst parameter?
$dst_in_use = localtime(TIME_NOW + ($time_offset * 3600), true);

// TIMEZONE STUFF END
$wantpasshash = make_passhash($wantpassword);
$wanthintanswer = make_passhash($hintanswer);
$user_frees = (XBT_TRACKER == true ? '0' : TIME_NOW + 14 * 86400);
check_banned_emails($email);
$ret = sql_query('INSERT INTO users (username, passhash, birthday, country, gender, stylesheet, passhint, hintanswer, email, status, ip, ' . (!$arr[0] ? 'class, ' : '') . 'added, last_access, time_offset, dst_in_use, free_switch) VALUES (' . implode(',', array_map('sqlesc', [
        $wantusername,
        $wantpasshash,
        $birthday,
        $country,
        $gender,
        $INSTALLER09['stylesheet'],
        $passhint,
        $wanthintanswer,
        $email,
        (!$arr[0] || (!$INSTALLER09['email_confirm'] || $INSTALLER09['auto_confirm']) ? 'confirmed' : 'pending'),
        $ip,
    ])) . ', ' . (!$arr[0] ? UC_SYSOP . ', ' : '') . '' . TIME_NOW . ',' . TIME_NOW . " , " . sqlesc($time_offset) . ", {$dst_in_use['tm_isdst']}, $user_frees)");
$mc1->delete_value('birthdayusers');
$mc1->delete_value('chat_users_list');
$message = "Welcome New {$INSTALLER09['site_name']} Member : - [user]" . htmlsafechars($wantusername) . '[/user]';
if (!$arr[0]) {
    write_staffs();
}
if (!$ret) {
    if (((is_object($GLOBALS['___mysqli_ston'])) ? mysqli_errno($GLOBALS['___mysqli_ston']) : (($___mysqli_res = mysqli_connect_errno()) ? $___mysqli_res : false)) == 1062) {
        stderr($lang['takesignup_user_error'], $lang['takesignup_user_exists']);
    }
}
$id = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS['___mysqli_ston']))) ? false : $___mysqli_res);
sql_query('INSERT INTO usersachiev (userid) VALUES (' . sqlesc($id) . ')') or sqlerr(__FILE__, __LINE__);
//==New member pm
$added = TIME_NOW;
$subject = sqlesc('Welcome');
$msg = sqlesc('Hey there ' . htmlsafechars($wantusername) . " ! Welcome to {$INSTALLER09['site_name']} ! :clap2: \n\n Please ensure your connectable before downloading or uploading any torrents\n - If your unsure then please use the forum and Faq or pm admin onsite.\n\ncheers {$INSTALLER09['site_name']} staff.\n");
sql_query("INSERT INTO messages (sender, subject, receiver, msg, added) VALUES (0, $subject, " . sqlesc($id) . ", $msg, $added)") or sqlerr(__FILE__, __LINE__);
//==End new member pm
$latestuser_cache['id'] = (int)$id;
$latestuser_cache['username'] = $wantusername;
$latestuser_cache['class'] = '0';
$latestuser_cache['donor'] = 'no';
$latestuser_cache['warned'] = '0';
$latestuser_cache['enabled'] = 'yes';
$latestuser_cache['chatpost'] = '1';
$latestuser_cache['leechwarn'] = '0';
$latestuser_cache['pirate'] = '0';
$latestuser_cache['king'] = '0';
/* OOP **/
$mc1->cache_value('latestuser', $latestuser_cache, $INSTALLER09['expires']['latestuser']);
write_log('User account ' . (int)$id . ' (' . htmlsafechars($wantusername) . ') was created');
if ($INSTALLER09['autoshout_on'] == 1) {
    autoshout($message);
}
$body = str_replace([
    '<#SITENAME#>',
    '<#USEREMAIL#>',
    '<#IP_ADDRESS#>',
    '<#REG_LINK#>',
], [
    $INSTALLER09['site_name'],
    $email,
    $ip,
    "{$INSTALLER09['baseurl']}/confirm.php?id=$id",
], $lang['takesignup_email_body']);
if ($arr[0] || $INSTALLER09['email_confirm']) {
    mail($email, "{$INSTALLER09['site_name']} {$lang['takesignup_confirm']}", $body, "{$lang['takesignup_from']} {$INSTALLER09['site_email']}");
}
header('Refresh: 0; url=ok.php?type=' . (!$arr[0] ? 'sysop' : ($INSTALLER09['email_confirm'] ? 'signup&email=' . urlencode($email) : 'confirm')));