<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Tropipay_Blocks extends AbstractPaymentMethodType {
    protected $name = 'tropipay';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_tropipay_settings', array() );
    }

    public function is_active() {
        $metodo_pago = new Tropipay_Gateway();
        return $metodo_pago->is_available();
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-tropipay-blocks-integration',
            plugins_url( 'pages/assets/js/tropipay-blocks.js', TROPIPAY_MAIN_FILE ),
            array(
                'wp-blocks',
                'wp-element',
                'wp-data',
                'wp-html-entities',
                'wp-i18n',
                'wc-blocks-registry',
                'wp-api-fetch'
            ),
            '1.0.0',
            true
        );

        return array( 'wc-tropipay-blocks-integration' );
    }

    public function get_payment_method_data() {
        $metodo_pago = new Tropipay_Gateway();
        $title = $metodo_pago->get_option( 'title', __( 'Pago con TropiPay', 'tropipay-gateway-for-woocommerce' ) );
        $description = $metodo_pago->get_option( 'description', __( 'Esta es la opción de la pasarela de pago con tarjeta de Tropipay.', 'tropipay-gateway-for-woocommerce' ) );
        
        $show_logo = $metodo_pago->get_option( 'tropipayshowlogo' ) === 'si';
        $logo_url = plugins_url( 'pages/assets/images/logoTropiPayp2.png', TROPIPAY_MAIN_FILE );

        $tropipaymentmethods = $metodo_pago->get_option('tropipaymentmethods');
        $labelcardextrafee = '';
        $labelbalanceextrafee = '';

        if ($metodo_pago->get_option('tropipayaddFees') === 'si' && $metodo_pago->get_option('tropipayshowfees') === 'si') {
            if (floatval($metodo_pago->get_option('tropipayfeecardpercent')) > 0) {
                $labelcardextrafee = ' ' . $metodo_pago->get_option('tropipayfeecardpercent') . '%';
            }
            if (floatval($metodo_pago->get_option('tropipayfeecardfixed')) > 0) {
                $labelcardextrafee .= ' +' . $metodo_pago->get_option('tropipayfeecardfixed');
            }
            if (floatval($metodo_pago->get_option('tropipayfeebalancepercent')) > 0) {
                $labelbalanceextrafee = ' ' . $metodo_pago->get_option('tropipayfeebalancepercent') . '%';
            }
            if (floatval($metodo_pago->get_option('tropipayfeebalancefixed')) > 0) {
                $labelbalanceextrafee .= ' +' . $metodo_pago->get_option('tropipayfeebalancefixed');
            }
        }

        $options = array(
            'card' => __( 'Tarjeta de crédito', 'tropipay-gateway-for-woocommerce' ) . $labelcardextrafee,
            'balance' => __( 'Saldo de Tropipay', 'tropipay-gateway-for-woocommerce' ) . $labelbalanceextrafee,
        );

        $default_method = 'card';
        if ($tropipaymentmethods === 'onlycard') {
            unset($options['balance']);
            $default_method = 'card';
        }
        if ($tropipaymentmethods === 'onlytpp') {
            unset($options['card']);
            $default_method = 'balance';
        }

        return array(
            'title'             => $title,
            'description'       => $description,
            'showLogo'          => $show_logo,
            'logoUrl'           => $logo_url,
            'paymentMethods'    => $options,
            'defaultMethod'     => $default_method,
            'supports'          => array( 'products' )
        );
    }
}
