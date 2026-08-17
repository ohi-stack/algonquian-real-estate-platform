<?php defined( 'ABSPATH' ) || exit; ?>
<div class="algq-commerce-access">
<?php if ( ! is_user_logged_in() ) : ?><p><?php echo esc_html__( 'Please log in to view purchased products.', 'algq-woocommerce-bridge' ); ?></p>
<?php elseif ( ! function_exists( 'wc_get_orders' ) ) : ?><p><?php echo esc_html__( 'WooCommerce is not available.', 'algq-woocommerce-bridge' ); ?></p>
<?php else : $orders = wc_get_orders( array( 'customer_id' => get_current_user_id(), 'status' => array( 'wc-processing', 'wc-completed' ), 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ) ); ?>
<?php if ( empty( $orders ) ) : ?><p><?php echo esc_html__( 'No qualifying purchases found.', 'algq-woocommerce-bridge' ); ?></p><?php else : ?><ul><?php foreach ( $orders as $order ) : ?><li><?php echo esc_html( sprintf( __( 'Order #%1$d — %2$s', 'algq-woocommerce-bridge' ), $order->get_id(), wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) ) ); ?></li><?php endforeach; ?></ul><?php endif; ?>
<?php endif; ?>
</div>
