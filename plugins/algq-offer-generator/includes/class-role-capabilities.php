<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Role_Capabilities {
    public const ROLE = 'algq_offer_manager';

    public static function init(): void {}

    public static function capabilities(): array {
        return array(
            'read'                          => true,
            'upload_files'                  => true,
            'manage_algq_offers'            => true,
            'create_algq_offers'            => true,
            'edit_algq_offer'               => true,
            'read_algq_offer'               => true,
            'delete_algq_offer'             => true,
            'edit_algq_offers'              => true,
            'edit_others_algq_offers'       => true,
            'publish_algq_offers'           => true,
            'read_private_algq_offers'      => true,
            'delete_algq_offers'            => true,
            'delete_private_algq_offers'    => true,
            'delete_published_algq_offers'  => true,
            'delete_others_algq_offers'     => true,
            'edit_private_algq_offers'      => true,
            'edit_published_algq_offers'    => true,
            'approve_algq_offers'           => true,
            'send_algq_offers'              => true,
            'generate_algq_offer_documents' => true,
            'view_algq_offer_history'       => true,
            'manage_algq_offer_templates'   => true,
        );
    }

    public static function install_roles(): void {
        $role = get_role( self::ROLE );
        if ( ! $role ) {
            add_role( self::ROLE, __( 'ARE Offer Manager', 'algq-offer-generator' ), array( 'read' => true ) );
            $role = get_role( self::ROLE );
        }

        if ( $role ) {
            foreach ( self::capabilities() as $capability => $grant ) {
                if ( $grant ) {
                    $role->add_cap( $capability );
                }
            }
        }

        foreach ( array( 'administrator', 'editor' ) as $role_name ) {
            $role = get_role( $role_name );
            if ( ! $role ) {
                continue;
            }
            foreach ( self::capabilities() as $capability => $grant ) {
                if ( $grant ) {
                    $role->add_cap( $capability );
                }
            }
        }
    }
}
