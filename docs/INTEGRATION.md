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
| `data_dir` | — | Répertoire snapshots / idempotence / état opérationnel |

## Routes exposées (défaut)

| Route | Méthode | En-tête |
|-------|---------|---------|
| `/internal/am/orchestration/commands` | POST | `X-Orchestration-Command-Token` |
| `/internal/am/instance-operational-state` | POST | `X-Instance-Operational-State-Token` |

Corps commande : `operation`, `targetId`, `appId`, `instanceId`, `tenantId`, `correlationId`, `idempotencyKey`, `occurredAt`.

## Fichiers locaux (par `tenantId`)

Sous `{data_dir}/` :

- `snapshots/{tenantId}.json` — `managed-instance-resource-snapshot.v1`
- `operational-state/{tenantId}-operational-state.json` — dernier `instance-operational-state.v1`
- `idempotency/` — clés commandes déjà traitées
- `operational-state-receipts/` — dedup `correlationId` + `computedAt`

## Paramètres Symfony (`am_driver.config.*`)

Le bundle enregistre le tableau `am_driver.config` **et**, pour chaque clé de `config/packages/am_driver.yaml`, un paramètre aplati `am_driver.config.<key>` (ex. `am_driver.config.orchestration_commands_path`). Les routes et services du bundle s’appuient sur ces paramètres.

## API applicative

```php
$publisher->pushResourceConsumption($tenantId, 'seats');
$publisher->flushPendingToAm($tenantId);
$snapshotManager->recordMeasurement($tenantId, 'seats', 12);
$stored = $resourceSnapshotStore->findByTenantId($tenantId); // lecture externe (alias de load)
```

## Même application Symfony qu’Application Manager

Voir [INTEGRATION-SAME-APP.md](./INTEGRATION-SAME-APP.md) (dogfooding, boucle Docker `application-manager-nginx`, handlers locaux).

Consommation : un POST AM par `resourceKey` ; `lastPushedToAm` mis à jour seulement sur **HTTP 202**.

Callbacks : envoyés après issue handler (succès → `SUCCEEDED`, échec métier → `FAILED` / `RETRYABLE_FAILURE`).

Voir [ECARTS-AM.md](./ECARTS-AM.md).
