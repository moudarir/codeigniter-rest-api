<?php

namespace Moudarir\CodeigniterApi\Models;

use Moudarir\CodeigniterApi\Exceptions\DatabaseCreateException;
use Moudarir\CodeigniterApi\Helpers\Carbonize;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;

class Model extends \CI_Model
{

    public int $id;

    public string $created_at;

    public string $updated_at;

    protected string $table;

    protected string $alias;

    /**
     * @var \CI_DB_mysqli_driver|\CI_DB_mysql_driver|\CI_DB_query_builder
     */
    private static $database;

    public function __construct()
    {
        parent::__construct();

        if (!isset($this->table)) {
            $className = CommonHelper::camelcase($this->getFormattedClassName(), '_');
            $this->table = \strtolower($className);
        }

        if (!isset($this->alias)) {
            $className = CommonHelper::camelcase($this->getFormattedClassName());
            $this->alias = CommonHelper::firstLetters($className, '', 'lower');
        }

        if (!isset(self::$database)) {
            $this->load->database();
            self::$database = $this->db;
        }
    }

    /**
     * @return \CI_DB_result|bool|string
     */
    protected function prepareQuery(?array $params = null)
    {
        $request = false;
        $fields = '`'.$this->alias.'`.*';
        $orderBy = '';
        $groupBy = '';
        $having = [];
        $table = '`'.$this->table.'` `'.$this->alias.'`';
        $page = null;
        $limit = null;

        if (\is_array($params) && !empty($params)) {
            if (\array_key_exists('query', $params)) {
                $request = true;
            } else {
                if (\array_key_exists('fields', $params)) {
                    $fields = $params['fields'];
                }
                if (\array_key_exists('table', $params)) {
                    $table = $params['table'];
                }
                if (\array_key_exists('order_by', $params)) {
                    $orderBy = $params['order_by'];
                }
                if (\array_key_exists('group_by', $params)) {
                    $groupBy = $params['group_by'];
                }
                if (\array_key_exists('having', $params)) {
                    $having = $params['having'];
                }
                if (\array_key_exists('page', $params) && (int)$params['page'] > 0) {
                    $page = (int)$params['page'];
                }
                if (\array_key_exists('limit', $params) && (int)$params['limit'] > 0) {
                    $limit = (int)$params['limit'];
                }

                if (\array_key_exists('joins', $params) && is_array($params['joins'])) {
                    $joins = $params['joins'];
                    foreach ($joins as $join) {
                        if (\array_key_exists('fields', $join) && !empty($join['fields'])) {
                            $fields .= ', '.$join['fields'];
                        }

                        self::$database->join($join['table'], $join['local'].'='.$join['foreign'], $join['type']);

                        if (\array_key_exists('where', $join)) {
                            self::$database->where($join['where'], null, true);
                        }
                        if (\array_key_exists('or_where', $join)) {
                            self::$database->or_where($join['or_where'], null, true);
                        }
                        if (\array_key_exists('order_by', $join)) {
                            $orderBy .= $join['order_by'];
                        }
                    }
                }

                if (\array_key_exists('where', $params)) {
                    self::$database->where($params['where'], null, true);
                }

                if (\array_key_exists('or_where', $params)) {
                    self::$database->or_where($params['or_where'], null, true);
                }

                if (\array_key_exists('where_in', $params)) {
                    $whereIn = $params['where_in'];
                    foreach ($whereIn as $key => $value) {
                        self::$database->where_in($key, $value, true);
                    }
                }

                if (\array_key_exists('where_not_in', $params)) {
                    $whereNotIn = $params['where_not_in'];
                    foreach ($whereNotIn as $key => $value) {
                        self::$database->where_not_in($key, $value, true);
                    }
                }
            }
        }

        if ($request) {
            $binds = \array_key_exists('binds', $params) ? $params['binds'] : false;
            $query = self::$database->query($params['query'], $binds);
        } else {
            self::$database->select($fields)
                ->from($table)
                ->order_by($orderBy, '', true);

            if ($groupBy !== '') {
                self::$database->group_by($groupBy, true);
            }
            if ($having !== '') {
                self::$database->having($having);
            }
            if ((int)$page > 0 || (int)$limit > 0) {
                $page = (int)$page > 0 ? (int)$page : 1;
                $limit = (int)$limit > 0 ? (int)$limit : 20;
                $offset = $limit * ($page - 1);
                self::$database->limit($limit, $offset);
            }

            $query = self::$database->get();
        }

        return $query;
    }

