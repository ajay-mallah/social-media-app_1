<?php 

namespace App\Services;

use App\Entity\OTP;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\Mailer;
use Exception as GlobalException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\Dotenv\Dotenv;
/**
 * Generates otp. 
 * Sends otp to user's email address.
 */
class OTPSender extends Mailer
{
    /**
     * @var object $userRepo
     *   Instance of User repository
     */
    private $userRepo = null;

    /**
     * @var object $otpRepo
     *   Instance of OTP repository
     */
    private $otpRepo = null;

    /** 
     * @var EntityManagerInterface $em
     *   Entity Manager instance.
     */
    private $em = null;

    /**
     * @var array $otpData 
     *    Stores OTP information.
     */
    private $otpData;

    /**
     * Initializes class variables.
     * 
     *   @param EntityManagerInterface $em
     *     Entity Manager instance.
     * 
     *   @param array $otpData
     *     Stores otp data.
     */
    public function __construct(EntityManagerInterface $em, array $otpData)
    {
        $this->em = $em;
        $this->userRepo = $this->em->getRepository(User::class);
        $this->otpRepo = $this->em->getRepository(OTP::class);
        $this->otpData = $otpData;
        
        parent::__construct();
    }
    
    /**
     * Inserts OTP into database and sends otp to user's email address.
     * 
     *   @return array
     *     Returns an array containing status and message.
     */
    public function setOTP() {
        // Grabbing user.
        $result = [];
        try {
            $user = $this->userRepo->findOneBy([$this->otpData['title'] => $this->otpData['uid']]);
            $email = $user->getEmail();
            $otpString = $this->generateOtp();
            
            // Setting OTP variables.
            $otp = new OTP();
            $otp->setOtp($otpString);
            $otp->setUserId($user);
            $otp->setStatus("active");
            $otp->setCreateTime(new \DateTime('now'));

            $this->em->persist($otp);
            $this->em->flush();

            return $this->mailOTP($email, $otpString, $user->getFullname());
        }
        catch (GlobalException $e) {
            echo $e->getMessage();
            $result['message'] = $e->getMessage();
        }
        $result['status'] = "danger";
        return $result;
    }

    /**
     * Generates 6 digit OTP.
     * 
     *   @return string 
     *     Returns random string.
     */
    private function generateOtp() {
        $rootString = "3579846102";
        $strLen = strlen($rootString);
        $otp = "";
        $otpLength = 6;
        for ($index = 0; $index < $otpLength; $index++) {
            $otp .= $rootString[random_int(0, $strLen - 1)];
        }
        return $otp; 
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
     *  @param string $name 
     *    users full name.
     *  
     *  @return array 
     *    Returns error message and status. 
     */
    private function mailOTP(string $email, string $otp, $name) {
        $subject = 'ChirpTalk account validation';
        $body = ('Hello, <strong>'. $name. 
            '</strong>your otp is: <br><strong style="font-size:1.5rem;">'. $otp . 
            "</strong><br>Please use the OTP to complete your login process. OTP is valid for 10 minutes.
            <br>If you didn't try to login just now, please ignore this email.");
        
        return $this->mail($email, $otp, $body, $subject);
    }
}