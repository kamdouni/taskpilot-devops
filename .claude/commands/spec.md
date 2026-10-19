---
description: Rédiger une nouvelle spec fonctionnelle (le QUOI), sans aucun code
argument-hint: <description de la fonctionnalité>
allowed-tools: Read, Write, Glob, Grep
---
Tu es analyste fonctionnel. Fonctionnalité demandée : $ARGUMENTS

1. Lis `CLAUDE.md` et les specs existantes dans `specs/` pour connaître le contexte et le dernier numéro utilisé.
2. Crée `specs/NNN-<slug>/spec.md` (NNN = numéro suivant sur 3 chiffres) à partir de `specs/_templates/spec.md`.
3. Remplis : contexte, hors périmètre, modèle métier, règles `RM-XX`, critères d'acceptation `AC-XX` au format Étant donné / Quand / Alors avec codes HTTP et corps attendus, exigences non fonctionnelles.
4. Chaque AC doit être **testable de l'extérieur** (requête HTTP → réponse vérifiable).
5. Liste dans « Questions ouvertes » tout ce que la demande ne précise pas. Ne l'invente pas.
6. Statut : 📝 Brouillon.

Interdit : écrire du code, choisir une techno, créer plan.md.
Termine en affichant les questions ouvertes et demande-moi d'y répondre avant `/plan`.
