<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'mailer/Exception.php';
require 'mailer/PHPMailer.php';
require 'mailer/SMTP.php';

class Mailer
{
    /**
     * ЦЕНТР СООБЩЕНИЙ ДЛЯ ВСЕХ ПОЛЬЗОВАТЕЛЕЙ СИСТЕМЫ
     * @param $email
     * @param $to_whom
     * @param $subject
     * @param $html_body
     * @param string $attachment
     * @param string $attach_name
     * @return string
     * @throws phpmailerException|Exception
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
        $mail->Username = 'amir.ntilab@gmail.com';
        /*SMTP password*/
        $mail->Password = 'auns qmav fopu xpkb';
        /*Enable TLS encryption, `ssl` also accepted*/
        $mail->SMTPSecure = 'tls';
        /*TCP port to connect to*/
        $mail->Port = 587;
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
}