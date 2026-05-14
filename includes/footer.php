    </main>

    <footer style="background: var(--white); padding: 2rem; margin-top: 3rem; border-top: 1px solid var(--border);">
        <div class="container" style="text-align: center;">
            <p style="color: var(--gray); font-size: 0.9rem;">
                &copy; <?php echo date('Y'); ?> InternHub. Connecting students with opportunities.
            </p>
        </div>
    </footer>

    <script>
        // Simple JavaScript utilities
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            // Confirm delete actions
            document.querySelectorAll('[data-confirm]').forEach(el => {
                el.addEventListener('click', function(e) {
                    if (!confirm(this.dataset.confirm)) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
