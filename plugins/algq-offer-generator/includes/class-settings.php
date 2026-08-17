<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Settings {
    public static function init(): void {
        add_action( 'admin_init', array( __CLASS__, 'register' ) );
    }

    public static function register(): void {
        register_setting(
            'algq_offer_settings',
            'algq_offer_default_strategy',
            array(
                'type'              => 'string',
                'default'           => 'cash',
                'sanitize_callback' => static function ( $value ): string {
                    $value = sanitize_key( $value );
                    return in_array( $value, ALGQ_Offer_Service::strategies(), true ) ? $value : 'cash';
                },
            )
        );
        register_setting(
            'algq_offer_settings',
            'algq_offer_company_name',
            array(
                'type'              => 'string',
                'default'           => 'Algonquian Real Estate LLC',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
        register_setting(
            'algq_offer_settings',
            'algq_offer_delete_data_on_uninstall',
            array(
                'type'              => 'boolean',
                'default'           => false,
                'sanitize_callback' => static fn( $value ): bool => (bool) $value,
            )
        );
    }
}
