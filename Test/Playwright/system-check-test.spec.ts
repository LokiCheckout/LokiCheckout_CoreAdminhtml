import {test, expect} from '@loki/test';
import config, {invalidConfig, themes, withTheme} from './config/config';
import {AdminLogin, SystemCheckPage, configureWithTheme} from './helpers/admin-objects';

test.describe('LokiCheckout_CoreAdminhtml system check', () => {
    for (const theme of themes) {
        test(`shows all system checks (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            const systemCheckPage = new SystemCheckPage(page);
            await systemCheckPage.open();

            const headers = systemCheckPage.getTable().locator('thead th');
            await expect(headers).toHaveCount(3);
            await expect(headers.nth(0)).toContainText('System Check');
            await expect(headers.nth(1)).toContainText('Value');
            await expect(headers.nth(2)).toContainText('Recommendation');

            for (const label of [
                'LokiCheckout_Core',
                'LokiCheckout_DevTools',
                'Loki_Debugger',
                'general/store_information/country_id',
                'general/store_information/merchant_vat_number',
                'customer/address/street_lines',
                'csp/mode/storefront_checkout_index_index/report_only',
            ]) {
                // Modules may be listed both as dev module and as module dependency
                await expect(systemCheckPage.getRow(label)).not.toHaveCount(0);
            }
        });

        test(`marks valid values as notice (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            const systemCheckPage = new SystemCheckPage(page);
            await systemCheckPage.open();

            const streetLines = systemCheckPage.getValue('customer/address/street_lines');
            await expect(streetLines).toHaveText('3');
            await expect(streetLines).toHaveClass(/system-check-notice/);

            const vatNumber = systemCheckPage.getValue('general/store_information/merchant_vat_number');
            await expect(vatNumber).toHaveText('NL123456789B01');
            await expect(vatNumber).toHaveClass(/system-check-notice/);

            const countryId = systemCheckPage.getValue('general/store_information/country_id');
            await expect(countryId).toHaveText('NL');
            await expect(countryId).toHaveClass(/system-check-notice/);
        });

        test(`marks invalid values as error (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(invalidConfig, theme));
            await new AdminLogin(page).login();

            const systemCheckPage = new SystemCheckPage(page);
            await systemCheckPage.open();

            const countryId = systemCheckPage.getValue('general/store_information/country_id');
            await expect(countryId).toHaveClass(/system-check-error/);
            await expect(systemCheckPage.getRow('general/store_information/country_id'))
                .toContainText('Make sure to enter a valid country for this store');

            const vatNumber = systemCheckPage.getValue('general/store_information/merchant_vat_number');
            await expect(vatNumber).toHaveClass(/system-check-error/);
            await expect(systemCheckPage.getRow('general/store_information/merchant_vat_number'))
                .toContainText('Make sure to enter a valid VAT number');

            const streetLines = systemCheckPage.getValue('customer/address/street_lines');
            await expect(streetLines).toHaveClass(/system-check-notice/);
        });

        test(`links address lines to the customer configuration (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            const systemCheckPage = new SystemCheckPage(page);
            await systemCheckPage.open();

            const link = systemCheckPage.getRow('customer/address/street_lines').locator('a');
            await expect(link).toHaveAttribute('href', /section\/customer\/.*#customer_address$/);

            await link.click();
            await expect(page).toHaveURL(/section\/customer/);
            await expect(page.locator('#customer_address-head')).toBeVisible();
        });
    }
});
