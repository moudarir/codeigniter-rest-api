<?php

namespace Moudarir\CodeigniterApi\Models;

use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use Moudarir\CodeigniterApi\Helpers\StringHelper;
use CI_DB_query_builder;
use CI_DB_result;
use CI_Model;
use Tightenco\Collect\Support\Collection;

class TableFactory extends CI_Model
{

    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $created_at;

    /**
     * @var string
     */
    public string $updated_at;

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var string
     */
    protected string $alias;

    /**
     * @var CI_DB_query_builder
     */
    private static $database;

    /**
     * CoreModel constructor.
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        parent::__construct();

        $table = null;
        $alias = null;

        if (array_key_exists('table', $config)) {
            $table = $config['table'];
        }
        if (array_key_exists('alias', $config)) {
            $alias = $config['alias'];
        }

        if (!isset(self::$database) || self::$database === null) {
            $this->load->database();
            self::$database = $this->db;
        }

        $this->setTable($table);
        $this->setAlias($alias);
    }

    /**
     * @param int|array $id
     * @param bool $asArray
     * @return static|array|null
     */
    public function __invoke($id, bool $asArray = false)
    {
        $param = [];
        if (is_array($id) && !empty($id)) {
            if (!array_key_exists('where', $id)) {
                return null;
            }

            if (!array_key_exists('`'.$this->alias.'`.`id`', $id['where'])) {
                return null;
            }

            $param = $id;
        } elseif ((int)$id > 0) {
            $param['where']['`'.$this->alias.'`.`id`'] = (int)$id;
        }

        if (!empty($param)) {
            return $asArray === true ? $this->fetchOne($param) : $this->findOne($param);
        }

        return null;
    }

    /**
     * @param array|null $params
     * @return CI_DB_result
     */
    protected function prepareQuery(?array $params = null): CI_DB_result
    {
        $request = false;
        $alias = $this->getAlias();
        $selection = '`'.$alias.'`.*';
        $orderBy = '';
        $groupBy = '';
        $having = [];
        $table = '`'.$this->getTable().'` `'.$alias.'`';
        $page = 1;
        $limit = 20; // max 50
        $count = false;

        if (is_array($params) && !empty($params)) {
            if (array_key_exists('query', $params)) {
                $request = true;
            } else {
                $selection = array_key_exists('fields', $params) ? $params['fields'] : $selection;
                $table = array_key_exists('table', $params) ? $params['table'] : $table;
                $orderBy = array_key_exists('order_by', $params) ? $params['order_by'] : $orderBy;
                $groupBy = array_key_exists('group_by', $params) ? $params['group_by'] : $groupBy;
                $having = array_key_exists('having', $params) ? $params['having'] : $having;

                if (array_key_exists('page', $params) && (int)$params['page'] > 0) {
                    $page = (int)$params['page'];
                }
                if (array_key_exists('limit', $params) && (int)$params['limit'] > 0 && (int)$params['limit'] <= 50) {
                    $limit = (int)$params['limit'];
                }
                if (array_key_exists('count', $params)) {
                    $count = (bool)$params['count'];
                }

                if (array_key_exists('joins', $params) && is_array($params['joins'])) {
                    $joins = $params['joins'];
                    foreach ($joins as $join) {
                        if (array_key_exists('fields', $join) && !empty($join['fields'])) {
                            $selection .= ', '.$join['fields'];
                        }

                        self::getDatabase()->join($join['table'], $join['local'].'='.$join['foreign'], $join['type']);

                        if (array_key_exists('where', $join)) {
                            self::getDatabase()->where($join['where'], null, true);
                        }
                        if (array_key_exists('or_where', $join)) {
                            self::getDatabase()->or_where($join['or_where'], null, true);
                        }
                        if (array_key_exists('order_by', $join)) {
                            $orderBy .= $join['order_by'];
                        }
                    }
                }

                if (array_key_exists('where', $params)) {
                    self::getDatabase()->where($params['where'], null, true);
                }

                if (array_key_exists('or_where', $params)) {
                    self::getDatabase()->or_where($params['or_where'], null, true);
                }

                if (array_key_exists('where_in', $params)) {
                    $whereIn = $params['where_in'];
                    foreach ($whereIn as $key => $value) {
                        self::getDatabase()->where_in($key, $value, true);
                    }
                }

                if (array_key_exists('where_not_in', $params)) {
                    $whereNotIn = $params['where_not_in'];
                    foreach ($whereNotIn as $key => $value) {
                        self::getDatabase()->where_not_in($key, $value, true);
                    }
                }
            }
        }

        if ($request) {
            $binds = array_key_exists('binds', $params) ? $params['binds'] : false;
            $query = self::getDatabase()->query($params['query'], $binds);
        } else {
            self::getDatabase()->select($selection)
                ->from($table)
                ->order_by($orderBy, '', true);

            if ($groupBy !== '') {
                self::getDatabase()->group_by($groupBy, true);
            }
            if ($having !== '') {
                self::getDatabase()->having($having);
            }

            if ($count === false) {
                $offset = $limit * ($page - 1);
                self::getDatabase()->limit($limit, $offset);
            }

            $query = self::getDatabase()->get();
        }

        return $query;
    }

