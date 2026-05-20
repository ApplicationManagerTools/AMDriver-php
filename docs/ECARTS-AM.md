# Écarts assumés et questions ouvertes (bundle am-driver v1)

Alignement sur le **back AM réel** (`ApplicationManager/ApplicationManager/`) et OpenAPI, pas sur l’exemple JSON long de `spec-instance-operational-state-push-v1.md` seul.

## Comportement AM actuel vs bundle

| Sujet | AM (référence) | Bundle v1 |
|-------|----------------|-----------|
| `CREATE_INSTANCE` sans `tenantSlug` / `tenantDisplayName` | `SendOrchestrationCommandHttpGateway` n’envoie pas ces champs | Handlers reçoivent `OrchestrationCommand` tel quel ; dérivation locale documentée côté produit hôte |
| `START_INSTANCE` | `POST /api/v1/instances/{id}/start` + commande `…:start_instance:v1` | Handler + route unique sur `operation` (OK) |
| Push état opérationnel | `ManagedAppIntegration` sur l’agrégat App (voir ADR0002) : `operationalStateUrl`, `operationalStateToken` | Route configurable + en-tête `X-Instance-Operational-State-Token` |
| `payment_modalities` | Absent du builder domaine | Non exigé ; champs inconnus dans `resources[]` ignorés |
| Réactions domaine | `block_resource`, `stop_instance`, … | Parsing tolérant (pas de validation stricte des hints) |
| Nombres | `limit` / `consumption.value` souvent en **float** dans le JSON AM | Acceptés string ou number |
| `DESTROY_INSTANCE` | Émis par AM | **Non implémenté** v1 : HTTP 400 + callback `FAILED` (message explicite) |
| Route commandes | Une URL par `targetId` dans `ManagedAppIntegration` sur l’agrégat App (voir ADR0002) | **Une route** POST routée par `operation` (variante cahier § 5) |
| Paramètres Symfony `am_driver.config.<key>` | Requis par routes/services bundle | Enregistrés par `ConfigurationParameters` à l’activation du bundle |
| Lecture snapshot externe | — | `ResourceSnapshotStoreInterface::findByTenantId()` (= `load()`) |
| Sonde route récepteur | — | `ConnectivityProbeInterface` + `HttpOrchestrationConnectivityProbe` (optionnel) |

## Politiques produit (à documenter par l’app hôte)

| Situation | Défaut bundle | Callback |
|-----------|---------------|----------|
| `idempotencyKey` déjà traitée | HTTP 200, pas de rappel handler, **pas** de second callback | — |
| `START_INSTANCE` instance déjà active | Délégué au handler ; lever `HandlerFailedException` ou succès idempotent | Selon handler |
| Instance inconnue | `HandlerFailedException::failed()` ou `ValidationException` | `FAILED` |
| Erreur transitoire | Exception non métier → `RETRYABLE_FAILURE` | puis `FAILED` côté AM si épuisement |

## Questions ouvertes (équipe AM / produit)

1. **Évolution AM** : ajouter `tenantSlug` / `tenantDisplayName` dans le JSON `CREATE_INSTANCE` ?
2. **Routes** : une URL unique vs trois chemins distincts pour CREATE / STOP / START ?
3. **`DESTROY_INSTANCE`** : ignorer v1 (actuel) ou handler dédié v1.1 ?
4. **`START_INSTANCE` déjà active** : 2xx idempotent + `SUCCEEDED` ou 409 ?
