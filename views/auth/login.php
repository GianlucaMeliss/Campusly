<section class="section">
    <div class="container narrow" style="max-width: 400px; margin: 0 auto;">
        <div class="highlight-box" style="padding: 2rem;">
            <h1 style="text-align: center; color: var(--primary-color);">Bentornato</h1>
            <p style="text-align: center; color: var(--text-secondary); margin-bottom: 2rem;">Accedi al tuo calendario</p>

            <?php if (($_GET['reset'] ?? '') === 'success'): ?>
                <div class="alert alert-success" style="margin-bottom: 1rem;">
                    Password aggiornata con successo. Ora puoi accedere.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <?php
                $errCode = (string)$_GET['error'];
                $errMsg = match ($errCode) {
                    'csrf'      => 'Sessione scaduta. Riprova ad accedere.',
                    'ratelimit' => 'Troppi tentativi di accesso. Riprova tra 15 minuti.',
                    default     => 'Credenziali non valide. Riprova.',
                };
                ?>
                <div style="background: #fee2e2; color: #ef4444; padding: 10px; border-radius: 6px; margin-bottom: 1rem; text-align: center;">
                    <?= htmlspecialchars($errMsg) ?>
                </div>
            <?php endif; ?>

            <form action="<?= htmlspecialchars($route('/login')) ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="email" style="font-size: 0.9rem; font-weight: 600;">Email universitaria</label>
                    <input type="email" name="email" id="email" placeholder="es. m.rossi@universita.it" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label for="password" style="font-size: 0.9rem; font-weight: 600; margin-bottom: 0;">Password</label>
                        <a href="<?= htmlspecialchars($route('/password-dimenticata')) ?>" style="font-size: 0.8rem; font-weight: 600;">Password dimenticata?</a>
                    </div>
                    <input type="password" name="password" id="password" placeholder="Inserisci la tua password" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember" style="font-size: 0.9rem; margin-bottom: 0;">Rimani collegato</label>
                </div>

                <button type="submit" style="background: var(--primary-color); color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px;">Accedi</button>
            </form>

            <p style="text-align: center; margin-top: 1.5rem; font-size: 0.9rem;">
                Non hai un account? <a href="<?= htmlspecialchars($route('/register')) ?>" style="color: var(--primary-color); font-weight: bold;">Registrati</a>
            </p>
        </div>
    </div>
</section>