    /**
     * @return static|null
     */
    public function findOne(?array $params = null): ?object
    {
        if (\is_array($params)) {
            if (!\array_key_exists('limit', $params)) {
                $params['limit'] = 1;
            }
        } else {
            $params = ['limit' => 1];
        }

        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->custom_row_object(0, static::class) : null;
    }

    public function fetchOne(?array $params = null): ?array
    {
        if (\is_array($params)) {
            if (!\array_key_exists('limit', $params)) {
                $params['limit'] = 1;
            }
        } else {
            $params = ['limit' => 1];
        }

        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->row_array() : null;
    }

    /**
     * @return static[]
     */
    public function findAll(?array $params = null): array
    {
        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->custom_result_object(static::class) : [];
    }

    public function fetchAll(?array $params = null): array
    {
        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->result_array() : [];
    }

    public function countAll(array $params = []): int
    {
        if (!\array_key_exists('fields', $params)) {
            $params['fields'] = '`'.$this->alias.'`.`id`';
        }

        return $this->prepareQuery($params)->num_rows();
    }

    /**
     * @throws DatabaseCreateException
     */
    public function create(?string $_table = null): self
    {
        $table = $this->getTable($_table);

        if (!isset($this->created_at)) {
            $this->setCreatedAt();
        }
        if (!isset($this->updated_at)) {
            $this->setUpdatedAt();
        }

        self::$database->insert($table, $this, true);

        if (self::$database->affected_rows() > 0) {
            $this->setId(self::$database->insert_id());
            return $this;
        }

        throw new DatabaseCreateException();
    }

    public function createBatch(array $data, ?string $_table = null): int
    {
        if (empty($data)) {
            return 0;
        }

        foreach ($data as $datum) {
            if (!isset($datum->created_at)) {
                $datum->setCreatedAt();
            }
            if (!isset($datum->updated_at)) {
                $datum->setUpdatedAt();
            }
        }

        $table = $this->getTable($_table);
        $batch = self::$database->insert_batch($table, $data, true);

        return $batch !== false ? $batch : 0;
    }

    /**
     * @param array|int|null $_id
     * @param string|null $_table
     * @return bool
     */
    public function update($_id = null, ?string $_table = null): bool
    {
        $id = $_id ?: $this->getId();

        if ($id === null) {
            return false;
        }

        $table = $this->getTable($_table);

        if (\is_array($id)) {
            self::$database->where($id, null, true);
        } else {
            self::$database->where('id', $id, true);
        }

        self::$database->update($table, $this);

        return self::$database->affected_rows() === 1;
    }

    public function updateBatch(array $data, string $index = 'id', ?string $_table = null): int
    {
        if (empty($data)) {
            return 0;
        }

        $table = $this->getTable($_table);
        $batch = self::$database->update_batch($table, $data, $index);

        return $batch !== false ? $batch : 0;
    }

    /**
     * @param array|int|null $_id
     * @param array|null $extra
     * @param string|null $_table
     * @return bool
     */
    public function delete($_id = null, ?array $extra = null, ?string $_table = null): bool
    {
        $id = $_id ?: ($this->getId() ?? null);

        if ($id === null) {
            return false;
        }

        $table = $this->getTable($_table);

        if (\is_array($id)) {
            self::$database->where($id, null, true);
        } else {
            self::$database->where('id', $id, true);
        }

        if (\is_array($extra) && !empty($extra)) {
            if (\array_key_exists('where', $extra)) {
                self::$database->where($extra['where'], null, true);
            }
            if (\array_key_exists('or_where', $extra)) {
                self::$database->or_where($extra['or_where'], null, true);
            }
        }

        return self::$database->delete($table);
    }

