<section class="section">
    <div class="container narrow" style="max-width: 400px; margin: 0 auto;">
        <div class="highlight-box" style="padding: 2rem;">
            <h1 style="text-align: center; color: var(--primary-color);">Bentornato</h1>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 2rem;">Accedi al tuo calendario</p>

            <?php if (isset($_GET['error'])): ?>
                <div style="background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
                    Credenziali non valide. Riprova.
                </div>
            <?php endif; ?>

            <!-- Modificato $url('/login') con $route('/login') -->
            <form action="<?= htmlspecialchars($route('/login')) ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="email" style="font-size: 0.9rem; font-weight: 600;">Email universitaria</label>
                    <input type="email" name="email" id="email" placeholder="es. m.rossi@studenti.uninsubria.it" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="password" style="font-size: 0.9rem; font-weight: 600;">Password</label>
                    <input type="password" name="password" id="password" placeholder="Inserisci la tua password" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" style="font-size: 0.9rem;">Rimani collegato</label>
                </div>

                <button type="submit" style="background: var(--primary-color); color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px;">Accedi</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
                <!-- Modificato $url('/register') con $route('/register') -->
                Non hai un account? <a href="<?= htmlspecialchars($route('/register')) ?>" style="color: var(--primary-color); font-weight: bold;">Registrati</a>
            </p>
        </div>
    </div>
</section>