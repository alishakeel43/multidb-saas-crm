#!/usr/bin/env bash
# Open Playwright codegen already logged in as the platform Super Admin.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

mkdir -p tests/saas/recorded .state
unset PLAYWRIGHT_BROWSERS_PATH || true

OUT="${1:-tests/saas/recorded/scratch.spec.ts}"
URL="${2:-http://krayinsaas.localhost:8888/super-admin}"
STATE=".state/platform-auth.json"

if [[ ! -f "$STATE" ]]; then
  echo "Missing $STATE — creating auto-login session first..."
  node <<'NODE'
const { chromium } = require("../../node_modules/playwright");
const fs = require("fs");
const path = require("path");

const email = process.env.PLATFORM_ADMIN_EMAIL || "e2e-platform@zaris.test";
const password = process.env.PLATFORM_ADMIN_PASSWORD || "E2ePlatformAdmin!234";
const baseURL = (process.env.APP_URL || "http://krayinsaas.localhost:8888").replace(/\/+$/, "");
const out = path.resolve(".state/platform-auth.json");

(async () => {
  fs.mkdirSync(path.dirname(out), { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();
  await page.goto(`${baseURL}/super-admin/login`);
  await page.locator('input[id="data.email"], input[type="email"]').first().fill(email);
  await page.locator('input[id="data.password"], input[type="password"]').first().fill(password);
  await page.getByRole("button", { name: /sign in/i }).click();
  await page.waitForURL(/super-admin(?!\/login)/, { timeout: 30000 });
  await context.storageState({ path: out });
  await browser.close();
  console.log(`Saved auto-login state → ${out}`);
})().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
NODE
fi

echo "Opening codegen with auto-login ($STATE) → $OUT"
npx playwright codegen \
  --target=playwright-test \
  --load-storage="$STATE" \
  -o "$OUT" \
  "$URL"
