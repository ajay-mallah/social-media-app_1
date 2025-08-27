<?php

namespace App\Services;

use App\Entity\Likes;
use App\Entity\Posts;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Add new Post to the Post table.
 * Updates edited post to the post table.
 */
class PostHandler 
{
    /**
     * @var object $em
     *   Instance of Entity Manager interface Instance
     */
    private $em = NULL;

    /**
     * @var object $userRepo
     *   Instance of User repository
     */
    private $userRepo = NULL;

    /**
     * @var object $postRepo
     *   Instance of Posts repository
     */
    private $postRepo = NULL;

    /**
     * @var array $formData 
     *    Stores form data.
     */
    private $formData;

    /**
     * sets class variables 
     *  @var object $em
     *    Instance of Entity Manager interface Instance
     * 
     *  @param string $userData 
     *    Stores user data.
     */
    public function __construct(EntityManagerInterface $em, array $formData) {
        // Grabbing data 
        $this->em = $em;
        $this->userRepo = $this->em->getRepository(User::class);
        $this->postRepo = $this->em->getRepository(Posts::class);
        $this->formData= $formData;
    }

    /**
     * Inserts a new post.
     * 
     *   @return array
     *     Returns an array having post data.
     */
    public function addPost() {
        $result['status'] = 'success';
        try {
            // Getting user object.
            $user = $this->userRepo->findOneBy(['id' => $this->formData['user_id']]);
            
            // Adding Post info.
            $post = new Posts();
            $post->setAuthorId($user);
            $post->setText($this->formData['text']);
            $post->setCreateTime(new \DateTime());
            
            // Adding Post to the database.
            $this->em->persist($post);
            $this->em->flush();
    
            $result['message'] = $this->getPost($post);
        }
        catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Updates a post.
     * 
     *   @return array
     */
    public function updatePost() {
        $result['status'] = 'success';
        try {
            // Getting user object.
            $post = $this->postRepo->findOneBy(['id' => $this->formData['post_id']]);
            
            // Adding Post info.
            $post->setText($this->formData['text']);
            
            // Adding Post to the database.
            $this->em->persist($post);
            $this->em->flush();
    
            $result['message'] = $post->getText();
        }
        catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Deletes post.
     * 
     *   @return array
     *     Returns an array having status and message data.
     */
    public function deletePost() {
        $result['status'] = 'success';
        try {
            // Getting post object.
            $post = $this->postRepo->findOneBy(['id' => $this->formData['post_id']]);
            
            // Deleting Post from the database.
            $this->em->remove($post);
            $this->em->flush();
        }
        catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Extracts post data and map into an array.
     * 
     *   @param Posts $post
     *     Posts object.
     *   @return array
     *     Returns an array having post data.
     */
    public function getPost(Posts $post) {
        $author = $post->getAuthorId();
        $result['id'] = $post->getId();
        $result ['author'] = [
            'id' => $author->getID(),
            'name' => $author->getFullname(),
            'image' => $author->getImageUrl() ? $author->getImageUrl() : '',
            'time' => $post->getCreateTime()->format('d-m-Y H:i:s'),
        ];

        $result['content'] = $post->getText();
        $result['like_id'] = NULL;

        $likes = $post->getLikes();
        if ($likes) {
            $result['likes'] = [];
            $likeCount = 0;
            foreach ($likes as $like) {
                if ($like->isLiked()) {
                    $likeCount++;
                }
                // assigning user's like id.
                if ($like->getUserId()->getId() == $this->formData['user_id']) {
                    $result['like_id'] = $like->getId();
                }
                $fetched = [
                    'liker_id' => $like->getUserId()->getId(),
                    'name' => $like->getUserId()->getFullname(),
                    'id' => $like->getId(),
                ];
                array_push($result['likes'], $fetched); 
            }
            $result['likes_count'] = $likeCount;
            if (!$result['like_id']) {
                $result['like_id'] = $this->addLike($post->getId());
            }
        }

        $comments = $post->getComments();
        if ($comments) {
            $result['comments'] = [];
            foreach ($comments as $comment) {
                $fetched = [
                    'id' => $comment->getId(),
                    'commenter_id' => $comment->getCommenter()->getId(),
                    'name' => $comment->getCommenter()->getFullname(),
                    'image' => $comment->getCommenter()->getImageUrl(),
                    'time' => $comment->getCreateTime()->format('d-m-Y H:i:s'),
                    'comment' => $comment->getComment()
                ];
                array_push($result['comments'], $fetched); 
            }
        }

        return $result;
    }

    /**
     * Fetches posts from the database.
     * 
     *   @return array
     *     Returns an array of post.
     */
    public function getAllPost() {
        $posts =  $this->postRepo->findAll();
        $result = [];
        foreach ($posts as $post) {
            array_push($result, $this->getPost($post));
        }
        return $result;
    }

    /**
     * Adds like to post.
     * 
     *   @param postId
     *     Post id.
     *   @return mixed
     *     Returns like id if there is no exception else error message.
     */
    private function addLike(int $postId) {
        try {
            $user = $this->userRepo->findOneBy(['id' => $this->formData['user_id']]);
    
            $like = new Likes();
            $like->setUserId($user);
            $like->setPost($this->postRepo->findOneBy(['id' => $postId]));
            $like->setLiked(FALSE);
    
            $this->em->persist($like);
            $this->em->flush();
    
            return $like->getId();
        }
        catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
