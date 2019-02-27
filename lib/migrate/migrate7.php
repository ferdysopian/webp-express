<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\Messenger;

function webpexpress_migrate7() {

    $config = Config::loadConfigAndFix();
    if ($config['operation-mode'] == 'just-redirect') {
        $config['operation-mode'] = 'no-conversion';
    }
    if ($config['operation-mode'] == 'no-varied-responses') {
        $config['operation-mode'] = 'cdn-friendly';
    }
    if ($config['operation-mode'] == 'varied-responses') {
        $config['operation-mode'] = 'varied-image-responses';
    }
    if ($config['do-not-pass-source-in-query-string']) {
        $config['method-for-passing-source'] = 'request-header';
    } else {
        $config['method-for-passing-source'] = 'querystring-full-path';
    }

    // Migrate some configurations to the new "No conversion" mode
    if ((!$config['enable-redirection-to-webp-realizer']) && (!$config['enable-redirection-to-converter']) && ($config['destination-folder'] == 'mingled') && ($config['operation-mode'] == 'cdn-friendly') && (!($config['web-service']['enabled']))) {
        $config['operation-mode'] = 'no-conversion';
    }

    // The ones who had disabled "do-not-pass-source-in-query-string", must have done it because they wanted to
    // pass through querystring. - So set the new "method" option to that
    if (!$config['do-not-pass-source-in-query-string']) {
        $config['method-for-passing-source'] = 'querystring-full-path';
    }

    // In next migration, we can remove do-not-pass-source-in-query-string
    // unset($config['do-not-pass-source-in-query-string']);
    // and also do: grep -r 'do-not-pass' .

    if (Config::saveConfigurationFileAndWodOptions($config)) {

        $msg = 'Successfully migrated <i>WebP Express</i> options for 0.12. ';

        if (!$config['alter-html']['enabled']) {
            if ($config['operation-mode'] == 'varied-responses') {
                $msg .= '<br>In WebP Express 0.12, the <i>Alter HTML</i> option is no longer in beta. ' .
                    '<i>You should consider to go and <a href="' . Paths::getSettingsUrl() . '">activate it</a></i> - ' .
                    'It works great in <i>Varied Image Responses</i> mode too. ';
            } else {
                $msg .= '<br>In WebP Express 0.12, Alter HTML is no longer in beta. ' .
                    '<i>Now would be a good time to <a href="' . Paths::getSettingsUrl() . '">go and activate it!</a></i>. ';
            }
        }

        // Display announcement. But only show while it is fresh news (we don't want this to show when one is upgrading from 0.11 to 0.14 or something)
        // - the next release with a migration in it will not show the announcement
        if (WEBPEXPRESS_MIGRATION_VERSION == 7) {
            $msg .= '<br><br>Btw: From this release and onward, WebP Express is <i>multisite compliant</i>.';
        }

        Messenger::addMessage(
            'info',
            $msg
        );

        if ($config['operation-mode'] == 'no-conversion') {
            Messenger::addMessage(
                'info',
                'WebP Express introduces a new operation mode: "No conversion". ' .
                    'Your configuration has been migrated to this mode, because your previous settings matched that mode (nothing where set up to trigger a conversion).'
            );
        }

        // PSST: When creating new migration files, remember to update WEBPEXPRESS_MIGRATION_VERSION in admin.php
        Option::updateOption('webp-express-migration-version', '7');

    } else {
        Messenger::addMessage(
            'error',
            'Failed migrating webp express options to 0.12+. Probably you need to grant write permissions in your wp-content folder.'
        );
    }

}

webpexpress_migrate7();