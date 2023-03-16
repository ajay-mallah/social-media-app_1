<?php 

namespace App\Services;

use App\Entity\OTP;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Verifies OTP
 * Activates and Deactivates OTP. 
 */
class OTPVerifier 
{
    /**
     * @var object $em
     *   Instance of Entity Manager interface Instance
     */
    private $em = null;

    /**
     * @var object $repository
     *   Instance of OTP repository
     */
    private $repository = null;

    /**
     * @var array $otpData 
     *    Stores OTP information.
     */
    private $otpData;

    /**
     * sets class variables 
     *  @var object $em
     *    Instance of Entity Manager interface Instance
     * 
     *  @param string $otpData 
     *    Stores OTP information.
     */
    public function __construct(EntityManagerInterface $em, array $otpData) {
        // Grabbing data 
        $this->em = $em;
        $this->otpData= $otpData;
        $this->repository = $em->getRepository(OTP::class);
    }

    /**
     * Verifies OTP
     * Changes OTP status if otp is valid.
     * 
     *   @return array
     *     Returns an array having status and message.
     */
    public function verifyOTP() {
        try {
            $response['status'] = "danger";
            $otp = $this->repository->findOneBy(['otp' => $this->otpData['otp']]);
            if ($otp) {
                if ($this->otpData['title'] == "email" && $otp->getUserID()->getEmail() != $this->otpData['uid']) {
                    $response['message'] = "Invalid otp";
                }
                else if ($this->otpData['title'] == "username" && $otp->getUserID()->getUsername() != $this->otpData['uid']) {
                    $response['message'] = "Invalid otp";
                }
                else if ($otp->getStatus() != "active") {
                    $response['message'] = "otp has been used already.";
                }
                else {
                    $otp->setStatus("inactive");
                    $this->em->merge($otp);
                    $this->em->flush();
                    $user = $otp->getUserId();
                    $user->setStatus("verified");
                    $this->em->merge($user);
                    $this->em->flush();
                    $response['status'] = "success";
                    $response['message'] = "otp verified successfully.";
                }
            }
            else {
                $response['message'] = "Invalid otp";
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        return $response;
    }
}