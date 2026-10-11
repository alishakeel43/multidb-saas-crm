import { expect, type Locator, type Page } from "@playwright/test";

export class SuperAdminPage {
    readonly page: Page;
    readonly emailInput: Locator;
    readonly passwordInput: Locator;
    readonly signInButton: Locator;

    constructor(page: Page) {
        this.page = page;
        this.emailInput = page.locator('input[id="data.email"], input[type="email"]').first();
        this.passwordInput = page.locator('input[id="data.password"], input[type="password"]').first();
        this.signInButton = page.getByRole("button", { name: /sign in/i });
    }

    async login(email: string, password: string): Promise<void> {
        await this.page.goto("super-admin/login");
        await expect(this.emailInput).toBeVisible();
        await this.emailInput.fill(email);
        await this.passwordInput.fill(password);
        await this.signInButton.click();
        await this.page.waitForURL(/super-admin(?!\/login)/);
    }

    async expectDashboard(): Promise<void> {
        await expect(this.page).toHaveURL(/super-admin/);
        await expect(
            this.page.getByRole("heading", { name: /operations overview/i }),
        ).toBeVisible();
        await expect(this.page.getByText(/tenant workspaces/i).first()).toBeVisible();
    }

    async openNav(label: string | RegExp): Promise<void> {
        await this.page.getByRole("link", { name: label }).first().click();
    }

    async expectPageHeading(name: string | RegExp): Promise<void> {
        await expect(this.page.getByRole("heading", { name })).toBeVisible();
    }

    async gotoConsole(path = "super-admin"): Promise<void> {
        await this.page.goto(path);
        await expect(this.page).not.toHaveURL(/super-admin\/login/);
    }

    async fillCreateTenantForm(input: {
        name: string;
        slug: string;
        adminName: string;
        adminEmail: string;
        adminPassword: string;
    }): Promise<void> {
        await this.page.getByRole("textbox", { name: "Name*", exact: true }).fill(input.name);
        await this.page.getByRole("textbox", { name: "Slug*" }).fill(input.slug);
        await this.page.getByRole("textbox", { name: "Tenant administrator name*" }).fill(input.adminName);
        await this.page.getByRole("textbox", { name: "Tenant administrator email*" }).fill(input.adminEmail);
        await this.page.getByRole("textbox", { name: /Initial tenant administrator/i }).fill(input.adminPassword);
    }

    async expectFeatureMatrix(): Promise<void> {
        await expect(this.page.getByRole("checkbox", { name: /^Contacts$/i }).first()).toBeVisible();
        await expect(this.page.getByText(/persons and organizations/i).first()).toBeVisible();
        await expect(this.page.getByRole("checkbox", { name: /Leads/i }).first()).toBeVisible();
        await expect(this.page.getByRole("checkbox", { name: /Quotes/i }).first()).toBeVisible();
        await expect(this.page.getByRole("checkbox", { name: /^Organizations$/i })).toHaveCount(0);
    }
}
