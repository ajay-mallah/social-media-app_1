<?php 

namespace App\Controller;

use App\Entity\OTP;
use App\Entity\User;
use App\Services\CustomFileUploader;
use app\Services\AddUser;
use App\Services\CommentHandler;
use App\Services\LikeHandler;
use App\Services\Login;
use App\Services\OTPSender;
use App\Services\OTPVerifier;
use App\Services\PostHandler;
use App\Services\ResetPassword;
use Doctrine\Migrations\Configuration\Migration\Exception\JsonNotValid;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Pusher\Pusher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Front controller class
 * Receives Url requests and response accordingly.
 */
class FrontController extends AbstractController {
    /**
     * @var object $em
     *   Instance of the Entity Manager.
     */
    private $em = NULL;

    /**
     * @var object $userRepo
     *   Instance of User repository
     */
    private $userRepo = NULL;

    /**
     * Initializes class variables.
     *  
     *  @param EntityManagerInterface $em
     *    Instance of EntityManagerInterface
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
        $this->userRepo = $em->getRepository(User::class);
    }

    /**
     * Default Url for the web application.
     * 
     *   @Route("/", name="app_index")
     *     Handles url request for "/".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Redirects to home page page if user is logged in.
     *     Redirects to login page page if user is not logged in.
     */
    public function index(Request $request): Response
    {   
        $cookie = $request->cookies;
        if ($cookie->get('current_user')) {
            $userId = base64_decode($request->cookies->get('current_user'));
            $postHandler = new PostHandler($this->em, ['user_id' => $userId]);
            $addUser= new AddUser($this->em, []);
            $activeUsers = [];
            $result= $addUser->getActiveUser();
            if ($result['status'] == "success") {
                $activeUsers = $result['message'];
            }
            else {
                $activeUsers = NULL;
            }
            return $this->render('home/index.html.twig', [
                'activeUsers' => $activeUsers,
                'posts' => $postHandler->getAllPost(),
                'user' => $userId,
            ]);
        }
        else {
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * Receives request for user Registration.
     * 
     *   @Route("/register", name = "app_register")
     *     Handles url request for "/register".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Redirects to register page if request is not post request.
     *     else returns json response.
     */
    public function register(Request $request) : Response {

        if ($request->isXmlHttpRequest()) {
            // Grabbing user's data from post request.
            $userData = [];
            $userData["username"] = $request->request->get("username");
            $userData["password"] = $request->request->get("password");
            $userData["confPassword"] = $request->request->get("confPassword");
            $userData["fullName"] = $request->request->get("fullName");
            $userData["email"] = $request->request->get("email");

            $result = NULL;
            $addUser = new AddUser($this->em, $userData);
            $result = $addUser->validateUserData();
            if (count($result) < 1) {
                $uid = $addUser->setUser();
                return new JsonResponse(["status" => "valid", "message" => $result, 'uid' => $uid]);
            }
            return new JsonResponse(["status" => "invalid", "message" => $result]);
        }   
        return $this->render('register/index.html.twig');
    }

    /**
     * Receives request for user login.
     * 
     *   @Route("/login", name = "app_login")
     *     Handles url request for "/login".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @param Pusher $pusher
     *     Instance of the Pusher class.
     * 
     *   @return Response
     *     Redirects to login page if request is not post request.
     *     else returns json response.
     */
    public function login(Request $request, Pusher $pusher) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['title'] = $request->request->get("title");
            $formData['uid'] = $request->request->get("uid");
            $formData['password'] = $request->request->get("password");
            try {
                $login = new Login($this->em, $formData);
                $result = $login->validateUser();
                
                if ($result['status'] == "verified") {
                    $userId = $this->em->getRepository(User::class)->findOneBy([$formData['title'] => $formData['uid']])->getId();
                    $this->setLoginedSession($request, $userId, $pusher);
                }
                return new JsonResponse($result);
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
        }
        return $this->render('login/index.html.twig');
    }

    /**
     * Receives request for user otp verification.
     * 
     *   @Route("/otp/verify", name = "app_otp", methods={"POST"})
     *     Handles url request for "/otp/verify".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function verifyOTP(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['title'] = $request->request->get("title");
            $formData['uid'] = $request->request->get("uid");
            $formData['otp'] = $request->request->get("otp");

            try {
                $optVerifier = new OTPVerifier($this->em, $formData);
                return new JsonResponse($optVerifier->verifyOTP());
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * Receives request reset password page.
     * 
     *   @Route("/reset", name = "app_reset")
     *     Handles url request for "/reset".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Redirects to reest password page.
     */
    public function resetPassword(Request $request) : Response {
        return $this->render('reset_password/index.html.twig');
    }
    
    /**
     * Receives request for user reset password.
     * 
     *   @Route("/reset/reset_password", name = "app_reset_resetPassword")
     *     Handles url request for "/reset/reset_password".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function changePassword(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['email'] = $request->request->get('email');
            $formData['key'] = $request->request->get('key');
            $formData['password'] = $request->request->get('password');
            try {
                $reset = new ResetPassword($this->em, $formData);
                return new JsonResponse($reset->resetPassword());
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
    
    /**
     * Receives request for resend reset key.
     * 
     *   @Route("/reset_password/send", name = "app_reset__verify_email", methods={"POST"})
     *     Handles url request for "/reset_password/send".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function sendResetKey(Request $request) : Response {
        if ($request->isXmlHttpRequest()) {
            $email = $request->request->get("email");
            try {
                $resetPassword = new ResetPassword($this->em, ['email' => $email]);
                return new JsonResponse($resetPassword->setKey());
            }
            catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * Receives request for send otp.
     * 
     *   @Route("/otp/send", name = "app_send_otp", methods={"POST"})
     *     Handles url request for "/otp/send".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function sendOTP(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['title'] = $request->request->get("title");
            $formData['uid'] = $request->request->get("uid");

            $otpSender = new OTPSender($this->em,  $formData);
            $result = $otpSender->setOTP();
            return new JsonResponse($result);
        }
    }

    /**
     * Receives request for logout.
     * 
     *   @Route("/logout", name = "app_logout", methods={"POST"})
     *     Handles url request for "/logout".
     *   
     *   @param Pusher $pusher 
     *     Instance of the Pusher class.
     * 
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function logout(Request $request, Pusher $pusher) : Response {
        if($request->isXmlHttpRequest()) {
            $this->unsetCurrentUser($request, $pusher);
            return new JsonResponse(['status' => 'success']);
        }
    }

    /**
     * Receives request for delete comment.
     * 
     *   @Route("/comments/delete", name = "app_deleteComments", methods={"POST"})
     *     Handles url request for "/comments/delete".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function deleteComment(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['comment_id'] = $request->request->get('comment_id');
            $commentHandler = new CommentHandler($this->em, $formData);

            return new JsonResponse($commentHandler->deleteComment());
        }
    }

    /**
     * Receives request for edit comment.
     * 
     *   @Route("/comments/edit", name = "app_editComments", methods={"POST"})
     *     Handles url request for "/comments/edit".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function editComment(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['comment_id'] = $request->request->get('comment_id');
            $formData['text'] = $request->request->get('text');
            $commentHandler = new CommentHandler($this->em, $formData);

            return new JsonResponse($commentHandler->editComment());
        }
    }

    /**
     * Receives request for add comment.
     * 
     *   @Route("/comments/add", name = "app_addComments", methods={"POST"})
     *     Handles url request for "/comments/add".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function addComment(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['post_id'] = $request->request->get('post_id');
            $formData['user_id'] = base64_decode($request->cookies->get('current_user'));
            $formData['text'] = $request->request->get('text');
            $commentHandler = new CommentHandler($this->em, $formData);

            $result = $commentHandler->addComment();
            if($result['status'] === "success") {
                return $this->render('blocks/comment.html.twig',[
                    'user' => $formData['user_id'],
                    'post' => ['id' => $formData['post_id']],
                    'comment' => $result['message'],
                ]);
            }
            return new JsonResponse($result);
        }
    }

    /**
     * Receives request for add post.
     * 
     *   @Route("/post/add", name = "app_addPost", methods={"POST"})
     *     Handles url request for "/post/add".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function addPost(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['text'] = $request->request->get('text');
            $formData['user_id'] = base64_decode($request->cookies->get('current_user'));
            
            $result = "hello";
            $postHandler = new PostHandler($this->em, $formData);
            $result = $postHandler->addPost();

            if ($result['status'] === "success") {
                return $this->render('blocks/post.html.twig',[
                    'user' => $formData['user_id'],
                    'post' => $result['message'],
                ]);
            }

            return new JsonResponse($result);
        }
    }

    /**
     * Receives request for delete post.
     * 
     *   @Route("/post/delete", name = "app_deletePost", methods={"POST"})
     *     Handles url request for "/post/delete".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function deletePost(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['text'] = $request->request->get('text');
            $formData['user_id'] = base64_decode($request->cookies->get('current_user'));
            $formData['post_id'] = $request->request->get('post_id');
            
            $postHandler = new PostHandler($this->em, $formData);
            $result = $postHandler->deletePost();

            return new JsonResponse($result);
        }
    }

    /**
     * Receives request for edit post.
     * 
     *   @Route("/post/edit", name = "app_editPost", methods={"POST"})
     *     Handles url request for "/post/edit".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function editPost(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['text'] = $request->request->get('text');
            $formData['user_id'] = base64_decode($request->cookies->get('current_user'));
            $formData['post_id'] = $request->request->get('post_id');
            
            $postHandler = new PostHandler($this->em, $formData);
            $result = $postHandler->updatePost();

            return new JsonResponse($result);
        }
    }

    /**
     * Receives request for edit like status.
     * 
     *   @Route("/likes", name = "app_likes", methods={"POST"})
     *     Handles url request for "/likes".
     *   
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function addLike(Request $request) : Response {
        if($request->isXmlHttpRequest()) {
            $formData['post_id'] = $request->request->get('post_id');
            $formData['like_id'] = $request->request->get('like_id');
            $formData['user_id'] = base64_decode($request->cookies->get('current_user'));

            $result = [];
            $likeHandler = new LikeHandler($this->em, $formData);
            $result = $likeHandler->editLike();
            
            return new JsonResponse($result);
        }
    }

    /**
     * Receives request for profile image update.
     * 
     *   @Route("/profile/upload", name = "app_profile_update", methods={"POST"})
     *     Handles url request for "/profile/upload".
     * 
     *   @param Request $request
     *     Requested URI.
     * 
     *   @param SluggerInterface $slugger
     *     Instance of SluggerInterface
     *
     *   @return Response
     *     Returns json response.
     */
    public function uploadProfile(Request $request, SluggerInterface $slugger) : Response {
        if($request->isXmlHttpRequest()) {
            $image = $request->files->get('image');
            $formData['user_id'] = base64_decode($request->cookies->get('current_user'));

            $fileUploader = new CustomFileUploader($this->getParameter('upload_directory'), $slugger, $this->em);
            $result = $fileUploader->upload($image, $formData['user_id']);
            return new JsonResponse($result);
        }
    }

    /**
     * Sets cookie for logged in session
     * 
     *   @param Pusher $pusher 
     *     Instance of the Pusher class.
     * 
     *   @param int $userId
     *     User identifier.
     * 
     *   @param Request $request
     *     Instance of the Request.
     */
    private function setLoginedSession(Request $request, int $userId, Pusher $pusher) {
        try {
            $user = $this->userRepo->findOneBy(['id' => $userId]);
            $user->setLogin(TRUE);
            $this->em->merge($user);
            $this->em->flush();
        }
        catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }

        $pusher->trigger("chirptalk-development", "active-users", "update");

        $response = new Response();
        $encrypteData = base64_encode($userId);
        $this->sendResponse("login", $encrypteData);
    }

