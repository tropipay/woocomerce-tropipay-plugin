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
include __DIR__ . "/TppSDK/TropiPay.php";

class WC_Tropipay extends WC_Payment_Gateway
{

    public function __construct()
    {
        $this->id                 = 'tropipay';
        // $this->icon               = home_url() . '/wp-content/plugins/tropipay/pages/assets/images/logoTropiPayp2.png';
        $this->method_title       = __('Pago con Tarjeta (Tropipay)', 'woocommerce');
        $this->method_description = __('Esta es la opción de la pasarela de pago de Tropipay.', 'woocommerce');
        $this->notify_url        = add_query_arg('wc-api', 'WC_tropipay', home_url('/'));
        $this->log                =  new WC_Logger();
        $this->idLog              = $this->generateIdLog();

        $this->has_fields         = false;

        // Load the settings
        $this->init_settings();
        $this->init_form_fields();
        //$this->payment_fields();

        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');

        // Get settings
        $this->tropipayentorno            = $this->get_option('tropipayentorno');
        $this->tropipaymail             = $this->get_option('tropipaymail');
        $this->tropipaypassw                = $this->get_option('tropipaypassw');
        $this->tropipaymoneda             = $this->get_option('tropipaymoneda');
        $this->tropipayactivar_log      = $this->get_option('tropipayactivar_log');
        $this->tropipayestado             = $this->get_option('tropipayestado');
        $this->tropipayaddFees                  = $this->get_option('tropipayaddFees');
        $this->tropipayfeecardpercent = $this->get_option('tropipayfeecardpercent');
        $this->tropipayfeecardfixed = $this->get_option('tropipayfeecardfixed');
        $this->tropipayfeebalancepercent = $this->get_option('tropipayfeebalancepercent');
        $this->tropipayfeebalancefixed = $this->get_option('tropipayfeebalancefixed');
        $this->tropipayshowfees = $this->get_option('tropipayshowfees');
        $this->tropipayshowlogo = $this->get_option('tropipayshowlogo');



        // Actions
        add_action('woocommerce_receipt_tropipay', array($this, 'receipt_page_tropipay'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'tropipay_coupons_check'));
        //Payment listener/API hook
        add_action('woocommerce_api_wc_tropipay', array($this, 'check_rds_response'));
        add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);
    }

    function tropipay_coupons_check()
    {

        $options = get_option('woocommerce_tropipay_settings');

        if ($options['tropipaydiscountpercent'] == 'yes') {

            $coupon_id = $options['tropipaydiscountcouponcaption'] . 'percent_ofer';
            $discount_type = 'percent';

            //Create discoun percent coupon
            $coupon_percent = new WC_Coupon();
            $coupon_percent->set_code($coupon_id);
            $coupon_percent->set_discount_type($discount_type);
            $coupon_percent->set_amount(intval($options['tropipaydiscountpercentcuantity']));
            $coupon_percent->set_individual_use(false);
            $coupon_percent->save();
        }

        if ($options['tropipaydiscountamount'] == 'yes') {

            $coupon_id = $options['tropipaydiscountcouponcaption'] . 'fixed_ofer';
            $discount_type = 'fixed_cart';

            //Create discoun fixed coupon
            $coupon_fixed = new WC_Coupon();
            $coupon_fixed->set_code($coupon_id);
            $coupon_fixed->set_discount_type($discount_type);
            $coupon_fixed->set_amount(intval($options['tropipaydiscountamountcuantity']));
            $coupon_fixed->set_individual_use(false);
            $coupon_fixed->save();
        }
    }


    function generateIdLog()
    {
        $vars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stringLength = strlen($vars);
        $result = '';
        for ($i = 0; $i < 20; $i++) {
            $result .= $vars[rand(0, $stringLength - 1)];
        }
        return $result;
    }

    function payment_scripts()
    {
        wp_register_style('tropipay_styles', plugins_url('pages/assets/css/tropipay.css', WC_TROPIPAY_MAIN_FILE));
        wp_enqueue_style('tropipay_styles');
    }


    function init_form_fields()
    {
        global $woocommerce;

        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Activar Tropipay:', 'woocommerce'),
                'label'       => __('Activar pago Tropipay', 'woocommerce'),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'yes'
            ),
            'title' => array(
                'title'       => __('Título', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce'),
                'default'     => __('Pago con TropiPay', 'woocommerce'),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __('Descripción', 'woocommerce'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your website.', 'woocommerce'),
                'default'     => __('Esta es la opción de la pasarela de pago con tarjeta de Tropipay. Te ayudamos en todo lo que necesites desde nuestra web: <b>www.tropipay.com</b>', 'woocommerce'),
                'desc_tip'    => true,
            ),
            'tropipayentorno' => array(
                'title'       => __('Entorno de Tropipay', 'woocommerce'),
                'type'        => 'select',
                'description' => __('Entorno del proceso de pago.', 'woocommerce'),
                'default'     => 'Sandbox',
                'desc_tip'    => true,
                'options'     => array(
                    'Sandbox' => __('Sandbox', 'woocommerce'),
                    'Live' => __('Live', 'woocommerce')
                )
            ),
            'clientid' => array(
                'title'       => __('Client Id', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Client Id', 'woocommerce'),
                'default'     => __('Client Id', 'woocommerce'),
                'desc_tip'    => true,
            ),
            'clientsecret' => array(
                'title'       => __('Client Secret', 'woocommerce'),
                'type'        => 'password',
                'description' => __('Client Secret.', 'woocommerce'),
                'default'     => __('', 'woocommerce'),
                'desc_tip'    => true
            ),
            'tropipaymoneda' => array(
                'title'       => __('Tipo de Moneda', 'woocommerce'),
                'type'        => 'select',
                'description' => __('Moneda del proceso de pago.', 'woocommerce'),
                'default'     => 'AUTO',
                'desc_tip'    => true,
                'options'     => array(
                    'AUTO' => __('AUTO', 'woocommerce'),
                    'EUR' => __('EURO', 'woocommerce'),
                    'USD' => __('DOLAR', 'woocommerce')
                )
            ),
            'tropimethod' => array(
                'title'           => __('Método del formulario', 'woocommerce'),
                'type'            => 'select',
                'description'     => __('Formulario embebido o redirección externa, para pagos con balance de TropiPay siempre será redirección', 'woocommerce'),
                'default'         => 'redirect',
                'desc_tip'        => true,
                'options'         => array(
                    'redirect' => __('Redirección externa', 'woocommerce'),
                    'embed' => __('Formulario embebido', 'woocommerce')
                )
            ),
            'tropipaymentmethods' => array(
                'title'            => __('Formas de pago', 'woocommerce'),
                'type'             => 'select',
                'description'      => __('Seleccione las formas de pago que desee', 'woocommerce'),
                'default'          => '',
                'desc_tip'         => true,
                'options'          => array(
                    'normal' => __('Pagar con tarjeta y con saldo Tropipay', 'woocommerce'),
                    'onlycard' => __('Solo Pagar con tarjeta', 'woocommerce'),
                    'onlytpp' => __('Solo Pagar con Tropipay', 'woocommerce'),
                )
            ),
            'tropipayshowlogo' => array(
                'title'       => __('Mostrar logo de TropiPay', 'woocommerce'),
                'type'        => 'select',
                'description' => __('', 'woocommerce'),
                'default'     => 'si',
                'desc_tip'    => true,
                'options'     => array(
                    'no' => __('No', 'woocommerce'),
                    'si' => __('Si', 'woocommerce')
                )
            ),
            'tropipayactivar_log' => array(
                'title'       => __('Activar Log', 'woocommerce'),
                'type'        => 'select',
                'description' => __('Activar trazas de log.', 'woocommerce'),
                'default'     => 'no',
                'desc_tip'    => true,
                'options'     => array(
                    'no' => __('No', 'woocommerce'),
                    'si' => __('Si', 'woocommerce')
                )
            ),
            'tropipayestado' => array(
                'title'       => __('Estado', 'tropipay-woo'),
                'type'        => 'select',
                'description' => __('Estado tras el pago.', 'tropipay-woo'),
                'default'     => 'no',
                'desc_tip'    => true,
                'options'     => array()
            ),
            'tropiexpirationdays' => array(
                'title'           => __('Días de cancelación', 'tropipay-woo'),
                'type'            => 'select',
                'description'     => __('', 'woocommerce'),
                'default'         => 0,
                'desc_tip'        => true,
                'options'         => array(
                    0 => '0',
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5',
                    6 => '6',
                    7 => '7',
                    8 => '8',
                    9 => '9',
                    10 => '10',
                    15 => '15',
                    20 => '20'
                )
            ),
            'tropipayaddFees' => array(
                'title'       => __('Agregar Fees', 'tropipay-woo'),
                'type'        => 'select',
                'description' => __('Agregar Fees.', 'tropipay-woo'),
                'default'     => 'no',
                'desc_tip'    => true,
                'options'     => array(
                    'no' => __('No', 'woocommerce'),
                    'si' => __('Si', 'woocommerce')
                )
            ),
            'tropipayfeecardpercent' => array(
                'title'       => __('Fee de pago por tarjeta (%)', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Porcentaje fee para tarjeta (valor por defecto: 3.45)', 'woocommerce'),
                'default'     => '3.45',
                'desc_tip'    => true,
            ),
            'tropipayfeecardfixed' => array(
                'title'       => __('Fee de pago por tarjeta (fijo)', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Fee fijo para tarjeta (valor por defecto: 0.5)', 'woocommerce'),
                'default'     => '0.5',
                'desc_tip'    => true,
            ),
            'tropipayfeebalancepercent' => array(
                'title'       => __('Fee de pago con balance (%)', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Porcentaje fee para tarjeta (valor por defecto: 1.0)', 'woocommerce'),
                'default'     => '1.0',
                'desc_tip'    => true,
            ),
            'tropipayfeebalancefixed' => array(
                'title'       => __('Fee de pago con balance (fijo)', 'woocommerce'),
                'type'        => 'text',
                'description' => __('Fee fijo para balance (valor por defecto: 0)', 'woocommerce'),
                'default'     => '0',
                'desc_tip'    => true,
            ),
            'tropipayshowfees' => array(
                'title'       => __('Mostrar fees', 'tropipay-woo'),
                'type'        => 'select',
                'description' => __('Mostrar Fees.', 'tropipay-woo'),
                'default'     => 'no',
                'desc_tip'    => true,
                'options'     => array(
                    'no' => __('No', 'woocommerce'),
                    'si' => __('Si', 'woocommerce')
                )
            ),
            'tropipaydiscountpercent' => array(
                'title'       => __('Porciento de descuento que desea aplicar', 'woocomemerce'),
                'type'        => 'checkbox',
                'description' => __('Habilitar, si deseamos aplicar un descuento al pago del cliente <br> cuando utilice tropipay como método de pago'),
                'defult'      => 'no',
                'desc_tip'    => true,
                'class'       => 'tropipaycheckbox',
                'label'       => 'Activar descuento'

            ),
            'tropipaydiscountpercentcuantity' => array(
                'title' => __('Valor del porciento de descuento', 'woocommerce'),
                'type'  => 'text',
                'description' => __('Valor del pociento de descueto que se desea aplicar a la cuenta del cliente. <br> Este porciento será aplicado al monto final de la cuenta del cliente.', 'woocommerce'),
                'default' => 0,
                'desc_tip' => true,
                'class' =>  "tropipayinput",
            ),
            'tropipaydiscountamount' => array(
                'title'  => __('Activar valor fijo de descuento', 'woocommerce'),
                'type'   => 'checkbox',
                'description' => __('Habilitar si deseamos aplicar un descuento fijo a la cuenta del cliente <br> cuando utilice tropipay como método de pago'),
                'default' => 'no',
                'desc_tip' => true,
                'class' => 'tropipaycheckbox',
                'laber' => 'Activar descuento de valor fijo'
            ),
            'tropipaydiscountamountcuantity' => array(
                'title' => __('Valor fijo a descontar', 'woocommerce'),
                'type'  => 'text',
                'description' => __('Valor fijo que se desea descontar a la cuenta del cliente.'),
                'default' => 0,
                'desc_tip' => true,
                'class' =>  "tropipayinput",
            ),
            'tropipaydiscountcouponcaption' => array(
                'title' => __('Nombre para los cupones descuento', 'woocommerce'),
                'type'  => 'text',
                'description' => __('Nombre personalizado para el/los cupon/es de descuentos que seran aplicados por el metodo de pago de Tropipay', 'woocommerce'),
                'default' => 'Tropipay',
                'desc_tip' => true,
            ),
        );



        $tmp_estados = wc_get_order_statuses();

        foreach ($tmp_estados as $est_id => $est_na) {
            $this->form_fields['tropipayestado']['options'][substr($est_id, 3)] = $est_na;
        }
    }
    function process_payment($order_id)
    {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $logActivo = $this->tropipayactivar_log;
        $this->tropipayescribirLog_wc($this->idLog . " -- " . "Acceso a la opción de pago con tarjeta de Tropipay", $logActivo);
        // Return receipt_page redirect
        return array(
            'result'     => 'success',
            'redirect'    => $order->get_checkout_payment_url(true)
        );
    }

    function generate_tropipay_form($order_id)
    {
        // Version
        $merchantModule = 'woocommerce_tropipay_1.0.0';

        //Recuperamos los datos de config.
        $logActivo = $this->get_option('tropipayactivar_log');
        $clientid = $this->get_option('clientid');
        $clientsecret = $this->get_option('clientsecret');
        $monedaconf = $this->get_option('tropipaymoneda');
        $entorno = $this->get_option('tropipayentorno');
        $tropimethod = $this->get_option('tropimethod');
        $tropipaymentmethods = $this->get_option('tropipaymentmethods');

        $this->tropipayescribirLog_wc($this->idLog . " -- " . "Acceso al formulario de pago con tarjeta de Tropipay", $logActivo);

        //Callback
        $urltienda = $this->notify_url;

        //Objeto tipo pedido
        $order = new WC_Order($order_id);

        //Calculo del precio total del pedido
        /*$transaction_amount = number_format( (float) ($order->get_total()), 2, '.', '' );
        $transaction_amount = str_replace('.','',$transaction_amount);
        $transaction_amount = floatval($transaction_amount);*/
        $transaction_amount = (int) str_replace(',', '', number_format($order->get_total() * 100, 2));

        if ($monedaconf === 'AUTO') {
            $moneda = $order->get_currency();
        } else {
            $moneda = $monedaconf;
        }


        $numpedido =  str_pad($order_id, 8, "0", STR_PAD_LEFT) . date('is');

        //Se establece el entorno del SIS
        if ($entorno == "Sandbox") {
            $tropipay_server = "https://tropipay-dev.herokuapp.com";
            $environment = "develop";
        } else if ($entorno == "Live") {
            $tropipay_server = "https://www.tropipay.com";
            $environment = "production";
        }

        $srv = new TropiPay($clientid, $clientsecret, $environment);

        $datetime = new DateTime('today');
        $arraycliente["name"] = $order->get_billing_first_name();
        $arraycliente["lastName"] = $order->get_billing_last_name();
        $arraycliente["address"] = $order->get_billing_address_1() . ", " . $order->get_billing_city() . ", " . $order->get_billing_postcode();
        if ($order->get_billing_phone()) {
            $arraycliente["phone"] = $order->get_billing_phone();
        } else {
            $arraycliente["phone"] = $order->get_shipping_phone();
        }
        $arraycliente["email"] = $order->get_billing_email();
        //$arraycliente["countryId"] = 1;
        $arraycliente["countryIso"] = $order->get_billing_country();
        if (strlen($arraycliente["countryIso"]) == 0) {
            $arraycliente["countryId"] = 1;
            unset($arraycliente["countryIso"]);
        }
        $arraycliente["termsAndConditions"] = true;
        $arraycliente["state"] = $order->get_billing_state();
        $arraycliente["city"] = $order->get_billing_city();
        $arraycliente["postCode"] = $order->get_billing_postcode();
        //$moneda=$this->get_option('tropipaymoneda');
        $datos = array(
            "reference" => $numpedido,
            "concept" => __('Order #: ') . $order->get_id(),
            "description" => " ",
            "amount" => $transaction_amount,
            "currency" => $moneda,
            "singleUse" => true,
            "reasonId" => 4,
            "expirationDays" => $this->get_option('tropiexpirationdays'),
            "lang" => "es",
            "urlSuccess" => $this->get_return_url($order),
            "urlFailed" => $order->get_cancel_order_url(),
            "urlNotification" => $urltienda,
            "serviceDate" => $datetime->format('Y-m-d'),
            "directPayment" => true,
            "client" => $arraycliente,
            "favorite" => false,
            "paymentMethods"
        );
        $selectedMethod = get_post_meta($order_id, 'tropipay_payment_method', true);
        switch ($selectedMethod) {
            case 'card':
                $datos["paymentMethods"] = array("EXT");
                break;
            case 'balance':
                $datos["paymentMethods"] = array("TPP");
                break;
        }
        $paylink = $srv->createPaylink($datos);
        $shorturl = $paylink['data']['shortUrl'];

        $paymenturl = $paylink['data']['paymentUrl'];

        //$paymenturl=$paylink['data']['rawUrlPayment'];

        $action = $shorturl;


        if ($tropimethod === "embed" && $selectedMethod === 'card') {
            return '<iframe style="border:none;width:100%;height:500px;" src="' . $paymenturl  . '"></iframe> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancelar Pedido', 'tropipay') . '</a>';
        } else {
            return '<h4>Espere, por favor</h4><form action="' . $action . '" method="GET" id="tropipay_payment_form" style="display:none;">' .
                '<input type="submit" class="button-alt" id="submit_tropipay_payment_form" value="' . __('Pagar con Tropipay', 'tropipay') . '" />' .
                '<a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancelar Pedido', 'tropipay') . '</a>
                    </form><script>document.getElementById("tropipay_payment_form").submit();</script>';
        }
    }

    function tropipay_payments_get_order_id($num)
    {
        return intval(substr($num, 0, -4));
    }

    /*Verificar respuesta del servidor para evitar suplantacion*/
    function check_rds_response()
    {
        $this->idLog = $this->generateIdLog();
        $logActivo = $this->tropipayactivar_log;
        $estado = $this->tropipayestado;

        $responsej = file_get_contents('php://input');
        $response = json_decode($responsej, true);
        $ds_amount = $response["data"]["amount"];
        $ds_order = $response["data"]["reference"];
        $ds_bankordercode = $response["data"]["bankOrderCode"];
        $ds_amount = $response["data"]["originalCurrencyAmount"];
        $ds_merchant_clientid = $this->get_option('clientid');
        $ds_merchant_clientsecret = $this->get_option('clientsecret');
        $ds_reference = $response["data"]["reference"];
        $ds_currency = $response["data"]["currency"];

        $order_id = $this->tropipay_payments_get_order_id($ds_reference);

        //Se verifica el id de la orden que viene en la respues
        if ($order_id) {
            $order = new WC_Order($order_id);
        } else {
            $this->tropipayescribirLog_wc($this->idLog . " -- " . "No se ha encontrado el pedido", $logActivo);
            wp_die('<img src="' . home_url() . '/wp-content/plugins/tropipay/pages/assets/images/cross.png" alt="Desactivado" title="Desactivado" />
            Fallo en el proceso de pago.<br>Su pedido ha sido cancelado.');
            return;
        }

        //Se verifica que coincidan las cantidades a pagar
        $transaction_amount = (int) str_replace(',', '', number_format($order->get_total() * 100, 2));
        if ($transaction_amount != abs($ds_amount)) {
            $this->tropipayescribirLog_wc($this->idLog . " -- " . "No coincide el amount", $logActivo);
            wp_die('<img src="' . home_url() . '/wp-content/plugins/tropipay/pages/assets/images/cross.png" alt="Desactivado" title="Desactivado" />
            Fallo en el proceso de pago.<br>Su pedido ha sido cancelado.');
            return;
        }

        //Validacion de firmas y registro del log para evidencia
        $firma_remota = $response["data"]["signaturev2"];
        $firma_local = hash('sha256', $ds_bankordercode . $ds_merchant_clientid . $ds_merchant_clientsecret . $ds_amount);
        $this->tropipayescribirLog_wc($this->idLog . " -- " . "firma remota: " . $firma_remota, $logActivo);
        $this->tropipayescribirLog_wc($this->idLog . " -- " . "firma local: " . $firma_local, $logActivo);
        $this->tropipayescribirLog_wc($this->idLog . " -- " . "response: " . $responsej, $logActivo);

        if ($firma_local == $firma_remota) {
            if ($response["status"] == "OK") {
                $order->update_status($estado, __('Awaiting Tropipay payment', 'woocommerce'));
                $this->tropipayescribirLog_wc($this->idLog . " -- " . "Operación finalizada. Respuesta del tpv: OK. PEDIDO ACEPTADO", $logActivo);
                $order->reduce_order_stock(); //Verificar si se puede actualizar esto a la par del estado
                add_post_meta($order_id, 'reference', $ds_order);
                add_post_meta($order_id, 'bankOrderCode', $ds_bankordercode);
                // Remove cart
                WC()->cart->empty_cart();
            } else {
                //$order->update_status('cancelled',__( 'Awaiting Tropipay payment', 'woocommerce' ));
                //WC()->cart->empty_cart();
                $this->tropipayescribirLog_wc($this->idLog . " -- " . "Operación finalizada. Respuesta del tpv: KO. PEDIDO CANCELADO", $logActivo);
            }
        } else {
            $this->tropipayescribirLog_wc($this->idLog . " -- " . "No coinciden las firmas", $logActivo);
            wp_die('<img src="' . home_url() . '/wp-content/plugins/tropipay/pages/assets/images/cross.png" alt="Desactivado" title="Desactivado" />
                Fallo en el proceso de pago.<br>Su pedido ha sido cancelado.');
        }
    }

    function receipt_page_tropipay($order)
    {
        if (get_post_meta($order, 'tropipay_payment_receipt', true))
            return; // Exit if already processed
        add_post_meta($order, 'tropipay_payment_receipt', 'si');
        $logActivo = $this->tropipayactivar_log;
        $this->tropipayescribirLog_wc($this->idLog . " -- " . "Acceso a la página de confirmación de la opción de pago con tarjeta de Tropipay", $logActivo);

        $selectedMethod = get_post_meta($order, 'tropipay_payment_method', true);

        if ($this->get_option('tropimethod') != 'embed' || $selectedMethod === 'balance') {
            echo '<p>' . __('Gracias por su pedido, por favor espera mientras le redireccionamos a la plataforma de pago. <img src="' . home_url() . '/wp-content/plugins/tropipay/pages/assets/images/loading.gif" alt="Desactivado" title="Desactivado" />', 'tropipay-woo') . '</p>';
        } else {
            if ($selectedMethod) {
                echo '<p>' . __('Gracias por su pedido, por favor introduzca los datos de su Tarjeta.', 'tropipay-woo') . '</p>';
            }
        }
        if ($selectedMethod) {
            echo $this->generate_tropipay_form($order);
        }
    }

    function tropipayescribirLog_wc($texto, $activo)
    {
        if ($activo == "si") {
            // Log
            $this->log->add('tropipay', $texto . "\r\n");
        }
    }


    function tropipay_get_bankordercode($order_id)
    {
        return get_post_meta($order_id, 'bankOrderCode', true);
    }
}
