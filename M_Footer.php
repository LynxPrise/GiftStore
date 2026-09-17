</div> <!-- End .app-layout -->

    <script>
        const burgerBtn = document.getElementById('mobileBurgerBtn');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        if (burgerBtn && sidebar && overlay) {
            burgerBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        }
    </script>
</body>
</html>