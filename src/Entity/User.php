<?php

namespace Adshares\Aduser\Entity;

use Serializable;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, Serializable
{
    /** @var int */
    private $id;

    /** @var string */
    private $email;

    /** @var string */
    private $password;

    /** @var array */
    private $roles = [];

    /** @var string */
    private $fullName;

    /** @var array */
    private $apiKeys = [];

    public function __construct(int $id, string $email, string $fullName, string $password, array $roles = [])
    {
        $this->id = $id;
        $this->email = $email;
        $this->fullName = $fullName;
        $this->password = $password;
        $this->roles = $roles;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getUsername(): ?string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantees that a user always has at least one role for security
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function isReviewer(): bool
    {
        return in_array('ROLE_REVIEWER', $this->roles);
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    /**
     * @return array
     */
    public function getApiKeys(): array
    {
        return $this->apiKeys;
    }

    public function addApiKey(ApiKey $apiKey): void
    {
        $this->apiKeys[] = $apiKey;
    }

    public function getSalt(): ?string
    {
        // See "Do you need to use a Salt?" at https://symfony.com/doc/current/cookbook/security/entity_provider.html
        // we're using bcrypt in security.yml to encode the password, so
        // the salt value is built-in and you don't have to generate one

        return null;
    }

    public function eraseCredentials(): void
    {
        // if you had a plainPassword property, you'd nullify it here
        // $this->plainPassword = null;
    }

    public function serialize(): string
    {
        return serialize(
            [
                $this->id,
                $this->email,
                $this->password,
                $this->roles,
                $this->fullName,
            ]
        );
    }

    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->email,
            $this->password,
            $this->roles,
            $this->fullName,
        ] = unserialize($serialized, ['allowed_classes' => false]);
    }

    public function __toString(): string
    {
        return $this->fullName;
    }

    public static function fromArray(array $data): User
    {
        return new self(
            (int)$data['id'],
            (string)$data['email'],
            (string)$data['full_name'],
            (string)$data['password'],
            json_decode($data['roles'])
        );
    }
}
