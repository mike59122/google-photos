
---

### `docs/oauth.md` – Configuration OAuth2

```markdown
# Configuration OAuth2

## Étapes
1. Aller sur [console.cloud.google.com](https://console.cloud.google.com)
2. Créer un projet
3. Activer l’API Google Photos
4. Créer des identifiants OAuth2 (type : Application Web)
5. Ajouter l’URL de redirection : `https://jeedom.mondomaine.fr/oauth2callback`
6. Renseigner la page d’accueil : `https://github.com/michael/google-photos-jeedom`
7. Ajouter les scopes :
   - `https://www.googleapis.com/auth/photoslibrary.readonly`
   - `https://www.googleapis.com/auth/photoslibrary.appendonly`
