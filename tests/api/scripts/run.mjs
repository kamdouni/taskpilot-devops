// Simulateur Postman automatique : joue la collection avec Newman.
//   node scripts/run.mjs local     → contre l'API Symfony (API_URL, défaut http://localhost:8080)
//   node scripts/run.mjs mock      → contre le mock du contrat (démarré automatiquement)
//   node scripts/run.mjs contract  → contre l'API, via un proxy Prism qui valide chaque réponse avec openapi.yaml
//   node scripts/run.mjs watch     → rejoue à chaque modification de la collection ou du code de l'API
import newman from 'newman';
import { spawn } from 'node:child_process';
import { watch, existsSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { resolve, dirname } from 'node:path';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const COLLECTION = resolve(ROOT, 'collections/taskpilot.postman_collection.json');
const API_URL = process.env.API_URL ?? 'http://localhost:8080';
const MAIN_REPO = process.env.MAIN_REPO ?? resolve(ROOT, '../../../taskpilot-devops');
const OPENAPI = process.env.OPENAPI ?? resolve(MAIN_REPO, 'specs/001-task-api/contracts/openapi.yaml');
const mode = process.argv[2] ?? 'local';
const children = [];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function waitFor(url, label, tries = 60) {
  for (let i = 0; i < tries; i++) {
    try { await fetch(url); return; } catch { await sleep(500); }
  }
  throw new Error(`${label} injoignable sur ${url}`);
}
function start(cmd, args, label) {
  const p = spawn(cmd, args, { cwd: ROOT, stdio: ['ignore', 'pipe', 'pipe'] });
  p.stderr.on('data', (d) => process.env.DEBUG && process.stderr.write(`[${label}] ${d}`));
  children.push(p);
  return p;
}
const cleanup = () => children.forEach((c) => c.kill());
process.on('exit', cleanup);
process.on('SIGINT', () => process.exit(130));

function runNewman(baseUrl, label) {
  mkdirSync(resolve(ROOT, 'reports'), { recursive: true });
  return new Promise((done) => {
    newman.run({
      collection: COLLECTION,
      envVar: [{ key: 'baseUrl', value: baseUrl }],
      reporters: ['cli', 'htmlextra', 'junit'],
      reporter: {
        htmlextra: { export: resolve(ROOT, `reports/${label}.html`), title: `TaskPilot — ${label}`, browserTitle: `TaskPilot ${label}` },
        junit: { export: resolve(ROOT, `reports/${label}.xml`) },
      },
      timeoutRequest: 10000,
    }, (err, summary) => {
      if (err) { console.error(err); return done(false); }
      const f = summary.run.failures.length;
      console.log(f ? `\n❌ ${f} assertion(s) en échec — rapport : reports/${label}.html` : `\n✅ Tout est vert — rapport : reports/${label}.html`);
      done(f === 0);
    });
  });
}

async function main() {
  if (mode === 'mock') {
    const port = process.env.MOCK_PORT ?? '4010';
    start('node', ['mock/server.mjs', port], 'mock');
    await waitFor(`http://localhost:${port}/health`, 'Le mock');
    return runNewman(`http://localhost:${port}`, 'mock');
  }
  if (mode === 'contract') {
    if (!existsSync(OPENAPI)) throw new Error(`Contrat introuvable : ${OPENAPI} (définis OPENAPI=...)`);
    await waitFor(`${API_URL}/health`, "L'API");
    start('npx', ['prism', 'proxy', OPENAPI, API_URL, '--port', '4011', '--errors'], 'prism');
    await waitFor('http://localhost:4011/health', 'Le proxy Prism');
    return runNewman('http://localhost:4011', 'contract');
  }
  if (mode === 'watch') {
    let timer, running = false;
    const trigger = (why) => {
      clearTimeout(timer);
      timer = setTimeout(async () => {
        if (running) return; running = true;
        console.clear(); console.log(`🔁 ${why} — nouvelle exécution\n`);
        await runNewman(API_URL, 'local').catch(console.error);
        running = false; console.log('\n👀 En attente de modifications… (Ctrl+C pour quitter)');
      }, 800);
    };
    watch(resolve(ROOT, 'collections'), () => trigger('Collection modifiée'));
    const apiSrc = resolve(MAIN_REPO, 'apps/api/src');
    if (existsSync(apiSrc)) watch(apiSrc, { recursive: true }, (_, f) => trigger(`apps/api/src/${f} modifié`));
    else console.log(`ℹ️  ${apiSrc} introuvable : seule la collection est surveillée`);
    trigger('Démarrage');
    return new Promise(() => {});
  }
  await waitFor(`${API_URL}/health`, "L'API", 10);
  return runNewman(API_URL, 'local');
}

main().then((ok) => process.exit(ok ? 0 : 1)).catch((e) => { console.error(`❌ ${e.message}`); process.exit(1); });
