<?php
require_once __DIR__ . '/../config/db.php';

class Course {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getCourseById($courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.id, c.title, c.description, c.price, c.instructor_id, c.created_at, u.username AS instructor_name
                FROM courses c
                JOIN users u ON c.instructor_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getCourseById: " . $e->getMessage());
            return null;
        }
    }

    public function getAllCourses($adminView = false) {
        try {
            $sql = "
                SELECT c.id, c.title, c.description, c.price, c.instructor_id, c.created_at, u.username AS instructor_name
                FROM courses c
                JOIN users u ON c.instructor_id = u.id
            ";
            if (!$adminView) {
                $sql .= " WHERE c.is_active = 1";
            }
            $sql .= " ORDER BY c.created_at DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllCourses: " . $e->getMessage());
            return [];
        }
    }

    public function createCourse($title, $description, $price, $instructorId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO courses (title, description, price, instructor_id, created_at, is_active)
                VALUES (?, ?, ?, ?, NOW(), 1)
            ");
            return $stmt->execute([$title, $description, $price, $instructorId]);
        } catch (PDOException $e) {
            error_log("Error in createCourse: " . $e->getMessage());
            return false;
        }
    }

    public function updateCourse($courseId, $title, $description, $price, $instructorId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE courses
                SET title = ?, description = ?, price = ?, instructor_id = ?
                WHERE id = ?
            ");
            return $stmt->execute([$title, $description, $price, $instructorId, $courseId]);
        } catch (PDOException $e) {
            error_log("Error in updateCourse: " . $e->getMessage());
            return false;
        }
    }

    public function deleteCourse($courseId) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("DELETE FROM enrolled_courses WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $stmt = $this->pdo->prepare("DELETE FROM submissions WHERE assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)");
            $stmt->execute([$courseId]);

            $stmt = $this->pdo->prepare("DELETE FROM assignments WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $stmt = $this->pdo->prepare("DELETE FROM course_materials WHERE course_id = ?");
            $stmt->execute([$courseId]);

            $stmt = $this->pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in deleteCourse: " . $e->getMessage());
            return false;
        }
    }

    public function isUserEnrolled($userId, $courseId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM enrolled_courses WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$userId, $courseId]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Error in isUserEnrolled: " . $e->getMessage());
            return false;
        }
    }

    public function enrollUser($userId, $courseId) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO enrolled_courses (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())");
            return $stmt->execute([$userId, $courseId]);
        } catch (PDOException $e) {
            error_log("Error in enrollUser: " . $e->getMessage());
            return false;
        }
    }

    public function addCourseMaterial($courseId, $filePath = null, $link = null) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO course_materials (course_id, file_path, link, created_at) VALUES (?, ?, ?, NOW())");
            return $stmt->execute([$courseId, $filePath, $link]);
        } catch (PDOException $e) {
            error_log("Error in addCourseMaterial: " . $e->getMessage());
            return false;
        }
    }

    public function getCourseMaterials($courseId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, file_path, link, created_at FROM course_materials WHERE course_id = ? ORDER BY created_at DESC");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getCourseMaterials: " . $e->getMessage());
            return [];
        }
    }

    public function getAssignments($courseId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, title, description, due_date, created_at FROM assignments WHERE course_id = ? ORDER BY created_at DESC");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAssignments: " . $e->getMessage());
            return [];
        }
    }
}
?>