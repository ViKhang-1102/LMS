<?php
include_once '../includes/auth.php';
include_once '../config/db.php';
include_once '../includes/header.php';
?>

<div class="container mt-5">
    <h1 class="text-center mb-4">About Our Learning Management System</h1>
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title">Welcome to LMS</h2>
                    <p class="card-text">
                        Our Learning Management System (LMS) is designed to empower students and administrators with a seamless and intuitive platform for online education. We aim to make learning accessible, engaging, and efficient for everyone.
                    </p>
                    <h3>Our Mission</h3>
                    <p>
                        To provide a robust and user-friendly platform that supports course management, assignment submission, grading, and interactive communication between students and instructors.
                    </p>
                    <h3>Key Features</h3>
                    <ul>
                        <li>Course Management: Create, manage, and enroll in courses with ease.</li>
                        <li>Assignments & Grading: Submit assignments and receive feedback effortlessly.</li>
                        <li>Discussion Forums: Engage in meaningful discussions with peers and instructors.</li>
                        <li>Analytics: Track your progress with detailed insights and charts.</li>
                        <li>Secure Payments: Purchase courses securely with integrated payment options.</li>
                    </ul>
                    <h3>Our Team</h3>
                    <p>
                        We are a dedicated team of educators and developers committed to transforming the online learning experience. Contact us to learn more or share your feedback!
                    </p>
                    <a href="/pages/contact.php" class="btn btn-primary">Get in Touch</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
include_once '../includes/footer.php';
?>