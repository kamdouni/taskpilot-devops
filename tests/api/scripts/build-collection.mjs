// Génère collections/taskpilot.postman_collection.json (format Postman v2.1).
// Tu peux aussi éditer directement le JSON dans Postman puis l'exporter : ce script n'est qu'un point de départ.
// Usage : node scripts/build-collection.mjs
import { writeFileSync, mkdirSync } from 'node:fs';
import { randomUUID } from 'node:crypto';

const lines = (s) => s.trim().split('\n');
const JSON_HDR = [{ key: 'Content-Type', value: 'application/json' }];
const PATCH_HDR = [{ key: 'Content-Type', value: 'application/merge-patch+json' }];

function req(name, method, path, { body, headers = [], test, pre } = {}) {
  const segments = path.split('?');
  const item = {
    name,
    request: {
      method,
      header: body ? [...(method === 'PATCH' ? PATCH_HDR : JSON_HDR), ...headers] : headers,
      url: {
        raw: `{{baseUrl}}${path}`,
        host: ['{{baseUrl}}'],
        path: segments[0].split('/').filter(Boolean),
        ...(segments[1] ? { query: segments[1].split('&').map((q) => { const [key, value] = q.split('='); return { key, value }; }) } : {}),
      },
    },
    event: [],
  };
  if (body) item.request.body = { mode: 'raw', raw: typeof body === 'string' ? body : JSON.stringify(body, null, 2) };
  if (pre) item.event.push({ listen: 'prerequest', script: { type: 'text/javascript', exec: lines(pre) } });
  if (test) item.event.push({ listen: 'test', script: { type: 'text/javascript', exec: lines(test) } });
  return item;
}

const violationOn = (field) => `
pm.test("ENF-02 : format application/problem+json", () => pm.expect(pm.response.headers.get("Content-Type")).to.include("application/problem+json"));
pm.test("Une violation porte sur ${field}", () => {
  const v = pm.response.json().violations || [];
  pm.expect(v.map(x => x.propertyPath)).to.include("${field}");
});`;

