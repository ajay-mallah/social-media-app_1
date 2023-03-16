<?php

namespace App\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Send email to the given email address.
 */
class Mailer
{
    /**
     * @var $smtpUser
     *   Stores SMTP username.
     */
    private $smtpUser;

    /**
     * @var $smtpPassword
     *   Stores SMTP user's password.
     */
    private $smtpPassword;

    /**
     * Initializes class variables.
     */
    public function __construct()
    {
        try {
            // Grabbing environment variables.
            $dotenv = new Dotenv();
            $dotenv->load(__DIR__.'/.env');
            $this->smtpPassword = $_ENV['SMTP_PASSWORD'];
            $this->smtpUser = $_ENV['SMTP_USER'];
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Mails an otp to given email address.
     * 
     *  @param string $email 
     *    Email address.
     * 
     *  @param string $otp 
     *    One Time Password.
     *  
     *  @param string $body 
     *    Email body.
     * 
     *  @param string $subject 
     *    Email subject.
     * 
     *  @return array 
     *    Returns error message and status. 
     */
    protected function mail(string $email, string $otp, string $body, string $subject) {
        // Initializing return variable.
        $return['status'] = null;
        $return['message'] = null;
        // Instance of php mailer.
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Sender email address.
            $mail->setFrom($this->smtpUser);
            // Receiver email address.
            $mail->addAddress($email);
            $mail->IsHTML(true); 
            $mail->Subject = $subject;
            $mail->Body    = $body;
            // Sending mail.
            if ($mail->send()) {
                $return['status'] = "success";
                $return['message'] = "OTP has been sent.";
            }
            else {
                $return['status'] = "danger";
                $return['message'] = "SMTP server failed to send OTP.";
            }
        } 
        catch (Exception $e) {
            $return['status'] = "danger";
            $return['message'] = $e->getMessage();
        }
        return $return;
    }
}