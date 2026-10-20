# Plan technique — Spec 001 (API de gestion des tâches)

> Ce plan décrit **comment** satisfaire [`spec.md`](spec.md). Le contrat [`contracts/openapi.yaml`](contracts/openapi.yaml) fait foi en cas de divergence.

## 1. Choix techniques

| Sujet | Choix | Justification |
|---|---|---|
| Framework API | API Platform 4 sur Symfony 8 | CRUD, pagination, filtres et format `problem+json` fournis |
| Formats | `json` uniquement (pas de JSON-LD) | Contrat simple pour le front et le notifier |
| Identifiant | `Uuid` v7 (`symfony/uid`) | Triable dans le temps, non devinable |
| Énumérations | Chaînes + `Assert\Choice` | Garantit une erreur `422` avec violation (AC-05), là où un enum PHP strict renverrait `400` |
| Date d'échéance | `DateTimeImmutable` en type Doctrine `date_immutable` | Pas d'heure, pas de fuseau |
| Horodatage | Callbacks Doctrine `PrePersist` / `PreUpdate` | Simple, sans dépendance supplémentaire |
| Santé | Contrôleur dédié `GET /health` qui exécute `SELECT 1` | AC-01 |

## 2. Fichiers à créer

```
apps/api/
├── config/packages/api_platform.yaml      # formats json, pagination 20/50, ordre par défaut
├── src/Entity/Task.php                    # entité + #[ApiResource] + contraintes
├── src/Controller/HealthController.php    # GET /health
├── src/Serializer/TrimTitleDenormalizer.php (ou setter qui trim)   # RM-01
├── migrations/VersionXXXX.php             # table task
└── tests/
    ├── Api/HealthTest.php                 # AC-01
    ├── Api/TaskCreateTest.php             # AC-02 → AC-07
    ├── Api/TaskReadTest.php               # AC-08 → AC-12
    ├── Api/TaskUpdateTest.php             # AC-13, AC-14
    └── Api/TaskDeleteTest.php             # AC-15
```

## 3. Entité `Task`

- `#[ApiResource(operations: [GetCollection, Get, Post, Patch, Delete], formats: ['json'], order: ['createdAt' => 'DESC'])]`
- Filtre `SearchFilter` sur `status` (exact) et `priority` (exact).
- Groupes de sérialisation : `task:read` (tous les champs), `task:write` (`title`, `description`, `status`, `priority`, `dueDate`) → applique RM-05.
- Contraintes :
  - `title` : `NotBlank`, `Length(max: 255)` ; le setter applique `trim()` (RM-01)
  - `description` : `Length(max: 2000)`
  - `status` : `Choice(['todo','in_progress','done'])`
  - `priority` : `Choice(['low','medium','high'])`
  - `dueDate` : `GreaterThanOrEqual('today')`, uniquement si la valeur change (RM-01 de la spec, AC-06)

## 4. Configuration API Platform

```yaml
api_platform:
  formats:
    json: ['application/json']
  patch_formats:
    json: ['application/merge-patch+json']
  error_formats:
    jsonproblem: ['application/problem+json']
  defaults:
    pagination_items_per_page: 20
    pagination_maximum_items_per_page: 50
    pagination_client_items_per_page: true
```

## 5. Stratégie de test

- **PHPUnit** (`ApiTestCase`) : un test par critère, nommé `testAC02CreatesTaskWithDefaults`, base PostgreSQL de test réinitialisée par `dama/doctrine-test-bundle`.
- **Postman / Newman** (worktree QA) : une requête par critère, nommée `AC-02 …`, jouée contre l'API réelle et contre le mock du contrat.
- **Contrat** : `npm run test:contract` fait passer Newman par un proxy Prism qui valide chaque réponse réelle contre `openapi.yaml`.

## 6. Risques

| Risque | Mitigation |
|---|---|
| `dueDate` passée refusée lors d'un simple changement de statut d'une ancienne tâche | Valider `dueDate` uniquement si elle est modifiée (groupe de validation conditionnel) |
| Divergence entre le code et `openapi.yaml` | Test de contrat Prism en CI (niveau 2) |
