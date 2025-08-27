<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = NULL;

    #[ORM\Column(length: 255)]
    private ?string $username = NULL;

    #[ORM\Column(length: 255)]
    private ?string $email = NULL;

    #[ORM\Column(length: 100)]
    private ?string $fullname = NULL;

    #[ORM\Column(length: 255)]
    private ?string $password = NULL;

    #[ORM\Column(length: 20, nullable: TRUE)]
    private ?string $status = NULL;

    #[ORM\Column(length: 255, nullable: TRUE)]
    private ?string $imageUrl = NULL;

    #[ORM\Column(length: 10, nullable: TRUE)]
    private ?string $resetKey = NULL;

    #[ORM\Column(nullable: TRUE)]
    private ?bool $resetKeyStatus = NULL;

    #[ORM\OneToMany(mappedBy: 'authorId', targetEntity: Posts::class, orphanRemoval: TRUE)]
    private Collection $posts;

    #[ORM\Column]
    private ?bool $login = NULL;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    public function getResetKey(): ?string
    {
        return $this->resetKey;
    }

    public function setResetKey(?string $resetKey): self
    {
        $this->resetKey = $resetKey;

        return $this;
    }

    public function isResetKeyStatus(): ?bool
    {
        return $this->resetKeyStatus;
    }

    public function setResetKeyStatus(?bool $resetKeyStatus): self
    {
        $this->resetKeyStatus = $resetKeyStatus;

        return $this;
    }

    /**
     * @return Collection<int, Posts>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Posts $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthorId($this);
        }

        return $this;
    }

    public function removePost(Posts $post): self
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to NULL (unless already changed)
            if ($post->getAuthorId() === $this) {
                $post->setAuthorId(NULL);
            }
        }

        return $this;
    }

    public function isLogin(): ?bool
    {
        return $this->login;
    }

    public function setLogin(bool $login): self
    {
        $this->login = $login;

        return $this;
    }

}
