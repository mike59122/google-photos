# Dépannage

## Problèmes courants

### ❌ Erreur "invalid_scope"
- Vérifie que les scopes sont bien activés dans Google Cloud Console
- Supprime le token et relance l’authentification

### ❌ Permission denied
- Vérifie les droits sur le dossier de destination
- Utilise `chmod 755` ou `chown www-data`

### ❌ Token expiré
- Lance `token_refresh.sh` via cron toutes les 30 minutes
