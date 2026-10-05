<?php
$url = "https://search.usi.ch/api/educations/100/schedules/61/3";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
curl_close($ch);

$dati = json_decode($response, true);

echo "<h3>Struttura reale di una lezione USI:</h3>";
echo "<pre style='background:#1e1e1e; color:#00ff00; padding:15px; border-radius:5px;'>";
// Stampiamo SOLO la prima lezione in modo leggibile
if (!empty($dati['data'][0])) {
    print_r($dati['data'][0]);
} else {
    echo "Nessun dato trovato.";
}
echo "</pre>";