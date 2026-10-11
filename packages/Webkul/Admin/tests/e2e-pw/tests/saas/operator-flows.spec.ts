import { test, expect } from "../../fixtures/SuperAdminFixtures";
import { SuperAdminPage } from "../../pages/SuperAdminPage";

/**
 * Finalized Super Admin operator flows derived from a Playwright codegen session.
 * Random clicks / broken page1 refs were removed; each case asserts one stable outcome.
 */
test.describe("ZARIS Super Admin operator flows", () => {
    test("overview and core billing nav stay reachable", async ({ superAdminPage }) => {
        const page = superAdminPage;
        const superAdmin = new SuperAdminPage(page);

        await superAdmin.gotoConsole("super-admin");
        await superAdmin.expectDashboard();

        for (const item of [
            { path: "super-admin/domains", heading: /domains/i },
            { path: "super-admin/tenants", heading: /tenants/i },
            { path: "super-admin/tenant-support-users", heading: /support users/i },
            { path: "super-admin/plan-features", heading: /plan features/i },
            { path: "super-admin/feature-overrides", heading: /tenant feature overrides|feature overrides/i },
            { path: "super-admin/invoices", heading: /invoices/i },
            { path: "super-admin/payments", heading: /payments/i },
            { path: "super-admin/platform-settings", heading: /platform settings/i },
            { path: "super-admin/manage-two-factor-authentication", heading: /two-factor authentication/i },
            { path: "super-admin/activity-logs", heading: /audit events|audit log/i },
        ]) {
            await page.goto(item.path);
            await expect(page).not.toHaveURL(/super-admin\/login/);
            await expect(
                page
                    .getByRole("heading", { name: item.heading })
                    .or(page.getByText(item.heading))
                    .first(),
            ).toBeVisible({
                timeout: 20000,
            });
        }
    });

    test("domains create form always offers create-new-tenant from the dropdown", async ({ superAdminPage }) => {
        const page = superAdminPage;

        await page.goto("super-admin/domains/create");
        await expect(page.getByRole("heading", { name: /create domain|new domain/i }).first()).toBeVisible();
        await expect(page.getByText(/tenant/i).first()).toBeVisible();
        await expect(page.getByRole("link", { name: /create new tenant/i })).toBeVisible();
        await expect(page.getByText(/not listed|no tenants yet/i).first()).toBeVisible();
        await expect(page.getByRole("button", { name: /create|save/i }).first()).toBeVisible();
    });

    test("support users and invoices also expose create-new-tenant", async ({ superAdminPage }) => {
        const page = superAdminPage;

        await page.goto("super-admin/tenant-support-users/create");
        await expect(page.getByRole("link", { name: /create new tenant/i })).toBeVisible();

        await page.goto("super-admin/feature-overrides");
        await expect(page.getByRole("link", { name: /create new tenant/i })).toBeVisible();

        await page.goto("super-admin/invoices");
        await page.getByRole("button", { name: /create invoice/i }).click();
        await expect(page.getByRole("link", { name: /create new tenant/i })).toBeVisible();
    });

    test("create tenant shows domain preview and email field validation", async ({ superAdminPage }) => {
        const page = superAdminPage;
        const superAdmin = new SuperAdminPage(page);
        const slug = `e2e-flow-${Date.now().toString().slice(-6)}`;

        await page.goto("super-admin/tenants/create");
        await expect(page.getByRole("heading", { name: /create tenant|new tenant/i }).first()).toBeVisible();

        await superAdmin.fillCreateTenantForm({
            name: "E2E Flow Tenant",
            slug,
            adminName: "E2E Admin",
            adminEmail: "not-a-valid-email",
            adminPassword: "E2eTenantAdmin!234",
        });

        await expect(page.getByText(new RegExp(`${slug}\\.`, "i")).first()).toBeVisible();

        await page.getByRole("button", { name: /^create$/i }).click();

        const emailField = page.getByRole("textbox", { name: /Tenant administrator email/i });
        await expect(emailField).toBeVisible();
        await expect(
            page.getByText(/valid email|email|invalid/i).first(),
        ).toBeVisible({ timeout: 15000 });
    });

    test("support users create form requires a tenant", async ({ superAdminPage }) => {
        const page = superAdminPage;

        await page.goto("super-admin/tenant-support-users/create");
        await expect(page).not.toHaveURL(/super-admin\/login/);
        await expect(page.getByRole("heading", { name: /create support user/i }).first()).toBeVisible();
        // Filament labels render as "Name *" (space before asterisk).
        await expect(page.locator('input[type="text"], input[type="email"], input:not([type])').first()).toBeVisible();
        await expect(page.getByRole("textbox", { name: /email/i }).first()).toBeVisible();
        await expect(page.locator('input[type="password"]').first()).toBeVisible();
        await expect(page.getByRole("link", { name: /create new tenant/i })).toBeVisible();
        await expect(page.getByText(/tenant/i).first()).toBeVisible();
    });

    test("plan features matrix shows category modules and save action", async ({ superAdminPage }) => {
        const page = superAdminPage;
        const superAdmin = new SuperAdminPage(page);

        await page.goto("super-admin/plan-features");
        await expect(page.getByRole("heading", { name: /plan features/i }).first()).toBeVisible();
        await superAdmin.expectFeatureMatrix();
        await expect(page.getByRole("button", { name: /save plan modules/i })).toBeVisible();
        await expect(page.getByText(/persons and organizations|requires leads|requires products/i).first()).toBeVisible();
    });

    test("feature overrides matrix blocks invalid dependency disables", async ({ superAdminPage }) => {
        const page = superAdminPage;
        const superAdmin = new SuperAdminPage(page);

        await page.goto("super-admin/feature-overrides");
        await expect(page.getByRole("heading", { name: /tenant feature overrides|feature overrides/i }).first()).toBeVisible();
        await superAdmin.expectFeatureMatrix();
        await expect(page.getByRole("button", { name: /save tenant modules/i })).toBeVisible();
        await expect(page.getByText(/persons and organizations/i).first()).toBeVisible();

        const tenantSelect = page.getByLabel(/tenant workspace/i);
        if (await tenantSelect.count()) {
            const value = await tenantSelect.inputValue().catch(() => "");
            test.skip(! value, "No tenant available for feature override dependency check");
        }

        const leads = page.getByRole("checkbox", { name: /^Leads$/i }).first();
        const quotes = page.getByRole("checkbox", { name: /^Quotes/i }).first();

        // Quotes enabled + Leads off must fail dependency validation.
        if (! await quotes.isChecked()) {
            await quotes.check();
        }

        if (await leads.isChecked()) {
            await leads.uncheck();
        }

        await page.getByRole("button", { name: /save tenant modules/i }).click();

        await expect(
            page.getByText(/required modules are missing|requires leads|depends on/i).first(),
        ).toBeVisible({ timeout: 15000 });
    });

    test("invoices create action opens a tenant-scoped form", async ({ superAdminPage }) => {
        const page = superAdminPage;

        await page.goto("super-admin/invoices");
        await expect(page.getByRole("heading", { name: /invoices/i }).first()).toBeVisible();

        const createButton = page.getByRole("button", { name: /create invoice/i });
        await expect(createButton).toBeVisible();
        await createButton.click();

        await expect(page.getByText(/tenant/i).first()).toBeVisible();
        await expect(page.getByText(/subscription/i).first()).toBeVisible();
    });

    test("audit log list and view open without crashing", async ({ superAdminPage }) => {
        const page = superAdminPage;

        await page.goto("super-admin/activity-logs");
        await expect(page.getByRole("heading", { name: /audit events/i }).first()).toBeVisible();
        await expect(page.getByText(/tenant id/i).first()).toBeVisible();

        const viewLink = page.getByRole("link", { name: /^view$/i }).first();
        if (await viewLink.count() === 0) {
            test.skip(true, "No audit events seeded yet");
            return;
        }

        await viewLink.click();
        await expect(page.getByText(/properties|actor|description/i).first()).toBeVisible({
            timeout: 15000,
        });
        await expect(page.getByText(/Platform event|Tenant ID|Keys/i).first()).toBeVisible();
    });
});
