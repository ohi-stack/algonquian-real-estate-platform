<?php
/**
 * Plugin Name: Algonquian Pipeline CRM
 * Plugin URI: https://algonquianrealestate.com/algonquian-pipeline-crm/
 * Description: Canonical deal records plus the shared ARE relationship CRM layer for contacts, organizations, activities, tasks and controlled links to specialized platform records.
 * Version: 2.2.1
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-pipeline-crm
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_PIPELINE_VERSION', '2.2.1' );
define( 'ALGQ_PIPELINE_SCHEMA_VERSION', '2.2.0' );
define( 'ALGQ_PIPELINE_FILE', __FILE__ );
define( 'ALGQ_PIPELINE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_PIPELINE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_PIPELINE_DIR . 'includes/class-stages.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-database.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-capabilities.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-repository.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-crm-repository.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-service.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-crm-service.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-migrator.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-rest.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-admin.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'ALGQ_Pipeline_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Pipeline_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'ALGQ_Pipeline_Plugin', 'boot' ), 20 );

add_action(
    'plugins_loaded',
    static function (): void {
        if ( ! interface_exists( 'ARE_Platform_Service_Interface' ) || ! function_exists( 'algq_platform_register_service' ) ) {
            return;
        }

        require_once ALGQ_PIPELINE_DIR . 'includes/class-platform-service.php';
        $result = algq_platform_register_service( new ALGQ_Pipeline_Platform_Service() );
        if ( is_wp_error( $result ) ) {
            do_action(
                'algq_audit_event',
                'pipeline.service_registration_failed',
                array(
                    'plugin'     => 'algq-pipeline-crm',
                    'error_code' => $result->get_error_code(),
                )
            );
        }
    },
    25
);

/**
 * Return one canonical deal record.
 *
 * @param int|string $identifier Numeric ID, UUID, or deal number.
 * @return array|null
 */
function algq_get_deal( $identifier ) {
    return ALGQ_Pipeline_Service::instance()->get_deal( $identifier );
}

/**
 * Create a canonical deal. Repeated source identifiers are idempotent.
 *
 * @param array $data Deal attributes.
 * @return array|WP_Error
 */
function algq_pipeline_create_deal( array $data ) {
    return ALGQ_Pipeline_Service::instance()->create_deal( $data );
}

/**
 * Transition a deal through the controlled lifecycle.
 *
 * @param int    $deal_id Deal ID.
 * @param string $stage Target stage key.
 * @param array  $context Transition context.
 * @return array|WP_Error
 */
function algq_pipeline_transition_deal( $deal_id, $stage, array $context = array() ) {
    return ALGQ_Pipeline_Service::instance()->transition( absint( $deal_id ), sanitize_key( $stage ), $context );
}

/**
 * Return one shared CRM contact.
 *
 * @param int|string $identifier Numeric ID or UUID.
 * @return array|null
 */
function algq_crm_get_contact( $identifier ) {
    return ALGQ_Pipeline_CRM_Service::instance()->get_contact( $identifier );
}

/**
 * Create or update a shared CRM contact using an optional source identity.
 *
 * @param array $data Shared contact fields.
 * @return array|WP_Error
 */
function algq_crm_upsert_contact( array $data ) {
    return ALGQ_Pipeline_CRM_Service::instance()->upsert_contact( $data );
}

/**
 * Create a shared CRM organization.
 *
 * @param array $data Organization fields.
 * @return array|WP_Error
 */
function algq_crm_create_organization( array $data ) {
    return ALGQ_Pipeline_CRM_Service::instance()->create_organization( $data );
}

/**
 * Link a shared CRM contact or organization to a Deal or authoritative external record.
 *
 * @param array $data Relationship fields.
 * @return array|WP_Error
 */
function algq_crm_link_relationship( array $data ) {
    return ALGQ_Pipeline_CRM_Service::instance()->link_relationship( $data );
}

/**
 * Record a relationship activity.
 *
 * @param int   $contact_id CRM contact ID.
 * @param array $data Activity fields.
 * @return array|WP_Error
 */
function algq_crm_add_activity( int $contact_id, array $data ) {
    return ALGQ_Pipeline_CRM_Service::instance()->add_activity( $contact_id, $data );
}

/**
 * Create a non-Deal relationship task.
 *
 * Deal-specific work must continue to use the canonical Pipeline Deal task system.
 *
 * @param array $data Task fields.
 * @return array|WP_Error
 */
function algq_crm_create_task( array $data ) {
    return ALGQ_Pipeline_CRM_Service::instance()->create_task( $data );
}
