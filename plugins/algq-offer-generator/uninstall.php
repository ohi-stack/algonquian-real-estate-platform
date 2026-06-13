<?php
if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

$options = array(
    'algq_offer_generator_settings',
);

foreach ($options as $option) {
    delete_option($option);
}
