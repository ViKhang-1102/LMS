<?php
include_once __DIR__ . '/auth.php';
include_once __DIR__ . '/../config/db.php';

$currentUser = getCurrentUser($pdo);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/index.php"><i class="fas fa-graduation-cap"></i> LMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if ($currentUser && $currentUser['role'] !== 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> 
                            <?php echo $currentUser['role'] === 'instructor' ? 'Instructor Dashboard' : 'Dashboard'; ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/admin/dashboard.php"><i class="fas fa-user-shield"></i> Admin Dashboard</a>
                    </li>
                <?php endif; ?>

                <?php if ($currentUser && in_array($currentUser['role'], ['admin', 'instructor'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/courses.php"><i class="fas fa-book"></i> Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                    </li>
                <?php endif; ?>

                <?php if ($currentUser): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/grades.php"><i class="fas fa-clipboard-check"></i> Grades</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/messages.php"><i class="fas fa-comments"></i> Messages</a>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($currentUser): ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo htmlspecialchars($currentUser['avatar_path'] ?? '/assets/images/default_avatar.jpg'); ?>" 
                                 alt="Avatar" class="rounded-circle me-2" style="width: 30px; height: 30px; object-fit: cover;">
                            <?php echo htmlspecialchars($currentUser['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/pages/profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="/controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auth/register.php"><i class="fas fa-user-plus"></i> Register</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>