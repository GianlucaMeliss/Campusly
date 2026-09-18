<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// 1. Leggiamo le date passate dal frontend, altrimenti usiamo quelle di default
$dataInizio = isset($_GET['inizio']) ? $_GET['inizio'] : date('Y-m-d\T00:00:00.000\Z');
$dataFine = isset($_GET['fine']) ? $_GET['fine'] : date('Y-m-d\T23:59:59.000\Z', strtotime('+7 days'));

// 2. Creiamo un nome file di cache UNICO per questa specifica settimana
$dataPulita = substr(preg_replace('/[^0-9]/', '', $dataInizio), 0, 8);
$cacheFile = 'cache_settimana_' . $dataPulita . '.json'; 

// IL FIX: Micro-cache di 60 secondi invece di 1 ora (3600)
// Protegge il server Cineca dai picchi di traffico, ma garantisce dati quasi in tempo reale.
$cacheTime = 300; 

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    header("X-Cache-Status: HIT-MICROCACHE"); 
    echo file_get_contents($cacheFile);
    exit; 
}

header("X-Cache-Status: MISS-FETCHING-CINECA"); 

$url = 'https://unins.prod.up.cineca.it/api/Impegni/getImpegniCalendarioPubblico';

$payloadArray = [
    "linkCalendarioId" => "6a69b9d9c4a67700195312e8",
    "clienteId" => "59f05192a635f443422fe8fd",
    "bachecaPrenotazioneSpaziAttiva" => false,
    "bachecaRisoluzioneImpegniAttiva" => false,
    "calcoloTassonomiaAule" => false,
    "mostraImpegniAnnullati" => false,
    "mostraPianificazioneInterna" => false,
    "pianificazioneInternaAttiva" => false,
    "dataInizio" => $dataInizio,
    "dataFine" => $dataFine
];

$payloadData = json_encode($payloadArray);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadData);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json;charset=utf-8',
    'Accept: application/json, text/plain, */*',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// --- NUOVE OPZIONI PER VELOCIZZARE LA CONNESSIONE ---
curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Evita i ritardi di risoluzione DNS IPv6
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Attende massimo 5 secondi per agganciare il server
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Attende massimo 15 secondi per scaricare i dati
// ----------------------------------------------------

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if(curl_errno($ch)){
    echo json_encode(['errore_curl' => curl_error($ch)]);
} else {
    // Salviamo la micro-cache solo se la risposta del Cineca è andata a buon fine
    if ($httpCode >= 200 && $httpCode < 300) {
        file_put_contents($cacheFile, $response);
    }
    http_response_code($httpCode);
    echo $response;
}
curl_close($ch);
?>