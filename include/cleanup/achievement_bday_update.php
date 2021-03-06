<?php
function achievement_bday_update($data)
{
    global $site_config, $queries, $mc1;
    set_time_limit(1200);
    ignore_user_abort(true);
    $maxdt = (TIME_NOW - 86400 * 365); // 1year
    $maxdt2 = (TIME_NOW - 86400 * 730); // 2 years
    $maxdt3 = (TIME_NOW - 86400 * 1095); // 3 years
    $maxdt4 = (TIME_NOW - 86400 * 1460); // 4 years
    $maxdt5 = (TIME_NOW - 86400 * 1825); // 5 years
    $maxdt6 = (TIME_NOW - 86400 * 2190); // 6 years
    $res = sql_query("SELECT u.id, u.added, a.bday FROM users AS u LEFT JOIN usersachiev AS a ON u.id = a.userid WHERE enabled = 'yes' AND added < $maxdt") or sqlerr(__FILE__, __LINE__);
    $msg_buffer = $usersachiev_buffer = $achievements_buffer = [];
    if (mysqli_num_rows($res) > 0) {
        $dt = TIME_NOW;
        $subject = sqlesc('New Achievement Earned!');
        $points = random_int(1, 3);
        while ($arr = mysqli_fetch_assoc($res)) {
            $bday = (int)$arr['bday'];
            $added = (int)$arr['added'];
            if ($bday == 0 && $added < $maxdt) {
                $msg = sqlesc('Congratulations, you have just earned the [b]First Birthday[/b] achievement. :) [img]' . $site_config['baseurl'] . '/images/achievements/birthday1.png[/img]');
                $msgs_buffer[] = '(0,' . $arr['id'] . ',' . TIME_NOW . ', ' . sqlesc($msg) . ', ' . sqlesc($subject) . ')';
                $achievements_buffer[] = '(' . $arr['id'] . ', ' . TIME_NOW . ', \'First Birthday\', \'birthday1.png\' , \'Been a member for at least 1 year.\')';
                $usersachiev_buffer[] = '(' . $arr['id'] . ',1, ' . $points . ')';
                $mc1->delete_value('inbox_new_' . $arr['id']);
                $mc1->delete_value('inbox_new_sb_' . $arr['id']);
                $mc1->delete_value('user_achievement_points_' . $arr['id']);
                $var1 = 'bday';
            }
            if ($bday == 1 && $added < $maxdt2) {
                $msg = sqlesc('Congratulations, you have just earned the [b]Second Birthday[/b] achievement. :) [img]' . $site_config['baseurl'] . '/images/achievements/birthday2.png[/img]');
                $msgs_buffer[] = '(0,' . $arr['id'] . ',' . TIME_NOW . ', ' . sqlesc($msg) . ', ' . sqlesc($subject) . ')';
                $achievements_buffer[] = '(' . $arr['id'] . ', ' . TIME_NOW . ', \'Second Birthday\', \'birthday2.png\' , \'Been a member for a period of at least 2 years.\')';
                $usersachiev_buffer[] = '(' . $arr['id'] . ',2, ' . $points . ')';
                $mc1->delete_value('inbox_new_' . $arr['id']);
                $mc1->delete_value('inbox_new_sb_' . $arr['id']);
                $var1 = 'bday';
            }
            if ($bday == 2 && $added < $maxdt3) {
                $msg = sqlesc('Congratulations, you have just earned the [b]Third Birthday[/b] achievement. :) [img]' . $site_config['baseurl'] . '/images/achievements/birthday3.png[/img]');
                $msgs_buffer[] = '(0,' . $arr['id'] . ',' . TIME_NOW . ', ' . sqlesc($msg) . ', ' . sqlesc($subject) . ')';
                $achievements_buffer[] = '(' . $arr['id'] . ', ' . TIME_NOW . ', \'Third Birthday\', \'birthday3.png\' , \'Been a member for a period of at least 3 years.\')';
                $usersachiev_buffer[] = '(' . $arr['id'] . ',3, ' . $points . ')';
                $mc1->delete_value('inbox_new_' . $arr['id']);
                $mc1->delete_value('inbox_new_sb_' . $arr['id']);
                $var1 = 'bday';
            }
            if ($bday == 3 && $added < $maxdt4) {
                $msg = sqlesc('Congratulations, you have just earned the [b]Fourth Birthday[/b] achievement. :) [img]' . $site_config['baseurl'] . '/images/achievements/birthday4.png[/img]');
                $msgs_buffer[] = '(0,' . $arr['id'] . ',' . TIME_NOW . ', ' . sqlesc($msg) . ', ' . sqlesc($subject) . ')';
                $achievements_buffer[] = '(' . $arr['id'] . ', ' . TIME_NOW . ', \'Fourth Birthday\', \'birthday4.png\' , \'Been a member for a period of at least 4 years.\')';
                $usersachiev_buffer[] = '(' . $arr['id'] . ',4, ' . $points . ')';
                $mc1->delete_value('inbox_new_' . $arr['id']);
                $mc1->delete_value('inbox_new_sb_' . $arr['id']);
                $var1 = 'bday';
            }
            if ($bday == 4 && $added < $maxdt5) {
                $msg = sqlesc('Congratulations, you have just earned the [b]Fifth Birthday[/b] achievement. :) [img]' . $site_config['baseurl'] . '/images/achievements/birthday5.png[/img]');
                $msgs_buffer[] = '(0,' . $arr['id'] . ',' . TIME_NOW . ', ' . sqlesc($msg) . ', ' . sqlesc($subject) . ')';
                $achievements_buffer[] = '(' . $arr['id'] . ', ' . TIME_NOW . ', \'Fifth Birthday\', \'birthday5.png\' , \'Been a member for a period of at least 5 years.\')';
                $usersachiev_buffer[] = '(' . $arr['id'] . ',5, ' . $points . ')';
                $mc1->delete_value('inbox_new_' . $arr['id']);
                $mc1->delete_value('inbox_new_sb_' . $arr['id']);
                $var1 = 'bday';
            }
            if ($bday == 5 && $added < $maxdt6) {
                $msg = sqlesc('Congratulations, you have just earned the [b]Sixth Birthday[/b] achievement. :) [img]' . $site_config['baseurl'] . '/images/achievements/birthday6.png[/img]');
                $msgs_buffer[] = '(0,' . $arr['id'] . ',' . TIME_NOW . ', ' . sqlesc($msg) . ', ' . sqlesc($subject) . ')';
                $achievements_buffer[] = '(' . $arr['id'] . ', ' . TIME_NOW . ', \'Sixth Birthday\', \'birthday6.png\' , \'Been a member for a period of at least 6 years.\')';
                $usersachiev_buffer[] = '(' . $arr['id'] . ',6, ' . $points . ')';
                $mc1->delete_value('inbox_new_' . $arr['id']);
                $mc1->delete_value('inbox_new_sb_' . $arr['id']);
                $var1 = 'bday';
            }
        }
        $count = count($achievements_buffer);
        if ($count > 0) {
            sql_query('INSERT INTO messages (sender,receiver,added,msg,subject) VALUES ' . implode(', ', $msgs_buffer)) or sqlerr(__FILE__, __LINE__);
            sql_query('INSERT INTO achievements (userid, date, achievement, icon, description) VALUES ' . implode(', ', $achievements_buffer) . ' ON DUPLICATE key UPDATE date=values(date),achievement=values(achievement),icon=values(icon),description=values(description)') or sqlerr(__FILE__, __LINE__);
            sql_query("INSERT INTO usersachiev (userid, $var1, achpoints) VALUES " . implode(', ', $usersachiev_buffer) . " ON DUPLICATE key UPDATE $var1=values($var1), achpoints=achpoints+values(achpoints)") or sqlerr(__FILE__, __LINE__);
        }
        if ($data['clean_log'] && $queries > 0) {
            write_log("Achievements Cleanup: Birthdays Completed using $queries queries. Birthday Achievements awarded to - " . $count . ' Member(s)');
        }
        unset($usersachiev_buffer, $achievement_buffer, $msgs_buffer, $count);
    }
}
