<?php
// FILE: /app/core/Model.php

/**
 * Base Model class
 * All models should extend this class
 */
class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    /**
     * Constructor - Initialize database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find a record by ID
     *
     * @param int $id
     * @return array|null
     */
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find all records with optional conditions
     *
     * @param array $conditions Key-value pairs for WHERE clause
     * @param string $orderBy ORDER BY clause
     * @param int $limit Limit number of results
     * @param int $offset Offset for pagination
     * @return array
     */
    public function findAll($conditions = [], $orderBy = '', $limit = null, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table}";

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);
        return $stmt->fetchAll();
    }

    /**
     * Find one record by conditions
     *
     * @param array $conditions
     * @return array|null
     */
    public function findOne($conditions)
    {
        $results = $this->findAll($conditions, '', 1);
        return $results[0] ?? null;
    }

    /**
     * Insert a new record
     *
     * @param array $data Key-value pairs for INSERT
     * @return int Last insert ID
     */
    public function insert($data)
    {
        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = ':' . implode(', :', $keys);

        $sql = "INSERT INTO {$this->table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return $this->db->lastInsertId();
    }

    /**
     * Update a record by ID
     *
     * @param int $id
     * @param array $data Key-value pairs for UPDATE
     * @return bool
     */
    public function update($id, $data)
    {
        $fields = [];
        foreach (array_keys($data) as $key) {
            $fields[] = "{$key} = :{$key}";
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = :id";
        $data['id'] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Delete a record by ID
     *
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Count records with optional conditions
     *
     * @param array $conditions
     * @return int
     */
    public function count($conditions = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Execute a custom query
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    protected function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a custom query and return single result
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    protected function queryOne($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a custom query (INSERT/UPDATE/DELETE)
     *
     * @param string $sql
     * @param array $params
     * @return bool
     */
    protected function execute($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Begin transaction
     */
    protected function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    protected function commit()
    {
        $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    protected function rollback()
    {
        $this->db->rollBack();
    }
}
