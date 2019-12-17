<?php

namespace Adshares\Aduser\Entity;

class ApiKey
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var string */
    private $secret;

    public function __construct(int $id, string $name, string $secret)
    {
        $this->id = $id;
        $this->name = $name;
        $this->secret = $secret;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public static function fromArray(array $data): ApiKey
    {
        return new self(
            (int)$data['id'],
            (string)$data['name'],
            (string)$data['secret']
        );
    }
}
