# Manuel d’intégration — intégrateurs humains (am-driver / Application Manager)

Ce document s’adresse aux **équipes qui intègrent une application métier** avec [Application Manager](https://github.com/ApplicationManagerTools) (AM) via le bundle PHP **`application-manager-tools/am-driver`**. Il pose le **cadre métier** et l’**enchaînement des travaux** ; le détail technique des routes, jetons et services Symfony est dans [INTEGRATION.md](./INTEGRATION.md) et les spécifications dans [README.md](./README.md).

---

## 1. À quoi sert cette intégration ?

### 1.1 Objectifs du développement

AM est la **plateforme d’orchestration et de facturation** des instances de vos applications catalogue : abonnements, plafonds de ressources, réactions (blocage, surfacturation, etc.) et visibilité opérationnelle. Votre application reste la **source de vérité de la mesure métier** (stockage réellement utilisé, appels API effectués, jetons consommés, etc.).

L’intégration vise à :

- **aligner** le catalogue AM (clés de ressources, unités, formules) avec ce que votre produit sait réellement mesurer ;
- **recevoir** depuis AM le **document d’état opérationnel** versionné (`instance-operational-state.v1`) pour chaque instance ;
- **répondre** aux **commandes d’orchestration** (`CREATE_INSTANCE`, `STOP_INSTANCE`, `START_INSTANCE`) émises par AM ;
- **pousser** vers AM les **mesures de consommation** au bon moment, avec un identifiant produit stable (`source`) ;
- **confirmer** à AM le succès ou l’échec des commandes via les **callbacks** HTTP prévus par AM.

Sans ce lien, AM ne peut ni provisionner correctement vos tenants, ni appliquer les règles contractuelles sur des mesures fiables.

### 1.2 Relation entre votre application et Application Manager

| Rôle | Application Manager (AM) | Votre application (gérée) |
|------|---------------------------|---------------------------|
| Catalogue | Définit applications, ressources, formules, instances | Implémente le métier correspondant |
| Orchestration | Envoie les commandes vers une **cible** (`targetId`) configurée | Expose une URL de réception ; exécute create / stop / start |
| Plafonds & politiques | Décide des réactions (blocage, surfacturation, etc.) | Mesure la consommation réelle et la notifie |
| État opérationnel | Construit et **pousse** le JSON d’état vers votre URL | Reçoit, valide, persiste ; met à jour la corrélation locale (snapshot) |
| Jetons / sécurité | `ManagedAppIntegration` sur l’agrégat App (voir ADR0002), webhooks sortants | Mêmes secrets côté bundle `am_driver` (en-têtes dédiés) |

Le bundle **am-driver** dans votre code Symfony fournit les **routes réceptrices**, la **persistance locale** (snapshots, idempotence, dernier état opérationnel) et les **clients HTTP** vers l’API AM — votre équipe fournit surtout les **handlers métier** et la **politique d’envoi** des consommations.

---

## 2. Travail de l’intégrateur (vue d’ensemble)

### 2.1 Choisir les ressources « calculables » par votre application

Pour chaque ressource facturable ou contrôlable par AM, il faut :

- une **clé stable** (`resourceKey`) partagée entre AM et votre code (ex. `proof_storage_mo`) ;
- une **unité** et une **description** compréhensibles côté manager ;
- la **capacité technique** de votre produit à **mesurer** ou **agréger** cette valeur de façon déterministe.

Ces clés sont enregistrées dans le **catalogue AM** (création d’application / définitions de ressources). Toute mesure poussée vers AM doit utiliser **exactement** ces clés.

### 2.2 Développer le suivi local et le statut des ressources

Côté application gérée vous devez :

- maintenir un **état local des ressources** (schéma `managed-instance-resource-snapshot.v1` : mesure courante, horodatage, corrélation avec le dernier état AM reçu, trace des derniers envois réussis vers AM) ;
- brancher votre log métier pour **mettre à jour** ces mesures (cron, fin de requête, job, etc.) ;
- utiliser l’API du bundle (`ConsumptionPublisher`, `ResourceSnapshotManager`, etc., voir [INTEGRATION.md](./INTEGRATION.md)) pour **enregistrer** et **pousser** les mesures vers `POST /api/v1/orchestration/consumption-events` avec le champ `source` attendu par AM (ex. `captain-learning`).

Le statut « côté produit » est donc : **mesure à jour** + **cohérence** avec ce qu’AM croit via le dernier état opérationnel reçu.

### 2.3 Recevoir l’état d’application sur votre propre point d’entrée

Vous exposez (directement ou via le bundle) une route **HTTPS** compatible avec le **push** AM :

- **POST** vers l’URL configurée dans AM (`operationalStateUrl` pour votre `targetId`) ;
- corps JSON `instance-operational-state.v1` ;
- authentification alignée sur `operationalStateToken` / en-tête attendu par le bundle.

Traitement minimal : valider schéma / identité tenant, **persister** le document, mettre à jour la section `lastInboundOperationalState` du snapshot local, répondre **2xx** (voir cahier des charges connecteur, § 2).

### 2.4 Rattacher l’application dans Application Manager

Côté AM (exploitation ou back-office selon votre processus) :

1. **Application catalogue** : créer ou mettre à jour l’application avec les **définitions de ressources** et les **formules d’abonnement** qui référencent ces clés.
2. **Cible d’orchestration** : ajouter une entrée dans `ManagedAppIntegration` sur l’agrégat App (voir ADR0002) avec au minimum `url`, `token`, `operationalStateUrl`, `operationalStateToken` pointant vers **votre** base URL et les mêmes secrets que dans `config/packages/am_driver.yaml`.
3. **Instance** : lors de la création / rattachement d’instance, renseigner le **`targetId`** correspondant à cette entrée pour que les commandes et le push d’état atteignent votre environnement.

Sans `targetId` cohérent et JSON de cibles complet, AM ne dispatche pas les commandes et ne peut pas pousser l’état opérationnel (voir messages d’erreur de connectivité dans la doc AM).

---

## 3. Illustration : Captain Learning (données de démo AM)

Le dépôt **Application Manager** propose une commande de fixtures qui matérialise une application fictive **« Captain Learning »**, utile comme **exemple de catalogue** aligné avec les tests et l’OpenAPI.

- **Commande** : `./bin/load-fixtures-demo` (après `./bin/load-fixtures` pour créer le manager de démo). Équivalent Symfony : `am:fixtures:load-demo`.
- **Fichier source** (détail métier des ressources et formules) : `ApplicationManager/src/Infrastructure/Console/LoadDemoFixturesConsoleCommand.php`.

### 3.1 Ressources définies pour Captain Learning

| `resourceKey` | Description (catalogue démo) | Unité |
|---------------|------------------------------|--------|
| `proof_storage_mo` | Quota de stockage des preuves (Qualiopi), en mébioctets | Mo |
| `third_party_api_calls` | Nombre d’appels API vers des logiciels tiers (période de facturation) | appels |
| `conversational_ai_tokens` | Nombre de jetons d’usage de l’IA conversationnelle (période de facturation) | jetons |

Une **application réelle** nommée Captain Learning devrait pouvoir **calculer** ces trois agrégats (ou un sous-ensemble effectivement vendu) et utiliser **les mêmes clés** dans le webhook de consommation.

### 3.2 Formules d’abonnement (démo)

Toujours dans la même commande : deux formules — **« Embarquement Qualiopi »** (gratuit, plafonds serrés, réaction blocage) et **« Navigation Qualiopi »** (payant mensuel, plafonds plus larges, dont surfacturation à paliers sur le stockage preuves). Cela montre comment **une même clé** (`proof_storage_mo`) peut avoir des **politiques différentes** selon la formule : votre intégration ne change pas ces règles dans AM, mais doit **pousser des mesures cohérentes** avec les unités attendues.

### 3.3 Exemple de câblage « Captain Learning » côté AM

Dans `ApplicationManager/.env.local.dist`, l’exemple de cible externe utilise un `targetId` du type `captain-learning-prod-eu1` et des URL distinctes pour les commandes et pour l’état opérationnel. Les chemins exacts peuvent varier selon votre routage ; l’important est l’**alignement des jetons** entre AM et votre configuration `am_driver`.

En résumé pour un intégrateur **Captain Learning** :

1. **Catalogue** : trois `resourceKey` ci-dessus + formules négociées avec le métier AM.
2. **`source` produit** : utiliser la valeur stable `captain-learning` (voir tableau du README du bundle).
3. **Implémentation** : bundle + handlers + mise à jour du snapshot + push consommation ; route POST pour l’état opérationnel.
4. **Rattachement AM** : entrée `captain-learning-prod-eu1` (ou autre id stable) dans `ManagedAppIntegration` sur l’agrégat App (voir ADR0002) + instances créées avec ce `targetId`.

---

## 4. Poursuivre la lecture

- [INTEGRATION.md](./INTEGRATION.md) — paramètres, routes, fichiers locaux, API PHP.
- [INTEGRATION-SAME-APP.md](./INTEGRATION-SAME-APP.md) — cas AM et connecteur dans le **même** Symfony (dogfooding).
- [ECARTS-AM.md](./ECARTS-AM.md) — comportements v1 et limites connues.
- Spécifications amont : [README.md](./README.md) (liens vers le dépôt agents / specs officielles).
