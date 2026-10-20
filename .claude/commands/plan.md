---
description: Produire le plan technique Symfony et le contrat OpenAPI d'une spec validée
argument-hint: <dossier de la spec, ex. specs/001-task-api>
allowed-tools: Read, Write, Edit, Glob, Grep
---
Spec à planifier : $ARGUMENTS

1. Lis `$ARGUMENTS/spec.md` et `CLAUDE.md`.
2. Si la section « Questions ouvertes » contient une question non tranchée, ou si le statut n'est pas ✅ Validée : **arrête-toi** et liste ce qui bloque.
3. Rédige `$ARGUMENTS/plan.md` à partir de `specs/_templates/plan.md` : choix techniques justifiés (Symfony 8, API Platform, Doctrine), fichiers à créer, contraintes de validation, stratégie de test, risques.
4. Écris ou mets à jour `$ARGUMENTS/contracts/openapi.yaml` (OpenAPI 3.1) : chaque AC doit correspondre à une opération et à un code de réponse documentés.
5. Vérifie la cohérence : affiche un tableau AC → opération OpenAPI → code HTTP.

Interdit : écrire du code dans `apps/`.
