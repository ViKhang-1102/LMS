<?php
require_once '../config/db.php';

class Payment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createPayment($userId, $courseId, $amount, $paymentMethod, $transactionId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO payments (user_id, course_id, amount, payment_method, transaction_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())
            ");
            return $stmt->execute([$userId, $courseId, $amount, $paymentMethod, $transactionId]);
        } catch (PDOException $e) {
            error_log("Error in createPayment: " . $e->getMessage());
            return false;
        }
    }

    public function getPaymentById($paymentId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.user_id, p.course_id, p.amount, p.status, p.payment_method, p.transaction_id, 
                       p.created_at, p.updated_at, u.username, c.title AS course_title
                FROM payments p
                JOIN users u ON p.user_id = u.id
                JOIN courses c ON p.course_id = c.id
                WHERE p.id = ?
            ");
            $stmt->execute([$paymentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getPaymentById: " . $e->getMessage());
            return null;
        }
    }

    public function getPaymentsByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.course_id, p.amount, p.status, p.payment_method, p.transaction_id, 
                       p.created_at, p.updated_at, c.title AS course_title
                FROM payments p
                JOIN courses c ON p.course_id = c.id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPaymentsByUser: " . $e->getMessage());
            return [];
        }
    }

    public function getPaymentsByCourse($courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.user_id, p.amount, p.status, p.payment_method, p.transaction_id, 
                       p.created_at, p.updated_at, u.username
                FROM payments p
                JOIN users u ON p.user_id = u.id
                WHERE p.course_id = ?
                ORDER BY p.created_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPaymentsByCourse: " . $e->getMessage());
            return [];
        }
    }

    public function updatePaymentStatus($paymentId, $status) {
        try {
            if (!in_array($status, ['pending', 'completed', 'failed', 'refunded'])) {
                return false;
            }
            $stmt = $this->pdo->prepare("
                UPDATE payments
                SET status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$status, $paymentId]);
        } catch (PDOException $e) {
            error_log("Error in updatePaymentStatus: " . $e->getMessage());
            return false;
        }
    }

    public function refundPayment($paymentId) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE payments
                SET status = 'refunded', updated_at = NOW()
                WHERE id = ?
            ");
            $success = $stmt->execute([$paymentId]);

            if ($success) {
                $stmt = $this->pdo->prepare("
                    DELETE FROM enrolled_courses
                    WHERE course_id = (SELECT course_id FROM payments WHERE id = ?)
                    AND user_id = (SELECT user_id FROM payments WHERE id = ?)
                ");
                $stmt->execute([$paymentId, $paymentId]);
            }

            $this->pdo->commit();
            return $success;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in refundPayment: " . $e->getMessage());
            return false;
        }
    }

    public function hasPaid($userId, $courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id
                FROM payments
                WHERE user_id = ? AND course_id = ? AND status = 'completed'
            ");
            $stmt->execute([$userId, $courseId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error in hasPaid: " . $e->getMessage());
            return false;
        }
    }


    public function getAllPayments() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.user_id, p.course_id, p.amount, p.status, p.payment_method, 
                       p.transaction_id, p.created_at, p.updated_at, u.username, c.title AS course_title
                FROM payments p
                JOIN users u ON p.user_id = u.id
                JOIN courses c ON p.course_id = c.id
                ORDER BY p.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllPayments: " . $e->getMessage());
            return [];
        }
    }
}
?>