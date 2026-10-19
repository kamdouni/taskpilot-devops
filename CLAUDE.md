# TaskPilot — règles pour Claude

## Méthode : Spec-Driven Development (SDD)

Aucune ligne de code applicatif n'est écrite sans spec validée. Le flux est toujours :

```
/spec  →  /plan  →  /tasks  →  /implement (boucle)  →  /api-test  →  /verify
 QUOI       COMMENT     DÉCOUPAGE      CODE + TESTS        POSTMAN       TRAÇABILITÉ
```

1. `specs/NNN-slug/spec.md` décrit le **quoi** et le **pourquoi** : règles métier et critères d'acceptation numérotés `AC-XX`. Aucun détail technique.
2. `specs/NNN-slug/plan.md` décrit le **comment** : choix Symfony, entités, contraintes. Le contrat `contracts/openapi.yaml` est la source de vérité de l'API.
3. `specs/NNN-slug/tasks.md` liste des tâches courtes, ordonnées, chacune liée à un ou plusieurs `AC-XX`.
4. Chaque critère `AC-XX` doit être couvert par **au moins un test PHPUnit et une requête Postman** dont le nom commence par `AC-XX`.

## Règles impératives

- Si une spec est ambiguë ou incomplète : **poser la question**, ne pas inventer. Noter les points ouverts dans la section « Questions ouvertes » de la spec.
- Ne **jamais** modifier une spec ou le contrat OpenAPI pour faire passer un test. Si le code révèle un problème de spec, s'arrêter et le signaler.
- Tests d'abord (TDD) : écrire le test qui échoue, puis le code, puis refactorer.
- Une tâche = un commit, au format Conventional Commits avec la référence : `feat(task): validate title length [T-04, AC-03]`.
- Ne pas cocher une tâche tant que `make test` n'est pas vert.

## Stack de l'API (`apps/api`)

- PHP 8.4, Symfony 8, API Platform, Doctrine ORM, PostgreSQL
- Tests : PHPUnit + `ApiTestCase` d'API Platform, base de test dédiée
- Qualité : PHPStan niveau 8, PHP-CS-Fixer (règles `@Symfony`)
- Format d'échange : JSON uniquement (`application/json`, `application/merge-patch+json` pour PATCH)

## Commandes utiles

| Action | Commande |
|---|---|
| Tests PHP | `cd apps/api && php bin/phpunit` |
| Analyse statique | `cd apps/api && vendor/bin/phpstan analyse` |
| Simulateur Postman (contre l'API locale) | `cd ../taskpilot-qa/tests/api && npm test` |
| Simulateur Postman (contre le mock du contrat) | `cd ../taskpilot-qa/tests/api && npm run test:mock` |

## Worktree QA

Le simulateur d'API vit dans un **git worktree** séparé : `../taskpilot-qa` (branche `qa/api-simulator`).
Il teste l'API de l'extérieur, comme le ferait un client, sans toucher au code de `apps/api`.
