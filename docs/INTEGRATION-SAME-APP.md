# Intégration — Application Manager et am-driver dans la même app Symfony

Cas **dogfooding** : l’API Application Manager et le récepteur `am-driver` tournent dans **un seul** projet Symfony (ex. dépôt `ApplicationManager`).

## Principe

- **Côté AM (orchestrateur)** : `ManagedAppIntegration` sur l’agrégat App (voir ADR0002) pointe vers les routes **internes** du même processus.
- **Côté am-driver (application gérée)** : handlers métier locaux + persistance sous `{data_dir}/`.

Boucle typique :

1. AM enregistre une `AppInstance` ; l’intégration sortante est sur l’agrégat **App** (`ManagedAppIntegration`).
2. AM dispatch `CREATE_INSTANCE` → `POST {route_prefix}/orchestration/commands` (même conteneur PHP / nginx ; ex. `route_prefix: internal/am`).
3. Handler hôte crée le contexte tenant + snapshot local ; callback → `POST /api/v1/orchestration/commands/callbacks`.
4. AM push `instance-operational-state.v1` → `POST {route_prefix}/instance-operational-state`.
5. Consommations sortantes : `ConsumptionPublisher` → `POST /api/v1/orchestration/consumption-events`.

## Configuration Docker (réseau interne)

Configurer l’agrégat **App** (API `PUT /api/v1/applications/{appId}/integration` ou `./bin/load-fixtures`) :

- `baseUrl` : `http://application-manager-nginx` (depuis le conteneur PHP) ou `http://127.0.0.1:12180` sur l’hôte ;
- `route_prefix: internal/am` côté bundle (chemins dérivés : `/internal/am/orchestration/commands`, `/internal/am/instance-operational-state`) ;
- jeton application : credential en base, aligné sur `INTEGRATION_APPLICATION_TOKEN` / `config/packages/am_driver.yaml` (`consumption_webhook_token`, `orchestration_callback_token`, récepteur commandes et état).

## Développement hors Docker (hôte)

Remplacer `application-manager-nginx` par `http://127.0.0.1:12180` (ou `URL_API` / `DEFAULT_URI`).

## Sécurité HTTP

- Routes récepteur (`{route_prefix}/*`) : **pas de JWT** ; authentification par en-têtes dédiés du bundle.
- Firewall Symfony hôte : `security: false` sur le préfixe configuré (ex. `^/internal/am` — voir doc projet hôte).
- API manager `/api/v1/*` : JWT inchangé.

## Handlers métier (hôte)

Implémenter et enregistrer (autoconfigure Symfony) :

- `CreateInstanceHandlerInterface`
- `StopInstanceHandlerInterface`
- `StartInstanceHandlerInterface`

Le `OrchestrationCommandProcessor` du bundle envoie les callbacks AM après succès / échec.

## Lecture du snapshot local (API manager)

```php
use ApplicationManagerTools\AmDriver\Core\Snapshot\FileResourceSnapshotStore;

// service injecté par le bundle
$snapshot = $store->findByTenantId($tenantId); // ou load()
```

## Espace disque tenant (handlers STOP/START locaux)

```php
use ApplicationManagerTools\AmDriver\Core\Tenant\FileTenantWorkspace;

$workspace = new FileTenantWorkspace($tenantsBaseDirectory);
$workspace->ensureContext($tenantId);
$workspace->markSuspended($tenantId); // STOP_INSTANCE minimal
$workspace->clearSuspended($tenantId); // START_INSTANCE
```

Lecture du dernier état opérationnel reçu : `OperationalStateStoreInterface::load($tenantId)` (pas d’adaptateur hôte dédié).

## Sonde connectivité (optionnel)

```php
use ApplicationManagerTools\AmDriver\Core\Contract\ConnectivityProbeInterface;

$result = $probe->probeOrchestrationRoute($orchestrationUrl, $commandToken);
// ['status' => 'ok|degraded|failed', 'message' => '...', 'checkedAt' => '...']
```

Service : `ConnectivityProbeInterface` → `HttpOrchestrationConnectivityProbe` (HTTP client Symfony).

## Paramètres Symfony

Le bundle enregistre `am_driver.config` **et** chaque clé en `am_driver.config.<key>` (routes et services du bundle). Aucun fichier `am_driver_parameters.yaml` de contournement n’est nécessaire côté hôte.

## Références

- [INTEGRATION.md](./INTEGRATION.md) — guide général application gérée
- [ECARTS-AM.md](./ECARTS-AM.md) — écarts / limites v1
