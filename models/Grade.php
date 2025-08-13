<?php
require_once '../config/db.php';

class Grade {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getSubmissionById($submissionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.assignment_id, s.user_id, s.file_path, s.link, s.status, s.grade, s.feedback, s.created_at, s.updated_at, 
                       u.username, a.title AS assignment_title, c.title AS course_title
                FROM submissions s
                JOIN users u ON s.user_id = u.id
                JOIN assignments a ON s.assignment_id = a.id
                JOIN courses c ON a.course_id = c.id
                WHERE s.id = ?
            ");
            $stmt->execute([$submissionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getSubmissionById: " . $e->getMessage());
            return null;
        }
    }

    public function getSubmissionsByAssignment($assignmentId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.user_id, s.file_path, s.link, s.status, s.grade, s.feedback, s.created_at, s.updated_at, 
                       u.username
                FROM submissions s
                JOIN users u ON s.user_id = u.id
                WHERE s.assignment_id = ?
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([$assignmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSubmissionsByAssignment: " . $e->getMessage());
            return [];
        }
    }

    public function getSubmissionsByCourse($courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.assignment_id, s.user_id, s.file_path, s.link, s.status, s.grade, s.feedback, s.created_at, s.updated_at, 
                       u.username, a.title AS assignment_title
                FROM submissions s
                JOIN users u ON s.user_id = u.id
                JOIN assignments a ON s.assignment_id = a.id
                WHERE a.course_id = ?
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSubmissionsByCourse: " . $e->getMessage());
            return [];
        }
    }

    public function getSubmissionsByUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.assignment_id, s.file_path, s.link, s.status, s.grade, s.feedback, s.created_at, s.updated_at, 
                       a.title AS assignment_title, c.title AS course_title
                FROM submissions s
                JOIN assignments a ON s.assignment_id = a.id
                JOIN courses c ON a.course_id = c.id
                WHERE s.user_id = ?
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSubmissionsByUser: " . $e->getMessage());
            return [];
        }
    }

    public function gradeSubmission($submissionId, $grade, $feedback) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE submissions
                SET grade = ?, feedback = ?, status = 'graded', updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$grade, $feedback, $submissionId]);
        } catch (PDOException $e) {
            error_log("Error in gradeSubmission: " . $e->getMessage());
            return false;
        }
    }

    public function updateGrade($submissionId, $grade, $feedback) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE submissions
                SET grade = ?, feedback = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$grade, $feedback, $submissionId]);
        } catch (PDOException $e) {
            error_log("Error in updateGrade: " . $e->getMessage());
            return false;
        }
    }

    public function isGraded($submissionId) {
        try {
            $stmt = $this->pdo->prepare("SELECT status FROM submissions WHERE id = ?");
            $stmt->execute([$submissionId]);
            $status = $stmt->fetchColumn();
            return $status === 'graded';
        } catch (PDOException $e) {
            error_log("Error in isGraded: " . $e->getMessage());
            return false;
        }
    }

    public function getAverageGradeByCourse($userId, $courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT AVG(s.grade)
                FROM submissions s
                JOIN assignments a ON s.assignment_id = a.id
                WHERE s.user_id = ? AND a.course_id = ? AND s.status = 'graded'
            ");
            $stmt->execute([$userId, $courseId]);
            $average = $stmt->fetchColumn();
            return $average !== false ? (float)$average : null;
        } catch (PDOException $e) {
            error_log("Error in getAverageGradeByCourse: " . $e->getMessage());
            return null;
        }
    }
}
?>