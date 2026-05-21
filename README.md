# application-manager-tools/am-driver

Symfony bundle and framework-agnostic PHP library to integrate **managed applications** with [Application Manager](https://github.com/ApplicationManagerTools) (orchestration commands, consumption webhooks, operational state push).

Specification: [cahier des charges connecteur AM](docs/README.md).  
**OpenAPI 3.1.1** (récepteur + client AM + kit Swagger UI) : [docs/openapi/README.md](docs/openapi/README.md).  
**Manuel d’intégration (intégrateurs humains)** : [docs/MANUEL-INTEGRATION-INTEGRATEURS.md](docs/MANUEL-INTEGRATION-INTEGRATEURS.md).  
Intégration technique : [docs/INTEGRATION.md](docs/INTEGRATION.md). Même app Symfony qu’AM (dogfooding) : [docs/INTEGRATION-SAME-APP.md](docs/INTEGRATION-SAME-APP.md). Écarts AM : [docs/ECARTS-AM.md](docs/ECARTS-AM.md).

## Install

```bash
composer require application-manager-tools/am-driver
```

PHP `^7.4`, `symfony/http-client` and `symfony/console` `^5.4|^6.4|^7.0|^8.0`.

## Développement du bundle (Docker)

Comme le back ApplicationManager : `./bin/php` exécute PHP dans le conteneur Compose (repli sur le PHP hôte si Docker est absent).

```bash
./install          # composer install + répertoires var/
./build            # image PHP (optionnel si BUILD_WHEN_INSTALL=true dans .env.local)
./bin/php -v
./bin/composer install
./bin/phpunit
./codecheck        # php-cs-fixer + phpstan + phpunit
```

Swagger UI (spec récepteur, port `AM_DRIVER_SWAGGER_PORT`, défaut `18098`) :

```bash
docker compose up -d swagger-ui
```

Réceptacle HTTP (port hôte configurable via `RECEPTACLE_PORT` dans `.env`, défaut `18099`) :

```bash
docker compose run --rm -p ${RECEPTACLE_PORT:-18099}:8099 php \
  php bin/am-driver serve --host=0.0.0.0 --port=8099 --data-dir=var/am-driver/receptacle
```

Variables Docker : `.env` (versionné) et `.env.local` (surcharges locales, non versionné).

## Symfony quickstart (≈10 minutes)

1. Register the bundle (Flex discovers it automatically, or):

```php
// config/bundles.php
ApplicationManagerTools\AmDriver\Bridge\Symfony\AmDriverBundle::class => ['all' => true],
```

2. Configure (see `.env.local.dist`):

```yaml
# config/packages/am_driver.yaml
am_driver:
    am_base_url: '%env(AM_DRIVER_AM_BASE_URL)%'
    source: '%env(AM_DRIVER_SOURCE)%'
    route_prefix: am   # → /am/orchestration/commands, /am/instance-operational-state
    consumption_webhook_token: '%env(AM_DRIVER_CONSUMPTION_WEBHOOK_TOKEN)%'
    orchestration_callback_token: '%env(AM_DRIVER_ORCHESTRATION_CALLBACK_TOKEN)%'
    orchestration_command_token: '%env(AM_DRIVER_ORCHESTRATION_COMMAND_TOKEN)%'
    operational_state_token: '%env(AM_DRIVER_OPERATIONAL_STATE_TOKEN)%'
```

3. Import routes:

```yaml
# config/routes/am_driver.yaml
am_driver:
    resource: '@AmDriverBundle/Resources/config/routes.yaml'
```

4. Implement the three handlers (your product logic only):

```php
use ApplicationManagerTools\AmDriver\Core\Contract\CreateInstanceHandlerInterface;
use ApplicationManagerTools\AmDriver\Core\Dto\OrchestrationCommand;

final class MyCreateInstanceHandler implements CreateInstanceHandlerInterface
{
    public function handle(OrchestrationCommand $command): void
    {
        // provision tenant / DB / storage
    }
}
```

Register services with Symfony autoconfigure, or explicit tags in `services.yaml`.

5. Optional: push consumption from your code:

```php
$publisher->pushResourceConsumption($tenantId, 'proof_storage_mo');
```

## `source` values (stable)

| Product | `source` |
|---------|----------|
| Captain Learning | `captain-learning` |
| Accident Prediction | `accident-prediction` |
| Application Manager (self-managed) | `application-manager` |

## Core-only (no bundle)

Use classes under `ApplicationManagerTools\AmDriver\Core\` and wire `AmApiClient`, `OrchestrationCommandProcessor`, `ResourceSnapshotManager` manually.

## CLI (local development)

```bash
vendor/bin/am-driver serve --port=8099
vendor/bin/am-driver orchestration:simulate create
vendor/bin/am-driver state:push-sample
vendor/bin/am-driver consumption:push --tenant-id=... --resource-key=seats --value=12 --am-url=... --token=...
```

### E2E loop (receptacle)

Terminal 1:

```bash
vendor/bin/am-driver serve --port=8099 --token-command=dev-command-token --token-state=dev-state-token
```

Terminal 2:

```bash
vendor/bin/am-driver orchestration:simulate create --token=dev-command-token
vendor/bin/am-driver state:push-sample --token=dev-state-token
```

Data is persisted under `/tmp/am-driver-receptacle` by default (`--data-dir`).

## License

MIT
