# Changelog

## 0.0.14 — 2026-06-23

### Breaking

- Configuration : un seul paramètre `application_token` / env `AM_DRIVER_APPLICATION_TOKEN` (les 4 clés legacy restent acceptées en alias de migration).
- HTTP : uniquement `X-AM-Application-Token` (entrée et sortie vers AM).
- Retrait de `instanceIntegrationToken` dans `OrchestrationCommand`.

### Added

- `ApplicationTokenAuthenticator` partagé (contrôleurs Symfony + `ReceptacleHttpKernel`).
