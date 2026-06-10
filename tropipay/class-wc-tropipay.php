<?php

/**
* NOTA SOBRE LA LICENCIA DE USO DEL SOFTWARE
* 
* El uso de este software está sujeto a las Condiciones de uso de software que
* se incluyen en el paquete en el documento "Aviso Legal.pdf". También puede
* obtener una copia en la siguiente url:
* https://www.tropipay.com/terms
* 
* Tropipay es titular de todos los derechos de propiedad intelectual e industrial
* del software.
* 
* Quedan expresamente prohibidas la reproducción, la distribución y la
* comunicación pública, incluida su modalidad de puesta a disposición con fines
* distintos a los descritos en las Condiciones de uso.
* 
* Tropipay se reserva la posibilidad de ejercer las acciones legales que le
* correspondan para hacer valer sus derechos frente a cualquier infracción de
* los derechos de propiedad intelectual y/o industrial.
* 
* Tropipay
*/

/**
 * Plugin Name: Tropipay Gateway for WooCommerce
 * Plugin URI: https://www.tropipay.com/
 * Description: Pagar con tarjeta mediante la pasarela de pago Tropipay
 * Version: 5.0.0
 * Author: Tropipay
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tropipay
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'WC_TROPIPAY_MAIN_FILE', __FILE__ );
add_action( 'plugins_loaded', 'tropipay_load' );

// Register block integration on woocommerce_blocks_loaded
add_action( 'woocommerce_blocks_loaded', 'tropipay_block_checkout_init' );
function tropipay_block_checkout_init() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-tropipay-blocks.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( $payment_method_registry ) {
            $payment_method_registry->register( new WC_Tropipay_Blocks() );
        }
    );
}

// REST route to save chosen payment method from block checkout
add_action( 'rest_api_init', function () {
    register_rest_route( 'tropipay/v1', '/set-payment-method', array(
        'methods'             => 'POST',
        'callback'            => 'tropipay_set_payment_method_callback',
        'permission_callback' => function( $request ) {
            $nonce = $request->get_header( 'X-WP-Nonce' );
            if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
                return new WP_Error( 'rest_forbidden', __( 'Acceso denegado.', 'tropipay' ), array( 'status' => 403 ) );
            }
            return true;
        },
    ) );
} );

function tropipay_set_payment_method_callback( $request ) {
    $params = $request->get_json_params();
    $method = isset( $params['method'] ) ? sanitize_text_field( $params['method'] ) : '';
    
    // Security: Only allow 'card' or 'balance' payment options
    if ( ! in_array( $method, array( 'card', 'balance' ), true ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Método de pago inválido.' ), 400 );
    }
    
    if ( WC()->session ) {
        WC()->session->set( 'tropipay_payment_method', $method );
    }
    
    return new WP_REST_Response( array( 'success' => true ), 200 );
}

function tropipay_load() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) 
        exit;

    include_once ('wc-tropipay.php');
    require_once plugin_dir_path( __FILE__ ) . 'includes/tropipay-checkout-description-fields.php';
	
    add_filter( 'woocommerce_payment_gateways', 'tropipay_add_gateway' );

    // Register AJAX action globally so it is loaded even during admin-ajax.php requests
    add_action( 'wp_ajax_tropipay_sync_accounts', 'tropipay_ajax_sync_accounts_handler' );
}

function tropipay_ajax_sync_accounts_handler() {
    if ( class_exists( 'WC_Tropipay' ) ) {
        $gateway = new WC_Tropipay();
        $gateway->ajax_sync_accounts();
    } else {
        wp_send_json_error( 'TropiPay gateway class not found.' );
    }
}

function tropipay_add_gateway($methods) {
    $methods[] = 'WC_Tropipay';
    return $methods;
}

/**
 * Add a fee when the user checks out with TropiPay
 */
