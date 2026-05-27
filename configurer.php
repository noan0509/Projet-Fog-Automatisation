<?php
/**
 * Application de déploiement automatisé via l'API FOG Project
 * Structure : L'Arche Oise
 */

// -------------------------------------------------------------------------
// 1. CONFIGURATION ET SÉCURITÉ PHP
// -------------------------------------------------------------------------

// Force l'affichage du contenu au format HTML avec un encodage UTF-8 (gestion des accents)
header('Content-Type: text/html; charset=utf-8');

// Force PHP à afficher visuellement toutes les erreurs (idéal pour le développement/débogage)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// -------------------------------------------------------------------------
// 2. CONFIGURATION DES ACCÈS AU SERVEUR FOG
// -------------------------------------------------------------------------

$fog_ip     = "192.168.2.128"; // Adresse IP locale de votre serveur FOG

// Jetons d'authentification cryptés (Base64) pour avoir le droit d'utiliser l'API FOG
$api_token  = "ODcyN2EyNWVkY2ZlYjhiOGE5YjhhNzViODM0MWJiNGQ1ZjhmYTU5ZDRhOTFmMzE5Y2E1ODA0YmIyMTg1ZjdhZjVmNjA5MzZjYTEwZTU4YjdlMmY3YzQ4ODNkMmM4ZWZkMmY4ZjgyZDNkMThkN2M5Yjk2M2QxYmQ3YTYzZGU2YmM=";
$user_token = "MzUzNDY4MDM1YTBiY2YwZGYzYjA3MzQ4MzM0YTIzMDdiZmYyMDIzY2MzNmZiOTYzNmE3MmYzNmQ5NTU2ZTYyMWE2YWQ0MDk3ZDQ2NDRkOGRhYTFhNzMxYzg1YzdjYzc3MzQ5MDZiMjY1NThmMjQzMTMwNmVmMmQ2ZjU3MGFmNTQ=";

// Préparation des en-têtes HTTP requis par FOG pour valider la connexion
$headers = [
    "Content-Type: application/json",    // On indique qu'on envoie des données au format JSON
    "fog-api-token: $api_token",         // Clé globale de l'API FOG
    "fog-user-token: $user_token"        // Clé spécifique à l'utilisateur FOG
];

// -------------------------------------------------------------------------
// 3. RÉCUPÉRATION ET TRAITEMENT DES DONNÉES DU FORMULAIRE (POST)
// -------------------------------------------------------------------------

// Récupère le nom de la machine (si vide, génère un nom aléatoire ex: HOST-412)
$hostname = trim($_POST['hostname'] ?? ('HOST-' . rand(100, 999)));

// Récupère l'adresse MAC brute tapée par l'utilisateur
$mac_raw  = trim($_POST['mac_address'] ?? '');

// NETTOYAGE DE LA MAC : FOG exige des minuscules et des séparateurs ":" (ex: 00:1a:2b:3c:4d:5e)
// On remplace donc les espaces et les tirets par des colons, puis on passe tout en minuscules
$mac      = strtolower(str_replace(['-', ' '], ':', $mac_raw));

// Récupère le type de matériel (Fixe par défaut) et la configuration IP (DHCP par défaut)
$materiel = $_POST['materiel'] ?? 'Fixe';
$ip_type  = $_POST['ip_type'] ?? 'DHCP';
$ip_val   = trim($_POST['ip_val'] ?? '');

// -------------------------------------------------------------------------
// 4. LOGIQUE MÉTIER : SÉLECTION DES LOGICIELS
// -------------------------------------------------------------------------

// Liste de base des logiciels installés sur toutes les machines de L'Arche Oise
$logiciels = [
    1 => 'WINVNC',
    2 => 'CHROME',
    3 => 'ADOBE',
    4 => 'LIBREOFFICE',
    5 => 'ESET',
];

