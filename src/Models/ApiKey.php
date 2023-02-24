<?php

namespace Moudarir\CodeigniterApi\Models;

use CI_Model;
use Moudarir\CodeigniterApi\Http\Helpers;

class ApiKey extends CI_Model
{

    /**
     * API Key Length
     */
    const API_KEY_LENGTH = 40;

    /**
     * API Key Length
     */
    const USERNAME_LENGTH = 8;

    /**
     * API Key Length
     */
    const PASSWORD_LENGTH = 16;

    /**
     * ApiKey constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param int|null $id
     * @param array|null $options
     * @return array|null
     */
    public function find(?int $id = null, ?array $options = null): ?array
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
        $config = is_array($options) ? array_merge($default, $options) : $default;
        $this->db->select('`ak`.*')->from('`api_keys` `ak`');

        if ((int)$id > 0) {
            $this->db->where('`ak`.`id`', (int)$id, true);
        }
        if ((int)$config['user_id'] > 0) {
            $this->db->where('`ak`.`user_id`', (int)$config['user_id'], true);
        }
        if (!empty($config['key'])) {
            $this->db->where('`ak`.`key`', $config['key'], true);
        }
        if (!empty($config['username'])) {
            $this->db->where('`ak`.`username`', $config['username'], true);
        }
        if (!empty($config['password'])) {
            $this->db->where('`ak`.`password`', $config['password'], true);
        }

        $query = $this->db->limit(1)->get();

        return ($query !== false && $query->num_rows() > 0) ? $query->row_array() : null;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string|null $key
     * @return array|null
     */
    public function verify(string $username, string $password, ?string $key = null): ?array
    {
        if (empty($username) || empty($password)) {
            return null;
        }

        return $this->find(null, [
            'username' => $username,
            'password' => $password,
            'key' => $key,
        ]);
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

        $this->db->insert('`api_keys`', $data, true);

        return $this->db->affected_rows() > 0 ? $this->db->insert_id() : null;
    }

    /**
     * @return string
     */
    public function setKey(): string
    {
        return $this->generateToken(self::API_KEY_LENGTH);
    }

    /**
     * @return string
     */
    public function setUsername(): string
    {
        return $this->generateToken(self::USERNAME_LENGTH, 'username');
    }

    /**
     * @return string
     */
    public function setPassword(): string
    {
        return $this->generateToken(self::PASSWORD_LENGTH, 'password');
    }

    /**
     * @param int $length
     * @param string $field
     * @param array $exclude
     * @return string
     */
    private function generateToken(int $length, string $field = 'key', array $exclude = []): string
    {
        $token = Helpers::generateToken($length, 'alnum');

        $this->db->where($field, $token);

        if (!empty($exclude)) {
            $this->db->where_not_in($field, $exclude);
        }

        $found = $this->db->count_all_results('`api_keys`');

        if ($found > 0) {
            $exclude[] = $token;
            return $this->generateToken($length, $field, $exclude);
        }

        return $token;
    }
}
