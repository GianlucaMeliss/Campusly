<?php
// Mostriamo tutti gli errori per non perderci nulla
ini_set('display_errors', 1);
error_reporting(E_ALL);

$url = "https://search.usi.ch/api/educations/100/schedules/61/3";

echo "<h2>Test Connessione API USI</h2>";
echo "Provo a contattare: <strong>$url</strong><br><br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
// Aggiungiamo un po' di header finti per ingannare Cloudflare
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json, text/javascript, */*; q=0.01',
    'Accept-Language: it-IT,it;q=0.9',
    'Referer: https://search.usi.ch/'
]);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Codice HTTP: <span style='color:" . ($httpCode == 200 ? "green" : "red") . "'>$httpCode</span></h3>";
echo "<hr><h3>Risposta Grezza del Server:</h3>";
echo "<pre style='background:#f4f4f4; padding:15px; border-radius:5px; max-height:400px; overflow-y:auto;'>";
if (!$response) {
    echo "NESSUNA RISPOSTA. Il server potrebbe aver bloccato la richiesta PHP.";
} else {
    // Stampiamo i primi 1000 caratteri per non intasare la pagina
    echo htmlspecialchars(substr($response, 0, 1000)) . "...";
}
echo "</pre>";

// Se abbiamo un JSON valido, proviamo a decodificarlo
$dati = json_decode($response, true);
if (is_array($dati)) {
    echo "<h3>✅ JSON Decodificato correttamente! (Trovati " . count($dati) . " eventi)</h3>";
} else {
    echo "<h3>❌ Il formato non è un JSON valido.</h3>";
}