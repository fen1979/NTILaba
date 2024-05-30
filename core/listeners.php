<?php
require 'rb-mysql.php';

if (isset($_POST['uid'])) {

    R::setup('mysql:host=localhost;dbname=nti_production', 'root', '8CwG24YwZG');
    if (!R::testConnection()) {
        exit ('No database connection');
    }
    $uid = $_POST['uid'];

    $logs = R::findOne('logs', ' ORDER BY id DESC LIMIT 1');
    $hd = R::load('hashdump', $uid);
    if ($logs->id > (int)$hd->uid) {
        if ($logs->object_type == 'PROJECT' || $logs->object_type == 'ORDER' || $logs->object_type == 'ORDER_CHAT') {
            $hd->uid = $logs->id;
            R::store($hd);
            echo 'has_changes';
        }
    }
}
exit('');

//i скрипт проверяет логи если в логах появилась новая ззапись если да,
// то пишем ее айди в отдельную таблицу и отправляем пользователю has_changes
//i при запросах проверяем таблицу состояний