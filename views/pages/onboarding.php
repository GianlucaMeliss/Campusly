<?php
$url = $url ?? function(string $path = '/') use ($basePath): string {
    return rtrim($basePath ?? '', '/') . '/' . ltrim($path, '/');
};
?>
<section class="section">
    <div class="container narrow" style="max-width: 600px; margin: 0 auto;">
        <div class="highlight-box" style="padding: 2rem;">
            <h1 style="color: var(--primary-color); margin-bottom: 0.5rem;">Configura il tuo Calendario</h1>
            <p style="color: var(--text-secondary); margin-bottom: 2rem;">Seleziona la tua università e inserisci i codici del tuo corso di laurea.</p>

            <form action="<?= htmlspecialchars($url('/onboarding')) ?>" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                
                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600;">Università</label>
                    <select name="university_id" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px; background: var(--bg-main);">
                        <!-- Per ora mettiamo hardcoded Insubria (ID 1 nel DB) -->
                        <option value="1">Università dell'Insubria (Cineca UP)</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; gap: 5px;">
                    <label style="font-weight: 600;">Nome del tuo Corso (es. Informatica 1° Anno)</label>
                    <input type="text" name="course_name" required style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                </div>

                <div style="background: rgba(45, 212, 191, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                    <h3 style="margin-top: 0; font-size: 1rem; color: var(--primary-color);">Guida: Come trovare i tuoi codici Insubria</h3>
                    <ol style="margin-bottom: 0; padding-left: 20px; font-size: 0.9rem; color: var(--text-secondary); line-height: 1.6;">
                        <li>Vai sul portale orari dell'Insubria e cerca il tuo corso.</li>
                        <li>Nella barra degli indirizzi (URL), vedrai qualcosa come: <br><code>.../Impegni?linkCalendarioId=<strong>6a69b9d...</strong>&clienteId=<strong>59f051...</strong></code></li>
                        <li>Copia quei due codici e incollali qui sotto!</li>
                    </ol>
                </div>

                <div style="display: flex; gap: 15px;">
                    <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                        <label style="font-weight: 600; font-size: 0.9rem;">Link Calendario ID</label>
                        <input type="text" name="link_calendario_id" required placeholder="es. 6a69b9..." style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 5px; flex: 1;">
                        <label style="font-weight: 600; font-size: 0.9rem;">Cliente ID</label>
                        <input type="text" name="cliente_id" required placeholder="es. 59f051..." style="padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                    </div>
                </div>

                <button type="submit" style="background: var(--primary-color); color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px;">Salva e Vai al Calendario</button>
            </form>
        </div>
    </div>
</section>