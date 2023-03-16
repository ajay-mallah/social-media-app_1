<?php

namespace App\Services;

use App\Entity\Comments;
use App\Entity\Posts;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

/**
 * Add new comment to the comments table.
 * Updates edited comment to the comments table.
 */
class CommentHandler 
{
    /**
     * @var object $em
     *   Instance of Entity Manager interface Instance
     */
    private $em = null;

    /**
     * @var object $userRepo
     *   Instance of User repository
     */
    private $userRepo = null;

    /**
     * @var object $postRepo
     *   Instance of Posts repository
     */
    private $postRepo = null;

    /**
     * @var object $commentRepo
     *   Instance of Comments repository
     */
    private $commentRepo = null;

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
        $this->commentRepo = $this->em->getRepository(Comments::class);
        $this->formData= $formData;
    }

    /**
     * Inserts a new comments.
     * 
     *   @return array
     *     Returns an array having status and post data.
     */
    public function addComment() {
        $result['status'] = 'success';
        try {
            $user = $this->userRepo->findOneBy(['id' => $this->formData['user_id']]);

            $comment = new Comments();
            $comment->setCommenter($user);
            $comment->setPost($this->postRepo->findOneBy(['id' => $this->formData['post_id']]));
            $comment->setComment($this->formData['text']);
            $comment->setCreateTime(new \DateTime());
            
            $this->em->persist($comment);
            $this->em->flush();

            $result['message'] = $this->getComment($comment);
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
     *   @param Comments $comment
     *     Comments object.
     *   @return array
     *     Returns an array having post data.
     */
    public function getComment(Comments $comment) {
        $result = [
            'id' => $comment->getId(),
            'commenter_id' => $comment->getCommenter()->getId(),
            'name' => $comment->getCommenter()->getFullname(),
            'image' => $comment->getCommenter()->getImageUrl(),
            'time' => $comment->getCreateTime()->format('d-m-Y H:i:s'),
            'comment' => $comment->getComment()
        ];

        return $result;
    }

    /**
     * Updates comments.
     * 
     *   @return array
     *     Returns an array having status and message.
     */
    public function editComment() {
        $result['status'] = 'success';
        try {
            $comment = $this->commentRepo->findOneBy(['id' => $this->formData['comment_id']]);
            $comment->setComment($this->formData['text']);
        
            $this->em->merge($comment);
            $this->em->flush();

            $result['message'] = $comment->getComment();
        }
        catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Deletes comments.
     * 
     *   @return array
     *     Returns an array having status and message.
     */
    public function deleteComment() {
        $result['status'] = 'success';
        try {
            $comment = $this->commentRepo->findOneBy(['id' => $this->formData['comment_id']]);
            $post = $comment->getPost();
    
            $post->removeComment($comment);
            $this->em->merge($post);
            $this->em->flush();
        
            $this->em->remove($comment);
            $this->em->flush();
        }
        catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
}
