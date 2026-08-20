# Changelog

## 0.0.23 — unreleased

### Breaking

- `AmApiClientInterface::createSubscriptionUpgradeSession(instanceId, returnUrl, targetFormulaId)` —
  corps JSON `{ "returnUrl": "…", "targetFormulaId": "am_sfm_…" }` vers
  `POST /api/v1/instances/{id}/billing/upgrade-session` (formule cible fixée côté app gérée).

## 0.0.22 — unreleased

### Breaking

- Nouvelle opération d’orchestration `UPGRADE_INSTANCE` (async, idempotence + callback comme CREATE/STOP).
- Interface hôte `UpgradeInstanceHandlerInterface` et `DeferredUpgradeInstanceDispatcherInterface`.
- `OrchestrationCommandProcessor` exige `UpgradeInstanceHandlerInterface` en 7ᵉ argument constructeur
  (après `SetStateViewInstanceHandlerInterface`) et `DeferredUpgradeInstanceDispatcherInterface` après
  `DeferredCreateInstanceDispatcherInterface`.
- Configuration Symfony : `upgrade_instance_execution` (`sync` ou `deferred`, défaut `deferred`).

### Migration

```yaml
ApplicationManagerTools\AmDriver\Core\Contract\UpgradeInstanceHandlerInterface:
    alias: App\Infrastructure\Am\UpgradeInstanceHandler

ApplicationManagerTools\AmDriver\Core\Contract\DeferredUpgradeInstanceDispatcherInterface:
    alias: App\Infrastructure\Am\SubprocessDeferredUpgradeInstanceDispatcher

# config/packages/am_driver.yaml
am_driver:
    upgrade_instance_execution: deferred
```

## 0.0.21 — unreleased

### Added

- `AmApiClientInterface::createSubscriptionResubscribeSession(instanceId, returnUrl)` —
  `POST /api/v1/instances/{id}/billing/resubscribe-session` (réabonnement Navigation / Checkout).
- OpenAPI client : route `resubscribe-session` documentée.

## 0.0.20 — unreleased

### Added

- `AmApiClientInterface::createSubscriptionUpgradeSession(instanceId, returnUrl)` —
  `POST /api/v1/instances/{id}/billing/upgrade-session` (upgrade Embarquement → Navigation / Portail).
- OpenAPI client : route `upgrade-session` documentée.

## 0.0.19 — unreleased

### Breaking

- Nouvelle opération d’orchestration `QUOTA_EXCEEDED` et interface hôte
  `QuotaExceededInstanceHandlerInterface` (idempotence + callback comme START/STOP).
- `OrchestrationCommandProcessor` exige ce handler en 4ᵉ argument constructeur
  (après `StartInstanceHandlerInterface`).

### Migration

```yaml
ApplicationManagerTools\AmDriver\Core\Contract\QuotaExceededInstanceHandlerInterface:
    alias: App\Infrastructure\Am\QuotaExceededInstanceHandler
```

## 0.0.17 — unreleased

### Breaking

- `GET_INFO_INSTANCE` / `SET_STATEVIEW_INSTANCE` ne sont plus gérés en interne (fichier + `queryParams`) :
  le processor délègue à `GetInfoInstanceHandlerInterface` / `SetStateViewInstanceHandlerInterface` (comme CREATE / START / STOP).
- `OrchestrationCommandProcessor::process()` ne prend plus de `$queryParams` ; le constructeur exige les deux handlers
  (plus d’arguments optionnels reader/writer/resolver).
- L’hôte doit aliaser les deux interfaces dans sa config Symfony. Helpers optionnels inchangés :
  `FileActualResourcesConsumptionReader`, `FileStateViewWriter`, `FileTenantWorkspace`.

### Migration

```yaml
ApplicationManagerTools\AmDriver\Core\Contract\GetInfoInstanceHandlerInterface:
    alias: App\Infrastructure\Am\GetInfoInstanceHandler

ApplicationManagerTools\AmDriver\Core\Contract\SetStateViewInstanceHandlerInterface:
    alias: App\Infrastructure\Am\SetStateViewInstanceHandler
```

## 0.0.16 — 2026-07-03

### Breaking

- `CreateInstanceHandlerResult` devient un DTO hybride : construit via `CreateInstanceHandlerResult::fromArray(array $data)`
  au lieu de `new CreateInstanceHandlerResult(?string $instanceLocation, ?string $startedAt)`. Les clés `startedAt` et
  `integrationInstanceId` (toutes deux non vides) restent obligatoires et validées par le bundle ; toute autre clé
  (`location`, ...) est libre et relayée telle quelle jusqu'au callback AM. Les accesseurs `instanceLocation()`
  disparaissent au profit de `toArray()` (`startedAt()` et `integrationInstanceId()` sont conservés, car champs
  garantis).
- `OrchestrationCallbackRequest` : le constructeur perd ses paramètres nommés `?string $location`/`?string $startedAt`
  au profit d'un tableau `array $extra = []` (4ᵉ paramètre). Les accesseurs `location()`/`startedAt()` disparaissent au
  profit de `extra(): array`. `fromArray()` ne valide plus `location` comme une URI stricte.
- `OrchestrationCommandProcessor::assertCreateInstanceStartedAt()` est retiré ; la validation de `startedAt` vit
  désormais dans `CreateInstanceHandlerResult::fromArray()`.

### Migration

Avant :

```php
return new CreateInstanceHandlerResult('https://tenant.example/login', $startedAt);
```

Après :

```php
return CreateInstanceHandlerResult::fromArray([
    'startedAt' => $startedAt,
    'integrationInstanceId' => $integrationInstanceId,
    'location' => 'https://tenant.example/login',
    // toute autre clé métier est acceptée sans changement bundle
]);
```

## 0.0.14 — 2026-06-23

### Breaking

- Configuration : un seul paramètre `application_token` / env `AM_DRIVER_APPLICATION_TOKEN` (les 4 clés legacy restent acceptées en alias de migration).
- HTTP : uniquement `X-AM-Application-Token` (entrée et sortie vers AM).
- Retrait de `instanceIntegrationToken` dans `OrchestrationCommand`.

### Added

- `ApplicationTokenAuthenticator` partagé (contrôleurs Symfony + `ReceptacleHttpKernel`).
