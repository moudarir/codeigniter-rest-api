<?php

namespace Moudarir\CodeigniterApi\Models\Users;

use Moudarir\CodeigniterApi\Models\TableFactory;

class Role extends TableFactory
{

    /**
     * @var string|null
     */
    public ?string $name;

    /**
     * @var string|null
     */
    public ?string $description;

    /**
     * @var string|null
     */
    public ?string $activation_method;

    /**
     * @var int|null
     */
    public ?int $manual_activation;

    /**
     * Role constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * Getters
     */

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name ?? null;
    }
}