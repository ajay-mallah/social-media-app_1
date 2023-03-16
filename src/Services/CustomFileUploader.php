<?php
namespace App\Services;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Receives File object and stores in the target directory.
 * Generates random file name.  
 */
class CustomFileUploader
{
    /**
     * @var $targetDirectory
     *   Directory where file will be saved.
     */
    private $targetDirectory;

    /**
     * @var object $em
     *   Instance of Entity Manager interface Instance
     */
    private $em = null;

    /**
     * @var $targetDirectory
     *   Instance of SluggerInterface.
     */
    private $slugger;

    /**
     * Initializes class variable.
     *   @param string $targetDirectory
     * 
     *   @param EntityManagerInterface $em
     *     Object of the Entity Manager Interface.
     */
    public function __construct(string $targetDirectory, SluggerInterface $slugger, EntityManagerInterface $em)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
        $this->em = $em;
    }

    /**
     * Uploads files to target directory.
     * 
     *   @param UploadedFile $file
     *     Object of uploaded file.
     * 
     *   @param int $userId
     *     User's Id.
     * 
     *   @return array
     *     Returns an array having status and message.
     */
    public function upload(UploadedFile $file, int $userId)
    {
        try {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            $file->move($this->getTargetDirectory(), $fileName);
            return $this->updateProfileImage("uploads/profile_image/" . $fileName, $userId);
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Updates user's profile-image attribute in the database.
     * 
     *   @param string $imageUrl
     *     Url of the profile image.
     * 
     *   @param int $userId
     *     User's id.
     *  
     *   @return string 
     *     Returns url of the profile image.
     */
    private function updateProfileImage(string $imageUrl, int $userId) {
        try {
            $user = $this->em->getRepository(User::class)->findOneBy(['id' => $userId]);
            $user->setImageUrl($imageUrl);
    
            $this->em->merge($user);
            $this->em->flush();
            return ['status' => 'success', 'fileName' => $user->getImageUrl()];
        }
        catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Getter for targetDirectory.
     *   @return string
     *     Returns targetDirectory.
     */
    private function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}