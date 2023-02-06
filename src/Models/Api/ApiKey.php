<?php

namespace Moudarir\CodeigniterApi\Models\Api;

use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use Moudarir\CodeigniterApi\Http\Config;
use Moudarir\CodeigniterApi\Models\TableFactory;
use Tightenco\Collect\Support\Collection;

class ApiKey extends TableFactory
{

    /**
     * @var int
     */
    public int $user_id;

    /**
     * @var string
     */
    public string $key;

    /**
     * @var string|null
     */
    public ?string $ip_addresses;

    /**
     * ApiKey constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @param int|null $id
     * @param array|null $options
     * @return self|null
     */
    public function find(?int $id = null, ?array $options = null): ?self
    {
        if ((int)$id <= 0 && $options === null) {
            return null;
        }

        $default = [
            'user_id'  => null,
            'key'      => null,
            'username' => null,
            'password' => null,
        ];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $param = [];

        if ((int)$id > 0) {
            $param['where']['`' . $alias . '`.`id`'] = (int)$id;
        }
        if ((int)$config['user_id'] > 0) {
            $param['where']['`' . $alias . '`.`user_id`'] = (int)$config['user_id'];
        }
        if (!empty($config['key'])) {
            $param['where']['`' . $alias . '`.`key`'] = $config['key'];
        }

        return $this->findOne($param);
    }

    /**
     * @param array|null $options
     * @return Collection
     */
    public function collect(?array $options = null): Collection
    {
        $default = [
            'ids' => null,
        ];
        $alias = $this->getAlias();
        $config = $this->setOptions($default, $options);
        $param = [];

        if ($config['ids'] !== null) {
            $ids = is_array($config['ids']) ? $config['ids'] : [(int)$config['ids']];
            $param['where_in']['`' . $alias . '`.`id`'] = $ids;
        }

        return $this->findAllCollection($param);
    }

    /**
     * @param array $ids
     * @return array
     */
    public function reset(array $ids): array
    {
        if (empty($ids)) {
            return [
                'error' => true,
                'message' => "Aucune clé API n'est sélectionnée."
            ];
        }

        $response = ['error' => true];
        self::getDatabase()->trans_start();
        $edited = $this->collect(['ids' => $ids])
            ->map(function (ApiKey $item) {
                $item->setKey();

                if ($item->update()) {
                    return $item->getId();
                }

                return null;
            })
            ->reject(fn ($id) => ($id === null))
            ->toArray();
        self::getDatabase()->trans_complete();

        $finished = count($edited);
        if (self::getDatabase()->trans_status() === false || $finished === 0) {
            $response['message'] = "Aucune clé API n'a été régénérée.";
        } else {
            $response['error'] = false;
            $response['message'] = $finished === 1
                ? "Une clé API régénérée avec succès"
                : $finished." clés API régénérées avec succès.";
        }

        return $response;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function remove(array $ids): array
    {
        if (empty($ids)) {
            return [
                'error' => true,
                'message' => "Aucune clé API n'est sélectionnée."
            ];
        }

        $response = ['error' => true];
        self::getDatabase()->trans_start();
        $deleted = $this->collect(['ids' => $ids])
            ->map(fn (ApiKey $item) => $item->delete())
            ->reject(fn ($status) => ($status === false))
            ->toArray();
        self::getDatabase()->trans_complete();

        $finished = count($deleted);
        if (self::getDatabase()->trans_status() === false || $finished === 0) {
            $response['message'] = "Aucune clé API n'a été supprimée.";
        } else {
            $response['error'] = false;
            $response['message'] = $finished === 1
                ? "Une clé API supprimée avec succès"
                : $finished." clés API supprimées avec succès.";
        }

        return $response;
    }

    /**
     * Getters
     */

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return string|null
     */
    public function getIpAddresses(): ?string
    {
        return $this->ip_addresses;
    }

    /**
     * Setters
     */

    /**
     * @param int $user_id
     * @return self
     */
    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @return self
     */
    public function setKey(): self
    {
        $this->key = $this->generateKey();
        return $this;
    }

    /**
     * @param string|null $ip_addresses
     * @return self
     */
    public function setIpAddresses(?string $ip_addresses = null): self
    {
        $this->ip_addresses = $ip_addresses;
        return $this;
    }

    /**
     * @param array $exclude
     * @return string
     */
    private function generateKey(array $exclude = []): string
    {
        $token = CommonHelper::generateToken(Config::API_KEY_LENGTH, 'alnum');

        self::getDatabase()->where('key', $token);

        if (!empty($exclude)) {
            self::getDatabase()->where_not_in('key', $exclude);
        }

        $found = self::getDatabase()->count_all_results($this->getTable());

        if ($found > 0) {
            $exclude[] = $token;
            return $this->generateKey($exclude);
        }

        return $token;
    }
}
