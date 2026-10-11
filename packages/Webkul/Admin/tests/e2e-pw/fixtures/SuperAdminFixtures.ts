import { test as base, expect, type Page } from "@playwright/test";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { SuperAdminPage } from "../pages/SuperAdminPage";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const STATE_DIR = path.resolve(__dirname, "../.state");
export const PLATFORM_AUTH_STATE_PATH = path.join(STATE_DIR, "platform-auth.json");

const PLATFORM_EMAIL =
    process.env.PLATFORM_ADMIN_EMAIL ?? "e2e-platform@zaris.test";
const PLATFORM_PASSWORD =
    process.env.PLATFORM_ADMIN_PASSWORD ?? "E2ePlatformAdmin!234";

type SuperAdminFixtures = {
    superAdminPage: Page;
};

export const test = base.extend<SuperAdminFixtures>({
    superAdminPage: async ({ browser }, use) => {
        fs.mkdirSync(STATE_DIR, { recursive: true });

        const authExists = fs.existsSync(PLATFORM_AUTH_STATE_PATH);
        const context = await browser.newContext(
            authExists ? { storageState: PLATFORM_AUTH_STATE_PATH } : {},
        );
        const page = await context.newPage();
        const superAdmin = new SuperAdminPage(page);

        if (!authExists) {
            await superAdmin.login(PLATFORM_EMAIL, PLATFORM_PASSWORD);
            await context.storageState({ path: PLATFORM_AUTH_STATE_PATH });
        } else {
            await page.goto("super-admin");
            if (page.url().includes("super-admin/login")) {
                await superAdmin.login(PLATFORM_EMAIL, PLATFORM_PASSWORD);
                await context.storageState({ path: PLATFORM_AUTH_STATE_PATH });
            }
        }

        await use(page);
        await context.close();
    },
});

export { expect, PLATFORM_EMAIL, PLATFORM_PASSWORD };
