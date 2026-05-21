# Exemple — dossier Swagger dans une application gérée

Après `composer require application-manager-tools/am-driver`, depuis la **racine Symfony** :

```bash
vendor/application-manager-tools/am-driver/docs/openapi/kit/sync-openapi.sh docker/swagger/am-driver
```

Puis fusionner `../docker-compose.snippet.yml` dans le `docker-compose.yml` du projet.

Le fichier `receptacle-v1.yaml` apparaît ici (non versionné dans cet exemple — généré par le script).