const items = [
  { name: '00 · Santé', item: [
    req('AC-01 · GET /health → 200', 'GET', '/health', { test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
pm.test("API et base OK", () => pm.expect(pm.response.json()).to.eql({ status: "ok", database: "ok" }));` }),
  ]},
  { name: '01 · Création', item: [
    req('AC-02 · POST tâche valide → 201 + valeurs par défaut', 'POST', '/api/tasks', {
      body: '{\n  "title": "  Relire le rapport trimestriel  ",\n  "dueDate": "{{futureDate}}"\n}',
      pre: `
const d = new Date(Date.now() + 7 * 86400000);
pm.collectionVariables.set("futureDate", d.toISOString().slice(0, 10));`,
      test: `
pm.test("Statut 201", () => pm.response.to.have.status(201));
const t = pm.response.json();
pm.test("Un id est généré", () => pm.expect(t.id).to.match(/^[0-9a-f-]{36}$/i));
pm.test("RM-01 : le titre est nettoyé", () => pm.expect(t.title).to.eql("Relire le rapport trimestriel"));
pm.test("Valeurs par défaut todo / medium", () => { pm.expect(t.status).to.eql("todo"); pm.expect(t.priority).to.eql("medium"); });
pm.test("createdAt et updatedAt renseignés", () => { pm.expect(t.createdAt).to.be.a("string"); pm.expect(t.updatedAt).to.be.a("string"); });
pm.collectionVariables.set("taskId", t.id);
pm.collectionVariables.set("taskTitle", t.title);
pm.collectionVariables.set("taskCreatedAt", t.createdAt);` }),
    req('AC-03 · POST sans titre → 422', 'POST', '/api/tasks', { body: { priority: 'high' }, test: `pm.test("Statut 422", () => pm.response.to.have.status(422));${violationOn('title')}` }),
    req('AC-03 · POST titre composé d\'espaces → 422', 'POST', '/api/tasks', { body: { title: '    ' }, test: `pm.test("Statut 422", () => pm.response.to.have.status(422));${violationOn('title')}` }),
    req('AC-04 · POST titre de 256 caractères → 422', 'POST', '/api/tasks', {
      body: '{ "title": "{{longTitle}}" }',
      pre: 'pm.collectionVariables.set("longTitle", "a".repeat(256));',
      test: `pm.test("Statut 422", () => pm.response.to.have.status(422));${violationOn('title')}` }),
    req('AC-05 · POST statut hors énumération → 422', 'POST', '/api/tasks', { body: { title: 'Statut invalide', status: 'archived' }, test: `pm.test("Statut 422", () => pm.response.to.have.status(422));${violationOn('status')}` }),
    req('AC-06 · POST échéance passée → 422', 'POST', '/api/tasks', { body: { title: 'En retard', dueDate: '2000-01-01' }, test: `pm.test("Statut 422", () => pm.response.to.have.status(422));${violationOn('dueDate')}` }),
    req('AC-07 · POST id et createdAt imposés → ignorés', 'POST', '/api/tasks', {
      body: { title: 'Tentative d\'injection', id: '11111111-1111-1111-1111-111111111111', createdAt: '1999-12-31T23:59:59+00:00' },
      test: `
pm.test("Statut 201", () => pm.response.to.have.status(201));
const t = pm.response.json();
pm.test("L'id envoyé est ignoré", () => pm.expect(t.id).to.not.eql("11111111-1111-1111-1111-111111111111"));
pm.test("Le createdAt envoyé est ignoré", () => pm.expect(t.createdAt).to.not.include("1999"));` }),
    req('SETUP · POST tâche in_progress (pour les filtres)', 'POST', '/api/tasks', { body: { title: 'Préparer la démo', status: 'in_progress', priority: 'high' }, test: 'pm.test("Statut 201", () => pm.response.to.have.status(201));' }),
  ]},
  { name: '02 · Lecture', item: [
    req('AC-08 · GET tâche existante → 200', 'GET', '/api/tasks/{{taskId}}', { test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
pm.test("C'est la bonne tâche", () => { const t = pm.response.json(); pm.expect(t.id).to.eql(pm.collectionVariables.get("taskId")); pm.expect(t.title).to.eql(pm.collectionVariables.get("taskTitle")); });
pm.test("ENF-01 : réponse en moins de 200 ms", () => pm.expect(pm.response.responseTime).to.be.below(200));` }),
    req('AC-09 · GET UUID inconnu → 404', 'GET', '/api/tasks/0192f000-0000-7000-8000-000000000000', { test: 'pm.test("Statut 404", () => pm.response.to.have.status(404));' }),
    req('AC-10 · GET liste → 200 triée par date décroissante', 'GET', '/api/tasks', { test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
const list = pm.response.json();
pm.test("La réponse est un tableau", () => pm.expect(list).to.be.an("array"));
pm.test("RM-03 : tri par createdAt décroissant", () => { for (let i = 1; i < list.length; i++) pm.expect(list[i - 1].createdAt >= list[i].createdAt, "ordre à l'index " + i).to.be.true; });
pm.test("La tâche créée est présente", () => pm.expect(list.map(t => t.id)).to.include(pm.collectionVariables.get("taskId")));` }),
    req('AC-11 · GET filtre status=todo', 'GET', '/api/tasks?status=todo', { test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
const list = pm.response.json();
pm.test("Au moins un résultat", () => pm.expect(list.length).to.be.above(0));
pm.test("Uniquement des tâches todo", () => list.forEach(t => pm.expect(t.status).to.eql("todo")));` }),
    req('AC-12 · GET itemsPerPage=1 → 1 élément max', 'GET', '/api/tasks?itemsPerPage=1', { test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
pm.test("Au plus 1 élément", () => pm.expect(pm.response.json().length).to.be.at.most(1));` }),
    req('AC-12 · GET itemsPerPage=500 → plafonné à 50', 'GET', '/api/tasks?itemsPerPage=500', { test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
pm.test("RM-04 : 50 éléments maximum", () => pm.expect(pm.response.json().length).to.be.at.most(50));` }),
  ]},
  { name: '03 · Modification', item: [
    req('AC-13 · PATCH statut done → 200', 'PATCH', '/api/tasks/{{taskId}}', { body: { status: 'done' }, test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
const t = pm.response.json();
pm.test("Le statut est done", () => pm.expect(t.status).to.eql("done"));
pm.test("Le titre est inchangé", () => pm.expect(t.title).to.eql(pm.collectionVariables.get("taskTitle")));
pm.test("updatedAt ≥ createdAt", () => pm.expect(new Date(t.updatedAt) >= new Date(t.createdAt)).to.be.true);` }),
    req('AC-14 · PATCH titre vide → 422', 'PATCH', '/api/tasks/{{taskId}}', { body: { title: '' }, test: `pm.test("Statut 422", () => pm.response.to.have.status(422));${violationOn('title')}` }),
    req('AC-14 · GET après PATCH refusé → tâche inchangée', 'GET', '/api/tasks/{{taskId}}', { test: `
pm.test("Statut 200", () => pm.response.to.have.status(200));
pm.test("Le titre n'a pas été modifié", () => pm.expect(pm.response.json().title).to.eql(pm.collectionVariables.get("taskTitle")));` }),
  ]},
  { name: '04 · Suppression', item: [
    req('AC-15 · DELETE → 204', 'DELETE', '/api/tasks/{{taskId}}', { test: 'pm.test("Statut 204", () => pm.response.to.have.status(204));' }),
    req('AC-15 · GET après suppression → 404', 'GET', '/api/tasks/{{taskId}}', { test: 'pm.test("Statut 404", () => pm.response.to.have.status(404));' }),
  ]},
];

// Contrôle de contrat appliqué à TOUTES les réponses de succès sur /api/tasks
const contractCheck = `
const path = pm.request.url.getPath();
if (path.startsWith("/api/tasks") && [200, 201].includes(pm.response.code)) {
  const taskSchema = {
    type: "object",
    required: ["id", "title", "description", "status", "priority", "dueDate", "createdAt", "updatedAt"],
    properties: {
      id: { type: "string" },
      title: { type: "string", minLength: 1, maxLength: 255 },
      description: { type: ["string", "null"] },
      status: { enum: ["todo", "in_progress", "done"] },
      priority: { enum: ["low", "medium", "high"] },
      dueDate: { type: ["string", "null"] },
      createdAt: { type: "string" },
      updatedAt: { type: "string" }
    }
  };
  const body = pm.response.json();
  pm.test("Contrat : schéma Task respecté", () => {
    pm.response.to.have.jsonSchema(Array.isArray(body) ? { type: "array", items: taskSchema } : taskSchema);
  });
}`;

const collection = {
  info: {
    _postman_id: randomUUID(),
    name: 'TaskPilot — Spec 001 (API des tâches)',
    description: 'Une requête par critère d\'acceptation de specs/001-task-api/spec.md. Le nom de chaque requête commence par son identifiant AC-XX.',
    schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
  },
  event: [{ listen: 'test', script: { type: 'text/javascript', exec: lines(contractCheck) } }],
  variable: [
    { key: 'taskId', value: '' }, { key: 'taskTitle', value: '' }, { key: 'taskCreatedAt', value: '' },
    { key: 'futureDate', value: '' }, { key: 'longTitle', value: '' },
  ],
  item: items,
};

mkdirSync(new URL('../collections/', import.meta.url), { recursive: true });
writeFileSync(new URL('../collections/taskpilot.postman_collection.json', import.meta.url), JSON.stringify(collection, null, 2) + '\n');
const count = items.reduce((n, f) => n + f.item.length, 0);
console.log(`✅ Collection générée : ${count} requêtes`);
