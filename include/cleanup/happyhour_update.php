<?php
function happyhour_update($data)
{
    global $site_config, $queries, $mc1;
    set_time_limit(1200);
    ignore_user_abort(true);
    require_once INCL_DIR . 'function_happyhour.php';
    //==Putyns HappyHour
    $f = $site_config['happyhour'];
    $happy = unserialize(file_get_contents($f));
    $happyHour = strtotime($happy['time']);
    $curDate = TIME_NOW;
    $happyEnd = $happyHour + 3600;
    if ($happy['status'] == 0 && $site_config['happy_hour'] == true) {
        if ($data['clean_log']) {
            write_log('Happy hour was @ ' . get_date($happyHour, 'LONG', 1, 0) . ' and Catid ' . $happy['catid'] . ' ');
        }
        happyFile('set');
    } elseif (($curDate > $happyEnd) && $happy['status'] == 1) {
        happyFile('reset');
    }
    //== End
    if ($data['clean_log'] && $queries > 0) {
        write_log("Happyhour Cleanup: Completed using $queries queries");
    }
}
