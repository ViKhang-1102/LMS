<?php
require_once '../config/db.php';

class Forum {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getThreadById($threadId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.id, t.course_id, t.user_id, t.title, t.content, t.created_at, t.updated_at, 
                       u.username, c.title AS course_title
                FROM forum_threads t
                JOIN users u ON t.user_id = u.id
                JOIN courses c ON t.course_id = c.id
                WHERE t.id = ?
            ");
            $stmt->execute([$threadId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error in getThreadById: " . $e->getMessage());
            return null;
        }
    }

    public function getThreadsByCourse($courseId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT t.id, t.title, t.content, t.created_at, t.updated_at, 
                       u.username, COUNT(p.id) AS post_count
                FROM forum_threads t
                JOIN users u ON t.user_id = u.id
                LEFT JOIN forum_posts p ON t.id = p.thread_id
                WHERE t.course_id = ?
                GROUP BY t.id
                ORDER BY t.updated_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getThreadsByCourse: " . $e->getMessage());
            return [];
        }
    }

    public function createThread($courseId, $userId, $title, $content) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO forum_threads (course_id, user_id, title, content, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            return $stmt->execute([$courseId, $userId, $title, $content]);
        } catch (PDOException $e) {
            error_log("Error in createThread: " . $e->getMessage());
            return false;
        }
    }

    public function updateThread($threadId, $title, $content) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE forum_threads
                SET title = ?, content = ?, updated_at = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([$title, $content, $threadId]);
        } catch (PDOException $e) {
            error_log("Error in updateThread: " . $e->getMessage());
            return false;
        }
    }

    public function deleteThread($threadId) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("DELETE FROM forum_posts WHERE thread_id = ?");
            $stmt->execute([$threadId]);

            $stmt = $this->pdo->prepare("DELETE FROM forum_threads WHERE id = ?");
            $stmt->execute([$threadId]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in deleteThread: " . $e->getMessage());
            return false;
        }
    }

    public function getPostsByThread($threadId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.thread_id, p.user_id, p.content, p.created_at, p.updated_at, 
                       u.username
                FROM forum_posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.thread_id = ?
                ORDER BY p.created_at ASC
            ");
            $stmt->execute([$threadId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getPostsByThread: " . $e->getMessage());
            return [];
        }
    }

    public function createPost($threadId, $userId, $content) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO forum_posts (thread_id, user_id, content, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $success = $stmt->execute([$threadId, $userId, $content]);

            if ($success) {
                $stmt = $this->pdo->prepare("UPDATE forum_threads SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$threadId]);
            }

            $this->pdo->commit();
            return $success;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in createPost: " . $e->getMessage());
            return false;
        }
    }

    public function updatePost($postId, $content) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                UPDATE forum_posts
                SET content = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $success = $stmt->execute([$content, $postId]);

            if ($success) {
                $stmt = $this->pdo->prepare("
                    UPDATE forum_threads
                    SET updated_at = NOW()
                    WHERE id = (SELECT thread_id FROM forum_posts WHERE id = ?)
                ");
                $stmt->execute([$postId]);
            }

            $this->pdo->commit();
            return $success;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in updatePost: " . $e->getMessage());
            return false;
        }
    }

    public function deletePost($postId) {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("SELECT thread_id FROM forum_posts WHERE id = ?");
            $stmt->execute([$postId]);
            $threadId = $stmt->fetchColumn();

            $stmt = $this->pdo->prepare("DELETE FROM forum_posts WHERE id = ?");
            $success = $stmt->execute([$postId]);

            if ($success && $threadId) {
                $stmt = $this->pdo->prepare("UPDATE forum_threads SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$threadId]);
            }

            $this->pdo->commit();
            return $success;
        } catch (PDOException $e) {
            $this->pdo->rollback();
            error_log("Error in deletePost: " . $e->getMessage());
            return false;
        }
    }
}
?>