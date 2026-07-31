<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Capabilities {
    public static function all(): array {
        return array(
            'view_algq_deals',
            'create_algq_deals',
            'edit_algq_deals',
            'assign_algq_deals',
            'transition_algq_deals',
            'archive_algq_deals',
            'manage_algq_pipeline',
            'export_algq_deals',
        );
    }

    public static function install(): void {
        $role = get_role( 'administrator' );
        if ( $role ) {
            foreach ( self::all() as $capability ) {
                $role->add_cap( $capability );
            }
        }

        $manager = get_role( 'algq_acquisition_manager' );
        if ( ! $manager ) {
            $manager = add_role( 'algq_acquisition_manager', 'Acquisition Manager', array( 'read' => true ) );
        }
        if ( $manager ) {
            foreach ( self::all() as $capability ) {
                if ( 'manage_algq_pipeline' !== $capability ) {
                    $manager->add_cap( $capability );
                }
            }
        }
    }
}