    public function deleteBatch(?array $ids = null, ?array $filters = null, ?string $table = null): bool
    {
        if (empty($ids) && empty($filters)) {
            return false;
        }

        if (!empty($ids)) {
            self::$database->where_in('id', $ids, true);
        }
        if (!empty($filters)) {
            if (\array_key_exists('where', $filters)) {
                self::$database->where($filters['where'], null, true);
            }
            if (\array_key_exists('or_where', $filters)) {
                self::$database->or_where($filters['or_where'], null, true);
            }
            if (\array_key_exists('where_in', $filters)) {
                foreach ($filters['where_in'] as $key => $value) {
                    self::$database->where_in($key, $value, true);
                }
            }
            if (\array_key_exists('where_not_in', $filters)) {
                foreach ($filters['where_not_in'] as $key => $value) {
                    self::$database->where_not_in($key, $value, true);
                }
            }
        }

        return self::$database->delete($this->getTable($table));
    }

    /**
     * Getters
     */

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|Carbonize|null
     */
    public function getCreatedAt(bool $carbonized = true, ?string $_created_at = null)
    {
        $created_at = $_created_at ?: $this->created_at;
        return $carbonized === true ? (new Carbonize())->getFromFormat($created_at) : $created_at;
    }

    /**
     * @return string|Carbonize|null
     */
    public function getUpdatedAt(bool $carbonized = true, ?string $_updated_at = null)
    {
        $updated_at = $_updated_at ?: $this->updated_at;
        return $carbonized === true ? (new Carbonize())->getFromFormat($updated_at) : $updated_at;
    }

    public function getTable(?string $table = null): string
    {
        return $table ?: $this->table;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getUniqueToken(string $field = 'token', array $options = [], array $exclude = []): string
    {
        $params = $this->setOptions([
            'length' => 16,
            'type' => 'alnum',
            'parts' => 1,
        ], $options);
        $token = CommonHelper::generateToken($params['length'], $params['type'], $params['parts']);

        self::$database->where($field, $token);

        if (!empty($exclude)) {
            self::$database->where_not_in($field, $exclude);
        }

        $found = self::$database->count_all_results($this->table);

        if ($found > 0) {
            $exclude[] = $token;
            return $this->getUniqueToken($field, $params, $exclude);
        }

        return $token;
    }

    /**
     * @return \CI_DB_mysqli_driver|\CI_DB_mysql_driver|\CI_DB_query_builder
     */
    public static function getDatabase()
    {
        return self::$database;
    }

    /**
     * Setters
     */

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param int|string|null $created_at
     * @return self
     */
    public function setCreatedAt($created_at = null): self
    {
        if ($created_at === null) {
            $this->created_at = (new Carbonize())->toDateTimeString();
        } elseif (\is_int($created_at)) {
            $this->created_at = (new Carbonize())->getFromTimestamp($created_at)->toDateTimeString();
        } else {
            $this->created_at = $created_at;
        }

        return $this;
    }

    /**
     * @param int|string|null $updated_at
     * @return self
     */
    public function setUpdatedAt($updated_at = null): self
    {
        if ($updated_at === null) {
            $this->updated_at = (new Carbonize())->toDateTimeString();
        } elseif (\is_int($updated_at)) {
            $this->updated_at = (new Carbonize())->getFromTimestamp($updated_at)->toDateTimeString();
        } else {
            $this->updated_at = $updated_at;
        }

        return $this;
    }

    private function getFormattedClassName(): string
    {
        $cls = \get_class($this);
        $clsArray = \explode('\\', $cls);
        return \end($clsArray);
    }

    protected function setOptions(array $default, ?array $options = null): array
    {
        return \is_array($options) ? \array_merge($default, $options) : $default;
    }
}
