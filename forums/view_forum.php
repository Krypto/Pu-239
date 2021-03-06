<?php
require_once INCL_DIR . 'html_functions.php';
global $lang, $site_config, $CURUSER;

$posts = $colour = $delete_me = $rpic = $content = $child = $parent_forum_name = $first_post_arr = $post_status_image = '';
$forum_id = (isset($_GET['forum_id']) ? intval($_GET['forum_id']) : (isset($_POST['forum_id']) ? intval($_POST['forum_id']) : 0));
if (!is_valid_id($forum_id)) {
    stderr($lang['gl_error'], $lang['gl_bad_id']);
}
//=== who is here
sql_query('DELETE FROM now_viewing WHERE user_id = ' . sqlesc($CURUSER['id']));
sql_query('INSERT INTO now_viewing (user_id, forum_id, added) VALUES(' . sqlesc($CURUSER['id']) . ', ' . sqlesc($forum_id) . ', ' . TIME_NOW . ')');
//=== Get forum data
$res = sql_query('SELECT name, min_class_read, min_class_write, min_class_create, forum_id, parent_forum FROM forums WHERE min_class_read <= ' . sqlesc($CURUSER['class']) . ' AND id=' . sqlesc($forum_id) . ' LIMIT 1');
$arr = mysqli_fetch_assoc($res);
$forum_name = htmlsafechars($arr['name'], ENT_QUOTES);
$parent_forum_id = (int)$arr['parent_forum'];
if ($CURUSER['class'] < $arr['min_class_read']) {
    stderr($lang['gl_error'], $lang['gl_bad_id']); //=== why tell them there is a forums here...
}
$may_post = ($CURUSER['class'] >= $arr['min_class_write'] && $CURUSER['class'] >= $arr['min_class_create'] && $CURUSER['forum_post'] == 'yes' && $CURUSER['suspended'] == 'no');
//=== if a sub forum, get the info!
$res_sub_forums = sql_query('SELECT id AS sub_forum_id, name AS sub_form_name, description AS sub_form_description, min_class_read, post_count AS sub_form_post_count, topic_count AS sub_form_topic_count FROM forums WHERE min_class_read <= ' . sqlesc($CURUSER['class']) . ' AND parent_forum=' . sqlesc($forum_id) . ' ORDER BY sort');
if ($res_sub_forums) {
    //===sub forums
    $sub_forums_stuff = '';
    while ($sub_forums_arr = mysqli_fetch_array($res_sub_forums)) {
        if ($CURUSER['class'] < $sub_forums_arr['min_class_read']) {
            die;
        }
        $post_res = sql_query('SELECT t.id AS topic_id, t.topic_name, t.status AS topic_status, t.anonymous AS tan, 
												p.id AS last_post_id, p.topic_id, p.added, p.anonymous AS pan,
												u.id, u.username, u.class, u.donor, u.suspended, u.warned, u.enabled, u.chatpost, u.leechwarn, u.pirate, u.king, u.offensive_avatar
												FROM topics AS t 
												LEFT JOIN posts AS p ON t.id = p.topic_id 
												LEFT JOIN users AS u ON p.user_id = u.id 
												WHERE ' . ($CURUSER['class'] < UC_STAFF ? ' p.status = \'ok\' AND t.status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' p.status != \'deleted\'  AND t.status != \'deleted\'  AND' : '')) . ' 
												t.forum_id=' . sqlesc($sub_forums_arr['sub_forum_id']) . ' ORDER BY p.id DESC LIMIT 1');
        $post_arr = mysqli_fetch_assoc($post_res);
        //=== only do more if there is a post there...
        if ($post_arr['last_post_id'] > 0) {
            $last_topic_id = (int)$post_arr['topic_id'];
            $last_post_id = (int)$post_arr['last_post_id'];
            //=== topic status
            $topic_status = htmlsafechars($post_arr['topic_status']);
            switch ($topic_status) {
                case 'ok':
                    $topic_status_image = '';
                    break;

                case 'recycled':
                    $topic_status_image = ' <img src="' . $site_config['pic_base_url'] . 'forums/recycle_bin.gif" alt="' . $lang['fe_recycled'] . '" title="' . $lang['fe_this_topic_is_currently'] . ' ' . $lang['fe_in_the_recycle_bin'] . '" />';
                    break;

                case 'deleted':
                    $topic_status_image = ' <img src="' . $site_config['pic_base_url'] . 'forums/delete_icon.gif" alt="' . $lang['fe_deleted'] . '" title="' . $lang['fe_this_topic_is_currently'] . ' ' . $lang['fe_deleted'] . '" />';
                    break;
            }
            //== Anonymous
            if ($post_arr['tan'] == 'yes') {
                if ($CURUSER['class'] < UC_STAFF && $post_arr['user_id'] != $CURUSER['id']) {
                    $last_post = '<span>' . $lang['fe_last_post_by'] . ': <i>' . $lang['fe_anonymous'] . '</i> in &#9658; <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name'], ENT_QUOTES) . '">
						<span>' . CutName(htmlsafechars($post_arr['topic_name'], ENT_QUOTES), 30) . '</span></a>' . $topic_status_image . '<br>
						' . get_date($post_arr['added'], '') . '<br></span>';
                } else {
                    $last_post = '<span>' . $lang['fe_last_post_by'] . ': <i>' . $lang['fe_anonymous'] . '</i> [' . format_username($post_arr) . '] 
						<span> [ ' . get_user_class_name($post_arr['class']) . ' ] </span><br>
						in &#9658; <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name'], ENT_QUOTES) . '">
						<span>' . CutName(htmlsafechars($post_arr['topic_name'], ENT_QUOTES), 30) . '</span></a>' . $topic_status_image . '<br>
						' . get_date($post_arr['added'], '') . '<br></span>';
                }
            } else {
                $last_post = '<span>' . $lang['fe_last_post_by'] . ': ' . format_username($post_arr) . ' 
						<span> [ ' . get_user_class_name($post_arr['class']) . ' ] </span><br>
						in &#9658; <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $last_topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="' . htmlsafechars($post_arr['topic_name'], ENT_QUOTES) . '">
						<span>' . CutName(htmlsafechars($post_arr['topic_name'], ENT_QUOTES), 30) . '</span></a>' . $topic_status_image . '<br>
						' . get_date($post_arr['added'], '') . '<br></span>';
            }
            //=== last post read in topic
            $last_unread_post_res = sql_query('SELECT last_post_read FROM read_posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($last_post_id));
            $last_unread_post_arr = mysqli_fetch_row($last_unread_post_res);
            $last_unread_post_id = ($last_unread_post_arr[0] >= 0 ? $last_unread_post_arr[0] : $first_post_arr['first_post_id']);
            $image_to_use = ($post_arr['added'] > (TIME_NOW - $readpost_expiry)) ? (!$last_unread_post_arr || $last_post_id > $last_unread_post_arr[0]) : 0;
            $img = ($image_to_use ? 'unlockednew' : 'unlocked');
        } else {
            $last_post = $lang['fe_na'];
            $img = 'unlocked';
        }
        $sub_forums_stuff .= '<tr>
								<td><table class="table table-bordered table-striped">
								<tr>
								<td><img src="' . $site_config['pic_base_url'] . 'forums/' . $img . '.gif" alt="' . $img . '" title="' . $img . '" /></td>
								<td><a class="altlink" href="?action=view_forum&amp;forum_id=' . (int)$sub_forums_arr['sub_forum_id'] . '">
								' . htmlsafechars($sub_forums_arr['sub_form_name'], ENT_QUOTES) . '</a>
								' . ($CURUSER['class'] >= UC_ADMINISTRATOR ? '<span>
								[<a class="altlink" href="staffpanel.php?tool=forum_manage&amp;action=forum_manage&amp;action2=edit_forum_page&amp;id=' . (int)$sub_forums_arr['sub_forum_id'] . '">' . $lang['fe_edit'] . '</a>] 
								[<a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=delete_forum&amp;forum_id=' . (int)$sub_forums_arr['sub_forum_id'] . '">' . $lang['fe_delete'] . '</a>]
								</span>' : '') . '<br>
								<span>' . htmlsafechars($sub_forums_arr['sub_form_description'], ENT_QUOTES) . '</span></td>
								</tr>
								</table>
								</td>
								<td width="100px"><span>
								' . number_format($sub_forums_arr['sub_form_post_count']) . ' ' . $lang['fe_posts'] . '<br>
								' . number_format($sub_forums_arr['sub_form_topic_count']) . ' ' . $lang['fe_topics'] . '</span></td>
								<td width="200px"><span> ' . $last_post . ' </span></td>
								</tr>';
    } //=== end while loop
    $sub_forums = ($sub_forums_stuff !== '' ? '<table class="table table-bordered table-striped">
					<tr><td class="forum_head_dark" colspan="3">' . $forum_name . ' ' . $lang['fe_child_boards2'] . '</td>
					</tr>' . $sub_forums_stuff . '</table>' : '');
    //=== now we need the parent forums name :P I'll try to get this into another query :P
    $parent_forum_res = sql_query('SELECT name AS parent_forum_name FROM forums WHERE id=' . sqlesc($parent_forum_id) . ' LIMIT 1');
    $parent_forum_arr = mysqli_fetch_assoc($parent_forum_res);
    if ($arr['parent_forum'] > 0) {
        $child = '<span> [ ' . $lang['fe_child_board'] . ' ]</span>';
        $parent_forum_name = '<img src="' . $site_config['pic_base_url'] . 'forums/arrow_next.gif" alt="&#9658;" title="&#9658;" /> 
			<a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_forum&amp;forum_id=' . $parent_forum_id . '">' . htmlsafechars($parent_forum_arr['parent_forum_name'], ENT_QUOTES) . '</a>';
    }
}
//=== Get topic count
$res = sql_query('SELECT COUNT(id) FROM topics 	WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' status != \'deleted\'  AND' : '')) . '  forum_id=' . sqlesc($forum_id));
$row = mysqli_fetch_row($res);
$count = $row[0];
//=== get stuff for the pager
$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$perpage = $CURUSER['topicsperpage'] !== 0 ? $CURUSER['topicsperpage'] : (isset($_GET['perpage']) ? (int)$_GET['perpage'] : 15);
//$perpage = max(($CURUSER['topicsperpage'] !== 0 ? $CURUSER['topicsperpage'] :  (isset($_GET['perpage']) ? (int)$_GET['perpage'] : 15)), 15);
list($menu, $LIMIT) = pager_new($count, $perpage, $page, 'forums.php?action=view_forum&amp;forum_id=' . $forum_id . (isset($_GET['perpage']) ? '&amp;perpage=' . $perpage : ''));
//=== Get topics data
$topic_res = sql_query('SELECT t.id as id, t.user_id as user_id, t.topic_name as topic_name, t.locked as locked, t.forum_id as forum_id, 
			t.last_post as last_post,t.sticky as sticky, t.views as views,t.poll_id as poll_id,t.num_ratings as num_ratings,
			t.rating_sum as rating_sum,t.topic_desc as topic_desc,t.post_count as post_count, t.first_post as first_post, 
			t.status as status,t.main_forum_id as main_forum_id,t.anonymous as anonymous, p.id as post_id, p.added as post_added, 
			p.topic_id as post_topic_id
				FROM topics AS t 
				LEFT JOIN posts AS p ON t.id = p.topic_id 
				WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' status != \'deleted\'  AND' : '')) . '  forum_id=' . $forum_id . ' GROUP BY p.topic_id ORDER BY sticky, post_added DESC ' . $LIMIT);

