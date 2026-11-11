<section class="section">
    <div class="container narrow" style="max-width: 400px; margin: 0 auto;">
        <div class="highlight-box" style="padding: 2rem;">
            <h1 style="text-align: center; color: var(--primary-color);">Recupera Password</h1>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;">
                Inserisci la tua email per ricevere il link di ripristino.
            </p>

            <?php if (($_GET['status'] ?? '') === 'sent'): ?>
                <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                    Se l'indirizzo email è associato a un account, riceverai a breve un link per reimpostare la password.
                </div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($route('/password-dimenticata')) ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="email" style="font-size: 0.9rem; font-weight: 600;">Email</label>
                    <input type="email" name="email" id="email" placeholder="es. m.rossi@universita.it" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <button type="submit" style="background: var(--primary-color); color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Invia link di recupero</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
                <a href="<?= htmlspecialchars($route('/login')) ?>" style="color: var(--primary-color); font-weight: bold;">Torna al Login</a>
            </p>
        </div>
    </div>
</section>
