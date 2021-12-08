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
 * Version: 3.0.1
 * Author: Tropipay
 *
 */

add_action( 'init', 'init_tropipay' );
add_action( 'plugins_loaded', 'load_tropipay' );

function init_tropipay() {
    load_plugin_textdomain( "tropipay", false, dirname( plugin_basename( __FILE__ ) ));
}

function load_tropipay() {
    if ( !class_exists( 'WC_Payment_Gateway' ) ) 
        exit;

    include_once ('wc-tropipay.php');
	
    add_filter( 'woocommerce_payment_gateways', 'anadir_pago_woocommerce_tropipay' );
}

function anadir_pago_woocommerce_tropipay($methods) {
    $methods[] = 'WC_Tropipay';
    return $methods;
}