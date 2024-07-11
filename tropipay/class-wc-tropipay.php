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
 * Plugin Name: Tropipay WooCommerce
 * Plugin URI: https://www.tropipay.com/
 * Description: Pagar con tarjeta mediante la pasarela de pago Tropipay
 * Version: 4.7.0
 * Author: Tropipay
 *
 */

define('WC_TROPIPAY_MAIN_FILE', __FILE__);
add_action('init', 'init_tropipay');
add_action('plugins_loaded', 'load_tropipay');
add_action('woocommerce_review_order_before_cart_contents', 'update_totals');

function init_tropipay()
{
  load_plugin_textdomain("tropipay", false, dirname(plugin_basename(__FILE__)));
}

function load_tropipay()
{
  if (!class_exists('WC_Payment_Gateway'))
    exit;

  include_once('wc-tropipay.php');
  require_once plugin_dir_path(__FILE__) . 'includes/tropipay-checkout-description-fields.php';

  add_filter('woocommerce_payment_gateways', 'anadir_pago_woocommerce_tropipay');
}

function anadir_pago_woocommerce_tropipay($methods)
{
  $methods[] = 'WC_Tropipay';
  return $methods;
}

/**
 * Add a fee when the user checks out with PayPal
 */
function tropipay_apply_payment_gateway_fee($cart)
{
  $totals = $cart->get_totals();
  

  $calculateamount = $totals["cart_contents_total"] + $totals["cart_contents_tax"];
  $payment_method = WC()->session->get('chosen_payment_method');

  if (!empty($_POST['post_data'])) {
    setcookie('customer-post-data', sanitize_text_field($_POST['post_data']), 0);
  }

  $posted_data = sanitize_text_field($_POST['post_data'] ?? '');

  // in case of absence of $_POST['post_data'] - take it from cookie
  if (empty($posted_data) && !empty($_COOKIE['customer-post-data'])) {
    $posted_data = $_COOKIE['customer-post-data'];
  }
  // Only apply the fee if the payment gateway is PayPal
  // Note that you might need to check this slug, depending on the PayPal gateway you're using
  parse_str($posted_data, $post_data_array);
  $options = get_option('woocommerce_tropipay_settings');
  $payment_method = $post_data_array['payment_method'];
  if ($payment_method == 'tropipay' && $posted_data) {

    $metodo_pago = new WC_Tropipay;

    // parse_str($posted_data, $post_data_array);
    $tropipay_payment_method = $post_data_array['tropipay_payment_method'];
    if ($metodo_pago->tropipayaddFees === 'si') {
      if ($tropipay_payment_method === 'card' || $_POST["tropipay_payment_method"] === 'card') {
        $label = __('Comisión pago', 'tropipay-woo');
        $amount = round(floatval($calculateamount / floatval(1 - (floatval($metodo_pago->tropipayfeecardpercent) / 100))), 2) + floatval($metodo_pago->tropipayfeecardfixed) - $calculateamount;
        WC()->cart->add_fee($label, $amount, true, 'standard');
      }
      if ($tropipay_payment_method === 'balance'  || $_POST["tropipay_payment_method"] === 'balance') {
        $label = __('Comisión pago', 'tropipay-woo');
        $amount = floatval($calculateamount / floatval(1 - (floatval($metodo_pago->tropipayfeebalancepercent) / 100))) + floatval($metodo_pago->tropipayfeebalancefixed) - $calculateamount;
        WC()->cart->add_fee($label, $amount, true, 'standard');
      }
    }
    
    if ($options['tropipaydiscountpercent'] == 'yes' && WC()->cart->has_discount($options['tropipaydiscountcouponcaption'] . 'percent_ofer') != 1) {
      WC()->cart->apply_coupon($options['tropipaydiscountcouponcaption'] . 'percent_ofer');
    }
    else if($options['tropipaydiscountpercent'] == 'no' && WC()->cart->has_discount($options['tropipaydiscountcouponcaption'] . 'percent_ofer') == 1){
      WC()->cart->remove_coupon($options['tropipaydiscountcouponcaption'] . 'percent_ofer');
 
    }

    if ($options['tropipaydiscountamount'] == 'yes' && WC()->cart->has_discount($options['tropipaydiscountcouponcaption'] . 'fixed_ofer') != 1) {
      WC()->cart->apply_coupon($options['tropipaydiscountcouponcaption'] . 'fixed_ofer');
    }
    else if( $options['tropipaydiscountamount'] == 'no' && WC()->cart->has_discount($options['tropipaydiscountcouponcaption'] . 'fixed_ofer') == 1 ){
      WC()->cart->remove_coupon($options['tropipaydiscountcouponcaption'] . 'fixed_ofer');
     
    }

    
  }
   else if ($payment_method != 'tropipay') {
    if (WC()->cart->has_discount($options['tropipaydiscountcouponcaption'] . 'percent_ofer') == 1) {
      WC()->cart->remove_coupon($options['tropipaydiscountcouponcaption'] . 'percent_ofer');
      
    }
    if (WC()->cart->has_discount($options['tropipaydiscountcouponcaption'] . 'fixed_ofer') == 1) {
      WC()->cart->remove_coupon($options['tropipaydiscountcouponcaption'] . 'fixed_ofer');

    }
  }

}



function update_totals() {
    // Recalcula los totales del carrito
    WC()->cart->calculate_totals();
    
}

add_action('woocommerce_cart_calculate_fees', 'tropipay_apply_payment_gateway_fee');


/**
 * Add some JS
 */
function tropipay_script()
{
?>
  <script>
    jQuery(document).ready(function($) {

      $('body').on('click', '.checkout #tropipay_payment_method_card', function() {
        $('body').trigger('update_checkout');
      });

      $('body').on('click', '.checkout #tropipay_payment_method_balance', function() {
        $('body').trigger('update_checkout');
      });


      //Check if after the user select tropipay method, changes his/her choice
      $(document.body).on('change', 'input[name="payment_method"]', function() {
        //if ($(this).val() !== 'tropipay') {
        $('body').trigger('update_checkout');
        //}

      });
    });
  </script>
<?php
}

add_action('woocommerce_after_checkout_form', 'tropipay_script');


function tropipay_add_attribs_script($hook)
{
  if ($hook != 'woocommerce_page_wc-settings')
    return;

?>
  <script>
    document.addEventListener("DOMContentLoaded", function() {

      var txtrequire = document.getElementsByClassName("tropipayinput");
      Array.prototype.forEach.call(txtrequire, function(txt) {
        txt.setAttribute("required", "");
        txt.addEventListener("input", () => {
          if (!/^\d*$/.test(txt.value)) {
            txt.value = txt.value.substring(0, txt.value.length - 1);
          }

        });
      });

    });
  </script>
<?php
}
//Engancho el script solo en la pagina de administracion
add_action('admin_enqueue_scripts', 'tropipay_add_attribs_script');
