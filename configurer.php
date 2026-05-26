<?php
header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// CONFIG FOG
$fog_ip     = "192.168.2.128";
$api_token  = "ODcyN2EyNWVkY2ZlYjhiOGE5YjhhNzViODM0MWJiNGQ1ZjhmYTU5ZDRhOTFmMzE5Y2E1ODA0YmIyMTg1ZjdhZjVmNjA5MzZjYTEwZTU4YjdlMmY3YzQ4ODNkMmM4ZWZkMmY4ZjgyZDNkMThkN2M5Yjk2M2QxYmQ3YTYzZGU2YmM=";
$user_token = "MzUzNDY4MDM1YTBiY2YwZGYzYjA3MzQ4MzM0YTIzMDdiZmYyMDIzY2MzNmZiOTYzNmE3MmYzNmQ5NTU2ZTYyMWE2YWQ0MDk3ZDQ2NDRkOGRhYTFhNzMxYzg1YzdjYzc3MzQ5MDZiMjY1NThmMjQzMTMwNmVmMmQ2ZjU3MGFmNTQ=";

$headers = [
    "Content-Type: application/json",
    "fog-api-token: $api_token",
    "fog-user-token: $user_token"
];

// DONNEES DU FORMULAIRE
$hostname = trim($_POST['hostname'] ?? ('HOST-' . rand(100, 999)));
$mac_raw  = trim($_POST['mac_address'] ?? '');
$mac      = strtolower(str_replace(['-', ' '], ':', $mac_raw));
$materiel = $_POST['materiel'] ?? 'Fixe';
$ip_type  = $_POST['ip_type'] ?? 'DHCP';
$ip_val   = trim($_POST['ip_val'] ?? '');

// Logiciels selon type
$logiciels = [
    1 => 'WINVNC',
    2 => 'CHROME',
    3 => 'ADOBE',
    4 => 'LIBREOFFICE',
    5 => 'ESET',
];
if ($materiel === "Portable") {
    $logiciels[6] = 'OPENVPN';
}

// FONCTION CURL
function fog_request($url, $method, $payload, $headers) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error     = curl_error($ch);
    curl_close($ch);
    return ['code' => $http_code, 'body' => $response, 'error' => $error];
}

// ETAPE 1 : CREER L'HOTE
$host_payload = [
    "name"    => $hostname,
    "imageID" => 1,
    "macs"    => [$mac],
];

if ($ip_type === 'Statique' && $ip_val !== '') {
    $host_payload["ip"] = $ip_val;
}

$result    = fog_request("https://$fog_ip/fog/host", "POST", $host_payload, $headers);
$host_data = json_decode($result['body'], true);
$step1_ok  = in_array($result['code'], [200, 201]);
$host_id   = null;

if (!$step1_ok) {
    $host_payload['macs'] = [["mac" => $mac]];
    $result    = fog_request("https://$fog_ip/fog/host", "POST", $host_payload, $headers);
    $host_data = json_decode($result['body'], true);
    $step1_ok  = in_array($result['code'], [200, 201]);
}

if ($step1_ok && isset($host_data['id'])) {
    $host_id = (int)$host_data['id'];
}

// ETAPE 2 : LANCER LE DEPLOIEMENT DE L'IMAGE
$deploy_ok     = false;
$deploy_result = null;

if ($host_id) {
    $deploy_result = fog_request(
        "https://$fog_ip/fog/host/$host_id/task",
        "POST",
        [
            "taskTypeID" => 1,   // 1 = Deploy dans FOG
            "shutdown"   => "0",
            "wol"        => "1"  // Wake On LAN pour allumer le PC
        ],
        $headers
    );
    $deploy_ok = in_array($deploy_result['code'], [200, 201]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Resultat deploiement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f4f4f4; padding-top:40px;">
<div class="card shadow mx-auto" style="max-width:580px;">
    <div class="card-header text-white fw-bold" style="background:#337ab7;">
        L'Arche Oise - Resultat du deploiement
    </div>
    <div class="card-body text-center">

        <?php if ($step1_ok && $host_id): ?>
            <h2 class="text-success mt-2">Hote cree avec succes !</h2>
            <table class="table table-bordered mt-3 text-start">
                <tr><th>Nom</th><td><?= htmlspecialchars($hostname) ?></td></tr>
                <tr><th>MAC</th><td><?= htmlspecialchars($mac) ?></td></tr>
                <tr><th>Type</th><td><?= htmlspecialchars($materiel) ?></td></tr>
                <tr><th>ID FOG</th><td><?= $host_id ?></td></tr>
                <tr><th>Reseau</th><td><?= $ip_type === 'Statique' ? "Statique : " . htmlspecialchars($ip_val) : 'DHCP' ?></td></tr>
                <tr>
                    <th>Logiciels (<?= count($logiciels) ?>)</th>
                    <td>
                        <ul class="mb-0" style="font-size:12px;">
                            <?php foreach ($logiciels as $nom): ?>
                                <li><?= $nom ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <th>Deploiement</th>
                    <td>
                        <?php if ($deploy_ok): ?>
                            <span class="text-success fw-bold">Lance avec succes - Le PC va demarrer sur FOG</span>
                        <?php else: ?>
                            <span class="text-warning fw-bold">
                                Hote cree mais deploiement non lance (code <?= $deploy_result['code'] ?? '?' ?>)
                            </span>
                            <pre class="bg-light p-1 mt-1" style="font-size:10px;"><?= htmlspecialchars($deploy_result['body'] ?? '(vide)') ?></pre>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

        <?php else: ?>
            <h2 class="text-danger mt-2">Echec de la creation</h2>
            <p>Code HTTP : <strong><?= $result['code'] ?></strong></p>
            <?php if ($result['error']): ?>
                <p class="text-danger">Erreur cURL : <?= htmlspecialchars($result['error']) ?></p>
            <?php endif; ?>
            <pre class="text-start bg-light p-2 mt-2" style="font-size:11px; overflow:auto;"><?= htmlspecialchars($result['body'] ?: '(reponse vide)') ?></pre>
            <hr>
            <p class="text-muted" style="font-size:11px;">Payload envoye :</p>
            <pre class="text-start bg-light p-2" style="font-size:11px;"><?= htmlspecialchars(json_encode($host_payload, JSON_PRETTY_PRINT)) ?></pre>
        <?php endif; ?>

        <a href="index.php" class="btn btn-primary mt-3">Retour</a>
    </div>
</div>
</body>
</html>