import coreConfig from '@loki/config';
import merge from '@loki/util/merge';

export type Theme = {
    name: string;
    theme: string;
};

export const themes: Theme[] = [
    {name: 'Luma', theme: 'Magento/luma'},
    {name: 'Hyvä', theme: 'Loki/hyva-dev'},
];

export const withTheme = (config: any, theme: Theme) => merge(config, {theme: theme.theme});

const config = merge(coreConfig, {
    modules: [
        'LokiCheckout_CoreAdminhtml',
    ],
    config: {
        'admin/captcha/enable': 0,
        'admin/security/use_form_key': 0,
        'customer/address/street_lines': 3,
        'general/store_information/country_id': 'NL',
        'general/store_information/merchant_vat_number': 'NL123456789B01',
        'loki_checkout/module_overview/show_registered': 0,
    }
});

// Street lines are not lowered here, because existing quote addresses would then fail validation
export const invalidConfig = merge(config, {
    config: {
        'general/store_information/country_id': '',
        'general/store_information/merchant_vat_number': '',
    }
});

export const showRegisteredConfig = merge(config, {
    config: {
        'loki_checkout/module_overview/show_registered': 1,
    }
});

export default config;