    /**
     * @param array|null $params
     * @param string|null $className
     * @return static|null
     */
    public function findOne(?array $params = null, ?string $className = null): ?object
    {
        if (is_array($params)) {
            if (!array_key_exists('limit', $params)) {
                $params['limit'] = 1;
            }
        } else {
            $params = ['limit' => 1];
        }

        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->custom_row_object(0, $className ?: static::class) : null;
    }

    /**
     * @param array|null $params
     * @return array|null
     */
    public function fetchOne(?array $params = null): ?array
    {
        if (is_array($params)) {
            if (!array_key_exists('limit', $params)) {
                $params['limit'] = 1;
            }
        } else {
            $params = ['limit' => 1];
        }

        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->row_array() : null;
    }

    /**
     * @param array|null $params
     * @param string|null $className
     * @return static[]|null
     */
    public function findAll(?array $params = null, ?string $className = null): ?array
    {
        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->custom_result_object($className ?: static::class) : null;
    }

    /**
     * @param array|null $params
     * @return Collection
     */
    public function findAllCollection(?array $params = null): Collection
    {
        $_data = $this->findAll($params);

        return collect($_data ?: []);
    }

    /**
     * @param array|null $params
     * @return array[]|null
     */
    public function fetchAll(?array $params = null): ?array
    {
        $query = $this->prepareQuery($params);

        return $query->num_rows() > 0 ? $query->result_array() : null;
    }

    /**
     * @param array|null $params
     * @return Collection
     */
    public function fetchAllCollection(?array $params = null): Collection
    {
        $_data = $this->fetchAll($params);

        return collect($_data ?: []);
    }

    /**
     * @param array|null $params
     * @return int
     */
    public function countAll(?array $params = null): int
    {
        $alias = $this->getAlias();
        $params['count'] = true;

        if (array_key_exists('fields', $params)) {
            $params['fields'] .= ', `'.$alias.'`.`id`';
        } else {
            $params['fields'] = '`'.$alias.'`.`id`';
        }

        $query = $this->prepareQuery($params);

        return $query->num_rows();
    }

    /**
     * @param bool $dry
     * @param string|null $_table
     * @return int|string|void|null
     */
    public function create(bool $dry = false, ?string $_table = null)
    {
        $table = $this->getTable($_table);

        if (!isset($this->created_at)) {
            $this->setCreatedAt();
        }
        if (!isset($this->updated_at)) {
            $this->setUpdatedAt();
        }

        if ($dry === true) {
            return self::getDatabase()->set($this)->get_compiled_insert($table);
        }

        self::getDatabase()->insert($table, $this, true);

        return self::getDatabase()->affected_rows() > 0 ? self::getDatabase()->insert_id() : null;
    }

    /**
     * @param static[] $data
     * @param string|null $_table
     * @return int
     */
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
        $batch = self::getDatabase()->insert_batch($table, $data, true);

