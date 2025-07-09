<!-- Footer -->
    <footer class="footer mt-auto py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-chart-line mr-2"></i>RiemInsights</h5>
                    <p class="text-muted">Advanced data analysis and visualization platform powered by AI.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-home mr-2"></i>Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/data_analysis.php"><i class="fas fa-chart-bar mr-2"></i>Data Analysis</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/upgrade_plan.php"><i class="fas fa-arrow-circle-up mr-2"></i>Upgrade Plan</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope mr-2"></i>info@rieminsights.com</li>
                        <li><i class="fas fa-phone mr-2"></i>+1 (555) 123-4567</li>
                    </ul>
                    <div class="social-icons mt-3">
                        <a href="#" class="mr-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="mr-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="mr-2"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-github"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> RiemInsights. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <a href="#" class="text-muted mr-3">Privacy Policy</a>
                    <a href="#" class="text-muted mr-3">Terms of Service</a>
                    <a href="#" class="text-muted">Help Center</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <?php if (isset($additional_js)) echo $additional_js; ?>
</body>
</html>