<?php
require_once realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'include' . DIRECTORY_SEPARATOR . 'bittorrent.php';
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'torrenttable_functions.php';
require_once INCL_DIR . 'pager_functions.php';
require_once INCL_DIR . 'searchcloud_functions.php';
require_once CLASS_DIR . 'class_user_options.php';
require_once CLASS_DIR . 'class_user_options_2.php';
check_user_status();
global $CURUSER, $site_config, $mc1;

if (isset($_GET['clear_new']) && $_GET['clear_new'] == 1) {
    sql_query('UPDATE users SET last_browse = ' . TIME_NOW . ' WHERE id = ' . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
    $mc1->begin_transaction('MyUser_' . $CURUSER['id']);
    $mc1->update_row(false, [
        'last_browse' => TIME_NOW,
    ]);
    $mc1->commit_transaction($site_config['expires']['curuser']);
    $mc1->begin_transaction('user' . $CURUSER['id']);
    $mc1->update_row(false, [
        'last_browse' => TIME_NOW,
    ]);
    $mc1->commit_transaction($site_config['expires']['user_cache']);
    header("Location: {$site_config['baseurl']}/browse.php");
}
$stdfoot = [
    'js' => [
    ],
];
$stdhead = [
    'css' => [
        get_file('browse_css')
    ],
];
$lang = array_merge(load_language('global'), load_language('browse'), load_language('torrenttable_functions'));
$HTMLOUT = $searchin = $select_searchin = $where = $addparam = $new_button = $vip_box = $only_free = $searchstr = '';
$searchincrt = [];

$catids = genrelist();
if (isset($_GET['search'])) {
    $searchstr = unesc($_GET['search']);
    $cleansearchstr = searchfield($searchstr);
    if (empty($cleansearchstr)) {
        unset($cleansearchstr);
    }
}
$valid_searchin = [
    'title' => [
        'name',
    ],
    'descr' => [
        'descr',
    ],
    'genre' => [
        'newgenre',
    ],
    'all' => [
        'name',
        'newgenre',
        'descr',
    ],
];
if (isset($_GET['searchin']) && isset($valid_searchin[$_GET['searchin']])) {
    $searchin = $valid_searchin[$_GET['searchin']];
    $select_searchin = $_GET['searchin'];
    $addparam .= sprintf('search=%s&amp;searchin=%s&amp;', $searchstr, $select_searchin);
}
if (isset($_GET['sort']) && isset($_GET['type'])) {
    $column = $ascdesc = '';
    $_valid_sort = [
        'id',
        'name',
        'numfiles',
        'comments',
        'added',
        'size',
        'times_completed',
        'seeders',
        'leechers',
        'owner',
    ];
    $column = isset($_GET['sort']) && isset($_valid_sort[(int)$_GET['sort']]) ? $_valid_sort[(int)$_GET['sort']] : $_valid_sort[0];
    switch (htmlsafechars($_GET['type'])) {
        case 'asc':
            $ascdesc = 'ASC';
            $linkascdesc = 'asc';
            break;

        case 'desc':
            $ascdesc = 'DESC';
            $linkascdesc = 'desc';
            break;

        default:
            $ascdesc = 'DESC';
            $linkascdesc = 'desc';
            break;
    }
    $orderby = "ORDER BY {$column} " . $ascdesc;
    $pagerlink = 'sort=' . intval($_GET['sort']) . "&amp;type={$linkascdesc}&amp;";
} else {
    $orderby = 'ORDER BY sticky ASC, id DESC';
    $pagerlink = '';
}
$wherea = $wherecatina = [];
if (isset($_GET['incldead']) && $_GET['incldead'] == 1) {
    $addparam .= 'incldead=1&amp;';
    if (!isset($CURUSER) || $CURUSER['class'] < UC_ADMINISTRATOR) {
        $wherea[] = "banned != 'yes'";
    }
} else {
    if (isset($_GET['incldead']) && $_GET['incldead'] == 2) {
        $addparam .= 'incldead=2&amp;';
        $wherea[] = "visible = 'no'";
    } else {
        $wherea[] = "visible = 'yes'";
    }
}
if (isset($_GET['only_free']) && $_GET['only_free'] == 1) {
    $wherea[] = XBT_TRACKER == true ? $wherea[] = "freetorrent >= '1'" : $wherea[] = "free >= '1'";
    $addparam .= 'only_free=1&amp;';
}
if (isset($_GET['vip'])) {
    if ($_GET['vip'] == 2) {
        $wherea[] = "vip = '1'";
    } elseif ($_GET['vip'] == 1) {
        $wherea[] = "vip = '0'";
    }
    $addparam .= "vip={$_GET['vip']}&amp;";
}
$category = (isset($_GET['cat'])) ? (int)$_GET['cat'] : false;
if (!$_GET && $CURUSER['notifs']) {
    foreach ($catids as $cat) {
        if (strpos($CURUSER['notifs'], '[cat' . $cat['id'] . ']') !== false) {
            $wherecatina[] = $cat['id'];
            $addparam .= "c{$cat['id']}=1&amp;";
        }
    }
} elseif ($category) {
    if (!is_valid_id($category)) {
        stderr("{$lang['browse_error']}", "{$lang['browse_invalid_cat']}");
    }
    $wherecatina[] = $cat['id'];
    $addparam .= "cat=$category&amp;";
} else {
    foreach ($catids as $cat) {
        if (isset($_GET["c{$cat['id']}"])) {
            $wherecatina[] = $cat['id'];
            $addparam .= "c{$cat['id']}=1&amp;";
        }
    }
}

if (count($wherecatina) > 1) {
    $wherea[] = 'category IN (' . join(', ', $wherecatina) . ') ';
} elseif (count($wherecatina) == 1) {
    $wherea[] = 'category =' . $wherecatina[0];
}
if (isset($cleansearchstr)) {
    //== boolean search by djgrr
    if ($searchstr != '') {
        $addparam .= 'search=' . rawurlencode($searchstr) . '&amp;searchin=' . htmlsafechars($_GET['searchin']) . '&amp;incldead=' . intval($_GET['incldead']) . '&amp;';
        $searchstring = str_replace([
            '_',
            '.',
            '-',
        ], ' ', $searchstr);
        $s = [
            '*',
            '?',
            '.',
            '-',
            ' ',
        ];
        $r = [
            '%',
            '_',
            '_',
            '_',
            '_',
        ];

        $wherea[] = 'MATCH (`search_text`, `descr`) AGAINST (' . sqlesc($searchstr) . ' IN NATURAL LANGUAGE MODE)';

        $searcha = explode(' ', $cleansearchstr);
        searchcloud_insert($cleansearchstr);
        foreach ($searcha as $foo) {
            foreach ($searchin as $boo) {
                $searchincrt[] = 'MATCH (`name`) AGAINST (' . sqlesc($searchstr) . ' IN NATURAL LANGUAGE MODE)';
            }
        }
        $wherea[] = '( ' . join(' OR ', $searchincrt) . ' )';
    }
}
$where = count($wherea) ? 'WHERE ' . join(' AND ', $wherea) : '';
$where_key = 'where::' . sha1($where);
if (($count = $mc1->get_value($where_key)) === false) {
    file_put_contents('/var/log/nginx/browse.log', "SELECT COUNT(id) FROM torrents $where" . PHP_EOL, FILE_APPEND);
    $res = sql_query("SELECT COUNT(id) FROM torrents $where") or sqlerr(__FILE__, __LINE__);
    $row = mysqli_fetch_row($res);
    $count = (int)$row[0];
    $mc1->cache_value($where_key, $count, $site_config['expires']['browse_where']);
}
$torrentsperpage = $CURUSER['torrentsperpage'];
if (!$torrentsperpage) {
    $torrentsperpage = 15;
}
if ($count) {
    if ($addparam != '') {
        if ($pagerlink != '') {
            if ($addparam[strlen($addparam) - 1] != ';') { // & = &amp;
                $addparam = $addparam . '&' . $pagerlink;
            } else {
                $addparam = $addparam . $pagerlink;
            }
        }
    } else {
        $addparam = $pagerlink;
    }
    $pager = pager($torrentsperpage, $count, './browse.php?' . $addparam);
    $query = "SELECT t.id, t.search_text, t.category, t.leechers, t.seeders, t.bump, t.release_group, t.subs, t.name, t.times_completed, t.size, t.added, t.poster, t.descr, t.free, t.freetorrent, t.silver, t.comments, t.numfiles, t.filename, t.anonymous, t.sticky, t.nuked, t.vip, t.nukereason, t.newgenre, t.description, t.owner, t.youtube, t.checked_by, IF(t.nfo <> '', 1, 0) as nfoav," . "IF(t.num_ratings < {$site_config['minvotes']}, NULL, ROUND(t.rating_sum / t.num_ratings, 1)) AS rating, t.checked_when, c.username AS checked_by_username
                FROM torrents AS t
                LEFT JOIN users AS c ON t.checked_by = c.id
                {$where}
                {$orderby}
                {$pager['limit']}";
    $res = sql_query($query) or sqlerr(__FILE__, __LINE__);
} else {
    unset($query);
}

if (isset($cleansearchstr)) {
    $title = "{$lang['browse_search']} $searchstr";
} else {
    $title = '';
}

$HTMLOUT .= "
            <div class='container is-fluid portlet'>
                <div class='top20 bottom20'>";
if ($CURUSER['opt1'] & user_options::VIEWSCLOUD) {
    $HTMLOUT .= "
                            <div id='wrapper' class='cloud has-text-centered bottom10'>" . cloud() . "
                            </div>";
}

// create the category table
$HTMLOUT .= "
                            <div class='table-responsive has-text-centered'>
                                <form id='catsids' method='get' action='./browse.php'>
                                    <div class='bg-02 padding20 round5 top20 bottom20'>
                                        <div id='checkbox_container' class='level-center'>";
if ($CURUSER['opt2'] & user_options_2::BROWSE_ICONS) {
    foreach ($catids as $cat) {
        $HTMLOUT .= "
                                            <span class='margin10 mw-50 is-flex tooltipper' title='" . htmlsafechars($cat['name']) . "'>
                                                <span class='bordered level-center bg-02'>
                                                    <input name='c" . (int)$cat['id'] . "' class='styled' type='checkbox' " . (in_array($cat['id'], $wherecatina) ? " checked" : '') . " value='1' />
                                                    <span class='cat-image left10'>
                                                        <a href='./browse.php?c" . (int)$cat['id'] . "'>
                                                            <img class='radius-sm' src='{$site_config['pic_base_url']}caticons/{$CURUSER['categorie_icon']}/" . htmlsafechars($cat['image']) . "'alt='" . htmlsafechars($cat['name']) . "' />
                                                        </a>
                                                    </span>
                                                </span>
                                            </span>";
    }
} else {
    foreach ($catids as $cat) {
        $HTMLOUT .= "
                                            <span class='margin10 bordered tooltipper' title='" . htmlsafechars($cat['name']) . "'>
                                                <label for='c" . (int)$cat['id'] . "'>
                                                    <input name='c" . (int)$cat['id'] . "' class='styled1' type='checkbox' " . (in_array($cat['id'], $wherecatina) ? " checked" : '') . "value='1' />
                                                    <a class='catlink' href='./browse.php?cat=" . (int)$cat['id'] . "'>" . htmlsafechars($cat['name']) . "</a>
                                                </label>
                                            </span>";
    }
}
$HTMLOUT .= "
                                        </div>
                                        <div class='has-text-centered'>
                                            <label for='checkAll'>
                                                <input type='checkbox' id='checkAll' /><span> Select All Categories</span>
                                            </label>
                                        </div>
                                    </div>";

if ($CURUSER['opt1'] & user_options::CLEAR_NEW_TAG_MANUALLY) {
    $new_button = "
        <a href='?clear_new=1'><input type='submit' value='clear new tag' class='button' /></a>
        <br>";
} else {
    //== clear new tag automatically
    sql_query('UPDATE users SET last_browse = ' . TIME_NOW . ' WHERE id = ' . $CURUSER['id']);
    $mc1->begin_transaction('MyUser_' . $CURUSER['id']);
    $mc1->update_row(false, [
        'last_browse' => TIME_NOW,
    ]);
    $mc1->commit_transaction($site_config['expires']['curuser']);
    $mc1->begin_transaction('user' . $CURUSER['id']);
    $mc1->update_row(false, [
        'last_browse' => TIME_NOW,
    ]);
    $mc1->commit_transaction($site_config['expires']['user_cache']);
}

$vip = ((isset($_GET['vip'])) ? intval($_GET['vip']) : '');
$vip_box = "
                    <select name='vip' class='w-100'>
                        <option value='0'>VIP Torrents Included</option>
                        <option value='1'" . ($vip == 1 ? " selected" : '') . ">VIP Torrents Not Included</option>
                        <option value='2'" . ($vip == 2 ? " selected" : '') . ">VIP Torrents Only</option>
                    </select>";

$selected = (isset($_GET['incldead'])) ? (int)$_GET['incldead'] : '';
$deadcheck = "
                    <select name='incldead' class='w-100'>
                        <option value='0'>{$lang['browse_active']}</option>
                        <option value='1'" . ($selected == 1 ? " selected" : '') . ">{$lang['browse_inc_dead']}</option>
                        <option value='2'" . ($selected == 2 ? " selected" : '') . ">{$lang['browse_dead']}</option>
                    </select>";

$only_free = ((isset($_GET['only_free'])) ? intval($_GET['only_free']) : '');
$only_free_box = "
                    <select name='only_free' class='w-100'>
                        <option value='0'>Include Non Free Torrents</option>
                        <option value='1'" . ($only_free == 1 ? " selected" : '') . ">Include Only Free Torrents</option>
                    </select>";

$searchin = '
                    <select name="searchin" class="w-100">';
foreach ([
             'title' => 'Name',
             'descr' => 'Description',
             'genre' => 'Genre',
             'all' => 'All',
         ] as $k => $v) {
    $searchin .= '
                        <option value="' . $k . '"' . ($select_searchin == $k ? ' selected' : '') . '>' . $v . '</option>';
}
$searchin .= '
                    </select>';
$HTMLOUT .= "
                <div class='bg-02 padding20 round5'>
                    <div class='padding10' class='w-100'>
                        <input type='text' name='search' placeholder='{$lang['search_search']}' class='search w-100' value='" . (!empty($_GET['search']) ? $_GET['search'] : '') . "' />
                    </div>
                    <div class='level-center'>
                        <div class='padding10 w-25 mw-50'>
                            $searchin
                        </div>
                        <div class='padding10 w-25 mw-50'>
                            $deadcheck
                        </div>
                        <div class='padding10 w-25 mw-50'>
                            $vip_box
                        </div>
                        <div class='padding10 w-25 mw-50'>
                            $only_free_box
                        </div>
                    </div>
                    <div class='top10'>
                        <input type='submit' value='{$lang['search_search_btn']}' class='button' />
                    </div>
                </div>
            </form>";
$HTMLOUT .= "{$new_button}";
if (isset($cleansearchstr)) {
    $HTMLOUT .= "<h2>{$lang['browse_search']} " . htmlsafechars($searchstr, ENT_QUOTES) . "</h2>";
}
if ($count) {
    $HTMLOUT .= "
                <div class='top20 bottom20'>
                    {$pager['pagertop']}
                </div>
                <div class='table-wrapper'>" . torrenttable($res) . "</div>
                <div class='top20'>
                    {$pager['pagerbottom']}
                </div>";
} else {
    if (isset($cleansearchstr)) {
        $HTMLOUT .= "<h2>{$lang['browse_not_found']}</h2>";
        $HTMLOUT .= "<p>{$lang['browse_tryagain']}</p>";
    } else {
        $HTMLOUT .= "<h2>{$lang['browse_nothing']}</h2>";
        $HTMLOUT .= "<p>{$lang['browse_sorry']}(</p>";
    }
}
$HTMLOUT .= '</div>';
$ip = getip();
$no_log_ip = ($CURUSER['perms'] & bt_options::PERMS_NO_IP);
if ($no_log_ip) {
    $ip = '127.0.0.1';
}
if (!$no_log_ip) {
    $userid = (int)$CURUSER['id'];
    $added = TIME_NOW;
    $res = sql_query('SELECT * FROM ips WHERE ip = ' . sqlesc($ip) . ' AND userid = ' . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res) == 0) {
        sql_query('INSERT INTO ips (userid, ip, lastbrowse, type) VALUES (' . sqlesc($userid) . ', ' . sqlesc($ip) . ", $added, 'Browse')") or sqlerr(__FILE__, __LINE__);
        $mc1->delete_value('ip_history_' . $userid);
    } else {
        sql_query("UPDATE ips SET lastbrowse = $added WHERE ip = " . sqlesc($ip) . ' AND userid = ' . sqlesc($userid)) or sqlerr(__FILE__, __LINE__);
        $mc1->delete_value('ip_history_' . $userid);
    }
}
$HTMLOUT .= "
                    </div>
                </div>";
echo stdhead($title, true, $stdhead) . $HTMLOUT . stdfoot($stdfoot);
