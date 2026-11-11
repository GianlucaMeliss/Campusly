</main>

    <footer style="padding: 40px 20px; text-align: center; border-top: 1px solid var(--border-color); margin-top: 60px;">
        <div class="container">
            <p style="color: var(--text-secondary); font-size: 0.9rem; font-weight: 500; margin-bottom: 12px;">
                © <?= gmdate('Y') ?> Campusly. Progettato per gli studenti.
            </p>
            <nav style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; font-size: 0.85rem;">
                <a href="<?= htmlspecialchars(BASE_PATH) ?>/privacy" style="color: var(--text-secondary);">Privacy Policy</a>
                <a href="<?= htmlspecialchars(BASE_PATH) ?>/cookie" style="color: var(--text-secondary);">Cookie Policy</a>
                <a href="<?= htmlspecialchars(BASE_PATH) ?>/termini" style="color: var(--text-secondary);">Termini e Condizioni</a>
            </nav>
        </div>
    </footer>
    <script>
        window.addEventListener('load', () => {
            const loader = document.getElementById('pwa-loader');
            if (loader) {
                loader.classList.add('hidden');
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                }, 500);
            }
        });
    </script>
</body>
</html>
