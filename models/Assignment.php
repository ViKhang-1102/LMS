<?php
require_once '../config/db.php';

class Assignment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAssignmentById($assignmentId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT a.id, a.course_id, a.title, a.description, a.due_date, a.created_at, c.title AS course_title
                FROM assignments a
                JOIN courses c ON a.course_id = c.id
                WHERE a.id = ?
            ");
            $stmt->execute([$assignmentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getAssignmentById: " . $e->getMessage());
            return null;
        }
    }

    public function getAssignmentsByCourse($courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, title, description, due_date, created_at
                FROM assignments
                WHERE course_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAssignmentsByCourse: " . $e->getMessage());
            return [];
        }
    }

    public function createAssignment($courseId, $title, $description, $dueDate) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO assignments (course_id, title, description, due_date, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            return $stmt->execute([$courseId, $title, $description, $dueDate]);
        } catch (PDOException $e) {
            error_log("Error in createAssignment: " . $e->getMessage());
            return false;
        }
    }

    public function updateAssignment($assignmentId, $title, $description, $dueDate) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE assignments
                SET title = ?, description = ?, due_date = ?
                WHERE id = ?
            ");
            return $stmt->execute([$title, $description, $dueDate, $assignmentId]);
        } catch (PDOException $e) {
            error_log("Error in updateAssignment: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAssignment($assignmentId) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("DELETE FROM submissions WHERE assignment_id = ?");
            $stmt->execute([$assignmentId]);

            $stmt = $this->pdo->prepare("DELETE FROM assignments WHERE id = ?");
            $stmt->execute([$assignmentId]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in deleteAssignment: " . $e->getMessage());
            return false;
        }
    }

    public function submitAssignment($assignmentId, $userId, $filePath = null, $link = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO submissions (assignment_id, user_id, file_path, link, status, created_at)
                VALUES (?, ?, ?, ?, 'pending', NOW())
            ");
            return $stmt->execute([$assignmentId, $userId, $filePath, $link]);
        } catch (PDOException $e) {
            error_log("Error in submitAssignment: " . $e->getMessage());
            return false;
        }
    }

    public function getSubmissions($assignmentId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.user_id, s.file_path, s.link, s.status, s.grade, s.feedback, s.created_at, u.username
                FROM submissions s
                JOIN users u ON s.user_id = u.id
                WHERE s.assignment_id = ?
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([$assignmentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getSubmissions: " . $e->getMessage());
            return [];
        }
    }

    public function getSubmissionById($submissionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.id, s.assignment_id, s.user_id, s.file_path, s.link, s.status, s.grade, s.feedback, s.created_at, u.username
                FROM submissions s
                JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            $stmt->execute([$submissionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getSubmissionById: " . $e->getMessage());
            return null;
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

    public function hasSubmitted($assignmentId, $userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM submissions WHERE assignment_id = ? AND user_id = ?");
            $stmt->execute([$assignmentId, $userId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error in hasSubmitted: " . $e->getMessage());
            return false;
        }
    }
}