---
description: Implémenter la prochaine tâche non cochée en TDD
argument-hint: <dossier de la spec> [T-XX]
allowed-tools: Read, Write, Edit, Glob, Grep, Bash(php:*), Bash(composer:*), Bash(vendor/bin/*:*), Bash(git:*), Bash(make:*)
---
Arguments : $ARGUMENTS (dossier de spec, puis éventuellement un identifiant de tâche).

1. Lis `CLAUDE.md`, puis `spec.md`, `plan.md`, `tasks.md` et `contracts/openapi.yaml` du dossier.
2. Choisis la tâche indiquée, sinon la **première non cochée**. Annonce-la en une ligne avec ses AC.
3. **Rouge** : écris d'abord le ou les tests PHPUnit (`tests/Api/…`, méthodes `testACXX…`) qui reproduisent exactement les AC. Lance `cd apps/api && php bin/phpunit --filter ACXX` et montre qu'ils échouent.
4. **Vert** : écris le code minimal pour les faire passer.
5. **Refactor** : nettoie, puis lance toute la suite `php bin/phpunit` et `vendor/bin/phpstan analyse`.
6. Si tout est vert : coche la tâche dans `tasks.md` et commite avec `type(scope): message [T-XX, AC-YY]`.
7. Si un AC semble faux ou impossible : **n'adapte ni la spec ni le contrat**. Arrête-toi et explique le problème.

Une seule tâche par exécution. Termine en indiquant la tâche suivante.
