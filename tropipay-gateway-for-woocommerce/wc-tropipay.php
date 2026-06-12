<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

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

class Tropipay_Gateway extends WC_Payment_Gateway {

    public $idLog;
    public $tropipayentorno;
    public $tropipaymail;
    public $tropipaypassw;
    public $tropipaymoneda;
    public $tropipayactivar_log;
    public $tropipayestado;
    public $tropipayaddFees;
    public $tropipayfeecardpercent;
    public $tropipayfeecardfixed;
    public $tropipayfeebalancepercent;
    public $tropipayfeebalancefixed;
    public $tropipayshowfees;
    public $tropipayshowlogo;
    public $tropipaypaymentcardtype;
    public $accountId;
    public $log;
    public $notify_url;

    private static $receipt_page_rendered = false;

    public function __construct() {
        $this->id                 = 'tropipay';
        // $this->icon               = home_url() . '/wp-content/plugins/tropipay/pages/assets/images/logoTropiPayp2.png';
        $this->method_title       = __( 'Pago con Tarjeta (Tropipay)', 'tropipay-gateway-for-woocommerce' );
        $this->method_description = __( 'Esta es la opción de la pasarela de pago de Tropipay.', 'tropipay-gateway-for-woocommerce' );
        $this ->notify_url        = add_query_arg( 'wc-api', 'tropipay_gateway', home_url( '/' ) );
        $this->log                =  new WC_Logger();
        $this->idLog              = $this->generateIdLog();

        $this->has_fields         = false;

        // Load the settings
        $this->init_settings();
        $this->init_form_fields();
        //$this->payment_fields();

        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );

        // Get settings
        $this->tropipayentorno            = $this->get_option( 'tropipayentorno' );
        $this->tropipaymail             = $this->get_option( 'tropipaymail' );
        $this->tropipaypassw                = $this->get_option( 'tropipaypassw' );
        $this->tropipaymoneda             = $this->get_option( 'tropipaymoneda' );
        $this->tropipayactivar_log	  = $this->get_option( 'tropipayactivar_log' );
        $this->tropipayestado             = $this->get_option( 'tropipayestado' );
        $this->tropipayaddFees                  = $this->get_option( 'tropipayaddFees');
        $this->tropipayfeecardpercent = $this->get_option('tropipayfeecardpercent');
        $this->tropipayfeecardfixed = $this->get_option('tropipayfeecardfixed');
        $this->tropipayfeebalancepercent = $this->get_option('tropipayfeebalancepercent');
        $this->tropipayfeebalancefixed = $this->get_option('tropipayfeebalancefixed');
        $this->tropipayshowfees = $this->get_option('tropipayshowfees');
        $this->tropipayshowlogo = $this->get_option('tropipayshowlogo');
        $this->tropipaypaymentcardtype = $this->get_option('tropipaypaymentcardtype');
        $this->accountId                  = $this->get_option('accountId');
        


        // Actions
        add_action( 'woocommerce_receipt_tropipay', array( $this, 'receipt_page_tropipay' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        //Payment listener/API hook
        add_action( 'woocommerce_api_tropipay_gateway', array( $this, 'check_tropipay_response' ) );
        add_action( 'wp_enqueue_scripts', [ $this, 'payment_scripts' ] );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

        
    }

    public function admin_scripts( $hook_suffix ) {
        if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
            return;
        }
        
        wp_register_script( 'tropipay_admin', plugins_url( 'pages/assets/js/tropipay-admin.js', TROPIPAY_MAIN_FILE ), array( 'jquery' ), '5.0.0', true );
        
        wp_localize_script( 'tropipay_admin', 'tropipay_admin_params', array(
            'sync_nonce'    => wp_create_nonce( 'tropipay_sync_nonce' ),
            'txt_syncing'   => __( 'Sincronizando...', 'tropipay-gateway-for-woocommerce' ),
            'txt_synced'    => __( '¡Sincronizado con éxito! Recargando...', 'tropipay-gateway-for-woocommerce' ),
            'txt_err_sync'  => __( 'Error al sincronizar.', 'tropipay-gateway-for-woocommerce' ),
            'txt_err_net'   => __( 'Error de red o sesión expirada. Verifica tu conexión.', 'tropipay-gateway-for-woocommerce' ),
            'txt_warning'   => __( 'Por favor, introduce y guarda primero tu Client ID y Client Secret.', 'tropipay-gateway-for-woocommerce' ),
            'txt_sync_btn'  => __( 'Sincronizar con TropiPay', 'tropipay-gateway-for-woocommerce' ),
        ) );
        
        wp_enqueue_script( 'tropipay_admin' );
    }

