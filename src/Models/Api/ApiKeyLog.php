<?php

namespace Moudarir\CodeigniterApi\Models\Api;

use Moudarir\CodeigniterApi\Models\TableFactory;

class ApiKeyLog extends TableFactory
{

    /**
     * @var int|null
     */
    public ?int $key_id;

    /**
     * @var string
     */
    public string $uri_string;

    /**
     * @var string
     */
    public string $method;

    /**
     * @var string
     */
    public string $ip_address;

    /**
     * @var float|null
     */
    public ?float $response_time;

    /**
     * @var int
     */
    public int $authorized;

    /**
     * @var int|null
     */
    public int $response_code;

    /**
     * ApiKeyLog constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $default = ['table' => 'api_key_logs'];
        $config = array_merge($default, $config);
        parent::__construct($config);
    }

    /**
     * @param array $data
     * @return int|null
     */
    public function add(array $data): ?int
    {
        if (!array_key_exists('created_at', $data) || !array_key_exists('updated_at', $data)) {
            $currentDate = date("Y-m-d H:i:s", time());
            if (!array_key_exists('created_at', $data)) {
                $data['created_at'] = $currentDate;
            }
            if (!array_key_exists('updated_at', $data)) {
                $data['updated_at'] = $currentDate;
            }
        }

        if (!array_key_exists('uri_string', $data)) {
            $data['uri_string'] = $this->uri->uri_string();
        }
        if (!array_key_exists('ip_address', $data)) {
            $data['ip_address'] = $this->input->ip_address();
        }

        self::getDatabase()->insert($this->getTable(), $data, true);

        return self::getDatabase()->affected_rows() > 0 ? self::getDatabase()->insert_id() : null;
    }

    /**
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function edit(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        if (!array_key_exists('updated_at', $data)) {
            $data['updated_at'] = date("Y-m-d H:i:s", time());
        }

        self::getDatabase()->where('id', $id, true);
        self::getDatabase()->update($this->getTable(), $data);

        return self::getDatabase()->affected_rows() === 1;
    }
}
