import { expect, test } from "../../fixtures/AdminFixtures";

import { buildRoleData, RolesPage, type RoleData } from "../../pages/settings/RolesPage";
import { buildUserData, UsersPage, type UserData } from "../../pages/settings/UsersPage";
import {
    ADMIN_EMAIL,
    ADMIN_PASSWORD,
    Session,
    TEST_PASSWORD,
    closeSessions,
    expectAllowed,
    expectDenied,
    getRoute,
    runSuffix,
    signInExpectingSuccess,
} from "../../utils/acl";

type ValidationPermissionCase = {
    permission: string;
    role: RoleData;
    user: UserData;
    session?: Session;
};

const validationPermissions = [
    "leads.create",
    "leads.edit",
    "contacts.persons.create",
    "contacts.persons.edit",
    "settings.automation.attributes",
    "settings.automation.attributes.edit",
];

const permittedAccounts: ValidationPermissionCase[] = validationPermissions.map((permission) => {
    const role = buildRoleData({ permissions: [permission] });

    return {
        permission,
        role,
        user: buildUserData({
            roleName: role.name,
            viewPermission: "global",
        }),
    };
});

const deniedRole = buildRoleData({ permissions: ["leads.view"] });
const deniedUser = buildUserData({
    roleName: deniedRole.name,
    viewPermission: "global",
});

async function checkUniqueEmailValidation(session: Session) {
    const query = new URLSearchParams({
        entity_type: "persons",
        attribute_code: "emails",
        attribute_value: `playwright-${runSuffix()}@example.test`,
    });

    return getRoute(
        session,
        `admin/settings/attributes/check-unique-validation?${query.toString()}`
    );
}

test.describe.serial("attribute validation permission exception", () => {
    let deniedSession: Session | undefined;

    test.beforeAll(async ({ browser, baseURL }) => {
        const admin = await signInExpectingSuccess(browser, baseURL, ADMIN_EMAIL, ADMIN_PASSWORD);

        try {
            const rolesPage = new RolesPage(admin.page);
            const usersPage = new UsersPage(admin.page);

            for (const account of permittedAccounts) {
                await rolesPage.createRole(account.role);
            }

            await rolesPage.createRole(deniedRole);

            for (const account of permittedAccounts) {
                await usersPage.createUser(account.user);
            }

            await usersPage.createUser(deniedUser);
        } finally {
            await closeSessions(admin);
        }

        for (const account of permittedAccounts) {
            account.session = await signInExpectingSuccess(
                browser,
                baseURL,
                account.user.email,
                TEST_PASSWORD
            );
        }

        deniedSession = await signInExpectingSuccess(
            browser,
            baseURL,
            deniedUser.email,
            TEST_PASSWORD
        );
    });

    test.afterAll(async () => {
        await closeSessions(
            ...permittedAccounts.map((account) => account.session),
            deniedSession
        );
    });

    for (const account of permittedAccounts) {
        test(`allows unique email validation with '${account.permission}'`, async () => {
            const response = await checkUniqueEmailValidation(account.session!);

            expectAllowed(response, `unique email validation with '${account.permission}'`);

            const result = await response.json();

            expect(result).toMatchObject({ validated: true });
        });
    }

    test("denies unique email validation without a create or edit permission", async () => {
        const response = await checkUniqueEmailValidation(deniedSession!);

        expectDenied(response, "unique email validation without an eligible permission");
    });
});
