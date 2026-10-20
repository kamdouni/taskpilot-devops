# Tâches — Spec 001 (API de gestion des tâches)

> Ordre imposé. Pour chaque tâche : test qui échoue → code → `php bin/phpunit` vert → commit `type(scope): message [T-XX, AC-YY]` → case cochée.

## Phase 0 — Socle

- [x] **T-01** Installer les dépendances : `api`, `orm`, `symfony/uid`, `phpunit`, `dama/doctrine-test-bundle`, `phpstan`, `php-cs-fixer` — *aucun AC*
- [x] **T-02** Configurer `api_platform.yaml` (formats JSON, pagination 20/50, erreurs `problem+json`) et la base de test — *ENF-02*
- [x] **T-03** `GET /health` avec vérification de la base — *AC-01*

## Phase 1 — Création

- [x] **T-04** Entité `Task` + migration + `POST /api/tasks` avec valeurs par défaut — *AC-02*
- [x] **T-05** Contraintes sur `title` (trim, NotBlank, Length 255) — *AC-03, AC-04*
- [x] **T-06** Contraintes `Choice` sur `status` et `priority` — *AC-05*
- [x] **T-07** Contrainte sur `dueDate` (pas dans le passé, seulement si modifiée) — *AC-06*
- [x] **T-08** Groupes de sérialisation : champs générés ignorés en écriture — *AC-07*

## Phase 2 — Lecture

- [x] **T-09** `GET /api/tasks/{id}` et `404` sur UUID inconnu — *AC-08, AC-09*
- [x] **T-10** `GET /api/tasks` trié par `createdAt` décroissant — *AC-10*
- [x] **T-11** Filtre `status` / `priority` — *AC-11*
- [x] **T-12** Pagination `itemsPerPage` plafonnée à 50 — *AC-12*

## Phase 3 — Modification et suppression

- [x] **T-13** `PATCH` merge-patch + `updatedAt` automatique — *AC-13*
- [x] **T-14** Validation au `PATCH`, tâche inchangée en cas d'erreur — *AC-14*
- [ ] **T-15** `DELETE` — *AC-15*

## Phase 4 — Vérification

- [ ] **T-16** Simulateur Postman vert contre l'API locale (`npm test` dans le worktree QA)
- [ ] **T-17** Test de contrat vert (`npm run test:contract`)
- [ ] **T-18** `/verify specs/001-task-api` : matrice de traçabilité 15/15 AC couverts