// Règle spécifique : Si la machine est un ordinateur "Portable", on ajoute OpenVPN pour le télétravail
if ($materiel === "Portable") {
    $logiciels[6] = 'OPENVPN';
}

// -------------------------------------------------------------------------
// 5. FONCTION REQUÊTE HTTP (cURL) : COMMUNICATEUR AVEC L'API
// -------------------------------------------------------------------------

function fog_request($url, $method, $payload, $headers) {
    // Initialisation d'une session cURL vers l'URL FOG ciblée
    $ch = curl_init($url);
    
    // Configuration de toutes les options de la requête réseau
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,                   // On demande à cURL de capturer la réponse du serveur
        CURLOPT_CUSTOMREQUEST  => $method,                 // Définit la méthode HTTP (POST, GET, PUT...)
        CURLOPT_POSTFIELDS     => json_encode($payload),   // Convertit le tableau PHP en format de texte JSON
        CURLOPT_HTTPHEADER     => $headers,                // Intègre nos jetons d'accès dans les en-têtes
        CURLOPT_FOLLOWLOCATION => true,                    // Suit les redirections si le serveur FOG change d'URL
        CURLOPT_SSL_VERIFYPEER => false,                   // Désactive la vérification SSL (indispensable si HTTPS local sans certificat officiel)
        CURLOPT_SSL_VERIFYHOST => false,                   // Désactive la vérification du nom de l'hôte SSL
        CURLOPT_TIMEOUT        => 15,                      // Arrête la tentative si le serveur FOG ne répond pas après 15 secondes
    ]);
    
    // Exécution de la requête et récupération des données
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Récupère le code de statut (ex: 200 = Succès, 404 = Introuvable)
    $error     = curl_error($ch);                       // Capture l'erreur réseau si le serveur est éteint
    
    curl_close($ch); // Fermeture de la session cURL pour libérer la mémoire RAM
    
    // On renvoie un tableau propre avec toutes les informations de la réponse
    return ['code' => $http_code, 'body' => $response, 'error' => $error];
}

// -------------------------------------------------------------------------
// 6. ÉTAPE 1 : CRÉATION DE L'HÔTE (L'ORDINATEUR) DANS FOG
// -------------------------------------------------------------------------

// Préparation des données requises par FOG pour enregistrer une nouvelle machine
$host_payload = [
    "name"    => $hostname, // Le nom réseau du PC
    "imageID" => 1,         // ID 1 = L'ID de votre image système principale dans FOG
    "macs"    => [$mac],    // L'adresse MAC de la machine (Format standard de base)
];

// Si l'administrateur a choisi une IP fixe, on l'ajoute aux données envoyées
if ($ip_type === 'Statique' && $ip_val !== '') {
    $host_payload["ip"] = $ip_val;
}

// Envoi de la requête de création de l'hôte à l'API FOG
$result    = fog_request("https://$fog_ip/fog/host", "POST", $host_payload, $headers);
$host_data = json_decode($result['body'], true);         // Décode la réponse JSON reçue de FOG en tableau PHP
$step1_ok  = in_array($result['code'], [200, 201]);      // Vrai si FOG renvoie un code de succès (200 ou 201)
$host_id   = null;

// SYSTÈME DE SECOURS (Fallback) :
// Certaines versions de FOG demandent un format d'adresse MAC différent (un tableau d'objets).
// Si le premier essai échoue, on tente immédiatement le deuxième format.
if (!$step1_ok) {
    $host_payload['macs'] = [["mac" => $mac]]; // Nouveau format imbriqué
    $result    = fog_request("https://$fog_ip/fog/host", "POST", $host_payload, $headers);
    $host_data = json_decode($result['body'], true);
    $step1_ok  = in_array($result['code'], [200, 201]);
}

// Si la machine a bien été créée, on extrait l'identifiant unique généré par FOG ($host_id)
if ($step1_ok && isset($host_data['id'])) {
    $host_id = (int)$host_data['id'];
}

