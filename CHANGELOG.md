# Changelog

## 0.0.15 — 2026-06-29

### Breaking

- Réponses HTTP du récepteur (contrôleurs Symfony et `bin/am-driver serve`) : enveloppe JSON standard
  `success`, `data`, `error`, `error_message` (ex. `data.accepted` au lieu de `accepted` à la racine).

### Added

- Couche `Application/Service` avec pattern Presenter (`PresenterInterface`, `*ServiceRequest`, `*ServiceResponse`).
- `ResponseData`, `ResponseEncoder`, `AbstractController` et handlers HTTP partagés Symfony / CLI.

## 0.0.14 — 2026-06-23

### Breaking

- Configuration : un seul paramètre `application_token` / env `AM_DRIVER_APPLICATION_TOKEN` (les 4 clés legacy restent acceptées en alias de migration).
- HTTP : uniquement `X-AM-Application-Token` (entrée et sortie vers AM).
- Retrait de `instanceIntegrationToken` dans `OrchestrationCommand`.

### Added

- `ApplicationTokenAuthenticator` partagé (contrôleurs Symfony + `ReceptacleHttpKernel`).
