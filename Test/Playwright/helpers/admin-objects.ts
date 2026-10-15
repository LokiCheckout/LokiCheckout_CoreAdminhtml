import {expect, test, type Locator, type Page} from '@playwright/test';

const env = (names: string[], fallback: string): string => {
    for (const name of names) {
        const value = process.env[name]?.trim();
        if (value) {
            return value;
        }
    }

    return fallback;
};

export const adminPath = env(['MAGENTO_BACKEND_FRONTNAME', 'ADMIN_PATH'], 'admin');
export const adminUser = env(['MAGENTO_ADMIN_USER', 'ADMIN_USER'], 'admin@loki-checkout.com');
export const adminPassword = env(['MAGENTO_ADMIN_PASSWORD', 'ADMIN_PASSWORD'], 'P@ssw0rd1234');

/**
 * Apply configuration (Magento config, modules, theme) without redirecting to the checkout
 */
export async function configure(page: Page, config: any): Promise<any> {
    const projectName = test.info().project.name;
    const modules: string[] = Array.isArray(config.modules) ? [...config.modules] : [];
    if (projectName && !modules.includes(projectName)) {
        modules.unshift(projectName);
    }

    const token = process.env.TEST_TOKEN;
    if (!token) {
        throw new Error('TEST_TOKEN is not set');
    }

    const response = await page.request.post(
        '/loki_functional_tests/index/configure?token=' + encodeURIComponent(token),
        {form: {config: JSON.stringify({...config, modules})}}
    );

    const body = await response.text();
    let data: any;
    try {
        data = JSON.parse(body);
    } catch (error) {
        console.error('Expected JSON but received something else', body);
        throw new Error('Bad JSON formatting');
    }

    if (data.error) {
        throw new Error('Configure error: ' + data.error);
    }

    const moduleResult = data.result?.modules ?? {};
    const moduleErrors = [...(moduleResult.enable_errors ?? []), ...(moduleResult.disable_errors ?? [])];
    if (moduleErrors.length > 0) {
        throw new Error('Module status errors: ' + moduleErrors.join('; '));
    }

    return data.result ?? {};
}

/**
 * Configure the environment and skip the current test if the requested theme is not available
 */
export async function configureWithTheme(page: Page, config: any) {
    const result = await configure(page, config);
    const themeErrors: string[] = result.theme?.errors ?? [];
    test.skip(themeErrors.length > 0, themeErrors.join('; '));
}

export class AdminLogin {
    constructor(private readonly page: Page) {
    }

    async login() {
        await this.page.goto('/' + adminPath);
        await this.page.locator('#username').fill(adminUser);
        await this.page.locator('#login').fill(adminPassword);
        await this.page.locator('.action-login').click();
        await expect(this.page.locator('.page-header .admin-user')).toBeVisible();
        await dismissAdminPopups(this.page);
    }
}

/**
 * Close popups (release notification, admin usage tracking) that block clicks after logging in
 */
export async function dismissAdminPopups(page: Page) {
    await expect(page.locator('.page-wrapper [data-role="spinner"]:visible')).toHaveCount(0, {timeout: 15000});

    for (let attempt = 0; attempt < 5; attempt++) {
        const popup = page.locator('.modal-popup._show');
        if (await popup.count() === 0) {
            return;
        }

        const lastPopup = popup.last();
        const declineButton = lastPopup.locator('.action-secondary', {hasText: /Don't Allow/i});
        if (await declineButton.count() > 0) {
            await declineButton.first().click();
        } else {
            await lastPopup.locator('.action-close').first().click();
        }

        await expect(lastPopup).toBeHidden();
    }
}

export class SystemCheckPage {
    constructor(private readonly page: Page) {
    }

    async open() {
        await this.page.goto('/' + adminPath + '/loki_checkout/check/index/');
        await this.expectToBeLoaded();
    }

    async expectToBeLoaded() {
        await expect(this.page).toHaveTitle(/LokiCheckout - System Check/);
        await expect(this.getTable()).toBeVisible();
    }

    getTable(): Locator {
        return this.page.locator('.loki-system-check table');
    }

    getRow(label: string): Locator {
        return this.getTable().locator('tbody tr').filter({
            has: this.page.locator('td:first-child', {hasText: new RegExp('^\\s*' + label + '\\s*$')}),
        });
    }

    getValue(label: string): Locator {
        return this.getRow(label).locator('.system-check');
    }
}

export class ModuleOverviewPage {
    constructor(private readonly page: Page) {
    }

    async open() {
        await this.page.goto('/' + adminPath + '/loki_checkout/modules/index/');
        await this.expectToBeLoaded();
    }

    async expectToBeLoaded() {
        await expect(this.page).toHaveTitle(/LokiCheckout - Module Overview/);
        await expect(this.getTable()).toBeVisible();
    }

    getTable(): Locator {
        return this.page.locator('.admin__data-grid-wrap table');
    }

    getRows(): Locator {
        return this.getTable().locator('tbody tr');
    }

    getRow(moduleName: string): Locator {
        return this.getRows().filter({
            has: this.page.locator('td:first-child a', {hasText: new RegExp('^\\s*' + moduleName + '\\s*$')}),
        });
    }

    getCounter(): Locator {
        return this.page.getByText(/\d+ modules found/);
    }

    getHeader(text: string): Locator {
        return this.getTable().locator('thead th', {hasText: text});
    }
}