// -------------------------------------------------------------------------
// 7. ÉTAPE 2 : SÉQUENCE DE DÉPLOIEMENT (DÉCLENCHEMENT DE LA TÂCHE)
// -------------------------------------------------------------------------

$deploy_ok     = false;
$deploy_result = null;

// On ne lance le déploiement QUE si la machine a été créée avec succès à l'étape 1
if ($host_id) {
    // On contacte l'API pour créer une tâche ("task") reliée à cet ordinateur précis
    $deploy_result = fog_request(
        "https://$fog_ip/fog/host/$host_id/task",
        "POST",
        [
            "taskTypeID" => 1,   // ID 1 dans FOG correspond à l'action "Deploy" (écraser le disque avec l'image)
            "shutdown"   => "0", // "0" signifie qu'on ne demande pas d'éteindre le serveur FOG
            "wol"        => "1"  // "1" = Active le Wake-on-LAN pour essayer d'allumer le PC cible par le réseau
        ],
        $headers
    );
    // Vérifie si l'ordre de déploiement a bien été accepté par le serveur FOG
    $deploy_ok = in_array($deploy_result['code'], [200, 201]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultat déploiement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f4f4f4; padding-top:40px;">
<div class="card shadow mx-auto" style="max-width:580px;">
    
    <div class="card-header text-white fw-bold" style="background:#337ab7;">
        L'Arche Oise - Résultat du déploiement
    </div>
    
    <div class="card-body text-center">

        <?php if ($step1_ok && $host_id): ?>
            <h2 class="text-success mt-2">Hôte créé avec succès !</h2>
            
            <table class="table table-bordered mt-3 text-start">
                <tr><th>Nom</th><td><?= htmlspecialchars($hostname) ?></td></tr>
                <tr><th>MAC</th><td><?= htmlspecialchars($mac) ?></td></tr>
                <tr><th>Type</th><td><?= htmlspecialchars($materiel) ?></td></tr>
                <tr><th>ID FOG</th><td><?= $host_id ?></td></tr>
                <tr><th>Réseau</th><td><?= $ip_type === 'Statique' ? "Statique : " . htmlspecialchars($ip_val) : 'DHCP' ?></td></tr>
                
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
                    <th>Déploiement</th>
                    <td>
                        <?php if ($deploy_ok): ?>
                            <span class="text-success fw-bold">Lancé avec succès - Le PC va démarrer sur FOG</span>
                        <?php else: ?>
                            <span class="text-warning fw-bold">
                                Hôte créé mais déploiement non lancé (code <?= $deploy_result['code'] ?? '?' ?>)
                            </span>
                            <pre class="bg-light p-1 mt-1" style="font-size:10px;"><?= htmlspecialchars($deploy_result['body'] ?? '(vide)') ?></pre>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

        <?php else: ?>
            <h2 class="text-danger mt-2">Échec de la création</h2>
            <p>Code HTTP reçu : <strong><?= $result['code'] ?></strong></p>
            
            <?php if ($result['error']): ?>
                <p class="text-danger">Erreur réseau cURL : <?= htmlspecialchars($result['error']) ?></p>
            <?php endif; ?>
            
            <p class="text-muted text-start mb-0" style="font-size:11px;">Réponse brute de FOG :</p>
            <pre class="text-start bg-light p-2 mt-1" style="font-size:11px; overflow:auto;"><?= htmlspecialchars($result['body'] ?: '(réponse vide)') ?></pre>
            <hr>
            <p class="text-muted text-start mb-0" style="font-size:11px;">Données JSON qui ont été envoyées :</p>
            <pre class="text-start bg-light p-2 mt-1" style="font-size:11px;"><?= htmlspecialchars(json_encode($host_payload, JSON_PRETTY_PRINT)) ?></pre>
        <?php endif; ?>

        <a href="index.php" class="btn btn-primary mt-3">Retour</a>
    </div>
</div>
</body>
</html>
