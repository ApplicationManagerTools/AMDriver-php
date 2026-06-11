# Changelog

## 0.0.9 — 2026-06-11

### Breaking changes

- **Correlation key** : `tenantId` remplacé par `instanceId` partout dans le bundle (DTO, snapshots, état opérationnel, consommation, espace disque local).
- **`OrchestrationCommand`** : `tenantId` supprimé du corps ; `correlationId` devient optionnel. Clés requises : `operation`, `appId`, `instanceId`, `idempotencyKey`, `occurredAt`.
- **`ConsumptionWebhookEvent`** : champ JSON `tenantId` → `instanceId`.
- **`ManagedInstanceResourceSnapshot`** : champ JSON `tenantId` → `instanceId` ; getter `instanceId()`.
- **Stores / managers** : paramètres et méthodes `findByTenantId()` → `findByInstanceId()` ; `load($instanceId)`, `save($instanceId, …)`, etc.
- **`InstanceOperationalStateValidator`** : exige `instance.instanceId` ; garde-fou `expected_tenant_id` supprimé (utiliser `expected_instance_id`).
- **CLI** : `--tenant-id` → `--instance-id` (`consumption:push`, `orchestration:simulate`).

### Migration

- Renommer les fichiers locaux `snapshots/{tenantId}.json` → `snapshots/{instanceId}.json` (et équivalents operational-state / receipts).
- Mettre à jour les payloads AM et les appels `pushResourceConsumption($instanceId, …)`.

## 0.0.8

Versions antérieures : voir tags Git.
