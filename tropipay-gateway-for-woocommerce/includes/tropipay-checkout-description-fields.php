<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_filter( 'woocommerce_gateway_description', 'tropipay_description_fields', 20, 2 );
add_action( 'woocommerce_checkout_process', 'tropipay_description_fields_validation' );
add_action( 'woocommerce_checkout_update_order_meta', 'tropipay_checkout_update_order_meta', 1, 1 );
#add_action( 'woocommerce_order_item_meta_end', 'tropipay_order_item_meta_end', 10, 3 );

function tropipay_checkout_update_order_meta($order_id) {
    delete_post_meta( $order_id, 'tropipay_payment_receipt' );
    // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! empty( $_POST['tropipay_payment_method'] ) ) {
        delete_post_meta( $order_id, 'tropipay_payment_method' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        add_post_meta( $order_id, 'tropipay_payment_method', sanitize_text_field( wp_unslash( $_POST['tropipay_payment_method'] ) ) );
    }
}

function tropipay_checkout_form_get_field_values() {
    $fields = [
        'tropipay_payment_method' => '',
        'payment_method' => ''
    ];

    foreach( $fields as $field_name => $value ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if( !empty( $_POST[ $field_name ] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $fields[ $field_name ] = sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) );
        } else {
            unset( $fields[ $field_name ] );
        }
    }

    return $fields;
}

function tropipay_description_fields_validation() {
    $field_values = tropipay_checkout_form_get_field_values();
    if ( empty( $field_values['tropipay_payment_method'] ) && $field_values['payment_method'] === 'tropipay' ) {
        wc_add_notice( 'Por favor seleccione la forma de pago con TropiPay', 'error' );
    }
}

function tropipay_description_fields( $description, $payment_id ) {
    global $woocommerce;

    if ( 'tropipay' !== $payment_id ) {
        return $description;
    }
    
    $metodo_pago = new Tropipay_Gateway;

    ob_start();

    $tropipaymentmethods = $metodo_pago->get_option('tropipaymentmethods');

    // Initialize fee labels to avoid undefined variable warnings
    $labelcardextrafee = '';
    $labelbalanceextrafee = '';

    if($metodo_pago->tropipayaddFees==='si' && $metodo_pago->tropipayshowfees==='si') {
        if(floatval($metodo_pago->tropipayfeecardpercent)>0) {
            $labelcardextrafee= ' ' . $metodo_pago->tropipayfeecardpercent . '%';
        }
        if(floatval($metodo_pago->tropipayfeecardfixed)>0) {
            $labelcardextrafee.= ' +' . $metodo_pago->tropipayfeecardfixed;
        }
        if(floatval($metodo_pago->tropipayfeebalancepercent)>0) {
            $labelbalanceextrafee= ' ' . $metodo_pago->tropipayfeebalancepercent . '%';
        }
        if(floatval($metodo_pago->tropipayfeebalancefixed)>0) {
            $labelbalanceextrafee.= ' +' . $metodo_pago->tropipayfeebalancefixed;
        }
    }

    $default = '';

    $optionst = array(
        'card' => __( 'Tarjeta de crédito', 'tropipay-gateway-for-woocommerce' ) . $labelcardextrafee,
        'balance' => __( 'Saldo de Tropipay', 'tropipay-gateway-for-woocommerce' ) . $labelbalanceextrafee,
    );

    if($tropipaymentmethods === 'onlycard') {
        unset($optionst['balance']);
        $default = 'card';
    }
    if($tropipaymentmethods === 'onlytpp') {
        unset($optionst['card']);
        $default = 'balance';
    }

    $element = array(
        'type' => 'radio',
        'class' => array( 'form-row', 'form-row-wide' ),
        'required' => true,
        'options' => $optionst,
        'default' => $default,
    );

    woocommerce_form_field(
        'tropipay_payment_method',
        $element
    );

    if ($metodo_pago->get_option('tropipayshowlogo') === 'si')
        echo '<div class="logoTropipay"></div>';


    $description .= ob_get_clean();

    return $description;
}
