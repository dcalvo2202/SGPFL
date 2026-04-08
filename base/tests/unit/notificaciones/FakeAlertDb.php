<?php

class FakeMysqliResult
{
    private array $rows;
    private int $index = 0;

    public function __construct(array $rows)
    {
        $this->rows = array_values($rows);
    }

    public function fetch_assoc(): ?array
    {
        if ($this->index >= count($this->rows)) {
            return null;
        }

        return $this->rows[$this->index++];
    }

    public function fetch_row(): ?array
    {
        $row = $this->fetch_assoc();
        return $row === null ? null : array_values($row);
    }
}

class FakeMysqliStmt
{
    private FakeMysqli $conn;
    private string $sql;
    private array $params = [];
    private ?FakeMysqliResult $result = null;

    public int $affected_rows = 0;
    public string $error = '';

    public function __construct(FakeMysqli $conn, string $sql)
    {
        $this->conn = $conn;
        $this->sql = $sql;
    }

    public function bind_param(string $types, &...$params): bool
    {
        $this->params = $params;
        return true;
    }

    public function execute(): bool
    {
        // INSERT de alertas
        if (strpos($this->sql, 'INSERT INTO user_alerts') !== false) {
            [$user_id, $subject, $message, $alert_type, $priority, $related_entity_type, $related_entity_id] = $this->params;

            if (in_array((string)$user_id, $this->conn->failAlertUsers, true)) {
                $this->error = 'Fallo simulado al insertar alerta';
                return false;
            }

            $this->conn->alerts[] = [
                'id' => $this->conn->nextAlertId++,
                'user_id' => (string)$user_id,
                'subject' => (string)$subject,
                'message' => (string)$message,
                'alert_type' => (string)$alert_type,
                'priority' => (string)$priority,
                'related_entity_type' => $related_entity_type === null ? null : (string)$related_entity_type,
                'related_entity_id' => $related_entity_id === null ? null : (int)$related_entity_id,
                'read_at' => null,
                'sent_at' => date('Y-m-d H:i:s'),
            ];

            $this->affected_rows = 1;
            return true;
        }

        // Usuarios por rol
        if (strpos($this->sql, 'SELECT l.id FROM sis_login l WHERE l.id_roll = ?') !== false) {
            $rolId = (int)$this->params[0];
            $rows = [];

            foreach ($this->conn->roleUsers[$rolId] ?? [] as $userId) {
                $rows[] = ['id' => (string)$userId];
            }

            $this->result = new FakeMysqliResult($rows);
            return true;
        }

        // Comité por proposal_id
        if (strpos($this->sql, 'SELECT c.tutor, c.asesor_1, c.asesor_2') !== false) {
            $proposalId = (int)$this->params[0];
            $row = $this->conn->committeeByProposal[$proposalId] ?? null;

            $this->result = new FakeMysqliResult($row ? [$row] : []);
            return true;
        }

        // Validar si usuario existe en sis_login
        if (strpos($this->sql, 'SELECT 1 FROM sis_login WHERE id = ? LIMIT 1') !== false) {
            $userId = (string)$this->params[0];
            $exists = array_key_exists($userId, $this->conn->loginRoles);

            $this->result = new FakeMysqliResult($exists ? [['exists' => 1]] : []);
            return true;
        }

        // Obtener rol de usuario
        if (strpos($this->sql, 'SELECT id_roll') !== false && strpos($this->sql, 'FROM sis_login') !== false) {
            $userId = (string)$this->params[0];
            $rows = [];

            if (array_key_exists($userId, $this->conn->loginRoles)) {
                $rows[] = ['id_roll' => $this->conn->loginRoles[$userId]];
            }

            $this->result = new FakeMysqliResult($rows);
            return true;
        }

        // Validación de duplicado reciente
        if (
            strpos($this->sql, 'FROM user_alerts') !== false &&
            strpos($this->sql, 'AND sent_at >= ?') !== false &&
            strpos($this->sql, 'SELECT 1') !== false
        ) {
            [
                $userId,
                $subject,
                $alertType,
                $relatedEntityTypeA,
                $relatedEntityTypeB,
                $relatedEntityIdA,
                $relatedEntityIdB,
                $cutoff
            ] = $this->params;

            $found = false;

            foreach ($this->conn->alerts as $alert) {
                $sameType = (
                    ($alert['related_entity_type'] === $relatedEntityTypeA) ||
                    ($alert['related_entity_type'] === null && $relatedEntityTypeB === null)
                );

                $sameId = (
                    ($alert['related_entity_id'] === ($relatedEntityIdA === null ? null : (int)$relatedEntityIdA)) ||
                    ($alert['related_entity_id'] === null && $relatedEntityIdB === null)
                );

                if (
                    $alert['user_id'] === (string)$userId &&
                    $alert['subject'] === (string)$subject &&
                    $alert['alert_type'] === (string)$alertType &&
                    $sameType &&
                    $sameId &&
                    strtotime($alert['sent_at']) >= strtotime((string)$cutoff)
                ) {
                    $found = true;
                    break;
                }
            }

            $this->result = new FakeMysqliResult($found ? [['exists' => 1]] : []);
            return true;
        }

        // Obtener alertas de usuario
        if (
            strpos($this->sql, 'SELECT id, subject, message, alert_type, priority, related_entity_type, related_entity_id, read_at, sent_at') !== false &&
            strpos($this->sql, 'FROM user_alerts') !== false
        ) {
            $userId = (string)$this->params[0];
            $limit = (int)$this->params[1];
            $unreadOnly = strpos($this->sql, 'AND read_at IS NULL') !== false;

            $rows = array_values(array_filter(
                $this->conn->alerts,
                function (array $alert) use ($userId, $unreadOnly): bool {
                    if ($alert['user_id'] !== $userId) {
                        return false;
                    }

                    if ($unreadOnly && $alert['read_at'] !== null) {
                        return false;
                    }

                    return true;
                }
            ));

            usort($rows, function (array $a, array $b): int {
                return strcmp($b['sent_at'], $a['sent_at']);
            });

            $rows = array_slice($rows, 0, $limit);
            $this->result = new FakeMysqliResult($rows);

            return true;
        }

        // Contar no leídas
        if (strpos($this->sql, 'SELECT COUNT(*) as count FROM user_alerts WHERE user_id = ? AND read_at IS NULL') !== false) {
            $userId = (string)$this->params[0];

            $count = 0;
            foreach ($this->conn->alerts as $alert) {
                if ($alert['user_id'] === $userId && $alert['read_at'] === null) {
                    $count++;
                }
            }

            $this->result = new FakeMysqliResult([['count' => $count]]);
            return true;
        }

        // Marcar una como leída
        if (strpos($this->sql, 'UPDATE user_alerts SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL') !== false) {
            $alertId = (int)$this->params[0];
            $userId = (string)$this->params[1];

            $affected = 0;

            foreach ($this->conn->alerts as &$alert) {
                if ($alert['id'] === $alertId && $alert['user_id'] === $userId && $alert['read_at'] === null) {
                    $alert['read_at'] = date('Y-m-d H:i:s');
                    $affected++;
                }
            }
            unset($alert);

            $this->affected_rows = $affected;
            return true;
        }

        // Marcar todas como leídas
        if (strpos($this->sql, 'UPDATE user_alerts SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL') !== false) {
            $userId = (string)$this->params[0];

            $affected = 0;

            foreach ($this->conn->alerts as &$alert) {
                if ($alert['user_id'] === $userId && $alert['read_at'] === null) {
                    $alert['read_at'] = date('Y-m-d H:i:s');
                    $affected++;
                }
            }
            unset($alert);

            $this->affected_rows = $affected;
            return true;
        }

        $this->result = new FakeMysqliResult([]);
        return true;
    }

    public function get_result(): FakeMysqliResult
    {
        return $this->result ?? new FakeMysqliResult([]);
    }

    public function close(): bool
    {
        return true;
    }
}

class FakeMysqli
{
    public string $error = 'Fake mysqli error';

    /** @var string[] */
    public array $prepareFailPatterns = [];

    /** @var array<int, array<int, string>> */
    public array $roleUsers = [];

    /** @var array<string, int> */
    public array $loginRoles = [];

    /** @var array<int, array{tutor:?string, asesor_1:?string, asesor_2:?string}> */
    public array $committeeByProposal = [];

    /** @var array<int, array<string, mixed>> */
    public array $alerts = [];

    /** @var string[] */
    public array $failAlertUsers = [];

    public int $nextAlertId = 1;

    public function prepare(string $sql)
    {
        foreach ($this->prepareFailPatterns as $pattern) {
            if (strpos($sql, $pattern) !== false) {
                return false;
            }
        }

        return new FakeMysqliStmt($this, $sql);
    }
}