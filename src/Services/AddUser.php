<?php

namespace App\Services;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Verifies user, check for valid user information.
 * Adds user info to the database. 
 */
 class AddUser {
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
        $this->repository = $em->getRepository(User::class);
        $this->userData= $userData;
    }

    /**
     * Validates forms input and returns error messages
     * 
     *  @return array
     *     Returns an array containing error messages.      
     */
    public function validateUserData() {
        $errors_array = [];
        if ($this->isEmpty()) {
            array_push($errors_array, ["response-msg", "Please fill all the fields."]);
        }
        else {
            if ($this->usernameExist()) {
                array_push($errors_array, ["error-username", "username already exist."]);
            }
            // verifying email address.
            if (!$this->isValidEmailSyntax()) {
                array_push($errors_array, ["error-email", "email syntax is not valid, include @ and valid [domain]."]);
            } 
            // @todo have to add email verifier.
            else if($this->emailExist()) {
                array_push($errors_array, ["error-email", "User already exists with given email address."]);
            }

            if (!$this->isSamePwd()) {
                array_push($errors_array, ["error-confPassword", "Password and confirmed passwords are not same"]);
            }
            if (!$this->isValidNameSyntax()) {
                array_push($errors_array, ["error-fullName", "Only alphabets and white spaces are allowed."]);
            }
        }
        return $errors_array;
    }

    /**
     * Checks if any field is empty or not.
     * 
     *   @return boolean
     *     Returns TRUE if any field is empty.
     */
    private function isEmpty() {
        return (empty($this->userData["username"]) || empty($this->userData["password"]) || empty($this->userData["confPassword"]) || empty($this->userData["fullName"]) || empty($this->userData["email"]));
    }

    /**
     * Checks user id already exists or not.
     * 
     *   @return bool
     *     Returns TRUE if user id exists.
     */
    private function usernameExist() {
        $user = NULL;
        try {
            $user = $this->repository->findOneBy(['username' => $this->userData["username"]]);
            return $user ? TRUE : FALSE;
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
    }
    
    /**
     * Checks user id already exists or not.
     * 
     *   @return bool 
     *     Returns TRUE if user email already exists.
     */
    private function emailExist() {
        $user = NULL;
        try {
            $user = $this->repository->findOneBy(['email' => $this->userData["email"]]);
            return $user ? TRUE : FALSE;
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
    
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

    /**
     * Checks for valid name syntax.
     * 
     *   @return bool 
     *     Returns TRUE if syntax matches.
     */
    private function isValidNameSyntax() {
        $names = explode(" ", $this->userData["fullName"]);
        foreach ($names as $name) {
            $result = preg_match('/^[A-Za-z]*$/', $name);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks for valid name syntax.
     * 
     *   @return bool 
     *     Returns TRUE if email syntax is valid.
     */
    private function isValidEmailSyntax() {
        return filter_var($this->userData["email"], FILTER_VALIDATE_EMAIL);
    }

    /**
     * Stores user info into user table.
     * 
     *   @return int
     *     Returns user ID.
     */
    public function setUser() {
        $options = ['cost' => 12];
        // Encrypting password.
        $password = password_hash($this->userData["password"], PASSWORD_BCRYPT, $options);
        // Setting user data.
        $user = new User();
        $user->setUsername($this->userData["username"]);
        $user->setFullname($this->userData["fullName"]);
        $user->setEmail($this->userData["email"]);
        $user->setPassword($password);
        $user->setStatus("unverified");
        $user->setLogin(FALSE);

        $this->em->persist($user);
        $this->em->flush();

        $newUser = $this->repository->findOneBy(['email' => $this->userData["email"], 'username' => $this->userData["username"]]);
        return $newUser->getId();
    }

    /**
     * Returns List of active users
     * 
     *   @return array
     *     Users list
     */
    public function getActiveUser() {
        $result['status'] = 'success';
        try {
            $users = $this->repository->findAll();
            $activeUsers = [];
            foreach ($users as $user) {
                if ($user->isLogin()) {
                    $userInfo = [];
                    $userInfo['name'] = $user->getFullname();
                    $userInfo['image'] = $user->getImageUrl();
                    $userInfo['id'] = base64_encode($user->getId());
                    array_push($activeUsers, $userInfo);
                }
            }
            $result['message'] = $activeUsers;
        }
        catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
 }