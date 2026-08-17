<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Capabilities {
    public static function register(): void {
        $administrator = get_role( 'administrator' );
        if ( $administrator ) {
            $administrator->add_cap( ALGQ_WCB_Security::CAP_MANAGE );
            $administrator->add_cap( ALGQ_WCB_Security::CAP_VIEW );
        }
        foreach ( array( 'customer', 'algq_buyer' ) as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                $role->add_cap( ALGQ_WCB_Security::CAP_VIEW );
            }
        }
    }
}
