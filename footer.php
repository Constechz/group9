        <!-- Shared Footer -->
        <footer class="no-print" style="margin-top: 60px; padding-top: 24px; border-top: 1px solid var(--border-color); text-align: center; color: var(--text-muted); font-size: 0.8rem;">
            <p>&copy; <?php echo date('Y'); ?> Almighty Driving School Database Project. Built by Group 9 of ITE Class.</p>
        </footer>
    </main>

    <!-- Global Scripts -->
    <script>
        // Modal toggling utility
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
            }
        }

        // Close modal when clicking outside content area
        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        });

        // Auto-dismiss alert notifications after 4 seconds
        document.addEventListener('DOMContentLoaded', () => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => alert.remove(), 500);
                }, 4000);
            });

            // Mobile sidebar toggle handler
            const mobileToggle = document.getElementById('mobile-nav-toggle');
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (mobileToggle && sidebar && overlay) {
                mobileToggle.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });

                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }
        });
    </script>
</body>
</html>
