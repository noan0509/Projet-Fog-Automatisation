<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>L'Arche Oise - Host Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f4f4; padding: 20px; font-family: sans-serif; }
        .header-blue { background-color: #337ab7; color: white; padding: 15px; text-align: center; font-weight: bold; border-radius: 5px 5px 0 0; }
        .main-card { background: white; padding: 30px; border: 1px solid #ddd; border-radius: 0 0 5px 5px; max-width: 600px; margin: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        label { font-weight: bold; margin-top: 10px; display: block; }
        .btn-arche { background-color: #337ab7; color: white; border: none; font-weight: bold; margin-top: 20px; padding: 12px; }
    </style>
</head>
<body>
<div style="max-width: 600px; margin: auto;">
    <div class="header-blue">L'Arche Oise - Host Management</div>
    <div class="main-card">
        <h3 class="text-center mb-4">Creer un Nouvel Hote Automatise</h3>
        <form action="configurer.php" method="POST">
            <div class="mb-3">
                <label>Nom de l'ordinateur</label>
                <input type="text" name="hostname" class="form-control" placeholder="Ex: D0165" required>
            </div>
            <div class="mb-3">
                <label>Adresse MAC</label>
                <input type="text" name="mac_address" class="form-control" placeholder="00:11:22:33:44:55" required>
            </div>
            <div class="mb-3">
                <label>Type de materiel</label>
                <select name="materiel" class="form-select">
                    <option value="Fixe">Ordinateur Fixe</option>
                    <option value="Portable">Ordinateur Portable (+ OpenVPN)</option>
                </select>
            </div>
            <div class="mb-3">
                <label>Reseau</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="ip_type" value="DHCP" checked
                        onclick="document.getElementById('z_ip').style.display='none'">
                    <label class="form-check-label" style="display:inline; font-weight:normal;">DHCP</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="ip_type" value="Statique"
                        onclick="document.getElementById('z_ip').style.display='block'">
                    <label class="form-check-label" style="display:inline; font-weight:normal;">Statique</label>
                </div>
            </div>
            <div id="z_ip" style="display:none; border:1px dashed #337ab7; padding:15px; border-radius:5px; margin-top:10px;">
                <label>IP souhaitee :</label>
                <input type="text" name="ip_val" class="form-control" placeholder="192.168.x.x">
            </div>
            <button type="submit" class="btn btn-arche w-100">LANCER LE DEPLOIEMENT</button>
        </form>
    </div>
</div>
</body>
</html>