        return $batch !== false ? $batch : 0;
    }

    /**
     * @param mixed $_id
     * @param bool $dry
     * @param string|null $_table
     * @return bool|string
     */
    public function update($_id = null, bool $dry = false, ?string $_table = null)
    {
        $id = $_id ?: $this->getId();
        $table = $this->getTable($_table);

        if ($id !== null) {
            if (is_array($id)) {
                self::getDatabase()->where($id, null, true);
            } else {
                self::getDatabase()->where('id', $id, true);
            }
        }

        if (!isset($this->updated_at)) {
            $this->setUpdatedAt();
        }

        if ($dry === true) {
            return self::getDatabase()->set($this)->get_compiled_update($table);
        }

        self::getDatabase()->update($table, $this);

        return self::getDatabase()->affected_rows() === 1;
    }

    /**
     * @param static[] $data
     * @param string $index
     * @param mixed $where
     * @param string|null $_table
     * @return int Number of affected rows
     */
    public function updateBatch(array $data, string $index = 'id', $where = null, ?string $_table = null): int
    {
        if (empty($data)) {
            return 0;
        }

        if (!is_null($where)) {
            if (is_array($where)) {
                self::getDatabase()->where($where, null, true);
            } else {
                self::getDatabase()->where('id', $where, true);
            }
        }

        foreach ($data as $datum) {
            if (!isset($datum->updated_at)) {
                $datum->setUpdatedAt();
            }
        }

        $table = $this->getTable($_table);
        $batch = self::getDatabase()->update_batch($table, $data, $index);

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
        $table = $this->getTable($_table);

        if ($id !== null) {
            if (is_array($id)) {
                self::getDatabase()->where($id, null, true);
            } else {
                self::getDatabase()->where('id', $id, true);
            }
        }

        if (is_array($extra) && !empty($extra)) {
            if (array_key_exists('where', $extra)) {
                self::getDatabase()->where($extra['where'], null, true);
            }
            if (array_key_exists('or_where', $extra)) {
                self::getDatabase()->or_where($extra['or_where'], null, true);
            }
        }

        return self::getDatabase()->delete($table);
    }

    /**
     * Getters
     */

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    /**
     * @param string|null $_created_at
     * @return string|null
     */
    public function getCreatedAt(?string $_created_at = null)
    {
        return $_created_at ?: ($this->created_at ?? null);
    }

    /**
     * @param string|null $_updated_at
     * @return string|null
     */
    public function getUpdatedAt(?string $_updated_at = null)
    {
        return $_updated_at ?: ($this->updated_at ?? null);
    }

    /**
     * @param string|null $table
     * @return string
     */
    public function getTable(?string $table = null): string
    {
        return $table ?: $this->table;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return CI_DB_query_builder
     */
    public static function getDatabase(): CI_DB_query_builder
    {
        return self::$database;
    }

    /**
     * Setters
     */

    /**
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string|int|null $created_at
     * @return self
     */
    public function setCreatedAt($created_at = null): self
    {
        if ($created_at === null) {
            $this->created_at = date("Y-m-d H:i:s", time());
        } elseif (is_int($created_at)) {
            $this->created_at = date("Y-m-d H:i:s", $created_at);
        } else {
            $this->created_at = $created_at;
        }

        return $this;
    }

    /**
     * @param string|int|null $updated_at
     * @return self
     */
    public function setUpdatedAt($updated_at = null): self
    {
        if ($updated_at === null) {
            $this->updated_at = date("Y-m-d H:i:s", time());
        } elseif (is_int($updated_at)) {
            $this->updated_at = date("Y-m-d H:i:s", $updated_at);
        } else {
            $this->updated_at = $updated_at;
        }

        return $this;
    }

    /**
     * @param array $default
     * @param array|null $options
     * @return array
     */
    protected function setOptions(array $default, ?array $options = null): array
    {
        return is_array($options) ? array_merge($default, $options) : $default;
    }

    /**
     * @param string|null $table
     * @return void
     */
    private function setTable(?string $table = null): void
    {
        if (!isset($this->table)) {
            if ($table === null) {
                $className = CommonHelper::camelcase($this->getFormattedClassName(), '_');
                $this->table = strtolower($className).'s';
            } else {
                $this->table = $table;
            }
        }
    }

    /**
     * @param string|null $alias
     * @return void
     */
    private function setAlias(?string $alias = null): void
    {
        if (!isset($this->alias)) {
            if ($alias === null) {
                $className = CommonHelper::camelcase($this->getFormattedClassName());
                $this->alias = StringHelper::firstLetters($className, '', 'lower');
            } else {
                $this->alias = $alias;
            }
        }
    }

    /**
     * @return string
     */
    private function getFormattedClassName(): string
    {
        $cls = get_class($this);
        $clsArray = explode('\\', $cls);
        return end($clsArray);
    }
}
