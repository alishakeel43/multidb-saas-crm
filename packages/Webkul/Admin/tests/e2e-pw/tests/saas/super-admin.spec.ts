import { test, expect, PLATFORM_EMAIL, PLATFORM_PASSWORD } from "../../fixtures/SuperAdminFixtures";
import { SuperAdminPage } from "../../pages/SuperAdminPage";

test.describe("ZARIS Super Admin SaaS console", () => {
    test("rejects invalid platform credentials", async ({ page }) => {
        const superAdmin = new SuperAdminPage(page);

        await page.goto("super-admin/login");
        await superAdmin.emailInput.fill("wrong@example.test");
        await superAdmin.passwordInput.fill("definitely-not-valid");
        await superAdmin.signInButton.click();

        await expect(page).toHaveURL(/super-admin\/login/);
        await expect(page.getByText(/these credentials do not match|failed|invalid/i).first()).toBeVisible({
            timeout: 15000,
        });
    });

    test("logs in and shows operations overview", async ({ page }) => {
        const superAdmin = new SuperAdminPage(page);
        await superAdmin.login(PLATFORM_EMAIL, PLATFORM_PASSWORD);
        await superAdmin.expectDashboard();
        await expect(page.getByText(/trial subscriptions/i).first()).toBeVisible();
        await expect(page.getByText(/open invoices/i).first()).toBeVisible();
    });

    test("authenticated operator can open core SaaS pages", async ({ superAdminPage }) => {
        const page = superAdminPage;
        const superAdmin = new SuperAdminPage(page);

        await page.goto("super-admin");
        await superAdmin.expectDashboard();

        const pages: Array<{ path: string; heading: RegExp }> = [
            { path: "super-admin/tenants", heading: /tenants/i },
            { path: "super-admin/plans", heading: /plans/i },
            { path: "super-admin/plan-features", heading: /plan features/i },
            { path: "super-admin/feature-overrides", heading: /tenant feature overrides|feature overrides/i },
            { path: "super-admin/subscriptions", heading: /subscriptions/i },
            { path: "super-admin/invoices", heading: /invoices/i },
            { path: "super-admin/payments", heading: /payments/i },
            { path: "super-admin/system-health", heading: /system health/i },
            { path: "super-admin/tenant-backups", heading: /tenant backups/i },
            { path: "super-admin/platform-settings", heading: /platform settings/i },
            { path: "super-admin/manage-two-factor-authentication", heading: /two-factor authentication/i },
            { path: "super-admin/activity-logs", heading: /audit events/i },
        ];

        for (const item of pages) {
            await page.goto(item.path);
            await expect(page).not.toHaveURL(/super-admin\/login/);
            await expect(page.getByRole("heading", { name: item.heading }).first()).toBeVisible({
                timeout: 20000,
            });
        }
    });

    test("system health page can trigger checks", async ({ superAdminPage }) => {
        const page = superAdminPage;

        await page.goto("super-admin/system-health");
        await expect(page.getByRole("heading", { name: /system health/i })).toBeVisible();

        const runButton = page.getByRole("button", { name: /run health checks/i });
        await expect(runButton).toBeVisible();
        await runButton.click();

        await expect(
            page.getByText(/health checks completed|landlord database|stripe/i).first(),
        ).toBeVisible({ timeout: 30000 });
    });

    test("platform settings page exposes billing and security defaults", async ({ superAdminPage }) => {
        const page = superAdminPage;

        await page.goto("super-admin/platform-settings");
        await expect(page.getByRole("heading", { name: /platform settings/i })).toBeVisible();
        await expect(page.getByText(/billing defaults/i)).toBeVisible();
        await expect(page.getByText(/enforce active subscription/i)).toBeVisible();
        await expect(page.getByRole("button", { name: /save platform settings/i })).toBeVisible();
    });

    test("guest is redirected from protected SaaS routes to login", async ({ page }) => {
        await page.goto("super-admin/tenants");
        await expect(page).toHaveURL(/super-admin\/login/);
        await expect(page.getByRole("button", { name: /sign in/i })).toBeVisible();
    });
});
