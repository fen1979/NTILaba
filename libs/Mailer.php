<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require 'mailer/Exception.php';
require 'mailer/PHPMailer.php';
require 'mailer/SMTP.php';

const USER_NAME = 'amir.ntilab@gmail.com';
const PASS_WORD = 'auns qmav fopu xpkb';
class Mailer
{
    /**
     * ЦЕНТР СООБЩЕНИЙ ДЛЯ ВСЕХ ПОЛЬЗОВАТЕЛЕЙ СИСТЕМЫ
     * @param $email
     * @param $to_whom - name user for who is email
     * @param $subject - email subject title
     * @param $html_body - email body html or text
     * @param string $attachment
     * @param string $attach_name
     * @return string
     * @throws Exception
     */
    public static function SendEmailNotification($email, $to_whom, $subject, $html_body, string $attachment = '', string $attach_name = ''): string
    {
        $mail = new PHPMailer;
        /*Enable verbose debug output*/
        // $mail->SMTPDebug = 3;
        /*Set mailer to use SMTP*/
        $mail->isSMTP();
        /*Specify main and backup SMTP servers*/
        $mail->Host = 'smtp.gmail.com';
        /*Enable SMTP authentication*/
        $mail->SMTPAuth = true;
        /*SMTP username*/
        $mail->Username = USER_NAME;
        /*SMTP password*/
        $mail->Password = PASS_WORD;
        /*Enable TLS encryption, `ssl` also accepted*/
        $mail->SMTPSecure = 'tls';
        /*TCP port to connect to*/
        $mail->Port = 587;
        // Задаем кодировку письма
        $mail->CharSet = 'UTF-8'; // Обеспечивает корректное отображение символов
        $mail->Encoding = 'base64'; // Кодировка содержания письма

        /*set from data*/
        $mail->setFrom('nti@co.il', 'NTI Group');
        /*Add a recipient*/
        $mail->addAddress($email, $to_whom);
        /*Name is optional*/
        // $mail->addReplyTo('info@example.com', 'Information');
        // $mail->addCC('cc@example.com');
        // $mail->addBCC('bcc@example.com');
        if ($attachment != '') {
            if ($attach_name != '') {
                /*Add attachments   => '/var/tmp/file.tar.gz' */
                $mail->addAttachment($attachment);
            } else {
                /*Optional name  => '/tmp/image.jpg', 'new.jpg'*/
                $mail->addAttachment($attachment, $attach_name);
            }
        }
        /*Set email format to HTML*/
        $mail->isHTML();
        /*Here is the subject*/
        $mail->Subject = $subject;
        /*This is the HTML message body <b>in bold!</b>*/
        $mail->Body = $html_body;

        if (!$mail->send()) {
            $thestate = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        } else {
            $thestate = 'success';
        }
        return $thestate;
    }

    /**
     * Рассылка оповещений с несколькими получателями и файлами
     * @param array $emails - массив email адресов получателей
     * @param string $subject - тема письма
     * @param string $html_body - тело письма (HTML или текст)
     * @param array $attachments - массив файлов для вложений (до 6 файлов)
     * @return string
     * @throws Exception
     */
    public static function SendNotifications(array $emails, string $subject, string $html_body, array $attachments = []): string
    {
        $mail = new PHPMailer;
        /*Enable verbose debug output*/
        // $mail->SMTPDebug = 3;
        /*Set mailer to use SMTP*/
        $mail->isSMTP();
        /*Specify main and backup SMTP servers*/
        $mail->Host = 'smtp.gmail.com';
        /*Enable SMTP authentication*/
        $mail->SMTPAuth = true;
        /*SMTP username*/
        $mail->Username = USER_NAME;
        /*SMTP password*/
        $mail->Password = PASS_WORD;
        /*Enable TLS encryption, `ssl` also accepted*/
        $mail->SMTPSecure = 'tls';
        /*TCP port to connect to*/
        $mail->Port = 587;
        // Задаем кодировку письма
        $mail->CharSet = 'UTF-8'; // Обеспечивает корректное отображение символов
        $mail->Encoding = 'base64'; // Кодировка содержания письма

        /*set from data*/
        $mail->setFrom('nti@co.il', 'NTI Group');

        /*Добавляем несколько получателей*/
        foreach ($emails as $email) {
            $mail->addAddress($email);
        }

        /*Добавляем вложения (до 6 файлов)*/
        if (!empty($attachments)) {
            $count = 0;
            foreach ($attachments as $attachment) {
                if ($count < 6) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? ''); // Добавляем файл
                    $count++;
                }
            }
        }

        /*Set email format to HTML*/
        $mail->isHTML();
        /*Here is the subject*/
        $mail->Subject = $subject;
        /*This is the HTML message body*/
        $mail->Body = $html_body;

        if (!$mail->send()) {
            $thestate = 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
        } else {
            $thestate = 'success';
        }
        return $thestate;
    }
}