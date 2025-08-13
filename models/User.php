<?php
require_once '../config/db.php';

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getUserById: " . $e->getMessage());
            return null;
        }
    }

    public function getUserByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, is_active, password, created_at FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getUserByUsername: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile($userId, $username, $email, $password = null, $avatar = null) {
        try {
            $sql = "UPDATE users SET username = ?, email = ?";
            $params = [$username, $email];

            if ($password) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            if ($avatar) {
                $sql .= ", avatar = ?";
                $params[] = $avatar;
            }

            $sql .= " WHERE id = ?";
            $params[] = $userId;

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error in updateProfile: " . $e->getMessage());
            return false;
        }
    }

    public function isEmailTaken($email, $excludeUserId = 0) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $excludeUserId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error in isEmailTaken: " . $e->getMessage());
            return false;
        }
    }

    public function updateRole($userId, $role) {
        try {
            if (!in_array($role, ['student', 'admin'])) {
                return false;
            }
            $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            return $stmt->execute([$role, $userId]);
        } catch (PDOException $e) {
            error_log("Error in updateRole: " . $e->getMessage());
            return false;
        }
    }

    public function toggleAccountStatus($userId, $isActive) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            return $stmt->execute([(int)$isActive, $userId]);
        } catch (PDOException $e) {
            error_log("Error in toggleAccountStatus: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser($userId) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("DELETE FROM enrolled_courses WHERE user_id = ?");
            $stmt->execute([$userId]);

            $stmt = $this->pdo->prepare("DELETE FROM submissions WHERE user_id = ?");
            $stmt->execute([$userId]);

            $stmt = $this->pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
            $stmt->execute([$userId, $userId]);

            $stmt = $this->pdo->prepare("DELETE FROM permissions WHERE user_id = ?");
            $stmt->execute([$userId]);

            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in deleteUser: " . $e->getMessage());
            return false;
        }
    }

    public function hasPermission($userId, $module, $permission) {
        try {
            $stmt = $this->pdo->prepare("SELECT $permission FROM permissions WHERE user_id = ? AND module = ?");
            $stmt->execute([$userId, $module]);
            $result = $stmt->fetchColumn();
            return $result === 1 || $result === true;
        } catch (PDOException $e) {
            error_log("Error in hasPermission: " . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("SELECT id, username, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllUsers: " . $e->getMessage());
            return [];
        }
    }

    public function getUserCount() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error in getUserCount: " . $e->getMessage());
            return 0;
        }
    }
}
?>
