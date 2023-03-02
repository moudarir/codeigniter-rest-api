<?php

namespace Moudarir\CodeigniterApi\Models;

use CI_Model;

class ApiKeyLimit extends CI_Model
{

    /**
     * ApiKeyLimit constructor.
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
            'key_id' => null,
            'request' => null
        ];
        $config = is_array($options) ? array_merge($default, $options) : $default;
        $this->db->select('`akl`.*')->from('`api_key_limits` `akl`');

        if ((int)$id > 0) {
            $this->db->where('`akl`.`id`', (int)$id, true);
        }
        if ((int)$config['key_id'] > 0) {
            $this->db->where('`akl`.`key_id`', (int)$config['key_id'], true);
        }
        if (!empty($config['request'])) {
            $this->db->where('`akl`.`request`', $config['request'], true);
        }

        $query = $this->db->limit(1)->get();

        return ($query !== false && $query->num_rows() > 0) ? $query->row_array() : null;
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

        $this->db->insert('`api_key_limits`', $data, true);

        return $this->db->affected_rows() > 0 ? $this->db->insert_id() : null;
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

        $this->db->where('id', $id, true);
        $this->db->update('`api_key_limits`', $data);

        return $this->db->affected_rows() === 1;
    }

    /**
     * @param int $id
     * @return bool
     */
    public function increaseCounter(int $id): bool
    {
        if ($id > 0) {
            $this->db->set('counter', 'counter+1', false);
            $this->db->where('id', $id);
            $this->db->update('`api_key_limits`');

            return $this->db->affected_rows() > 0;
        }

        return false;
    }
}
