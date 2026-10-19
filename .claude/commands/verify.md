---
description: Vérifier qu'une spec est entièrement implémentée et testée (matrice de traçabilité)
argument-hint: <dossier de la spec>
allowed-tools: Read, Glob, Grep, Bash(php:*), Bash(vendor/bin/*:*), Bash(npm:*)
---
Spec : $ARGUMENTS

1. Extrais tous les `AC-XX` de `$ARGUMENTS/spec.md`.
2. Cherche dans `apps/api/tests/` les méthodes `testACXX…`, et dans `../taskpilot-qa/tests/api/collections/` les requêtes `AC-XX …`.
3. Lance `cd apps/api && php bin/phpunit` puis `cd ../taskpilot-qa/tests/api && npm test`.
4. Produis la matrice :

| AC | Test PHPUnit | Requête Postman | Résultat |
|---|---|---|---|

5. Verdict final : ✅ si chaque AC a ses deux tests verts et que toutes les tâches de `tasks.md` sont cochées, sinon ❌ avec la liste précise de ce qui manque.

Lecture seule : ne modifie aucun fichier.
