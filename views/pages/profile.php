<?php
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
};
?>
<section class="section">
    <div class="container narrow" style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 30px;">
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'updated'): ?>
            <div style="background: #d1fae5; color: #059669; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold;">
                Impostazioni aggiornate con successo!
            </div>
        <?php endif; ?>

        <!-- 1. Gestione Corso -->
        <div class="highlight-box" style="padding: 2rem;">
            <h2 style="color: var(--primary-color); margin-top: 0; margin-bottom: 1rem;">Configurazione Corso</h2>
            <form action="<?= htmlspecialchars($url('/profilo/aggiorna-corso')) ?>" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600;">Nome del Corso</label>
                    <input type="text" name="course_name" value="<?= htmlspecialchars($course['name']) ?>" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                        <label style="font-weight: 600;">Link Calendario ID</label>
                        <input type="text" name="link_calendario_id" value="<?= htmlspecialchars($course['linkId']) ?>" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                        <label style="font-weight: 600;">Cliente ID</label>
                        <input type="text" name="cliente_id" value="<?= htmlspecialchars($course['clienteId']) ?>" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                </div>
                <button type="submit" style="background: var(--primary-color); color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; align-self: flex-start; margin-top: 10px;">Aggiorna Codici</button>
            </form>
        </div>

        <!-- 2. Corsi Nascosti -->
        <div class="highlight-box" style="padding: 2rem;">
            <h2 style="color: var(--primary-color); margin-top: 0; margin-bottom: 1rem;">Corsi Nascosti</h2>
            <p style="color: var(--text-secondary); margin-bottom: 1.5rem; font-size: 0.95rem;">Queste materie sono invisibili nel tuo calendario. Clicca su "Ripristina" per tornare a vederle.</p>
            
            <?php if (empty($hiddenCourses)): ?>
                <p><em>Nessun corso nascosto al momento.</em></p>
            <?php else: ?>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 10px;">
                    <?php foreach ($hiddenCourses as $hc): ?>
                        <li style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-main); padding: 12px 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                            <span style="font-weight: 600; font-size: 0.95rem;"><?= htmlspecialchars($hc) ?></span>
                            <!-- Riutilizziamo la funzione JS globale che abbiamo già scritto! -->
                            <button onclick="toggleCorsoNascosto('<?= htmlspecialchars(addslashes($hc)) ?>'); setTimeout(() => location.reload(), 500);" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: bold;">
                                Ripristina
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Necessario per far funzionare il bottone ripristina (che chiama l'API) -->
<script>window.APP_BASE_PATH = '<?= htmlspecialchars($basePath ?? '') ?>';</script>
<script src="<?= htmlspecialchars($url('/assets/js/app.js')) ?>"></script>