# Changelog

## 0.0.16 — unreleased

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
