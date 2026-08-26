import { access, readFile } from 'node:fs/promises';

const requiredFiles = [
  'package.json',
  'api/server.js',
  'api/.env.example',
];

for (const file of requiredFiles) {
  await access(file);
}

const pkg = JSON.parse(await readFile('package.json', 'utf8'));

for (const script of ['dev', 'build', 'start', 'lint']) {
  if (!pkg.scripts?.[script]) {
    throw new Error(`Missing required package script: ${script}`);
  }
}

if (pkg.private !== true) {
  throw new Error('Root platform package must remain private.');
}

if (!String(pkg.engines?.node || '').includes('20')) {
  throw new Error('Node 20+ runtime contract is required.');
}

console.log('ARE Node import/runtime validation passed.');