function tropipay_apply_payment_gateway_fee($cart) {
  $totals = $cart->get_totals();
  $calculateamount = $totals["cart_contents_total"] + $totals["cart_contents_tax"];
  
  $chosen_payment_method = WC()->session ? WC()->session->get( 'chosen_payment_method' ) : null;

  // phpcs:ignore WordPress.Security.NonceVerification.Missing
  if ( ! empty( $_POST['post_data'] ) ) {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    setcookie( 'customer-post-data', sanitize_text_field( wp_unslash( $_POST['post_data'] ) ), 0 );
  }

  // phpcs:ignore WordPress.Security.NonceVerification.Missing
  $posted_data = isset( $_POST['post_data'] ) ? sanitize_text_field( wp_unslash( $_POST['post_data'] ) ) : '';

  // in case of absence of $_POST['post_data'] - take it from cookie
  if ( empty( $posted_data ) && ! empty( $_COOKIE['customer-post-data'] ) ) {
    $posted_data = sanitize_text_field( wp_unslash( $_COOKIE['customer-post-data'] ) );
  }

  parse_str($posted_data, $post_data_array);
  $payment_method = isset($post_data_array['payment_method']) ? $post_data_array['payment_method'] : $chosen_payment_method;

  if( $payment_method == 'tropipay' ) {
    $metodo_pago = new WC_Tropipay;
    
    // First try the session (set by WooCommerce blocks via our REST API)
    $tropipay_payment_method = WC()->session ? WC()->session->get( 'tropipay_payment_method' ) : null;
    
    // If not in session, fallback to post data or post variable
    if ( empty( $tropipay_payment_method ) ) {
        $tropipay_payment_method = isset($post_data_array['tropipay_payment_method']) ? $post_data_array['tropipay_payment_method'] : null;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $tropipay_payment_method_post = isset($_POST["tropipay_payment_method"]) ? sanitize_text_field( wp_unslash( $_POST["tropipay_payment_method"] ) ) : null;
    
    $final_method = !empty($tropipay_payment_method) ? $tropipay_payment_method : $tropipay_payment_method_post;

    // Handle default/only method if none selected yet
    if ( empty( $final_method ) ) {
        $tropipaymentmethods = $metodo_pago->get_option('tropipaymentmethods');
        if ( $tropipaymentmethods === 'onlycard' ) {
            $final_method = 'card';
        } elseif ( $tropipaymentmethods === 'onlytpp' ) {
            $final_method = 'balance';
        } else {
            $final_method = 'card'; // Default to card
        }
    }

    if($metodo_pago->tropipayaddFees==='si') {
      if($final_method === 'card') {
          $label = __( 'Comisión pago', 'tropipay' );
          $percent = floatval($metodo_pago->tropipayfeecardpercent);
          if ( $percent >= 100 || $percent < 0 ) {
              $percent = 0; // Guard against division by zero and negative rates
          }
          $amount = round(floatval($calculateamount / floatval(1 - ($percent/100))), 2) + floatval($metodo_pago->tropipayfeecardfixed) - $calculateamount;
          WC()->cart->add_fee( $label, $amount, true, 'standard' );
      }
      if($final_method === 'balance') {
          $label = __( 'Comisión pago', 'tropipay' );
          $percent = floatval($metodo_pago->tropipayfeebalancepercent);
          if ( $percent >= 100 || $percent < 0 ) {
              $percent = 0; // Guard against division by zero and negative rates
          }
          $amount = floatval($calculateamount / floatval(1 - ($percent/100))) + floatval($metodo_pago->tropipayfeebalancefixed) - $calculateamount;
          WC()->cart->add_fee( $label, $amount, true, 'standard' );
      }
    }
  }
}

add_action( 'woocommerce_cart_calculate_fees', 'tropipay_apply_payment_gateway_fee');


/**
 * Add some JS
 */
function tropipay_script() {
    ?>
    <script>
    jQuery(document).ready(function($){
      $('body').on('click','.checkout #tropipay_payment_method_card',function(){
        $('body').trigger('update_checkout');
      });
      $('body').on('click','.checkout #tropipay_payment_method_balance',function(){
        $('body').trigger('update_checkout');
      });        
    });

    </script>
  <?php
}

add_action( 'woocommerce_after_checkout_form', 'tropipay_script' );
