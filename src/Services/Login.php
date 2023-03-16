<?php

namespace App\Services;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Performs login service
 * Verifies user, check for valid user information. 
 */
 class Login 
 {
    /**
     * @var object $em
     *   Instance of Entity Manager interface Instance
     */
    private $em = NULL;

    /**
     * @var object $repository
     *   Instance of User repository
     */
    private $repository = NULL;

    /**
     * @var array $userData 
     *    Stores user data.
     */
    private $userData;

    /**
     * sets class variables 
     *  @var object $em
     *    Instance of Entity Manager interface Instance
     * 
     *  @param string $userData 
     *    Stores user data.
     */
    public function __construct(EntityManagerInterface $em, array $userData) {
        // Grabbing data 
        $this->em = $em;
        $this->userData= $userData;
        $this->repository = $em->getRepository(User::class);
    }

    /**
     * Validates inputs of the form.
     * 
     *  @return array
     *     Returns an array containing status and error messages.      
     */
    public function validateUser() {
        $result['status'] ='danger';
        $result['message'] =[];
        try {
            $user = $this->repository->findOneBy([$this->userData['title'] => $this->userData["uid"]]);
            if(!$user) {
                array_push($result['message'], ['error-uid', 'user does not exist with given '. $this->userData['title']]);
            } 
            else if (!password_verify($this->userData['password'], $user->getPassword())) {
                array_push($result['message'], ['error-password', 'wrong password']);
            }
            else if($user->getStatus() == "unverified") {
                $result['status'] ='unverified';
            }
            else {
                $result['status'] ='verified';
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        return $result;
    }
 }