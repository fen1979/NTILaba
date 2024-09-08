<?php

/**
 * @throws \PHPMailer\PHPMailer\Exception
 */
function checkToolsNextCheckDate()
{
    $tools = R::findAll(TOOLS);

    foreach ($tools as $tool) {
        list($name, $mail) = json_decode($tool['service_manager']);
        $days = $tool['work_life'];
        $body = '';

        // if time before 3 month to inspection expiration date send mail once per day
        if ($tool['date_of_inspection'] && isDateInRange($tool['date_of_inspection'], $days)) {
            $body = "<h3>Good day $name! You are responsible for this operation.</h3>";
            $body .= "<p>You have received this email because the tool is approaching the end of its life, tool serial number: {$tool['qc_serial']}.</p>";
            $body .= '<p style="color: red; font-size: 1.1em;">Expiration date ' . $tool['expaire_date'] . ',</p>';
            $body .= '<p>you should re-inspect the instrument for operability in the next 3 months!</p>';
            $body .= '<p>After receiving the certificate and the date of the next survey, you should go to the tools menu and renew your licenses for use!</p>';
            $body .= '<p>This letter will be sent once a day until the license is renewed!</p>';
            $body .= '<p>Best regards,<br>NTI Group Company</p>';
            $subject = 'NTI Tools Notification Service';
        }

        // if time before 1 month to maintenance date send mail once per day
        if ($tool['service_date'] && isDateInRange($tool['service_date'], $days)) {
            $body = "<h3>Good day $name! You are responsible for this operation.</h3>";
            $body .= "<p>Tool service time is approaching! The instrument serial number: {$tool['qc_serial']}.</p>";
            $body .= '<p>must be verified and serviced within 2 weeks from the date of receipt of this letter!</p>';
            $body .= '<p>After performing the required technical work on the tool, update the information about the next service date in the program!</p>';
            $body .= '<p>You will receive this letter every day for the next 2 weeks or until the information in the system is updated!</p>';
            $body .= '<p>Best regards,<br>NTI Group Company</p>';
            $subject = 'NTI Tools Maintenance Service';
        }

        if (!empty($body)) {
            echo $answer = Mailer::SendEmailNotification($mail, $name, $subject, $body);
            writeLogs($logdata);
        }
    }
}


/**
 * Проверяет, находится ли переданная дата в указанном диапазоне от текущей даты.
 *
 * @param string $dateFromDb Дата из базы данных в формате 'Y-m-d H:i:s'.
 * @param int $days Диапазон в днях (положительное значение для будущих дат, отрицательное для прошлых).
 * @return bool Возвращает true, если дата в диапазоне, иначе false.
 * @throws Exception
 */
function isDateInRange(string $dateFromDb, int $days): bool
{
    $currentDate = new DateTime();
    $targetDate = new DateTime($dateFromDb);
    $interval = new DateInterval("P" . abs($days) . "D");

    if ($days > 0) {
        $rangeStartDate = $currentDate;
        $rangeEndDate = (clone $currentDate)->add($interval);
    } else {
        $rangeStartDate = (clone $currentDate)->sub($interval);
        $rangeEndDate = $currentDate;
    }

    return ($targetDate >= $rangeStartDate && $targetDate <= $rangeEndDate);
}


function writeLogs($logdata)
{
    //$log = R::dispense(LOGS);

}

function sendNotificationAboutLongTimeWaitingParcelCheck()
{
    // check what track is compare with 48 hours
    // get all data from track
    // send notifications
    $t = R::findAll(TRACK_DATA, 'processed = 0');
    $trackingController = new TrackingController($user);
    $trackingController->cronRequest();
}

try {
    //checkToolsNextCheckDate();
    //sendNotificationAboutLongTimeWaitingParcelCheck();
} catch (\PHPMailer\PHPMailer\Exception $e) {
}
