# Intégration — Application Manager et am-driver dans la même app Symfony

Cas **dogfooding** : l’API Application Manager et le récepteur `am-driver` tournent dans **un seul** projet Symfony (ex. dépôt `ApplicationManager`).

## Principe

- **Côté AM (orchestrateur)** : `ORCHESTRATION_TARGETS_JSON` pointe vers les routes **internes** du même processus.
- **Côté am-driver (application gérée)** : handlers métier locaux + persistance sous `{data_dir}/`.

Boucle typique :

1. AM enregistre une `AppInstance` avec `targetId` (ex. `application-manager-self`).
2. AM dispatch `CREATE_INSTANCE` → `POST /internal/am/orchestration/commands` (même conteneur PHP / nginx).
3. Handler hôte crée le contexte tenant + snapshot local ; callback → `POST /api/v1/orchestration/commands/callbacks`.
4. AM push `instance-operational-state.v1` → `POST /internal/am/instance-operational-state`.
5. Consommations sortantes : `ConsumptionPublisher` → `POST /api/v1/orchestration/consumption-events`.

## Configuration Docker (réseau interne)

Dans `ORCHESTRATION_TARGETS_JSON` (côté AM) :

```json
{
  "application-manager-self": {
    "url": "http://application-manager-nginx/internal/am/orchestration/commands",
    "token": "<AM_DRIVER_ORCHESTRATION_COMMAND_TOKEN>",
    "operationalStateUrl": "http://application-manager-nginx/internal/am/instance-operational-state",
    "operationalStateToken": "<AM_DRIVER_OPERATIONAL_STATE_TOKEN>"
  }
}
```

Les jetons doivent être **identiques** entre :

- `AM_DRIVER_ORCHESTRATION_COMMAND_TOKEN` / `AM_DRIVER_OPERATIONAL_STATE_TOKEN` (bundle), et
- `token` / `operationalStateToken` dans le JSON ci-dessus.

Réutiliser aussi `WEBHOOK_CONSUMPTION_TOKEN` et `ORCHESTRATION_CALLBACK_TOKEN` pour les clients HTTP sortants du bundle (`consumption_webhook_token`, `orchestration_callback_token`).

## Développement hors Docker (hôte)

Remplacer `application-manager-nginx` par `http://127.0.0.1:12180` (ou `URL_API` / `DEFAULT_URI`).

## Sécurité HTTP

- Routes `/internal/am/*` : **pas de JWT** ; authentification par en-têtes dédiés du bundle.
- Firewall Symfony hôte : `security: false` sur `^/internal/am` (voir doc projet hôte).
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
