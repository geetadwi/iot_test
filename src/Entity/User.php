<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\ChangePasswordController;
use App\Controller\EditAvatarController;
use App\Repository\UserRepository;
use App\State\UserProcessor;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(security: 'is_granted(\'view\',object)'),
        new Put(security: 'is_granted(\'edit\',object)'),
        new Delete(security: 'is_granted(\'edit\',object)'),
        new Patch,
        new Put(
            uriTemplate: '/users/password/update/{id}',
            requirements: ['id' => '.+', '_method' => 'PUT'],
            controller: ChangePasswordController::class,
            openapiContext: [
                'summary' => 'Change the password of User Resource',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '' => 'object',
                                'properties' => [
                                    'oldPassword' => ['type' => 'string'],
                                    'newPassword' => ['type' => 'string'],
                                    'confirmPassword' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            security: 'is_granted(\'edit\',object)'
        ),
        new Post(
            uriTemplate: '/users/avatar/{id}',
            requirements: ['id' => '.+', '_method' => 'POST'],
            controller: EditAvatarController::class,
            openapiContext: [
                'requestBody' => [
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'avatar' => [
                                        'type' => 'string',
                                        'format' => 'binary'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            security: 'is_granted(\'edit\',object)',
            deserialize: false
        ),
        new GetCollection(),
        new Post(),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    paginationEnabled: true,
    processor: UserProcessor::class,
    extraProperties: [
        'standard_put' => false,
    ]
)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity("email")]
#[ApiFilter(filterClass: OrderFilter::class, properties: ['id', 'email', 'pec', 'firstName', 'lastName', 'roles', 'createdAt', 'updatedAt'])]
#[ApiFilter(filterClass: SearchFilter::class, properties: ['id' => 'exact', 'firstName' => 'partial', 'lastName' => 'partial', 'email' => 'partial', 'pec' => 'partial', 'roles' => 'partial'])]
#[ApiFilter(filterClass: DateFilter::class, properties: ['createdAt', 'updatedAt'])]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, JWTUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["user:read"])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Email,
        new Assert\Length(max: 170)
    ])]
    #[Groups(["user:read", "user:write"])]
    private ?string $email = null;


    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Length(max: 250)

    ])]
    private ?string $firstName = null;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Length(max: 250)
    ])]
    private ?string $lastName = null;

    #[ApiProperty(iris: ['https://schema.org/image'], openapiContext: ['type' => 'string'])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(["user:read", "user:avatar"])]
    private $avatar;

    #[Groups(["user:read"])]
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVerified = false;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Groups(["user:read", "user:write"])]
    #[Assert\Sequentially([
        new Assert\NotBlank,
        new Assert\Length(max: 250)
    ])]
    private ?string $password = null;

    #[ORM\Column(type: "datetime")]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: "datetime")]
    #[Groups(["user:read"])]
    private ?\DateTimeInterface $updatedAt = null;

    public static function createFromPayload($id, array $payload): JWTUserInterface
    {
        $user = new self;

        //$user->setId(Uuid::fromString($id));
        $user->setId($id);
        $user->setEmail($payload['username'] ?? '');
        $user->setRoles($payload['roles']);

        return $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string)$this->email;
    }

    /**
     * @return list<string>
     * @see UserInterface
     *
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }


    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updatedTimestamps(): void
    {
        $this->updatedAt = new \DateTime('now');
        if ($this->getCreatedAt() === null) {
            $this->createdAt = new \DateTime('now');
        }
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[Groups(["user:read"])]
    public function getCreatedAtAgo(): string
    {
        if ($this->createdAt === null) {
            return "";
        }
        return Carbon::instance($this->createdAt)->diffForHumans();
    }

    #[Groups(["user:read"])]
    public function getUpdatedAtAgo(): string
    {
        if ($this->updatedAt === null) {
            return "";
        }
        return Carbon::instance($this->updatedAt)->diffForHumans();
    }
}