    private function sanitize_array_recursive( $array ) {
        $sanitized = array();
        if ( ! is_array( $array ) ) {
            return sanitize_text_field( $array );
        }
        foreach ( $array as $key => $value ) {
            if ( is_array( $value ) ) {
                $sanitized[ $key ] = $this->sanitize_array_recursive( $value );
            } else {
                $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }
        return $sanitized;
    }

    function generateIdLog() {
        $vars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stringLength = strlen($vars);
        $result = '';
        for ($i = 0; $i < 20; $i++) {
            $result .= $vars[wp_rand(0, $stringLength - 1)];
        }
        return $result;
    }

    function fetch_and_store_accounts() {
        try {
            $clientid = $this->get_option('clientid');
            $clientsecret = $this->get_option('clientsecret');
            $entorno = $this->get_option('tropipayentorno');
            
            if (empty($clientid) || empty($clientsecret)) {
                delete_option('tropipay_accounts');
                return false;
            }

            $environment = ($entorno == "Sandbox") ? "develop" : "production";
            $srv = new TropiPay($clientid, $clientsecret, $environment);
            
            $res = $srv->getAccounts(1);
            if ($res && empty($res['error']) && is_array($res['data'])) {
                update_option('tropipay_accounts', $res['data']);
                return $res;
            } else {
                if (get_option('tropipay_accounts') === false) {
                    update_option('tropipay_accounts', array());
                }
                if ($this->get_option('tropipayactivar_log') === 'si') {
                    $this->tropipayescribirLog_wc($this->idLog." -- Error fetching accounts: " . json_encode($res), 'si');
                }
                return $res;
            }
        } catch (Exception $e) {
            if ($this->get_option('tropipayactivar_log') === 'si') {
                $this->tropipayescribirLog_wc($this->idLog." -- Exception fetching accounts: " . $e->getMessage(), 'si');
            }
            return array('error' => $e->getMessage(), 'data' => null);
        }
    }

    function fetch_and_store_merchant_status() {
        try {
            $clientid = $this->get_option('clientid');
            $clientsecret = $this->get_option('clientsecret');
            $entorno = $this->get_option('tropipayentorno');
            
            if (empty($clientid) || empty($clientsecret)) {
                delete_option('tropipay_is_marketplace');
                return false;
            }

            $environment = ($entorno == "Sandbox") ? "develop" : "production";
            $srv = new TropiPay($clientid, $clientsecret, $environment);
            
            $profile = $srv->getProfile();
            
            $is_marketplace = isset($profile['data']['options']['isMarketPlaceUser']) && $profile['data']['options']['isMarketPlaceUser'] === true;
            
            update_option('tropipay_is_marketplace', $is_marketplace ? 'yes' : 'no');
            return $is_marketplace;
        } catch (Exception $e) {
            if ($this->get_option('tropipayactivar_log') === 'si') {
                $this->tropipayescribirLog_wc($this->idLog." -- Error in fetch_and_store_merchant_status: " . $e->getMessage(), 'si');
            }
            return false;
        }
    }

    public function process_admin_options() {
        $old_clientid = $this->get_option('clientid');
        $old_clientsecret = $this->get_option('clientsecret');
        $old_entorno = $this->get_option('tropipayentorno');

        $saved = parent::process_admin_options();

        $new_clientid = $this->get_option('clientid');
        $new_clientsecret = $this->get_option('clientsecret');
        $new_entorno = $this->get_option('tropipayentorno');

        // If credentials or environment changed, invalidate the cached data so they can sync again
        if ($old_clientid !== $new_clientid || $old_clientsecret !== $new_clientsecret || $old_entorno !== $new_entorno) {
            delete_option('tropipay_accounts');
            delete_option('tropipay_is_marketplace');
        }

        return $saved;
    }

    function check_tropipay_merchant_status() {
        $cached = get_option('tropipay_is_marketplace');
        if ($cached === false) {
            return false; // Don't do automatic call, let the user sync manually
        }
        return $cached === 'yes';
    }

    public function generate_sync_button_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'text',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                <?php echo wp_kses_post( $this->get_tooltip_html( $data ) ); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <button class="button-secondary <?php echo esc_attr( $data['class'] ); ?>" type="button" id="tropipay_sync_btn" style="<?php echo esc_attr( $data['css'] ); ?>">
                        <?php echo esc_html__( 'Sincronizar con TropiPay', 'tropipay-gateway-for-woocommerce' ); ?>
                    </button>
                    <p class="description"><?php echo wp_kses_post( $data['description'] ); ?></p>
                    <p id="tropipay_sync_warning" style="font-weight: bold; margin-top: 5px;"></p>
                    <span id="tropipay_sync_status" style="margin-left: 10px; font-weight: bold;"></span>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function ajax_sync_accounts() {
        check_ajax_referer( 'tropipay_sync_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'No tienes permisos para hacer esto.', 'tropipay-gateway-for-woocommerce' ) );
        }

        $clientid = $this->get_option('clientid');
        $clientsecret = $this->get_option('clientsecret');

        if ( empty( $clientid ) || empty( $clientsecret ) ) {
            wp_send_json_error( __( 'Por favor, introduce y guarda primero tu Client ID y Client Secret.', 'tropipay-gateway-for-woocommerce' ) );
        }

        // Force fetch and store
        $this->fetch_and_store_merchant_status();
        $accounts_res = $this->fetch_and_store_accounts();

        $accounts = get_option('tropipay_accounts');
        if ( is_array( $accounts ) && ! empty( $accounts ) ) {
            wp_send_json_success( __( 'Cuentas sincronizadas correctamente.', 'tropipay-gateway-for-woocommerce' ) );
        } else {
            $msg = __( 'No se pudieron sincronizar las cuentas o no tienes cuentas activas. ', 'tropipay-gateway-for-woocommerce' );
            if ( isset( $accounts_res['error'] ) && $accounts_res['error'] ) {
                $msg .= ' Error: ' . $accounts_res['error'];
            } elseif ( isset( $accounts_res['data']['error'] ) ) {
                $msg .= ' API Error: ' . ( is_array( $accounts_res['data']['error'] ) ? json_encode( $accounts_res['data']['error'] ) : $accounts_res['data']['error'] );
            } elseif ( is_array( $accounts ) && empty( $accounts ) ) {
                $msg .= ' (La API devolvió 0 cuentas para este comercio)';
            }
            wp_send_json_error( $msg );
        }
    }

    function payment_scripts() {
        wp_register_style( 'tropipay_styles', plugins_url( 'pages/assets/css/tropipay.css', TROPIPAY_MAIN_FILE ), array(), '5.0.0' );
        wp_register_script( 'tropipay_checkout', plugins_url( 'pages/assets/js/tropipay-checkout.js', TROPIPAY_MAIN_FILE ), array( 'jquery' ), '5.0.0', true );
        if ( is_checkout() || is_checkout_pay_page() ) {
            wp_enqueue_style( 'tropipay_styles' );
            wp_enqueue_script( 'tropipay_checkout' );
        }
    }

    /*function payment_fields() {
        global $woocommerce;
        //echo '<label for="mi_metodo_pago_tipo_pago">Seleccione el tipo de pago:</label>';
        echo '<div>';
        echo '<input type="radio" id="mi_metodo_pago_tipo_pago_tarjeta" name="mi_metodo_pago_tipo_pago" value="tarjeta" required>';
        echo '<label for="mi_metodo_pago_tipo_pago_tarjeta">Tarjeta de crédito</label>';
        echo '</div>';
        echo '<div>';
        echo '<input type="radio" id="mi_metodo_pago_tipo_pago_balance" name="mi_metodo_pago_tipo_pago" value="balance" required>';
        echo '<label for="mi_metodo_pago_tipo_pago_balance">Pagar con balance de TropiPay</label>';
        echo '</div>';
    }*/

    function init_form_fields() {
        global $woocommerce;

        $hidden = '';
        if (is_admin()) {
            // solo define campos avanzados si estás en el admin
            $hidden = $this->check_tropipay_merchant_status() ? '' : 'hidden';
        }

        $accounts = get_option('tropipay_accounts');
        $active_accounts = array();
        if (is_array($accounts)) {
            foreach ($accounts as $acc) {
                $state = $acc['state'] ?? null;
                if ((int) $state === 1) {
                    $active_accounts[] = $acc;
                }
            }
        }

        $account_options = array('' => __( 'Sincroniza tus cuentas primero...', 'tropipay-gateway-for-woocommerce' ));
        $hide_account_id_class = 'hidden';

        if (count($active_accounts) > 1) {
            $account_options = array('' => __( 'Seleccione una cuenta de destino...', 'tropipay-gateway-for-woocommerce' ));
            foreach ($active_accounts as $acc) {
                $id = $acc['id'] ?? '';
                $currency = $acc['currency'] ?? '';
                $acc_num = $acc['accountNumber'] ?? '';
                if ($id) {
                    $account_options[$id] = "{$currency} - {$acc_num}";
                }
            }
            $hide_account_id_class = '';
        }

        $this->form_fields = array(
                'enabled' => array(
                        'title'       => __( 'Activar Tropipay:', 'tropipay-gateway-for-woocommerce' ),
                        'label'       => __( 'Activar pago Tropipay', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'checkbox',
                        'description' => '',
                        'default'     => 'yes'
                ),
                'title' => array(
                        'title'       => __( 'Título', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Payment method description that the customer will see on your checkout.', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => __( 'Pago con TropiPay', 'tropipay-gateway-for-woocommerce' ),
                        'desc_tip'    => true,
                ),
                'description' => array(
                        'title'       => __( 'Descripción', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'textarea',
                        'description' => __( 'Payment method description that the customer will see on your website.', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => __( 'Esta es la opción de la pasarela de pago con tarjeta de Tropipay. Te ayudamos en todo lo que necesites desde nuestra web: <b>www.tropipay.com</b>', 'tropipay-gateway-for-woocommerce' ),
                        'desc_tip'    => true,
                ),
                'tropipayentorno' => array(
                        'title'       => __( 'Entorno de Tropipay', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Entorno del proceso de pago.', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => 'Sandbox',
                        'desc_tip'    => true,
                        'options'     => array(
                                'Sandbox' => __( 'Sandbox', 'tropipay-gateway-for-woocommerce' ),
                                'Live' => __( 'Live', 'tropipay-gateway-for-woocommerce' )
                        )
                ),
                'clientid' => array(
                        'title'       => __( 'Client Id', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Client Id', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => __( 'Client Id', 'tropipay-gateway-for-woocommerce' ),
                        'desc_tip'    => true,
                ),
                'clientsecret' => array(
                        'title'       => __( 'Client Secret', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'password',
                        'description' => __( 'Client Secret.', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => '',
                        'desc_tip'    => true
                ),
                'tropipay_sync' => array(
                        'title'       => __( 'Sincronizar Cuentas', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'sync_button',
                        'description' => __( 'Haz clic para sincronizar y conectar tu cuenta WooCommerce con TropiPay. Esto cargará de forma segura tus cuentas activas de destino.', 'tropipay-gateway-for-woocommerce' ),
                ),
                'accountId' => array(
                        'title'       => __( 'Cuenta de destino', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Seleccione la cuenta de TropiPay donde se recibirá el dinero.', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => '',
                        'desc_tip'    => true,
                        'options'     => $account_options,
                        'class'       => $hide_account_id_class
                ),
                'tropipaymoneda' => array(
                        'title'       => __( 'Tipo de Moneda', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Moneda del proceso de pago.', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => 'AUTO',
                        'desc_tip'    => true,
                        'options'     => array(
                                'AUTO' => __( 'AUTO', 'tropipay-gateway-for-woocommerce' ),
                                'EUR' => __( 'EURO', 'tropipay-gateway-for-woocommerce' ),
                                'USD' => __( 'DOLAR', 'tropipay-gateway-for-woocommerce' )
                        )
                ),
                'tropipaymentmethods' => array(
                    'title'            => __( 'Formas de pago', 'tropipay-gateway-for-woocommerce' ),
                    'type'             => 'select',
                    'description'      => __( 'Seleccione las formas de pago que desee', 'tropipay-gateway-for-woocommerce'),
                    'default'          => '',
                    'desc_tip'         => true,
                    'options'          => array(
                        'normal' => __('Pagar con tarjeta y con saldo Tropipay', 'tropipay-gateway-for-woocommerce'),
                        'onlycard' => __('Solo Pagar con tarjeta', 'tropipay-gateway-for-woocommerce'),
                        'onlytpp' => __('Solo Pagar con Tropipay', 'tropipay-gateway-for-woocommerce'),
                    )
                ),
                'tropipayshowlogo' => array(
                    'title'       => __( 'Mostrar logo de TropiPay', 'tropipay-gateway-for-woocommerce' ),
                    'type'        => 'select',
                    'description' => '',
                    'default'     => 'si',
                    'desc_tip'    => true,
                    'options'     => array(
                            'no' => __( 'No', 'tropipay-gateway-for-woocommerce' ),
                            'si' => __( 'Si', 'tropipay-gateway-for-woocommerce' )
                    )
                ),
                'tropipayactivar_log' => array(
                        'title'       => __( 'Activar Log', 'tropipay-gateway-for-woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Activar trazas de log.', 'tropipay-gateway-for-woocommerce' ),
                        'default'     => 'no',
                        'desc_tip'    => true,
                        'options'     => array(
                                'no' => __( 'No', 'tropipay-gateway-for-woocommerce' ),
                                'si' => __( 'Si', 'tropipay-gateway-for-woocommerce' )
                        )
                ),
				'tropipayestado' => array(
					'title'       => __( 'Estado', 'tropipay-gateway-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Estado tras el pago.', 'tropipay-gateway-for-woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
					'options'     => array()
				),
                'tropiexpirationdays' => array(
                    'title'           => __( 'Días de cancelación', 'tropipay-gateway-for-woocommerce'),
                    'type'            => 'select',
                    'description'     => '',
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
					'title'       => __( 'Agregar Fees', 'tropipay-gateway-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Agregar Fees.', 'tropipay-gateway-for-woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
                    'options'     => array(
                        'no' => __( 'No', 'tropipay-gateway-for-woocommerce' ),
                        'si' => __( 'Si', 'tropipay-gateway-for-woocommerce' )
                    )
                ),
                'tropipayfeecardpercent' => array(
                    'title'       => __( 'Fee de pago por tarjeta (%)', 'tropipay-gateway-for-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Porcentaje fee para tarjeta (valor por defecto: 3.45)', 'tropipay-gateway-for-woocommerce' ),
                    'default'     => '3.45',
                    'desc_tip'    => true,
                ),
                'tropipayfeecardfixed' => array(
                    'title'       => __( 'Fee de pago por tarjeta (fijo)', 'tropipay-gateway-for-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Fee fijo para tarjeta (valor por defecto: 0.5)', 'tropipay-gateway-for-woocommerce' ),
                    'default'     => '0.5',
                    'desc_tip'    => true,
                ),
                'tropipayfeebalancepercent' => array(
                    'title'       => __( 'Fee de pago con balance (%)', 'tropipay-gateway-for-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Porcentaje fee para tarjeta (valor por defecto: 1.0)', 'tropipay-gateway-for-woocommerce' ),
                    'default'     => '1.0',
                    'desc_tip'    => true,
                ),
                'tropipayfeebalancefixed' => array(
                    'title'       => __( 'Fee de pago con balance (fijo)', 'tropipay-gateway-for-woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Fee fijo para balance (valor por defecto: 0)', 'tropipay-gateway-for-woocommerce' ),
                    'default'     => '0',
                    'desc_tip'    => true,
                ),
				'tropipayshowfees' => array(
					'title'       => __( 'Mostrar fees', 'tropipay-gateway-for-woocommerce' ),
					'type'        => 'select',
					'description' => __( 'Mostrar Fees.', 'tropipay-gateway-for-woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
                    'options'     => array(
                        'no' => __( 'No', 'tropipay-gateway-for-woocommerce' ),
                        'si' => __( 'Si', 'tropipay-gateway-for-woocommerce' )
                    )
                ),
                'tropipaypaymentcardtype' => array(
                    'title'       => __( 'Tipo de paymentcard', 'tropipay-gateway-for-woocommerce' ),
                    'type'        => 'select',
                    'description' => __( 'Tipo de paymentcard.', 'tropipay-gateway-for-woocommerce' ),
                    'default'     => 1,
                    'desc_tip'    => true,
                    'options'     => array(
                        1 => 'Normal',
                        4 => 'Marketplace'
                    ),
                    'class'       => $hidden
                )
			   	);
				
				$tmp_estados=wc_get_order_statuses();
				foreach($tmp_estados as $est_id=>$est_na){
					$this->form_fields['tropipayestado']['options'][substr($est_id,3)]=$est_na;
				}
    }

    function process_payment( $order_id ) {
        global $woocommerce;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( empty( $_POST['tropipay_payment_method'] ) ) {
            $raw_payload = file_get_contents( 'php://input' );
            if ( ! empty( $raw_payload ) ) {
                $request_data = json_decode( $raw_payload, true );
                if ( is_array( $request_data ) ) {
                    $request_data = $this->sanitize_array_recursive( $request_data );
                }
                if ( isset( $request_data['payment_data'] ) ) {
                    foreach ( $request_data['payment_data'] as $item ) {
                        if ( isset( $item['key'] ) && $item['key'] === 'tropipay_payment_method' ) {
                            // phpcs:ignore WordPress.Security.NonceVerification.Missing
                            $_POST['tropipay_payment_method'] = sanitize_text_field( wp_unslash( $item['value'] ) );
                        }
                    }
                }
                if ( isset( $request_data['payment_method_data'] ) && isset( $request_data['payment_method_data']['tropipay_payment_method'] ) ) {
                    // phpcs:ignore WordPress.Security.NonceVerification.Missing
                    $_POST['tropipay_payment_method'] = sanitize_text_field( wp_unslash( $request_data['payment_method_data']['tropipay_payment_method'] ) );
                }
            }
        }

        // Also check if we stored it in the WC session during selection in WooCommerce blocks
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( empty( $_POST['tropipay_payment_method'] ) && WC()->session ) {
            $session_method = WC()->session->get( 'tropipay_payment_method' );
            if ( ! empty( $session_method ) ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $_POST['tropipay_payment_method'] = sanitize_text_field( wp_unslash( $session_method ) );
            }
        }

        // If no method selected yet, determine default/only option
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( empty( $_POST['tropipay_payment_method'] ) ) {
            $tropipaymentmethods = $this->get_option('tropipaymentmethods');
            if ( $tropipaymentmethods === 'onlycard' ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $_POST['tropipay_payment_method'] = 'card';
            } elseif ( $tropipaymentmethods === 'onlytpp' ) {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $_POST['tropipay_payment_method'] = 'balance';
            } else {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $_POST['tropipay_payment_method'] = 'card'; // Fallback default
            }
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        if ( ! empty( $_POST['tropipay_payment_method'] ) ) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            update_post_meta( $order_id, 'tropipay_payment_method', sanitize_text_field( wp_unslash( $_POST['tropipay_payment_method'] ) ) );
        }

        $order = new WC_Order($order_id);
        $logActivo=$this->tropipayactivar_log;
        $this->tropipayescribirLog_wc($this->idLog." -- "."Acceso a la opción de pago con tarjeta de Tropipay",$logActivo);
        // Return receipt_page redirect
        return array(
            'result' 	=> 'success',
            'redirect'	=> $order->get_checkout_payment_url( true )
        );
    }

    function generate_tropipay_form( $order_id ) {
            // Version
        $merchantModule = 'woocommerce_tropipay_5.0.0';

        //Recuperamos los datos de config.
        $logActivo=$this->get_option('tropipayactivar_log');

        // Reutilizar enlace de pago existente si ya fue generado previamente para este pedido
        $existing_paylink = get_post_meta($order_id, '_tropipay_paylink_url', true);
        if (!empty($existing_paylink)) {
            $this->tropipayescribirLog_wc($this->idLog." -- "."Reutilizando enlace de pago existente: " . $existing_paylink, $logActivo);
            $action = $existing_paylink;
            $order = wc_get_order($order_id);
            return '<h4>' . esc_html__( 'Espere, por favor...', 'tropipay-gateway-for-woocommerce' ) . '</h4>' .
                   '<form action="' . esc_url( $action ) . '" method="GET" id="tropipay_payment_form" style="display:none;">' .
                        '<input type="submit" class="button-alt" id="submit_tropipay_payment_form" value="' . esc_attr__( 'Pagar con Tropipay', 'tropipay-gateway-for-woocommerce' ) . '" />' .
                        '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . esc_html__( 'Cancelar Pedido', 'tropipay-gateway-for-woocommerce' ) . '</a>' .
                   '</form>';
        }

        $clientid=$this->get_option('clientid');
        $clientsecret=$this->get_option('clientsecret');
        $monedaconf=$this->get_option('tropipaymoneda');
        $entorno=$this->get_option('tropipayentorno');
        $tropimethod=$this->get_option('tropimethod');
        $tropipaymentmethods=$this->get_option('tropipaymentmethods');
        $tropipaypaymentcardtype = $this->get_option('tropipaypaymentcardtype', 1);

        $this->tropipayescribirLog_wc($this->idLog." -- "."Acceso al formulario de pago con tarjeta de Tropipay",$logActivo);

        //Callback
        $urltienda = add_query_arg( 'v', '50', $this->notify_url );

        //Objeto tipo pedido
        $order = new WC_Order($order_id);

        //Calculo del precio total del pedido
        /*$transaction_amount = number_format( (float) ($order->get_total()), 2, '.', '' );
        $transaction_amount = str_replace('.','',$transaction_amount);
        $transaction_amount = floatval($transaction_amount);*/
        $transaction_amount = (int) str_replace(',', '', number_format($order->get_total() * 100, 2));

        if($monedaconf === 'AUTO') {
            $moneda = $order->get_currency();
        } else {
            $moneda = $monedaconf;
        }


        $numpedido =  str_pad($order_id, 8, "0", STR_PAD_LEFT).gmdate('is');

        //Se establece el entorno del SIS
        if($entorno=="Sandbox"){
            $tropipay_server="https://tropipay-dev.herokuapp.com";
            $environment="develop";
        }
        else if($entorno=="Live"){
            $tropipay_server="https://www.tropipay.com";
            $environment="production";
        }

        $srv = new TropiPay($clientid, $clientsecret, $environment);

        $datetime = new DateTime('today');
        $arraycliente = array();
        $arraycliente["name"]=$order->get_billing_first_name();
        $arraycliente["lastName"]=$order->get_billing_last_name();
        $arraycliente["address"]=$order->get_billing_address_1() . ", " . $order->get_billing_city() . ", " . $order->get_billing_postcode();
        if($order->get_billing_phone()) {
            $arraycliente["phone"]=$order->get_billing_phone();
        } else {
            $arraycliente["phone"]=$order->get_shipping_phone();
        }
        $arraycliente["email"]=$order->get_billing_email();
        //$arraycliente["countryId"] = 1;
        $arraycliente["countryIso"] = $order->get_billing_country();
        if(strlen($arraycliente["countryIso"])==0) {
            $arraycliente["countryId"] = 1;
            unset( $arraycliente["countryIso"] );
        }
        $arraycliente["termsAndConditions"] = true;
        $arraycliente["state"]=$order->get_billing_state();
        $arraycliente["city"]=$order->get_billing_city();
        $arraycliente["postCode"]=$order->get_billing_postcode();
        //$moneda=$this->get_option('tropipaymoneda');
        $datos=array(
            "paymentcardType" => $tropipaypaymentcardtype,
            "reference" => $numpedido,
            // translators: %s: Order ID
            "concept" => sprintf( __( 'Pedido %s', 'tropipay-gateway-for-woocommerce' ), $order->get_id() ),
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
            "favorite" => false
        );
        $selectedMethod = get_post_meta( $order_id, 'tropipay_payment_method', true );
        switch($selectedMethod) {
            case 'card':
                $datos["paymentMethods"]=array("EXT");
                break;
            case 'balance':
                $datos["paymentMethods"]=array("TPP");
                break;
            default:
                if ($tropipaymentmethods === 'onlycard') {
                    $datos["paymentMethods"] = array("EXT");
                } elseif ($tropipaymentmethods === 'onlytpp') {
                    $datos["paymentMethods"] = array("TPP");
                } else {
                    $datos["paymentMethods"] = array("EXT", "TPP");
                }
                break;
        }

        if (!empty($this->accountId)) {
            $datos['accountId'] = (int) $this->accountId;
        }

        $paylink = $srv->createPaylink($datos);

        // Check if paylink creation was successful and data exists before accessing shortUrl
        if (!isset($paylink['data']['shortUrl'])) {
             $this->tropipayescribirLog_wc($this->idLog." -- "."Error al crear Paylink: No se encontró shortUrl. Respuesta: " . json_encode($paylink), $logActivo);
             return '<p class="woocommerce-error">'.__('Hubo un error al generar el enlace de pago. Por favor, inténtelo de nuevo o contacte con el soporte.', 'tropipay-gateway-for-woocommerce').'</p>';
        }
        $shorturl=$paylink['data']['shortUrl'];
        update_post_meta( $order_id, '_tropipay_paylink_url', $shorturl );

        //$paymenturl=$paylink['data']['paymentUrl'];

        //$paymenturl=$paylink['data']['rawUrlPayment'];

        $action = $shorturl;

        return '<h4>' . esc_html__( 'Espere, por favor...', 'tropipay-gateway-for-woocommerce' ) . '</h4>' .
               '<form action="' . esc_url( $action ) . '" method="GET" id="tropipay_payment_form" style="display:none;">' .
                    '<input type="submit" class="button-alt" id="submit_tropipay_payment_form" value="' . esc_attr__( 'Pagar con Tropipay', 'tropipay-gateway-for-woocommerce' ) . '" />' .
                    '<a class="button cancel" href="' . esc_url( $order->get_cancel_order_url() ) . '">' . esc_html__( 'Cancelar Pedido', 'tropipay-gateway-for-woocommerce' ) . '</a>' .
               '</form>';
        
    }

    function tropipay_payments_get_order_id($num) {
      return intval(substr($num,0,-4));
    }

    function check_tropipay_response() {
        $this->idLog = $this->generateIdLog();
        $logActivo = $this->tropipayactivar_log;
        $estado = !empty($this->tropipayestado) ? $this->tropipayestado : 'processing';

        $responsej = file_get_contents('php://input');
        $response = json_decode($responsej, true);

        if ( is_array( $response ) ) {
            $response = $this->sanitize_array_recursive( $response );
        }

        // Check if response and data key exist before accessing nested keys
        if (!isset($response['data']) || !is_array($response['data'])) {
            $this->tropipayescribirLog_wc($this->idLog." -- "."Respuesta JSON inválida o sin clave 'data'", $logActivo);
            wp_send_json_error('Error procesando la respuesta de Tropipay.');
            return;
        }

        $data = $response['data']; // Use a variable for easier access

        // Safely access and explicitly sanitize/cast data keys
        $ds_amount = isset($data["amount"]) ? sanitize_text_field($data["amount"]) : null;
        $ds_order = isset($data["reference"]) ? sanitize_text_field($data["reference"]) : null;
        $ds_bankordercode = isset($data["bankOrderCode"]) ? sanitize_text_field($data["bankOrderCode"]) : null;
        $ds_original_amount = isset($data["originalCurrencyAmount"]) ? intval($data["originalCurrencyAmount"]) : null;
        $ds_merchant_clientid = $this->get_option('clientid');
        $ds_merchant_clientsecret = $this->get_option('clientsecret');
        $ds_reference = $ds_order;
        $ds_currency = isset($data["currency"]) ? sanitize_text_field($data["currency"]) : null;
        $firma_remota = isset($data["signaturev3"]) ? sanitize_text_field($data["signaturev3"]) : null;
        $response_status = isset($response["status"]) ? sanitize_text_field($response["status"]) : null;

        // Check if essential data is present
        if (is_null($ds_reference) || is_null($ds_original_amount) || is_null($ds_bankordercode) || is_null($firma_remota) || is_null($response_status)) {
            $response_safe_json = wp_json_encode($response);
            $this->tropipayescribirLog_wc($this->idLog." -- "."Faltan datos esenciales en la respuesta JSON: " . $response_safe_json, $logActivo);
            wp_send_json_error('Error: Datos incompletos en la respuesta de Tropipay.');
            return;
        }

        $order_id = $this->tropipay_payments_get_order_id($ds_reference);
        $order = wc_get_order($order_id);
        if (!$order) {
            $this->tropipayescribirLog_wc($this->idLog." -- "."No se ha encontrado el pedido",$logActivo);
            wp_send_json_error('Pedido no encontrado.');
            return;
        }

        // --- IDEMPOTENCIA ---
        // Si el pedido ya fue marcado como procesado/pagado previamente, retornar éxito inmediato sin duplicar
        if ($order->is_paid() || get_post_meta($order_id, '_tropipay_processed', true) === 'yes' || get_post_meta($order_id, 'bankOrderCode', true) === $ds_bankordercode) {
            $this->tropipayescribirLog_wc($this->idLog." -- "."Pedido " . $order_id . " ya procesado por idempotencia.", $logActivo);
            wp_send_json_success('Pedido ya procesado anteriormente.');
            return;
        }

        $transaction_amount = (int) str_replace(',', '', number_format($order->get_total() * 100, 2));
        // Use the safely accessed original amount
        if($transaction_amount != abs($ds_original_amount)) {
            $this->tropipayescribirLog_wc($this->idLog." -- "."No coincide el amount. Esperado: " . $transaction_amount . " Recibido: " . $ds_original_amount, $logActivo);
            wp_send_json_error('Discrepancia en el importe.');
            return;
        }

        // --- VALIDAR MONEDA ---
        $expected_currency = ($this->get_option('tropipaymoneda') === 'AUTO') ? $order->get_currency() : $this->get_option('tropipaymoneda');
        if (!empty($ds_currency) && strtoupper($ds_currency) !== strtoupper($expected_currency)) {
            $this->tropipayescribirLog_wc($this->idLog." -- "."No coincide la moneda. Esperada: " . $expected_currency . " Recibida: " . $ds_currency, $logActivo);
            wp_send_json_error('Discrepancia en la moneda.');
            return;
        }

        // Use the safely accessed original amount for signature calculation
        $firma_local=hash('sha256', $ds_bankordercode . $ds_merchant_clientid . sha1($ds_merchant_clientsecret) . $ds_original_amount);
        $this->tropipayescribirLog_wc($this->idLog." -- "."firma remota: " .$firma_remota ,$logActivo);
        $this->tropipayescribirLog_wc($this->idLog." -- "."firma local: " .$firma_local ,$logActivo);
        
        $response_safe_json = wp_json_encode($response);
        $this->tropipayescribirLog_wc($this->idLog." -- "."response: " .$response_safe_json ,$logActivo);
        
        if($firma_local==$firma_remota) {
            // Use the safely accessed response status
            if($response_status=="OK") {
                // Marcar como procesado por seguridad
                update_post_meta($order_id, '_tropipay_processed', 'yes');
                update_post_meta($order_id, 'reference', $ds_order);
                update_post_meta($order_id, 'bankOrderCode', $ds_bankordercode);

                // Completar el pago oficialmente en WooCommerce
                // Esto marca el estado, guarda el id de transacción, reduce stock una vez y envía correos automáticamente
                $order->payment_complete($ds_bankordercode);

                if (!empty($estado) && $estado !== 'processing' && $estado !== $order->get_status()) {
                    $order->update_status($estado, __('Estado personalizado configurado.', 'tropipay-gateway-for-woocommerce'));
                }

                $this->tropipayescribirLog_wc($this->idLog." -- "."Operación finalizada. Respuesta de TropiPay: OK. PEDIDO ACEPTADO",$logActivo);
                wp_send_json_success('Pago completado con éxito.');
            }
            else {
                $this->tropipayescribirLog_wc($this->idLog." -- "."Operación finalizada. Respuesta de TropiPay: KO. PEDIDO RECHAZADO",$logActivo);
                $order->update_status('failed', __('El pago en TropiPay fue rechazado.', 'tropipay-gateway-for-woocommerce'));
                wp_send_json_error('Pago rechazado por TropiPay.');
            }
        }
        else {
            $this->tropipayescribirLog_wc($this->idLog." -- "."No coinciden las firmas",$logActivo);
            wp_send_json_error('Firma de seguridad inválida.');
        }
    }

    function receipt_page_tropipay( $order_id ) {
        if ( self::$receipt_page_rendered ) {
            return;
        }
        self::$receipt_page_rendered = true;

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $logActivo=$this->tropipayactivar_log;
        $this->tropipayescribirLog_wc($this->idLog." -- "."Acceso a la página de confirmación de la opción de pago con tarjeta de Tropipay",$logActivo);
        
        $selectedMethod = get_post_meta( $order_id, 'tropipay_payment_method', true );

        $form_html = $this->generate_tropipay_form($order_id);

        $allowed_html = array(
            'h4' => array(),
            'p' => array(
                'class' => array(),
            ),
            'form' => array(
                'action' => array(),
                'method' => array(),
                'id'     => array(),
                'style'  => array(),
            ),
            'input' => array(
                'type'  => array(),
                'class' => array(),
                'id'    => array(),
                'value' => array(),
            ),
            'a' => array(
                'class' => array(),
                'href'  => array(),
            ),
        );

        // Si falló la generación del paylink, no bloqueamos la página para permitir un posterior reintento
        if (strpos($form_html, 'woocommerce-error') !== false) {
            echo wp_kses( $form_html, $allowed_html );
            return;
        }

        update_post_meta( $order_id, 'tropipay_payment_receipt', 'si' );
        
        if($this->get_option('tropimethod')!='embed' || $selectedMethod === 'balance') {
            echo '<p>' . esc_html__( 'Gracias por su pedido, por favor espera mientras le redireccionamos a la plataforma de pago.', 'tropipay-gateway-for-woocommerce' ) . ' <img src="' . esc_url( plugins_url( 'pages/assets/images/loading.gif', TROPIPAY_MAIN_FILE ) ) . '" alt="" /></p>';
        }
        else {
            if($selectedMethod) {
                echo '<p>' . esc_html__( 'Gracias por su pedido, por favor introduzca los datos de su Tarjeta.', 'tropipay-gateway-for-woocommerce' ) . '</p>';
            }
        }
        
        echo wp_kses( $form_html, $allowed_html );
    }

    function tropipayescribirLog_wc($texto,$activo) {
        if($activo=="si"){
            // Log
            $this->log->add( 'tropipay', $texto."\r\n");
        }
    }
}

    function tropipay_get_bankordercode($order_id) {
        return get_post_meta( $order_id, 'bankOrderCode', true );
    }
