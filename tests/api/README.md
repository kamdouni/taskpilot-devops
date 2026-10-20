# 🧪 Simulateur d'API TaskPilot

Joue automatiquement la collection Postman (avec Newman) contre l'API. Chaque requête porte le nom d'un critère d'acceptation `AC-XX` de la spec.

| Commande | Contre quoi | Usage |
|---|---|---|
| `npm run test:mock` | Le mock du contrat (démarré tout seul) | Valider la collection **avant** que l'API existe |
| `npm test` | L'API Symfony (`API_URL`, défaut `http://localhost:8080`) | Vérifier l'implémentation |
| `npm run test:contract` | L'API, à travers un proxy Prism | Vérifier que chaque réponse respecte `openapi.yaml` |
| `npm run watch` | L'API, en boucle | Rejoue tout à chaque sauvegarde dans `apps/api/src` ou dans la collection |
| `npm run build` | — | Régénère la collection depuis `scripts/build-collection.mjs` |

Les rapports HTML et JUnit sont écrits dans `reports/` (ouvre `reports/local.html` dans ton navigateur).

## Variables

| Variable | Défaut | Rôle |
|---|---|---|
| `API_URL` | `http://localhost:8080` | URL de l'API Symfony |
| `MAIN_REPO` | `../../../taskpilot-devops` | Dépôt principal (contrat OpenAPI, code surveillé par `watch`) |
| `MOCK_PORT` | `4010` | Port du mock |

Exemple avec le serveur Symfony CLI : `API_URL=http://127.0.0.1:8000 npm run watch`

## Dans l'application Postman

Importe `collections/taskpilot.postman_collection.json` et un fichier de `environments/`, puis lance la collection avec le **Collection Runner**. Après une modification dans Postman, exporte la collection au même emplacement : Newman jouera ta version.
