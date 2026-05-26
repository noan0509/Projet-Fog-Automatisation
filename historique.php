<?php
$user_token = "MzUzNDY4MDM1YTBiY2YwZGYzYjA3MzQ4MzM0YTIzMDdiZmYyMDIzY2MzNmZiOTYzNmE3MmYzNmQ5NTU2ZTYyMWE2YWQ0MDk3ZDQ2NDRkOGRhYTFhNzMxYzg1YzdjYzc3MzQ5MDZiMjY1NThmMjQzMTMwNmVmMmQ2ZjU3MGFmNTQ=";
$api_token = "ODcyN2EyNWVkY2ZlYjhiOGE5YjhhNzViODM0MWJiNGQ1ZjhmYTU5ZDRhOTFmMzE5Y2E1ODA0YmIyMTg1ZjdhZjVmNjA5MzZjYTEwZTU4YjdlMmY3YzQ4ODNkMmM4ZWZkMmY4ZjgyZDNkMThkN2M5Yjk2M2QxYmQ3YTYzZGU2YmM=";
$ch = curl_init("https://192.168.2.119/fog/host");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["fog-api-token: $api_token", "fog-user-token: $user_token"]);
$data = json_decode(curl_exec($ch), true);
$hosts = $data['hosts'] ?? [];
?>
<!DOCTYPE html>
<html><body style="padding:20px;font-family:sans-serif;">
    <h2>Historique L'Arche</h2>
    <table border="1" style="width:100%; border-collapse:collapse;">
        <tr style="background:#337ab7; color:white;"><th>Nom</th><th>Description</th></tr>
        <?php foreach($hosts as $h): ?>
        <tr><td><?php echo $h['name']; ?></td><td><?php echo $h['description']; ?></td></tr>
        <?php endforeach; ?>
    </table>
</body></html>