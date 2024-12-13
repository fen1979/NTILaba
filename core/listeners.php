<?php
if (isset($_POST['uid'])) {
    $uid = _E($_POST['uid']);

    $logs = R::findOne(LOGS, ' ORDER BY id DESC LIMIT 1');
    $hd = R::load('hashdump', $uid);
    if ($logs->id != (int)$hd->uid) {

        if ($logs->object_type == 'PROJECT' || $logs->object_type == 'ORDER' || $logs->object_type == 'ORDER_CHAT') {
            $hd->uid = $logs->id;
            try {
                R::store($hd);
            } catch (\RedBeanPHP\RedException\SQL $e) {
                // message collector (text/ color/ auto_hide = true)
                _flashMessage('Error: ' . $e->getMessage(), 'danger');
            }
            echo '1';
        }
    }
    exit();
}

class listeners
{
    public static function ActionProceed(): string
    {
       $message = 'Error: This client: ' . $existingCustomer->name . ' information about phone or email was changed, ' .
           '<br> Do you want to proceed operation?<br>' .
           '<button id="proceed-button-flashmsg" class="btn btn-outline-warning">proceed</button>';

        return $message;
    }
}

//i скрипт проверяет логи если в логах появилась новая ззапись если да,
// то пишем ее айди в отдельную таблицу и отправляем пользователю has_changes
//i при запросах проверяем таблицу состояний