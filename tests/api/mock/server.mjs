// Mock "vivant" de la spec 001 : implémente les règles du contrat en mémoire.
// Sert à valider la collection Postman AVANT que l'API Symfony existe.
// Aucune dépendance : node mock/server.mjs [port]
import { createServer } from 'node:http';
import { randomUUID } from 'node:crypto';

const PORT = Number(process.argv[2] ?? process.env.MOCK_PORT ?? 4010);
const STATUSES = ['todo', 'in_progress', 'done'];
const PRIORITIES = ['low', 'medium', 'high'];
const tasks = new Map();

const today = () => new Date().toISOString().slice(0, 10);

function send(res, code, body, type = 'application/json') {
  res.writeHead(code, body === undefined ? {} : { 'Content-Type': type });
  res.end(body === undefined ? undefined : JSON.stringify(body));
}
const notFound = (res) =>
  send(res, 404, { type: '/errors/404', title: 'Not Found', status: 404, detail: 'Task not found' }, 'application/problem+json');
const invalid = (res, violations) =>
  send(res, 422, { type: '/validation_errors', title: 'Unprocessable Entity', status: 422, detail: violations.map((v) => `${v.propertyPath}: ${v.message}`).join('\n'), violations }, 'application/problem+json');

function validate(input, current = {}) {
  const v = [];
  if ('title' in input || !current.id) {
    const t = typeof input.title === 'string' ? input.title.trim() : '';
    if (!t) v.push({ propertyPath: 'title', message: 'This value should not be blank.' });
    else if (t.length > 255) v.push({ propertyPath: 'title', message: 'This value is too long. It should have 255 characters or less.' });
  }
  if (input.description != null && String(input.description).length > 2000)
    v.push({ propertyPath: 'description', message: 'This value is too long. It should have 2000 characters or less.' });
  if ('status' in input && !STATUSES.includes(input.status))
    v.push({ propertyPath: 'status', message: 'The value you selected is not a valid choice.' });
  if ('priority' in input && !PRIORITIES.includes(input.priority))
    v.push({ propertyPath: 'priority', message: 'The value you selected is not a valid choice.' });
  if (input.dueDate != null && input.dueDate !== current.dueDate) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(input.dueDate)) v.push({ propertyPath: 'dueDate', message: 'This value is not a valid date.' });
    else if (input.dueDate < today()) v.push({ propertyPath: 'dueDate', message: `This value should be greater than or equal to ${today()}.` });
  }
  return v;
}

function pick(input) {
  const out = {};
  for (const k of ['title', 'description', 'status', 'priority', 'dueDate']) if (k in input) out[k] = input[k];
  if (typeof out.title === 'string') out.title = out.title.trim();
  return out; // RM-05 : id, createdAt, updatedAt ignorés
}

async function readJson(req) {
  let raw = '';
  for await (const chunk of req) raw += chunk;
  if (!raw) return {};
  try { return JSON.parse(raw); } catch { return null; }
}

createServer(async (req, res) => {
  const url = new URL(req.url, `http://localhost:${PORT}`);
  const path = url.pathname.replace(/\/$/, '');
  const m = path.match(/^\/api\/tasks\/([^/]+)$/);

  if (req.method === 'GET' && path === '/health') return send(res, 200, { status: 'ok', database: 'ok' });

  if (path === '/api/tasks' && req.method === 'GET') {
    let list = [...tasks.values()].sort((a, b) => b.createdAt.localeCompare(a.createdAt));
    for (const f of ['status', 'priority']) {
      const val = url.searchParams.get(f);
      if (val) list = list.filter((t) => t[f] === val);
    }
    const per = Math.min(Math.max(Number(url.searchParams.get('itemsPerPage') ?? 20) || 20, 1), 50);
    const page = Math.max(Number(url.searchParams.get('page') ?? 1) || 1, 1);
    return send(res, 200, list.slice((page - 1) * per, page * per));
  }

  if (path === '/api/tasks' && req.method === 'POST') {
    const body = await readJson(req);
    if (body === null) return send(res, 400, { title: 'Bad Request', status: 400, detail: 'Syntax error' }, 'application/problem+json');
    const violations = validate(body);
    if (violations.length) return invalid(res, violations);
    const now = new Date().toISOString();
    const task = { id: randomUUID(), description: null, status: 'todo', priority: 'medium', dueDate: null, ...pick(body), createdAt: now, updatedAt: now };
    tasks.set(task.id, task);
    return send(res, 201, task);
  }

  if (m) {
    const task = tasks.get(m[1]);
    if (!task) return notFound(res);
    if (req.method === 'GET') return send(res, 200, task);
    if (req.method === 'DELETE') { tasks.delete(task.id); return send(res, 204); }
    if (req.method === 'PATCH') {
      if (!String(req.headers['content-type'] ?? '').includes('merge-patch+json'))
        return send(res, 415, { title: 'Unsupported Media Type', status: 415 }, 'application/problem+json');
      const body = await readJson(req);
      if (body === null) return send(res, 400, { title: 'Bad Request', status: 400 }, 'application/problem+json');
      const violations = validate(body, task);
      if (violations.length) return invalid(res, violations);
      Object.assign(task, pick(body), { updatedAt: new Date().toISOString() });
      return send(res, 200, task);
    }
  }
  return notFound(res);
}).listen(PORT, () => console.log(`🧪 Mock du contrat TaskPilot sur http://localhost:${PORT}`));
