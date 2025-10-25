<footer class="mt-5 py-4 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <h5 class="mb-3">TESCON Ghana</h5>
                <p class="text-muted">Tertiary Students Confederacy of the New Patriotic Party</p>
                <p class="text-muted small">Empowering students, building leaders, shaping Ghana's future.</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="mb-3">Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-muted text-decoration-none">Home</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="members.php" class="text-muted text-decoration-none">Members</a></li>
                        <li><a href="pay_dues.php" class="text-muted text-decoration-none">Pay Dues</a></li>
                    <?php else: ?>
                        <li><a href="register.php" class="text-muted text-decoration-none">Register</a></li>
                        <li><a href="login.php" class="text-muted text-decoration-none">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="mb-3">Contact Us</h6>
                <ul class="list-unstyled text-muted small">
                    <li><i class="fas fa-envelope me-2"></i> info@tesconghana.org</li>
                    <li><i class="fas fa-phone me-2"></i> +233 XX XXX XXXX</li>
                    <li class="mt-3">
                        <a href="#" class="text-muted me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-muted me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    </li>
                </ul>
            </div>
        </div>
        <hr class="my-4 bg-secondary">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-muted small mb-0">&copy; <?php echo date('Y'); ?> TESCON Ghana. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="text-muted small mb-0">
                    <a href="#" class="text-muted text-decoration-none">Privacy Policy</a> | 
                    <a href="#" class="text-muted text-decoration-none">Terms of Service</a>
                </p>
            </div>
        </div>
    </div>
</footer>
