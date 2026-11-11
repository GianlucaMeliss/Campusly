<section class="section">
    <div class="container narrow" style="max-width: 400px; margin: 0 auto;">
        <div class="highlight-box" style="padding: 2rem;">
            <h1 style="text-align: center; color: var(--primary-color);">Nuova Password</h1>

            <?php if (empty($isValid)): ?>
                <div style="background: #fee2e2; color: #ef4444; padding: 12px; border-radius: 6px; margin-top: 1.5rem; text-align: center;">
                    Questo link di recupero è scaduto o non è più valido.
                </div>
                <p style="text-align: center; margin-top: 1.5rem;">
                    <a href="<?= htmlspecialchars($route('/password-dimenticata')) ?>" class="btn btn-primary" style="width: 100%;">Richiedi un nuovo link</a>
                </p>
            <?php else: ?>
                <?php if (($_GET['error'] ?? '') === 'weak_password'): ?>
                    <div style="background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 6px; margin-top: 1rem; text-align: center;">
                        La password deve avere almeno 8 caratteri.
                    </div>
                <?php endif; ?>

                <form action="<?= htmlspecialchars($route('/reset-password/' . $token)) ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px; margin-top: 1.5rem;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <label for="password" style="font-size: 0.9rem; font-weight: 600;">Nuova Password</label>
                        <input type="password" name="password" id="password" placeholder="Almeno 8 caratteri" required minlength="8" style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>

                    <button type="submit" style="background: var(--primary-color); color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Salva Nuova Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
