<?php 

namespace App\Services;

use App\Entity\OTP;
use App\Entity\User;
use App\Services\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Exception as GlobalException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\Dotenv\Dotenv;
/**
 * Resets user's password. 
 * Sends reset password key to user's email address.
 */
class ResetPassword extends Mailer
{
    /**
     * @var object $userRepo
     *   Instance of User repository
     */
    private $userRepo = null;

    /** 
     * @var EntityManagerInterface $em
     *   Entity Manager instance.
     */
    private $em = null;

    /**
     * @var array $userData 
     *    Stores OTP information.
     */
    private $userData;

    /**
     * Initializes class variables.
     * 
     *   @param EntityManagerInterface $em
     *     Entity Manager instance.
     * 
     *   @param array $userData
     *     Stores otp data.
     */
    public function __construct(EntityManagerInterface $em, array $userData)
    {
        $this->em = $em;
        $this->userRepo = $this->em->getRepository(User::class);
        $this->userData = $userData;
        
        parent::__construct();
    }
    
    /**
     * Inserts OTP into database and sends otp to user's email address.
     * 
     *   @return array
     *     Returns an array containing status and message.
     */
    public function setKey() {
        // Grabbing user.
        $result = [];
        try {
            $user = $this->userRepo->findOneBy(['email' => $this->userData['email']]);
            if (!$user) {
                return ['status' => 'danger', 'message' =>'user does not exist'];
            }
            else if ($user->getResetKey() && $user->isResetKeyStatus()) {
                return $this->mailResetKey($user->getEmail(), $user->getResetKey(), $user->getFullname());
            }
            else {
                $email = $user->getEmail();
                $key = $this->generateKey();
                $user->setResetKey($key);
                $user->setResetKeyStatus(TRUE);
                $this->em->merge($user);
                $this->em->flush();
                return $this->mailResetKey($user->getEmail(), $user->getResetKey(), $user->getFullname());
            }
        }
        catch (GlobalException $e) {
            echo $e->getMessage();
            $result['message'] = $e->getMessage();
        }
        $result['status'] = "danger";
        return $result;
    }

    /**
     * Generates 6 digit key.
     * 
     *   @return string 
     *     Returns random string.
     */
    private function generateKey() {
        $rootString = "35798QOIRPPNCAPOWJSPLIUFQVVJHW46102HEBOAWOHI";
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
     *  @param string $key 
     *    Reset key.
     * 
     *  @param string $name 
     *    users full name.
     *  
     *  @return array 
     *    Returns error message and status. 
     */
    private function mailResetKey(string $email, string $key, string $name) {
        $subject = 'ChirpTalk reset password';
        $body = 'Hello, <strong>'. $name. 
            '</strong><br>your reset password key is here: <strong>'. $key . 
            "</strong><br>Please use the key to reset your password. key is valid for 10 minutes.
            <br>If you didn't try to login just now, please ignore this email.";

        return $this->mail($email, $key, $body, $subject);
    }

    /**
     * Checks for valid reset key and resets password.
     * 
     *  @return array 
     *    Returns error message and status. 
     **/
    public function resetPassword() {
        $repository = $this->em->getRepository(User::class);
        if ($this->isEmpty()) {
            $response['status'] = 'danger';
            $response['message'] = 'Please fill all empty filed.';
        }
        else if ($repository->findOneBy(['email' => $this->userData['email'], 'resetKey' => $this->userData['key']])) {
            $user = $repository->findOneBy(['email' => $this->userData['email'], 'resetKey' => $this->userData['key']]);
            $user->setResetKeyStatus(FALSE);
            // Encrypting password.
            $options = ['cost' => 12];
            $password = password_hash($this->userData["password"], PASSWORD_BCRYPT, $options);
            $user->setPassword($password);

            $this->em->merge($user);
            $this->em->flush();
            
            $response['status'] = 'success';
            $response['message'] = 'password changed';
        }
        else {
            $response['status'] = 'danger';
            $response['message'] = 'Invalid reset key.';
        }
        return $response;
    }

    /**
     * Checks if any field is empty or not.
     * 
     *   @return boolean
     *     Returns TRUE if any field is empty.
     */
    private function isEmpty() {
        foreach ($this->userData as $key => $value) {
            if (empty($value)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * Checks password is same as repeated password.
     *
     *   @return bool 
     *     Returns TRUE if passwords are same.
     */
    private function isSamePwd() {
        return ($this->userData["password"] === $this->userData["confPassword"]);
    }
}