    /**
     * Unsets cookie for logged in session
     * 
     *   @param Request $request
     *     Instance of the Request.
     */
    private function unsetCurrentUser(Request $request, Pusher $pusher) {
        $userId = base64_decode($request->cookies->get('current_user'));
        try {
            $user = $this->userRepo->findOneBy(['id' => $userId]);
            $user->setLogin(FALSE);
            $this->em->merge($user);
            $this->em->flush();
        }
        catch (Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage()]);
        }        

        $pusher->trigger("chirptalk-development", "active-users", "update");

        $this->sendResponse("logout", NULL);
    }

    /**
     * Receives request for active user's list.
     * 
     *   @Route("/Update/userList", name = "app_userList", methods={"POST"})
     *     Handles url request for "/Update/userList".
     * 
     *   @param Pusher $pusher 
     *     Instance of the Pusher class.
     * 
     *   @param Request $request
     *     Instance of the Request class.
     * 
     *   @return Response
     *     Returns json response.
     */
    public function getUserList(Request $request, Pusher $pusher) : Response {
        if($request->isXmlHttpRequest()) {
            $addUser = new AddUser($this->em, []);
            $result = $addUser->getActiveUser();

            if ($result['status'] == "success") {
                return $this->render('blocks/users.html.twig', [
                    'activeUsers' => $result['message'],
                ]);
            }

            return new JsonResponse($result);
        }
    }

    /**
     * Sends response 
     *   
     *   @param string $content
     *      Content of the response.
     * 
     *   @param mixed $userId
     *     User's id.
     * 
     *   @return void
     */
    private function sendResponse(string $content, mixed $userId) {
        $response = new Response();
        $response->headers->setCookie(new Cookie('current_user', $userId, strtotime('tomorrow'), '/'));
        $response->setContent($content);
        $response->headers->set('Content-Type', 'text/html');
        $response->send();
    }
}