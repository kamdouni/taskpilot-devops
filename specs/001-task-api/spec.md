# Spec 001 — API de gestion des tâches

| Champ | Valeur |
|---|---|
| Statut | ✅ Validée |
| Auteur | Karim Amdouni |
| Contrat | [`contracts/openapi.yaml`](contracts/openapi.yaml) |
| Plan | [`plan.md`](plan.md) |

## 1. Contexte et objectif

TaskPilot a besoin d'une API REST pour gérer des tâches. Elle sera consommée par le front React, par le service `notifier`, puis par l'agent IA (niveau 7). Cette première version ne gère **pas** l'authentification : toutes les tâches sont publiques.

## 2. Hors périmètre

- Authentification et utilisateurs (spec future)
- Événements vers Redis / notifier (spec 002)
- Fonctionnalités IA (spec 007)

## 3. Modèle métier : une tâche

| Attribut | Type | Obligatoire | Règles |
|---|---|---|---|
| `id` | UUID v7 | généré | Non modifiable |
| `title` | texte | oui | 1 à 255 caractères après suppression des espaces en début et fin |
| `description` | texte | non | 2000 caractères maximum, `null` par défaut |
| `status` | énumération | non | `todo`, `in_progress` ou `done`. Défaut : `todo` |
| `priority` | énumération | non | `low`, `medium` ou `high`. Défaut : `medium` |
| `dueDate` | date (`YYYY-MM-DD`) | non | Ne peut pas être dans le passé au moment où elle est définie |
| `createdAt` | date-heure ISO 8601 | généré | Non modifiable |
| `updatedAt` | date-heure ISO 8601 | généré | Mis à jour à chaque modification |

## 4. Règles métier

- **RM-01** : le titre est nettoyé (`trim`) avant validation. Un titre composé uniquement d'espaces est vide.
- **RM-02** : une tâche `done` peut être rouverte (`todo` ou `in_progress`). Aucune transition n'est interdite.
- **RM-03** : la liste est triée par défaut par `createdAt` décroissant (les plus récentes d'abord).
- **RM-04** : la liste est paginée : 20 éléments par page par défaut, 50 au maximum.
- **RM-05** : les attributs générés (`id`, `createdAt`, `updatedAt`) envoyés par le client sont ignorés.

## 5. Critères d'acceptation

Chaque critère est couvert par un test PHPUnit **et** une requête Postman dont le nom commence par son identifiant.

### Santé
- **AC-01** : *Étant donné* l'API démarrée et la base accessible, *quand* j'appelle `GET /health`, *alors* je reçois `200` avec `{"status":"ok","database":"ok"}`.

### Création
- **AC-02** : *Quand* j'envoie `POST /api/tasks` avec un titre valide, *alors* je reçois `201`, la tâche avec un `id`, `status = todo`, `priority = medium`, `createdAt` et `updatedAt` renseignés.
- **AC-03** : *Quand* j'envoie un titre absent, vide ou composé d'espaces, *alors* je reçois `422` avec une violation sur `title`.
- **AC-04** : *Quand* j'envoie un titre de 256 caractères, *alors* je reçois `422` avec une violation sur `title`.
- **AC-05** : *Quand* j'envoie `status = "archived"` (valeur hors énumération), *alors* je reçois `422` avec une violation sur `status`.
- **AC-06** : *Quand* j'envoie une `dueDate` passée, *alors* je reçois `422` avec une violation sur `dueDate`.
- **AC-07** : *Quand* j'envoie un `id` ou un `createdAt` dans le corps, *alors* ils sont ignorés (RM-05).

### Lecture
- **AC-08** : *Étant donné* une tâche existante, *quand* j'appelle `GET /api/tasks/{id}`, *alors* je reçois `200` et la tâche complète.
- **AC-09** : *Quand* j'appelle `GET /api/tasks/{id}` avec un UUID inexistant, *alors* je reçois `404`.
- **AC-10** : *Quand* j'appelle `GET /api/tasks`, *alors* je reçois `200` et un tableau JSON trié par `createdAt` décroissant.
- **AC-11** : *Quand* j'appelle `GET /api/tasks?status=todo`, *alors* toutes les tâches renvoyées ont `status = todo`.
- **AC-12** : *Quand* j'appelle `GET /api/tasks?itemsPerPage=1`, *alors* je reçois au plus 1 élément. Avec `itemsPerPage=500`, je reçois au plus 50 éléments.

### Modification
- **AC-13** : *Quand* j'envoie `PATCH /api/tasks/{id}` (`application/merge-patch+json`) avec `{"status":"done"}`, *alors* je reçois `200`, le statut est modifié, les autres champs sont inchangés et `updatedAt ≥ createdAt`.
- **AC-14** : *Quand* j'envoie un `PATCH` avec `{"title":""}`, *alors* je reçois `422` et la tâche n'est pas modifiée.

### Suppression
- **AC-15** : *Quand* j'appelle `DELETE /api/tasks/{id}`, *alors* je reçois `204`, puis un `GET` sur cette tâche renvoie `404`.

## 6. Exigences non fonctionnelles

- **ENF-01** : `GET /api/tasks/{id}` répond en moins de 200 ms (p95) en local.
- **ENF-02** : les erreurs suivent le format `application/problem+json` (RFC 7807), avec un tableau `violations` pour les erreurs `422`.
- **ENF-03** : aucune stack trace n'est exposée en environnement `prod`.
- **ENF-04** : une documentation interactive de l'API est consultable dans un navigateur. Elle décrit toutes les opérations exposées, y compris la santé, et permet de les essayer. Le contrat de référence est consultable sous la même forme, pour comparer ce que l'API expose à ce qui est attendu.

## 7. Questions ouvertes

_Aucune. Toute nouvelle question est ajoutée ici et doit être tranchée avant `/plan`._