if ($count > 0) {
    while ($topic_arr = mysqli_fetch_assoc($topic_res)) {
        $topic_id = (int)$topic_arr['id'];
        $locked = $topic_arr['locked'] == 'yes';
        $sticky = $topic_arr['sticky'] == 'yes';
        $topic_poll = (int)$topic_arr['poll_id'] > 0;
        //=== topic status
        $topic_status = htmlsafechars($topic_arr['status']);
        switch ($topic_status) {
            case 'ok':
                $topic_status_image = '';
                break;

            case 'recycled':
                $topic_status_image = ' <img src="' . $site_config['pic_base_url'] . 'forums/recycle_bin.gif" alt="' . $lang['fe_recycled'] . '" title="' . $lang['fe_this_topic_is_currently'] . ' ' . $lang['fe_in_the_recycle_bin'] . '" />';
                break;

            case 'deleted':
                $topic_status_image = ' <img src="' . $site_config['pic_base_url'] . 'forums/delete_icon.gif" alt="' . $lang['fe_deleted'] . '" title="' . $lang['fe_this_topic_is_currently'] . ' ' . $lang['fe_deleted'] . '" />';
                break;
        }
        //=== Get user ID and date of last post
        $res_post_stuff = sql_query('SELECT p.id AS last_post_id, p.added, p.user_id,  p.status, p.anonymous,
												u.id, u.username, u.class, u.donor, u.suspended, u.warned, u.enabled, u.chatpost, u.leechwarn, u.pirate, u.king
												FROM posts AS p 
												LEFT JOIN users AS u ON p.user_id = u.id 
												WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' p.status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' p.status != \'deleted\'  AND' : '')) . '  topic_id=' . sqlesc($topic_id) . '
												ORDER BY p.id DESC LIMIT 1');
        $arr_post_stuff = mysqli_fetch_assoc($res_post_stuff);
        //=== post status
        $post_status = htmlsafechars($arr_post_stuff['status']);
        switch ($post_status) {
            case 'ok':
                $post_status_image = '';
                break;

            case 'recycled':
                $post_status_image = ' <img src="' . $site_config['pic_base_url'] . 'forums/recycle_bin.gif" alt="' . $lang['fe_recycled'] . '" title="' . $lang['fe_this_post_is_currently'] . ' ' . $lang['fe_in_the_recycle_bin'] . '" width="18px" />';
                break;

            case 'deleted':
                $post_status_image = ' <img src="' . $site_config['pic_base_url'] . 'forums/delete_icon.gif" alt="' . $lang['fe_deleted'] . '" title="' . $lang['fe_this_post_is_currently'] . ' ' . $lang['fe_deleted'] . '" width="18px" />';
                break;

            case 'postlocked':
                $post_status = 'postlocked';
                $post_status_image = ' <img src="' . $site_config['pic_base_url'] . 'forums/thread_locked.gif" alt="' . $lang['fe_locked'] . '" title="' . $lang['fe_this_post_is_currently'] . ' ' . $lang['fe_locked'] . '" width="18px" />';
                break;
        }
        //== Anonymous
        if ($arr_post_stuff['anonymous'] == 'yes') {
            if ($CURUSER['class'] < UC_STAFF && $arr_post_stuff['user_id'] != $CURUSER['id']) {
                $last_post_username = ($arr_post_stuff['username'] !== '' ? '<i>' . $lang['fe_anonymous'] . '</i>' : '' . $lang['fe_lost'] . ' [' . (int)$arr_post_stuff['id'] . ']');
            } else {
                $last_post_username = ($arr_post_stuff['username'] !== '' ? '<i>' . $lang['fe_anonymous'] . '</i> [' . format_username($arr_post_stuff) . ']' : '' . $lang['fe_lost'] . ' [' . (int)$arr_post_stuff['id'] . ']');
            }
        } else {
            $last_post_username = ($arr_post_stuff['username'] !== '' ? format_username($arr_post_stuff) : '' . $lang['fe_lost'] . ' [' . (int)$arr_post_stuff['id'] . ']');
        }
        //==
        $last_post_id = (int)$arr_post_stuff['last_post_id'];
        //=== Get author / first post info
        $first_post_res = sql_query('SELECT p.id AS first_post_id, p.added, p.icon, p.body, p.anonymous, p.user_id,
												u.id, u.username, u.class, u.donor, u.suspended, u.warned, u.enabled, u.chatpost, u.leechwarn, u.pirate, u.king
												FROM posts AS p 
												LEFT JOIN users AS u ON p.user_id = u.id 
												WHERE  ' . ($CURUSER['class'] < UC_STAFF ? ' p.status = \'ok\' AND' : ($CURUSER['class'] < $min_delete_view_class ? ' p.status != \'deleted\'  AND' : '')) . '  
												topic_id=' . sqlesc($topic_id) . ' ORDER BY p.id ASC LIMIT 1');
        $first_post_arr = mysqli_fetch_assoc($first_post_res);
        //== Anonymous
        if ($first_post_arr['anonymous'] == 'yes') {
            if ($CURUSER['class'] < UC_STAFF && $first_post_arr['user_id'] != $CURUSER['id']) {
                $thread_starter = ($first_post_arr['username'] !== '' ? '<i>' . $lang['fe_anonymous'] . '</i>' : '' . $lang['fe_lost'] . ' [' . $topic_arr['user_id'] . ']') . '<br>' . get_date($first_post_arr['added'], '');
            } else {
                $thread_starter = ($first_post_arr['username'] !== '' ? '<i>' . $lang['fe_anonymous'] . '</i> [' . format_username($first_post_arr) . ']' : '' . $lang['fe_lost'] . ' [' . $topic_arr['user_id'] . ']') . '<br>' . get_date($first_post_arr['added'], '');
            }
        } else {
            $thread_starter = ($first_post_arr['username'] !== '' ? format_username($first_post_arr) : '' . $lang['fe_lost'] . ' [' . $topic_arr['user_id'] . ']') . '<br>' . get_date($first_post_arr['added'], '');
        }
        //==
        $icon = ($first_post_arr['icon'] == '' ? '<img src="' . $site_config['pic_base_url'] . 'forums/topic_normal.gif" alt="' . $lang['fe_topic'] . '" title="' . $lang['fe_topic'] . '" />' : '<img src="' . $site_config['pic_base_url'] . 'smilies/' . htmlsafechars($first_post_arr['icon']) . '.gif" alt="' . htmlsafechars($first_post_arr['icon']) . '" />');
        $first_post_text = tool_tip('<img src="' . $site_config['pic_base_url'] . 'forums/mg.gif" height="14" alt="' . $lang['fe_preview'] . '" title="' . $lang['fe_preview'] . '" />', format_comment($first_post_arr['body'], true, false, false), '' . $lang['fe_first_post'] . ' ' . $lang['fe_preview'] . '');
        //=== last post read in topic
        $last_unread_post_res = sql_query('SELECT last_post_read FROM read_posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($topic_id));
        $last_unread_post_arr = mysqli_fetch_row($last_unread_post_res);
        $last_unread_post_id = ($last_unread_post_arr[0] > 0 ? $last_unread_post_arr[0] : $first_post_arr['first_post_id']);
        $did_i_post_here = sql_query('SELECT user_id FROM posts WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($topic_id));
        $posted = (mysqli_num_rows($did_i_post_here) > 0 ? 1 : 0);
        //=== add subscribed forum image
        $sub = sql_query('SELECT user_id FROM subscriptions WHERE user_id=' . sqlesc($CURUSER['id']) . ' AND topic_id=' . sqlesc($topic_id));
        $subscriptions = (mysqli_num_rows($sub) > 0 ? 1 : 0);
        //=== make the multi pages thing...
        $total_pages = floor($posts / $perpage);
        switch (true) {
            case $total_pages == 0:
                $multi_pages = '';
                break;

            case $total_pages > 11:
                $multi_pages = ' <span> <img src="' . $site_config['pic_base_url'] . 'forums/multipage.gif" alt="+" title="+" />';
                for ($i = 1; $i < 5; ++$i) {
                    $multi_pages .= ' <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $i . '">' . $i . '</a>';
                }
                $multi_pages .= ' ... ';
                for ($i = ($total_pages - 2); $i <= $total_pages; ++$i) {
                    $multi_pages .= ' <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $i . '">' . $i . '</a>';
                }
                $multi_pages .= '</span>';
                break;

            case $total_pages < 11:
                $multi_pages = ' <span> <img src="' . $site_config['pic_base_url'] . 'forums/multipage.gif" alt="+" title="+" />';
                for ($i = 1; $i < $total_pages; ++$i) {
                    $multi_pages .= ' <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $i . '">' . $i . '</a>';
                }
                $multi_pages .= '</span>';
                break;
        }
        $new = ($arr_post_stuff['added'] > (TIME_NOW - $readpost_expiry)) ? (!$last_unread_post_res || $last_post_id > $last_unread_post_id) : 0;
        $topic_pic = ($posts < 30 ? ($locked ? ($new ? 'lockednew' : 'locked') : ($new ? 'topicnew' : 'topic')) : ($locked ? ($new ? 'lockednew' : 'locked') : ($new ? 'hot_topic_new' : 'hot_topic')));
        $topic_name = ($sticky ? '<img src="' . $site_config['pic_base_url'] . 'forums/pinned.gif" alt="' . $lang['fe_pinned'] . '" title="' . $lang['fe_pinned'] . '" /> ' : ' ') . ($topic_poll ? '<img src="' . $site_config['pic_base_url'] . 'forums/poll.gif" alt="Poll:" title="' . $lang['fe_poll'] . '" /> ' : ' ') . ' <a class="altlink" href="?action=view_topic&amp;topic_id=' . $topic_id . '">' . htmlsafechars($topic_arr['topic_name'], ENT_QUOTES) . '</a> ' . ($posted ? '<img src="' . $site_config['pic_base_url'] . 'forums/posted.gif" alt="Posted" title="Posted" /> ' : ' ') . ($subscriptions ? '<img src="' . $site_config['pic_base_url'] . 'forums/subscriptions.gif" alt="' . $lang['fe_subscribed'] . '" title="Subcribed" /> ' : ' ') . ($new ? ' <img src="' . $site_config['pic_base_url'] . 'forums/new.gif" alt="' . $lang['fe_new_post_in_topic'] . '!" title="' . $lang['fe_new_post_in_topic'] . '!" />' : '') . $multi_pages;
        $rpic = ($topic_arr['num_ratings'] != 0 ? ratingpic_forums(round($topic_arr['rating_sum'] / $topic_arr['num_ratings'], 1)) : '');
        //=== delete thread  //= .$delete_me
        if ($CURUSER['class'] == UC_MAX && $forum_id === 2) { //=== set this to your forum that you don't want to bother with the sanity check
            $delete_me = '&#160;&#160;&#160;&#160;&#160;<span>[ <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=delete_topic&amp;topic_id=' . $topic_id . '&amp;sure=1&amp;send_me_back=666">' . $lang['fe_delete'] . '</a> ]</span>';
        }
        $content .= '<tr>
		<td><img src="' . $site_config['pic_base_url'] . 'forums/' . $topic_pic . '.gif" alt="' . $lang['fe_topic'] . '" title="' . $lang['fe_topic'] . '" /></td>
		<td>' . $icon . '</td>
		<td>
		<table class="table table-bordered table-striped">
		<tr>
		<td >' . $topic_name . $first_post_text . $topic_status_image . '</td>
		<td>' . $rpic . '</td>
		</tr>
		</table>
		' . ($topic_arr['topic_desc'] !== '' ? '&#9658; <span>' . htmlsafechars($topic_arr['topic_desc'], ENT_QUOTES) . '</span>' : '') . '</td>
		<td>' . $thread_starter . '</td>
		<td>' . number_format($topic_arr['post_count']) . '</td>
		<td>' . number_format($topic_arr['views']) . '</td>
		<td><span>' . get_date($arr_post_stuff['added'], '') . '</span><br>
		<a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $last_post_id . '#' . $last_post_id . '" title="' . $lang['fe_go_to_the_last_post_in_this_thread'] . '">' . $lang['fe_last_post'] . '</a> by&#160;' . $last_post_username . '</td>
		<td>' . $post_status_image . ' <a class="altlink" href="' . $site_config['baseurl'] . '/forums.php?action=view_topic&amp;topic_id=' . $topic_id . '&amp;page=' . $last_unread_post_id . '#' . $last_unread_post_id . '" title="' . $lang['fe_last_unread_post_in_this_thread'] . '">
		<img src="' . $site_config['pic_base_url'] . 'forums/last_post.gif" alt="' . $lang['fe_last_post'] . '" title="' . $lang['fe_last_post'] . '" /></a></td>
		</tr>';
    }
    $the_top_and_bottom = '<table class="table table-bordered table-striped">
		<tr><td class="three" width="33%"></td>
		<td class="three" width="33%">' . (($count > $perpage) ? $menu : '') . '</td>
		<td class="three"  width="34%">' . ($locked == 'yes' && $_GET['action'] == 'view_topic' ? '<span>
		' . $lang['fe_this_topic_is_locked'] . '... ' . $lang['fe_no_new_posts_are_allowed'] . '.</span>' : ($may_post ? '<form action="' . $site_config['baseurl'] . '/forums.php" method="post" name="new">
		<input type="hidden" name="action" value="new_topic" />
		<input type="hidden" name="forum_id" value="' . $forum_id . '" />
		<input type="submit" name="button" class="button" value="' . $lang['fe_new_topic'] . '" />
		</form>' : '<span>
		' . $lang['fe_you_are_not_permitted_to_post_in_this_forum.'] . '</span>')) . '</td></tr></table>';
} else {
    $content .= '<tr><td class="clear" colspan="8">
	<span>' . $lang['fe_no_topics_found'] . '</span><br>
		' . ($may_post ? '<form action="' . $site_config['baseurl'] . '/forums.php" method="post" name="new">
		<input type="hidden" name="action" value="new_topic" />
		<input type="hidden" name="forum_id" value="' . $forum_id . '" />
        <input type="submit" name="button" class="button" value="' . $lang['fe_start_new_topic'] . '" />
		</form></td></tr>' : '<span>' . $lang['fe_you_are_not_permitted_to_post_in_this_forum.'] . '</span>');
    $the_top_and_bottom = '';
}
$HTMLOUT .= $mini_menu . '<br>' . $sub_forums . '
		<table class="table table-bordered table-striped">
		<tr><td class="clear" colspan="8">
		' . $the_top_and_bottom . '
		</td>
		</tr>
		' . ($count == 0 ? '' : '<tr>
		<td class="forum_head_dark" width="10"><img src="' . $site_config['pic_base_url'] . 'forums/topic.gif" alt="' . $lang['fe_topic'] . '" title="' . $lang['fe_topic'] . '" /></td>
		<td class="forum_head_dark" width="10"><img src="' . $site_config['pic_base_url'] . 'forums/topic_normal.gif" alt=' . $lang['fe_thread_icon'] . '" title=' . $lang['fe_thread_icon'] . '" /></td>
		<td class="forum_head_dark">' . $lang['fe_topic'] . '</td>
		<td class="forum_head_dark">' . $lang['fe_started_by'] . '</td>
		<td class="forum_head_dark" width="10">' . $lang['fe_replies'] . '</td>
		<td class="forum_head_dark" width="10">' . $lang['fe_views'] . '</td>
		<td class="forum_head_dark" width="140">' . $lang['fe_last_post'] . '</td>
		<td class="forum_head_dark" width="10"><img src="' . $site_config['pic_base_url'] . 'forums/last_post.gif" alt="' . $lang['fe_last_post'] . '" title="' . $lang['fe_last_post'] . '" /></td>
		</tr>') . $content . '
		<tr><td class="clear" colspan="8">
		' . $the_top_and_bottom . '</td>
		</tr></table><br>' . $mini_menu;
