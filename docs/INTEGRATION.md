# Guide d’intégration — am-driver (application gérée)

## Rôle

- **AM** : orchestrateur (commandes sortantes, push état opérationnel, réception consommation + callbacks).
- **Ce bundle** : récepteur HTTP entrant, persistance locale, clients HTTP sortants vers AM.
- **Votre app** : handlers métier (`CreateInstanceHandler`, etc.) + politique « quand pousser » la consommation.

## Configuration (produit hôte)

### Côté Application Manager (`ManagedAppIntegration` sur l’agrégat App (voir ADR0002))

Exemple aligné sur `ApplicationManager/.env.local.dist` :

```json
{
  "captain-learning-prod-eu1": {
    "url": "https://<cible>/internal/am/orchestration/commands",
    "token": "<secret commandes>",
    "operationalStateUrl": "https://<cible>/internal/am/instance-operational-state",
    "operationalStateToken": "<secret état opérationnel>"
  }
}
```

### Côté bundle Symfony (`config/packages/am_driver.yaml`)

| Paramètre bundle | Variable env suggérée | Équivalent AM |
|------------------|----------------------|---------------|
| `am_base_url` | `AM_DRIVER_AM_BASE_URL` ou `URL_API` | Base API AM |
| `consumption_webhook_token` | `AM_DRIVER_CONSUMPTION_WEBHOOK_TOKEN` | `WEBHOOK_CONSUMPTION_TOKEN` |
| `orchestration_callback_token` | `AM_DRIVER_ORCHESTRATION_CALLBACK_TOKEN` | `ORCHESTRATION_CALLBACK_TOKEN` |
| `orchestration_command_token` | `AM_DRIVER_ORCHESTRATION_COMMAND_TOKEN` | token dans `ManagedAppIntegration` sur l’agrégat App (voir ADR0002) |
| `operational_state_token` | `AM_DRIVER_OPERATIONAL_STATE_TOKEN` | `operationalStateToken` |
| `source` | `AM_DRIVER_SOURCE` | `captain-learning`, `accident-prediction`, `application-manager` |
| `expected_tenant_id` | `AM_DRIVER_EXPECTED_TENANT_ID` | (optionnel) garde-fou mono-tenant |
| `expected_instance_id` | `AM_DRIVER_EXPECTED_INSTANCE_ID` | (optionnel) |
| `create_instance_execution` | — | `sync` (défaut) ou `deferred` pour CREATE_INSTANCE |
| `data_dir` | — | Répertoire snapshots / idempotence / état opérationnel |

## Routes exposées (défaut)

Le préfixe est configurable via `route_prefix` (défaut : `am`). Les chemins complets sont dérivés automatiquement :

| Suffixe | Chemin par défaut (`route_prefix: am`) |
|---------|----------------------------------------|
| `/orchestration/commands` | `/am/orchestration/commands` |
| `/instance-operational-state` | `/am/instance-operational-state` |

Exemple pour conserver l’ancien préfixe multi-segments :

```yaml
am_driver:
    route_prefix: internal/am
```

Les clés `orchestration_commands_path` et `operational_state_path` restent disponibles pour surcharger le chemin complet.

| Route (défaut) | Méthode | En-tête |
|----------------|---------|---------|
| `/am/orchestration/commands` | POST | `X-Orchestration-Command-Token` |
| `/am/instance-operational-state` | POST | `X-Instance-Operational-State-Token` |

Contrat détaillé (OpenAPI 3.1.1) : [openapi/receptacle-v1.yaml](./openapi/receptacle-v1.yaml). Appels sortants : [openapi/am-client-v1.yaml](./openapi/am-client-v1.yaml). **Swagger UI (Try it out)** : [openapi/kit/README.md](./openapi/kit/README.md).

Corps commande : `operation`, `appId`, `instanceId`, `tenantId`, `correlationId`, `idempotencyKey`, `occurredAt` (optionnel : `instanceIntegrationToken`).

## Fichiers locaux (par `tenantId`)

Sous `{data_dir}/` :

- `snapshots/{tenantId}.json` — `managed-instance-resource-snapshot.v1`
- `operational-state/{tenantId}-operational-state.json` — dernier `instance-operational-state.v1`
- `idempotency/` — clés commandes déjà traitées
- `idempotency-in-progress/` — commandes CREATE_INSTANCE en cours (mode `deferred`)
- `operational-state-receipts/` — dedup `correlationId` + `computedAt`

## Paramètres Symfony (`am_driver.config.*`)

Le bundle enregistre le tableau `am_driver.config` **et**, pour chaque clé de `config/packages/am_driver.yaml`, un paramètre aplati `am_driver.config.<key>` (ex. `am_driver.config.orchestration_commands_path`). Les routes et services du bundle s’appuient sur ces paramètres.

## API applicative

```php
$publisher->pushResourceConsumption($tenantId, 'seats');
$publisher->flushPendingToAm($tenantId);
$snapshotManager->recordMeasurement($tenantId, 'seats', 12);
$stored = $resourceSnapshotStore->findByInstanceId($instanceId); // lecture externe (alias de load)
```

## Même application Symfony qu’Application Manager

Voir [INTEGRATION-SAME-APP.md](./INTEGRATION-SAME-APP.md) (dogfooding, boucle Docker `application-manager-nginx`, handlers locaux).

Consommation : un POST AM par `resourceKey` ; `lastPushedToAm` mis à jour seulement sur **HTTP 202**.

Callbacks : envoyés après issue handler (succès → `SUCCEEDED`, échec métier → `FAILED` / `RETRYABLE_FAILURE`).

Contrat du résultat `CreateInstanceHandlerInterface::handle()` (`CreateInstanceHandlerResult::fromArray()`, depuis 0.0.16) :
`startedAt` (horodatage non vide) et `integrationInstanceId` (identifiant non vide côté app hôte) sont requis et connus
du bundle ; toute autre clé fournie par l'hôte (`location`, ...) est relayée telle quelle dans le JSON du callback,
sans validation ni interprétation par le bundle. Voir [README.md](../README.md) (section « Symfony quickstart ») pour
l'exemple de handler.

### CREATE_INSTANCE en mode `deferred`

```yaml
am_driver:
    create_instance_execution: deferred
```

1. HTTP `POST /orchestration/commands` → **200** `{ "accepted": true }` sans attendre le provisionnement.
2. L’hôte implémente `DeferredCreateInstanceDispatcherInterface` (ex. subprocess vers `am-driver:execute-deferred-create-instance`).
3. Le worker appelle `DeferredCreateInstanceWorker` → handler métier → callback AM avec `location`.

Override Symfony :

```yaml
ApplicationManagerTools\AmDriver\Core\Contract\DeferredCreateInstanceDispatcherInterface:
    class: App\Infrastructure\Am\SubprocessDeferredCreateInstanceDispatcher
```

Voir [ECARTS-AM.md](./ECARTS-AM.md).
