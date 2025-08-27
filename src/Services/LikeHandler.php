<?php

namespace App\Services;

use App\Entity\Likes;
use App\Entity\Posts;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Add new like to the likes table.
 * Updates user's like status to the particular post.
 */
class LikeHandler 
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
     * @var object $likeRepo
     *   Instance of Likes repository
     */
    private $likeRepo = NULL;

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
     *  @param string $formData 
     *    Stores form data.
     */
    public function __construct(EntityManagerInterface $em, array $formData) {
        // Grabbing data 
        $this->em = $em;
        $this->userRepo = $this->em->getRepository(User::class);
        $this->postRepo = $this->em->getRepository(Posts::class);
        $this->likeRepo = $this->em->getRepository(Likes::class);
        $this->formData= $formData;
    }

    /**
     * Adds like to post.
     * 
     *   @return array
     *     Returns an array having status and message.
     */
    public function addLike() {
        $result['status'] = 'added';
        try {
            $user = $this->userRepo->findOneBy(['id' => $this->formData['user_id']]);
    
            $like = new Likes();
            $like->setUserId($user);
            $like->setPost($this->postRepo->findOneBy(['id' => $this->formData['post_id']]));
            $like->setLiked(FALSE);
    
            $this->em->persist($like);
            $this->em->flush();
    
            $result['message'] = $this->getLike($like);
        }
        catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Extracts like data and map into an array.
     * 
     *   @param Likes $like
     *     Instance of Likes entity.
     * 
     *   @return array
     *     Returns an array having like data.
     */
    public function getLike(Likes $like) {
        $result = [
            'id' => $like->getId(),
            'liker_id' => $like->getUserId()->getId(),
            'name' => $like->getUserId()->getFullname(),
            'is_liked' => $like->isLiked(),
        ];

        return $result;
    }

    /**
     * Updates like status to for the particular post.
     * 
     *   @return array
     *     Returns an array having status and message.
     */
    public function editLike() {
        $result['status'] ='success';
        try {
            $like = $this->likeRepo->findOneBy(['id' => $this->formData['like_id']]);
            $like->setLiked(!$like->isLiked());

            $this->em->merge($like);
            $this->em->flush();

            $result['message'] = $like->isLiked();
        }
        catch (\Exception $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
        }
        return $result;
    }
}
