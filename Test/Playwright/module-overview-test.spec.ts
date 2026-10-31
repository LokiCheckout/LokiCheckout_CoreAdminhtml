import {test, expect} from '@loki/test';
import config, {showRegisteredConfig, themes, withTheme} from './config/config';
import {AdminLogin, ModuleOverviewPage, configureWithTheme} from './helpers/admin-objects';

test.describe('LokiCheckout_CoreAdminhtml module overview', () => {
    for (const theme of themes) {
        test(`lists the Loki modules (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            const moduleOverviewPage = new ModuleOverviewPage(page);
            await moduleOverviewPage.open();

            const rowCount = await moduleOverviewPage.getRows().count();
            expect(rowCount).toBeGreaterThan(0);
            await expect(moduleOverviewPage.getCounter()).toHaveText(new RegExp('^\\s*' + rowCount + ' modules found\\s*$'));

            const ownRow = moduleOverviewPage.getRow('LokiCheckout_CoreAdminhtml');
            await expect(ownRow).toHaveCount(1);
            await expect(ownRow.locator('td').nth(1)).toContainText('Enabled');
            await expect(ownRow).toContainText('loki-checkout/magento2-core-adminhtml');
            await expect(ownRow.locator('td:first-child a')).toHaveAttribute(
                'href',
                'https://loki-checkout.com/docs/04-modules/LokiCheckout_CoreAdminhtml'
            );

            await expect(moduleOverviewPage.getRow('LokiCheckout_Core')).toHaveCount(1);
            await expect(moduleOverviewPage.getRows().filter({hasText: /^\s*Magento_/})).toHaveCount(0);
        });

        test(`opens the module documentation safely in a new tab (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            const moduleOverviewPage = new ModuleOverviewPage(page);
            await moduleOverviewPage.open();

            const links = moduleOverviewPage.getTable().locator('tbody td:first-child a');
            expect(await links.count()).toBeGreaterThan(0);
            for (const link of await links.all()) {
                await expect(link).toHaveAttribute('target', '_blank');
                await expect(link).toHaveAttribute('rel', /\bnoopener\b/);
                await expect(link).toHaveAttribute('rel', /\bnoreferrer\b/);
            }
        });

        test(`shows a status badge for the newest version (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            const moduleOverviewPage = new ModuleOverviewPage(page);
            await moduleOverviewPage.open();

            const badge = moduleOverviewPage.getRow('LokiCheckout_CoreAdminhtml').locator('td').last().locator('.module-status');
            await expect(badge).toHaveCount(1);
            await expect(badge).toHaveClass(/module-status-(notice|warning|error)/);
            await expect(badge).not.toHaveText(/^\s*$/);
            await expect(badge).not.toHaveText(/[0-9a-f]{40}/);
        });

        test(`hides the registration column by default (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            const moduleOverviewPage = new ModuleOverviewPage(page);
            await moduleOverviewPage.open();

            await expect(moduleOverviewPage.getTable().locator('thead th')).toHaveCount(5);
            await expect(moduleOverviewPage.getHeader('LokiCheckout registered')).toHaveCount(0);
        });

        test(`shows the registration column when enabled (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(showRegisteredConfig, theme));
            await new AdminLogin(page).login();

            const moduleOverviewPage = new ModuleOverviewPage(page);
            await moduleOverviewPage.open();

            await expect(moduleOverviewPage.getTable().locator('thead th')).toHaveCount(6);
            await expect(moduleOverviewPage.getHeader('LokiCheckout registered')).toHaveCount(1);

            const ownRow = moduleOverviewPage.getRow('LokiCheckout_CoreAdminhtml');
            await expect(ownRow.locator('td').nth(2)).toHaveText(/Yes|No/);
        });
    }
});
