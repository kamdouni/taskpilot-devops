---
description: Synchroniser la collection Postman du worktree QA avec les AC d'une spec, puis la jouer
argument-hint: <dossier de la spec> [local|mock|contract]
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(npm:*), Bash(node:*), Bash(git:*)
---
Arguments : $ARGUMENTS (dossier de spec, puis la cible : `local` par défaut, `mock` ou `contract`).

Le simulateur se trouve dans le worktree `../taskpilot-qa/tests/api`.

1. Lis `spec.md` et `contracts/openapi.yaml` de la spec.
2. Ouvre `../taskpilot-qa/tests/api/collections/taskpilot.postman_collection.json`.
3. Pour chaque `AC-XX` sans requête dont le nom commence par `AC-XX`, ajoute la requête et ses assertions `pm.test` (code HTTP, schéma, valeurs). Les requêtes s'enchaînent : celle qui crée une ressource enregistre son id dans une variable de collection.
4. Ne supprime jamais une requête existante sans me le demander.
5. Lance `npm test` (local), `npm run test:mock` ou `npm run test:contract` selon la cible.
6. Affiche un tableau : AC → requête → résultat. Pour chaque échec, indique si c'est l'API ou le test qui ne respecte pas la spec.
7. Commite dans le worktree (branche `qa/api-simulator`) : `test(api): sync collection with spec NNN`.
