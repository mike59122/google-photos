# Jeedom Google Photos Sync

Cette application permet de synchroniser automatiquement les photos de votre compte Google Photos avec votre système domotique Jeedom.

## 📸 Fonctionnalités
- Authentification sécurisée via OAuth2
- Téléchargement des nouvelles photos dans un dossier local
- Support des albums, des dates et des métadonnées

## 🔐 Scopes utilisés
- `https://www.googleapis.com/auth/photoslibrary.readonly`
- `https://www.googleapis.com/auth/photoslibrary.appendonly`

## ⚙️ Technologies
- PHP (OAuth2 + API Google)
- Bash (automatisation)
- Jeedom (scénarios, cron, interactions)
- Raspberry Pi ou serveur local

## 📄 Politique de confidentialité
Cette application est auto-hébergée. Aucune donnée n’est transmise à des serveurs tiers. Les identifiants OAuth2 sont stockés localement et chiffrés. Les photos sont uniquement utilisées dans le cadre de votre usage personnel avec Jeedom.

## 📚 Documentation
- [Guide d’installation](docs/setup.md)
- [Configuration OAuth2](docs/oauth.md)
- [Dépannage](docs/troubleshooting.md)

## 🧪 Statut
Application en développement actif. Testée sur Jeedom v4 avec Raspberry Pi OS Lite.

## 📬 Contact
Pour toute question ou suggestion : [michael@ozaer.eu](mailto:michael@ozaer.eu)
