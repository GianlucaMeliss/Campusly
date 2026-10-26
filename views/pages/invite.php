<section class="section">
    <div class="container narrow" style="max-width: 460px; margin: 0 auto;">
        <div class="highlight-box" style="text-align: center; padding: 36px 28px;">
            
            <?php if (!$group): ?>
                <div style="font-size: 48px; margin-bottom: 16px;">😕</div>
                <h1 class="section-title">Invito non valido</h1>
                <p class="section-subtitle">
                    Il link di invito che hai seguito non esiste più oppure il codice non è corretto.
                </p>
                <a href="<?= htmlspecialchars($route('/dashboard')) ?>" class="btn btn-primary" style="width: 100%;">Vai al Calendario</a>

            <?php else: ?>
                <div style="width: 64px; height: 64px; border-radius: 20px; background: var(--gradient-brand); color: #fff; font-size: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                    👥
                </div>

                <span style="background: rgba(124, 77, 255, 0.12); color: var(--brand-purple); font-size: 0.8rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; display: inline-block; margin-bottom: 12px;">
                    Invito al Gruppo di Studio
                </span>

                <h1 style="font-size: 1.75rem; margin-bottom: 8px; color: var(--text-primary);">
                    <?= htmlspecialchars($group['name']) ?>
                </h1>

                <p style="color: var(--text-secondary); font-size: 0.95rem; margin-bottom: 24px;">
                    Creato da <strong><?= htmlspecialchars($group['creator_name'] ?? 'uno studente') ?></strong> &bull; 
                    <strong><?= (int)$group['members_count'] ?></strong> <?= (int)$group['members_count'] === 1 ? 'membro' : 'membri' ?>
                </p>

                <form id="form-magic-invite" onsubmit="accettaInvito(event)" style="text-align: left; display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label for="invite-privacy">Come vuoi condividere il tuo orario?</label>
                        <select id="invite-privacy">
                            <option value="transparent" selected>👁️ Trasparente (Mostra materie e aule)</option>
                            <option value="logistical">📍 Logistico (Mostra solo in che sede/aula sei)</option>
                            <option value="opaque">🔒 Opaco (Mostra solo "Occupato")</option>
                        </select>
                    </div>

                    <button type="submit" id="btn-accept-invite" class="btn btn-primary" style="width: 100%; margin-top: 8px;">
                        Unisciti al Gruppo
                    </button>
                    <a href="<?= htmlspecialchars($route('/dashboard')) ?>" class="btn btn-secondary" style="width: 100%;">
                        Annulla
                    </a>
                </form>

                <script>
                    window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';
                    window.CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>';

                    async function accettaInvito(e) {
                        e.preventDefault();
                        const btn = document.getElementById('btn-accept-invite');
                        btn.disabled = true;
                        btn.textContent = 'Ingresso in corso...';

                        try {
                            const res = await fetch(window.APP_BASE_PATH + '/api/gruppi/unisciti', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-Token': window.CSRF_TOKEN
                                },
                                body: JSON.stringify({
                                    invite_code: '<?= htmlspecialchars($group['invite_code']) ?>',
                                    privacy_level: document.getElementById('invite-privacy').value,
                                    csrf_token: window.CSRF_TOKEN
                                })
                            });
                            const data = await res.json();
                            if (data.status === 'success' && data.group_id) {
                                window.location.href = window.APP_BASE_PATH + '/dashboard?group=' + data.group_id;
                            } else {
                                alert(data.error || 'Impossibile unirsi al gruppo.');
                                btn.disabled = false;
                                btn.textContent = 'Unisciti al Gruppo';
                            }
                        } catch (err) {
                            alert('Errore di rete. Riprova.');
                            btn.disabled = false;
                            btn.textContent = 'Unisciti al Gruppo';
                        }
                    }
                </script>
            <?php endif; ?>

        </div>
    </div>
</section>