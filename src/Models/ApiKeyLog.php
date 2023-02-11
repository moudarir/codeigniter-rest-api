<?php

namespace Moudarir\CodeigniterApi\Models;

use CI_Model;

class ApiKeyLog extends CI_Model
{

    /**
     * ApiKeyLog constructor.
     */
    public function __construct()
    {
        parent::__construct();
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

        $this->db->insert('`api_key_logs`', $data, true);

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
        $this->db->update('`api_key_logs`', $data);

        return $this->db->affected_rows() === 1;
    }
}
