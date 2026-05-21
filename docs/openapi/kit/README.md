# Kit Swagger UI — routes récepteur am-driver

Permet d’exécuter **Try it out** contre le **vrai serveur** de l’application gérée (nginx + routes Symfony du bundle), quelle que soit l’app, en ne personnalisant que l’**URL de base** et les **jetons**.

## Fichiers du kit

| Fichier | Rôle |
|---------|------|
| [sync-openapi.sh](./sync-openapi.sh) | Copie `receptacle-v1.yaml` dans l’app hôte (`docker/swagger/am-driver/`) |
| [docker-compose.snippet.yml](./docker-compose.snippet.yml) | Service `swagger-ui-am-driver` à fusionner dans le Compose de l’app |
| [servers.example.yaml](./servers.example.yaml) | Exemples d’URL `servers` selon produit |
| [managed-app.example/](./managed-app.example/) | Arborescence type à créer dans l’application gérée |

## Développement du bundle (ce dépôt)

```bash
docker compose up -d swagger-ui
# http://127.0.0.1:18098 — choisir le server « Réceptacle CLI » ou variables host/routePrefix
```

Avec réceptacle CLI pour tests réels :

```bash
docker compose run --rm -p 18099:8099 php \
  php bin/am-driver serve --host=0.0.0.0 --port=8099 \
  --token-command=dev-command-token --token-state=dev-state-token
```

## Intégration dans une application gérée

### 1. Copier la spec

Depuis la racine du projet Symfony (après `composer require application-manager-tools/am-driver`) :

```bash
vendor/application-manager-tools/am-driver/docs/openapi/kit/sync-openapi.sh
```

Cible par défaut : `./docker/swagger/am-driver/receptacle-v1.yaml`.

### 2. Adapter les serveurs Swagger

Éditer **uniquement** la section `servers` du fichier copié (ou utiliser le menu **Servers** dans Swagger UI) :

- URL = `{origine HTTP de l’app}/{route_prefix}` sans slash final.
- `route_prefix` : `config/packages/am_driver.yaml` (ex. `api/v1/am`, `internal/am`, `am`).

Voir [servers.example.yaml](./servers.example.yaml).

### 3. Ajouter Swagger UI au Docker Compose

Fusionner [docker-compose.snippet.yml](./docker-compose.snippet.yml) dans le `docker-compose.yml` de l’app.

Variables suggérées dans `.env` :

```env
AM_DRIVER_SWAGGER_PORT=11784
```

### 4. Lancer et tester

```bash
docker compose up -d nginx php swagger-ui-am-driver   # noms de services selon votre projet
```

1. Ouvrir `http://127.0.0.1:${AM_DRIVER_SWAGGER_PORT}` (ex. `11784`).
2. Sélectionner le **Server** correspondant à votre app (host + `route_prefix`).
3. **Authorize** : renseigner les jetons (`AM_DRIVER_ORCHESTRATION_COMMAND_TOKEN`, `AM_DRIVER_OPERATIONAL_STATE_TOKEN`, ou alias `X-AM-Application-Token`).
4. S’assurer que l’API (nginx) tourne avant **Try it out**.

### 5. CORS

Swagger UI et l’API sont sur des **ports différents** : le navigateur applique CORS. Si « Failed to fetch » :

- autoriser l’origine Swagger côté API (ex. subscriber CORS en dev), ou
- utiliser la commande **curl** générée par Swagger.

Captain Learning expose déjà `Access-Control-Allow-Origin: *` sur `public/index.php`. Application Manager : `CorsSwaggerDevSubscriber`.

## Spec canonique

- Récepteur : [../receptacle-v1.yaml](../receptacle-v1.yaml) (OpenAPI **3.1.1**, paths suffixes).
- Client AM : [../am-client-v1.yaml](../am-client-v1.yaml) — documenter les appels vers la plateforme via le Swagger **Application Manager**.
