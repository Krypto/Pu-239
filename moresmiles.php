<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'bbcode_functions.php';
check_user_status();
$lang = array_merge(load_language('global'));
$htmlout = '';
$htmlout = "<!doctype html>
<html>
<head>
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
	<title>More Smilies</title>
    <link rel='stylesheet' href='" . get_file('css') . "' />
</head>
<body>
    <script>
        function SmileIT(smile,form,text){
            window.opener.document.forms[form].elements[text].value = window.opener.document.forms[form].elements[text].value+' '+smile+' ';
            window.opener.document.forms[form].elements[text].focus();
            window.close();
        }
    </script>
    <table class='list' width='100%' cellpadding='1' cellspacing='1'>";
$count = '';
while ((list($code, $url) = each($smilies))) {
    if ($count % 3 == 0) {
        $htmlout .= "
        <tr>";
    }
    $htmlout .= "
            <td class='list has-text-centered'>
                <a href=\"javascript: SmileIT('" . str_replace("'", "\'", $code) . "','" . htmlsafechars($_GET['form']) . "','" . htmlsafechars($_GET['text']) . "')\">
                    <img border='0' src='./images/smilies/" . $url . "' alt='' />
                </a>
            </td>";
    ++$count;
    if ($count % 3 == 0) {
        $htmlout .= "
        </tr>";
    }
}
$htmlout .= "
    </table>
    <div class='has-text-centered'>
        <a href='javascript: window.close()'>[ Close Window ]</a>
    </div>
</body>
</html>";
echo $htmlout;
