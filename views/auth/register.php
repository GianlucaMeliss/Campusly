<section class="section">
    <div class="container narrow" style="max-width: 400px; margin: 0 auto;">
        <div class="highlight-box" style="padding: 2rem;">
            <h1 style="text-align: center; color: var(--primary-color);">Crea Account</h1>
            
            <!-- Modificato $url('/register') con $route('/register') -->
            <form action="<?= htmlspecialchars($route('/register')) ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px; margin-top: 2rem;">
                <div style="display: flex; gap: 10px;">
                    <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                        <label for="first_name" style="font-size: 0.9rem; font-weight: 600;">Nome</label>
                        <input type="text" name="first_name" id="first_name" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                        <label for="last_name" style="font-size: 0.9rem; font-weight: 600;">Cognome</label>
                        <input type="text" name="last_name" id="last_name" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="email" style="font-size: 0.9rem; font-weight: 600;">Email</label>
                    <input type="email" name="email" id="email" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="password" style="font-size: 0.9rem; font-weight: 600;">Password</label>
                    <input type="password" name="password" id="password" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <button type="submit" style="background: var(--primary-color); color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px;">Registrati</button>
            </form>
        </div>
    </div>
</section>