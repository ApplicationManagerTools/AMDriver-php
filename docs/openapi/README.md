# OpenAPI — am-driver (3.1.1)

| Fichier | Rôle |
|---------|------|
| [receptacle-v1.yaml](./receptacle-v1.yaml) | Routes **entrantes** (suffixes `/orchestration/commands`, `/instance-operational-state`) |
| [am-client-v1.yaml](./am-client-v1.yaml) | Appels **sortants** vers l’API AM (consommation, callbacks) |
| [kit/](./kit/) | **Swagger UI** : sync vers l’app gérée, fragment Docker, guide Try it out |

## Swagger UI (bundle)

```bash
docker compose up -d swagger-ui
# http://127.0.0.1:18098 (AM_DRIVER_SWAGGER_PORT)
```

Choisir le **Server** adapté (variables `host` / `routePrefix`, ou réceptacle CLI `18099/am`).

## Application gérée (Captain Learning, etc.)

Voir [kit/README.md](./kit/README.md) : `sync-openapi.sh` + service `swagger-ui-am-driver`.

## Autres outils

```bash
npx --yes @redocly/cli preview-docs docs/openapi/receptacle-v1.yaml
```

## Plateforme AM

`ApplicationManager/docker/swagger/v1/openapi.yaml` — webhooks, callbacks, API manager.
