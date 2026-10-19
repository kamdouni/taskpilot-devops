---
description: Découper un plan en tâches TDD ordonnées et traçables
argument-hint: <dossier de la spec>
allowed-tools: Read, Write, Glob
---
Spec : $ARGUMENTS

1. Lis `spec.md`, `plan.md` et `contracts/openapi.yaml` dans `$ARGUMENTS`.
2. Écris `$ARGUMENTS/tasks.md` à partir de `specs/_templates/tasks.md`.
3. Règles de découpage :
   - tâches numérotées `T-XX`, chacune faisable en moins d'une heure ;
   - chaque tâche cite les `AC-XX` qu'elle couvre ;
   - ordre : socle → création → lecture → modification → suppression → vérification ;
   - **chaque AC de la spec apparaît dans au moins une tâche**.
4. Termine par une vérification : liste des AC et tâches associées, et signale tout AC orphelin.
