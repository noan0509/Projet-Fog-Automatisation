# 🚀 Interface d'Initialisation de Poste - L'Arche Oise

Ce projet a été développé dans le cadre de mon alternance au sein de l'association **L'Arche Oise**. Il permet d'automatiser et de standardiser la post-configuration des postes de travail (PC Fixes et Portables).

## 📝 Description du projet
L'outil consiste en une interface web locale (PHP/Apache) qui permet à un technicien de saisir les paramètres réseau et de choisir un profil de poste. 
## ✨ Fonctionnalités
- **Renommage automatique** du poste (Nom NetBIOS).
- **Configuration réseau intelligente** :
  - Mode DHCP (Automatique).
  - Mode Statique (IP, Passerelle, DNS Primaire et Secondaire).
- **Aiguillage par profil** :
  - **Profil Fixe** : Configuration Ethernet + Logiciels bureautiques (Chrome, Adobe).
  - **Profil Portable** : Configuration Wi-Fi + Outils de mobilité (Teams, VPN).
- **Installation automatisée** des logiciels via le gestionnaire **Winget**.

## 🛠️ Technologies utilisées
* **Frontend :** HTML / CSS (Design moderne et responsive).
* **Backend :** PHP (Aiguillage et exécution système).
* **Scripting :** PowerShell (Configuration Windows).
* **Gestion de version :** Git & GitHub.

## 📁 Structure du dépôt
* `index.php` : Formulaire de saisie utilisateur.
* `style.css` : Design de l'interface.
* `configurer.php` : Script de traitement et appel des scripts PowerShell.
* `setup-fixe.ps1` : Script de configuration pour les postes de bureau.
* `setup-portable.ps1` : Script de configuration pour les postes nomades.

## 🚀 Installation
1. Copier le contenu du dossier dans `C:\xampp\htdocs\arche`.
2. Lancer le module Apache via le panneau de contrôle XAMPP.
3. Accéder à l'interface via `http://localhost/arche`.
4. Exécuter en tant qu'administrateur pour permettre les modifications système.

---
## 📊 Comparatif : Avant / Après l'automatisation

L'implémentation de cette interface permet de réduire drastiquement le temps de préparation des postes.

| Critères | Ancienne Méthode (Manuelle) | Nouvelle Méthode (Interface + Scripts) |
| :--- | :--- | :--- |
| **Temps moyen / poste** | ~1h30 (Installation + Config) | ~15 minutes (Automatisé) |
| **Risque d'erreur** | Élevé (Oublis, fautes de frappe) | Quasi nul (Standardisation) |
| **Profils de poste** | Configuration identique pour tous | Profils adaptés (Fixe / Portable) |
| **Déploiement** | Individuel et manuel | Centralisé et scripté |

---
## 🛡️ Sécurité et Conformité
L'exécution des scripts est sécurisée par une politique de contournement (`Bypass`) limitée à la session de déploiement. L'utilisation de **Winget** garantit que les logiciels installés proviennent de sources officielles et sont à jour.
