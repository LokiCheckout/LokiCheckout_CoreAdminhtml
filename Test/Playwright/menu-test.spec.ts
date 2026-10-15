import {test, expect} from '@loki/test';
import type {Page} from '@playwright/test';
import config, {themes, withTheme} from './config/config';
import {AdminLogin, ModuleOverviewPage, SystemCheckPage, configureWithTheme} from './helpers/admin-objects';

async function openLokiCheckoutMenu(page: Page) {
    const systemMenu = page.locator('#menu-magento-backend-system');
    const lokiCheckoutMenu = systemMenu.locator('[data-ui-id="menu-lokicheckout-coreadminhtml-index"]');

    await page.evaluate(() => window.scrollTo(0, 0));

    // The admin menu toggles on click and initializes asynchronously, so retry until it is open
    await expect(async () => {
        if (!await lokiCheckoutMenu.isVisible()) {
            await systemMenu.locator('> a').click();
        }

        await expect(lokiCheckoutMenu).toBeVisible({timeout: 2000});
    }).toPass({timeout: 20000});

    return lokiCheckoutMenu;
}

test.describe('LokiCheckout_CoreAdminhtml admin menu', () => {
    for (const theme of themes) {
        test(`opens the LokiCheckout pages via the System menu (${theme.name})`, async ({page}) => {
            await configureWithTheme(page, withTheme(config, theme));
            await new AdminLogin(page).login();

            let lokiCheckoutMenu = await openLokiCheckoutMenu(page);
            await expect(lokiCheckoutMenu).toContainText('LokiCheckout');

            const moduleOverviewLink = lokiCheckoutMenu.locator('[data-ui-id="menu-lokicheckout-coreadminhtml-modules"] a');
            await expect(moduleOverviewLink).toHaveText(/Module Overview/);
            await expect(lokiCheckoutMenu.locator('[data-ui-id="menu-lokicheckout-coreadminhtml-check"] a'))
                .toHaveText(/System Check/);

            // Activate links via the keyboard, because Playwright considers the fixed submenu panel outside the viewport
            await moduleOverviewLink.press('Enter');
            await new ModuleOverviewPage(page).expectToBeLoaded();

            lokiCheckoutMenu = await openLokiCheckoutMenu(page);
            await lokiCheckoutMenu.locator('[data-ui-id="menu-lokicheckout-coreadminhtml-check"] a').press('Enter');
            await new SystemCheckPage(page).expectToBeLoaded();
        });
    }
});
