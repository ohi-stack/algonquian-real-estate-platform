<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_Pages {
    public static function register_shortcodes(): void {
        add_shortcode( 'algq_automation_overview', array( __CLASS__, 'overview' ) );
        add_shortcode( 'algq_automation_getting_started', array( __CLASS__, 'getting_started' ) );
        add_shortcode( 'algq_automation_docs', array( __CLASS__, 'documentation' ) );
        add_shortcode( 'algq_automation_rules', array( __CLASS__, 'rules' ) );
    }

    public static function create_pages(): void {
        $pages = array(
            'plugin/automation-engine'       => array( 'Automation Engine', '[algq_automation_overview]' ),
            'plugin/automation-engine/start' => array( 'Getting Started With the Automation Engine', '[algq_automation_getting_started]' ),
            'plugin/automation-engine/docs'  => array( 'Automation Engine Documentation', '[algq_automation_docs]' ),
            'automation-rules'               => array( 'Automation Rules', '[algq_automation_rules]' ),
        );

        $ids = get_option( 'algq_automation_page_ids', array() );

        foreach ( $pages as $path => $definition ) {
            $parent_id = self::ensure_parent_path( dirname( $path ) );
            $existing  = get_page_by_path( $path );

            if ( ! $existing ) {
                $legacy = get_posts(
                    array(
                        'post_type'      => 'page',
                        'post_status'    => 'any',
                        'posts_per_page' => 1,
                        'meta_key'       => '_algq_generated_slug',
                        'meta_value'     => $path,
                        'fields'         => 'ids',
                    )
                );

                if ( $legacy ) {
                    wp_update_post(
                        array(
                            'ID'          => (int) $legacy[0],
                            'post_name'   => sanitize_title( basename( $path ) ),
                            'post_parent' => $parent_id,
                        )
                    );
                    $ids[ $path ] = (int) $legacy[0];
                    continue;
                }
            }

            if ( $existing ) {
                $ids[ $path ] = (int) $existing->ID;
                continue;
            }

            $content = '[vc_row full_width="stretch_row"][vc_column][vc_column_text]' . "\n" . $definition[1] . "\n" . '[/vc_column_text][/vc_column][/vc_row]';
            $page_id = wp_insert_post(
                array(
                    'post_title'   => sanitize_text_field( $definition[0] ),
                    'post_name'    => sanitize_title( basename( $path ) ),
                    'post_parent'  => $parent_id,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ),
                true
            );

            if ( ! is_wp_error( $page_id ) ) {
                $ids[ $path ] = (int) $page_id;
                update_post_meta( $page_id, '_algq_generated_page', 'automation-engine' );
                update_post_meta( $page_id, '_algq_generated_slug', $path );
            }
        }

        update_option( 'algq_automation_page_ids', $ids, false );
    }

    private static function ensure_parent_path( string $path ): int {
        if ( '.' === $path || '/' === $path || '' === $path ) {
            return 0;
        }

        $existing = get_page_by_path( $path );
        if ( $existing ) {
            return (int) $existing->ID;
        }

        $segments = array_filter( explode( '/', trim( $path, '/' ) ) );
        $parent   = 0;
        $built    = '';

        foreach ( $segments as $segment ) {
            $built    = ltrim( $built . '/' . $segment, '/' );
            $existing = get_page_by_path( $built );

            if ( $existing ) {
                $parent = (int) $existing->ID;
                continue;
            }

            $created = wp_insert_post(
                array(
                    'post_title'  => ucwords( str_replace( '-', ' ', $segment ) ),
                    'post_name'   => sanitize_title( $segment ),
                    'post_parent' => $parent,
                    'post_status' => 'publish',
                    'post_type'   => 'page',
                ),
                true
            );

            if ( is_wp_error( $created ) ) {
                return $parent;
            }

            $parent = (int) $created;
        }

        return $parent;
    }

    public static function overview(): string {
        return self::wrap(
            __( 'Algonquian Automation Engine', 'algq-automation-engine' ),
            __( 'Build controlled trigger, condition, and action workflows with durable queues, retries, dead-letter handling, idempotency, and audit logs.', 'algq-automation-engine' )
        );
    }

    public static function getting_started(): string {
        $body = '<ol><li>' . esc_html__( 'Review available triggers and actions.', 'algq-automation-engine' ) . '</li>';
        $body .= '<li>' . esc_html__( 'Create a draft rule and define JSON conditions.', 'algq-automation-engine' ) . '</li>';
        $body .= '<li>' . esc_html__( 'Run a manual test event.', 'algq-automation-engine' ) . '</li>';
        $body .= '<li>' . esc_html__( 'Review the queue and audit log.', 'algq-automation-engine' ) . '</li>';
        $body .= '<li>' . esc_html__( 'Activate the rule after validation.', 'algq-automation-engine' ) . '</li></ol>';
        return self::wrap( __( 'Getting Started', 'algq-automation-engine' ), $body, true );
    }

    public static function documentation(): string {
        return self::wrap(
            __( 'Automation Engine Documentation', 'algq-automation-engine' ),
            __( 'Rules are evaluated against registered platform events. Matching rules create idempotent queue jobs. Failed jobs retry with exponential backoff and move to the dead-letter state after the configured attempt limit.', 'algq-automation-engine' )
        );
    }

    public static function rules(): string {
        if ( ! is_user_logged_in() || ! ALGQ_Automation_Security::can( 'view_algq_automation' ) ) {
            return self::wrap( __( 'Automation Rules', 'algq-automation-engine' ), __( 'Administrator access is required.', 'algq-automation-engine' ) );
        }

        return self::wrap(
            __( 'Automation Rules', 'algq-automation-engine' ),
            sprintf(
                '<a class="button" href="%s">%s</a>',
                esc_url( admin_url( 'admin.php?page=algq-automation-rules' ) ),
                esc_html__( 'Open Rule Manager', 'algq-automation-engine' )
            ),
            true
        );
    }

    private static function wrap( string $title, string $body, bool $trusted_html = false ): string {
        return sprintf(
            '<section class="algq-automation-public"><h2>%s</h2><div>%s</div></section>',
            esc_html( $title ),
            $trusted_html ? wp_kses_post( $body ) : wpautop( esc_html( $body ) )
        );
    }
}
