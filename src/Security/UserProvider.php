<?php

namespace Adshares\Aduser\Security;

use Adshares\Aduser\Entity\ApiKey;
use Adshares\Aduser\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        UserPasswordEncoderInterface $passwordEncoder,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->passwordEncoder = $passwordEncoder;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function loadUserByUsername($username): UserInterface
    {
        if (($user = $this->fetchUserByEmail($username)) === null) {
            $ex = new UsernameNotFoundException(sprintf('There is no user with name "%s".', $username));
            $ex->setUsername($username);
            throw $ex;
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }

    public function createUser(string $email, string $fullname, string $password, array $roles = []): User
    {
        $mockUser = new User(0, '', '', '');
        $encodedPassword = $this->passwordEncoder->encodePassword($mockUser, $password);

        $this->connection->insert(
            'user',
            [
                'email' => $email,
                'full_name' => $fullname,
                'password' => $encodedPassword,
                'roles' => $roles,
            ],
            [
                'roles' => Types::JSON,
            ]
        );

        $userId = (int)$this->connection->lastInsertId();
        $this->connection->insert(
            'api_key',
            [
                'user_id' => $userId,
                'name' => trim(base64_encode(random_bytes(8)), '='),
                'secret' => trim(base64_encode(random_bytes(16)), '='),
            ]
        );

        return $this->fetchUserByEmail($email);
    }

    private function fetchUserByEmail(string $email, bool $deleted = false): ?User
    {
        try {
            $query = 'SELECT id, email, full_name, password, roles FROM user WHERE email = ?';
            if ($deleted === false) {
                $query .= ' AND deleted_at IS NULL';
            }
            $data = $this->connection->fetchAssoc($query, [$email]);
        } catch (DBALException $exception) {
            $this->logger->error($exception->getMessage());
            $data = false;
        }

        if ($data === false) {
            return null;
        }

        $user = User::fromArray($data);
        foreach ($this->fetchApiKeysByUserId($user->getId()) as $key) {
            $user->addApiKey($key);
        }

        return $user;
    }

    private function fetchApiKeysByUserId(int $userId, bool $deleted = false): array
    {
        try {
            $query = 'SELECT id, name, secret FROM api_key WHERE user_id = ?';
            if ($deleted === false) {
                $query .= ' AND deleted_at IS NULL';
            }
            $rows = $this->connection->fetchAll($query, [$userId]);
        } catch (DBALException $exception) {
            $this->logger->error($exception->getMessage());
            $rows = [];
        }

        $keys = [];
        foreach ($rows as $data) {
            $keys[] = ApiKey::fromArray($data);
        }

        return $keys;
    }
}