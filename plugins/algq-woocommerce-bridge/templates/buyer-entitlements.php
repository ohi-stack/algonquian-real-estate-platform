<?php defined( 'ABSPATH' ) || exit; ?>
<div class="algq-commerce-access">
<?php if ( ! is_user_logged_in() ) : ?>
<p><?php echo esc_html__( 'Please log in to view active access.', 'algq-woocommerce-bridge' ); ?></p>
<?php else : $rows = ALGQ_WCB_Entitlements::for_user( get_current_user_id() ); ?>
<?php if ( $rows ) : ?><ul><?php foreach ( $rows as $row ) : ?><li><strong><?php echo esc_html( $row['access_key'] ); ?></strong> — <?php echo esc_html( ucfirst( $row['status'] ) ); ?><?php if ( ! empty( $row['expires_at'] ) ) : ?> — <?php echo esc_html( sprintf( __( 'expires %s UTC', 'algq-woocommerce-bridge' ), $row['expires_at'] ) ); ?><?php endif; ?></li><?php endforeach; ?></ul><?php else : ?><p><?php echo esc_html__( 'No entitlements found.', 'algq-woocommerce-bridge' ); ?></p><?php endif; ?>
<?php endif; ?>
</div>
