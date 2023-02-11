<?php

namespace Moudarir\CodeigniterApi\Models;

use CI_Model;

class User extends CI_Model
{

    /**
     * User constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param int|null $id
     * @return array|null
     */
    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $query = $this->db
            ->select('`u`.*')
            ->from('`users` `u`')
            ->where('`u`.`id`', $id, true)
            ->limit(1)->get();

        return ($query !== false && $query->num_rows() > 0) ? $query->row_array() : null;
    }

    /**
     * @param array $options
     * @return array[]|array
     */
    public function all(array $options = []): array
    {
        $page = (array_key_exists('page', $options) && (int)$options['page'] > 0)
            ? (int)$options['page']
            : 1;
        $limit = (array_key_exists('limit', $options) && (int)$options['limit'] > 0)
            ? (int)$options['limit']
            : 20;
        $offset = $limit * ($page - 1);
        $query = $this->db
            ->select('`u`.*')->from('`users` `u`')
            ->limit($limit, $offset)
            ->get();
        return ($query !== false && $query->num_rows() > 0) ? $query->result_array() : ['ko' => $this->db->last_query()];
    }

    /**
     * @param array|null $options
     * @return int
     */
    public function count(?array $options = null): int
    {
        $default = ['ids' => null];
        $config = is_array($options) ? array_merge($default, $options) : $default;
        $this->db->select('`u`.`id`')->from('`users` `u`');

        if ($config['ids'] !== null) {
            $ids = is_array($config['ids']) ? $config['ids'] : [(int)$config['ids']];
            $this->db->where_in('`u`.`id`', $ids, true);
        }

        return $this->db->get()->num_rows();
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

        $this->db->insert('`users`', $data, true);

        return $this->db->affected_rows() > 0 ? $this->db->insert_id() : null;
    }
}
