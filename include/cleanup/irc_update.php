<?php
function irc_update($data)
{
    global $site_config, $queries, $mc1;
    set_time_limit(1200);
    ignore_user_abort(true);
    //==Irc idle mod - pdq
    $res = sql_query("SELECT id, seedbonus, irctotal FROM users WHERE onirc = 'yes'") or sqlerr(__FILE__, __LINE__);
    if (mysqli_num_rows($res) > 0) {
        while ($arr = mysqli_fetch_assoc($res)) {
            $users_buffer[] = '(' . $arr['id'] . ',0.225,' . $site_config['autoclean_interval'] . ')'; // .250 karma
            //$users_buffer[] = '('.$arr['id'].',15728640,'.$site_config['autoclean_interval'].')'; // 15 mb
            $update['seedbonus'] = ($arr['seedbonus'] + 0.225);
            $update['irctotal'] = ($arr['irctotal'] + $site_config['autoclean_interval']);
            $mc1->begin_transaction('user' . $arr['id']);
            $mc1->update_row(false, [
                'irctotal' => $update['irctotal'],
            ]);
            $mc1->commit_transaction($site_config['expires']['user_cache']);
            $mc1->begin_transaction('user_stats' . $arr['id']);
            $mc1->update_row(false, [
                'seedbonus' => $update['seedbonus'],
            ]);
            $mc1->commit_transaction($site_config['expires']['user_stats']);
            $mc1->begin_transaction('userstats_' . $arr['id']);
            $mc1->update_row(false, [
                'seedbonus' => $update['seedbonus'],
            ]);
            $mc1->commit_transaction($site_config['expires']['u_stats']);
        }
        $count = count($users_buffer);
        if ($count > 0) {
            sql_query('INSERT INTO users (id,seedbonus,irctotal) VALUES ' . implode(', ', $users_buffer) . ' ON DUPLICATE key UPDATE seedbonus=seedbonus+values(seedbonus),irctotal=irctotal+values(irctotal)') or sqlerr(__FILE__, __LINE__);
        }
        if ($data['clean_log']) {
            write_log('Cleanup ' . $count . ' users idling on IRC');
        }
        unset($users_buffer, $update, $count);
    }
    //== End
    if ($data['clean_log'] && $queries > 0) {
        write_log("Irc Cleanup: Completed using $queries queries");
    }
}
