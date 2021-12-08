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


class WC_Tropipay extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'tropipay';
        //$this->icon               = home_url() . '/wp-content/plugins/redsys/pages/assets/images/Redsys.png';
        $this->method_title       = __( 'Pago con Tarjeta (Tropipay)', 'woocommerce' );
        $this->method_description = __( 'Esta es la opción de la pasarela de pago de Tropipay.', 'woocommerce' );
        $this ->notify_url        = add_query_arg( 'wc-api', 'WC_tropipay', home_url( '/' ) );
        $this->log                =  new WC_Logger();
        $this->idLog              = $this->generateIdLog();

        $this->has_fields         = false;

        // Load the settings
        $this->init_settings();
        $this->init_form_fields();

        $this->title              = $this->get_option( 'title' );
        $this->description        = $this->get_option( 'description' );

        // Get settings
        $this->tropipayentorno            = $this->get_option( 'tropipayentorno' );
        $this->tropipaymail             = $this->get_option( 'tropipaymail' );
        $this->tropipaypassw                = $this->get_option( 'tropipaypassw' );
        $this->tropipaymoneda             = $this->get_option( 'tropipaymoneda' );
        $this->tropipayactivar_log	  = $this->get_option( 'tropipayactivar_log' );
        $this->tropipayestado             = $this->get_option( 'tropipayestado' );


        // Actions
        add_action( 'woocommerce_receipt_tropipay', array( $this, 'receipt_page' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        //Payment listener/API hook
        add_action( 'woocommerce_api_wc_tropipay', array( $this, 'check_rds_response' ) );
    }

    function generateIdLog() {
        $vars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $stringLength = strlen($vars);
        $result = '';
        for ($i = 0; $i < 20; $i++) {
            $result .= $vars[rand(0, $stringLength - 1)];
        }
        return $result;
    }

    function init_form_fields() {
        global $woocommerce;

        $this->form_fields = array(
                'enabled' => array(
                        'title'       => __( 'Activar Tropipay:', 'woocommerce' ),
                        'label'       => __( 'Activar pago Tropipay', 'woocommerce' ),
                        'type'        => 'checkbox',
                        'description' => '',
                        'default'     => 'yes'
                ),
                'title' => array(
                        'title'       => __( 'Título', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
                        'default'     => __( 'Pago con tarjeta', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'description' => array(
                        'title'       => __( 'Descripción', 'woocommerce' ),
                        'type'        => 'textarea',
                        'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ),
                        'default'     => __( 'Esta es la opción de la pasarela de pago con tarjeta de Tropipay. Te ayudamos en todo lo que necesites desde nuestra web: <b>www.tropipay.com</b>', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'tropipayentorno' => array(
                        'title'       => __( 'Entorno de Tropipay', 'woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Entorno del proceso de pago.', 'woocommerce' ),
                        'default'     => 'Sandbox',
                        'desc_tip'    => true,
                        'options'     => array(
                                'Sandbox' => __( 'Sandbox', 'woocommerce' ),
                                'Live' => __( 'Live', 'woocommerce' )
                        )
                ),
                'tropipaymail' => array(
                        'title'       => __( 'Email de usuario', 'woocommerce' ),
                        'type'        => 'text',
                        'description' => __( 'Email de usuario', 'woocommerce' ),
                        'default'     => __( 'Mail', 'woocommerce' ),
                        'desc_tip'    => true,
                ),
                'tropipaypassw' => array(
                        'title'       => __( 'Password', 'woocommerce' ),
                        'type'        => 'password',
                        'description' => __( 'Password.', 'woocommerce' ),
                        'default'     => __( '', 'woocommerce' ),
                        'desc_tip'    => true
                ),
                'tropipaymoneda' => array(
                        'title'       => __( 'Tipo de Moneda', 'woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Moneda del proceso de pago.', 'woocommerce' ),
                        'default'     => 'EUR',
                        'desc_tip'    => true,
                        'options'     => array(
                                'EUR' => __( 'EURO', 'woocommerce' ),
                                'USD' => __( 'DOLAR', 'woocommerce' )
                        )
                ),
                'tropimethod' => array(
                    'title'           => __( 'Método del formulario', 'woocommerce' ),
                    'type'            => 'select',
                    'description'     => __( 'Formulario embebido o redirección externa', 'woocommerce'),
                    'default'         => 'redirect',
                    'desc_tip'        => true,
                    'options'         => array(
                        'redirect' => __('Redirección externa', 'woocommerce'),
                        'embed' => __('Formulario embebido' , 'woocommerce')
                    )
                ),
                'tropinotifyclient' => array(
                    'title'           => __( 'Enviar email del pago a cliente', 'woocommerce' ),
                    'type'            => 'select',
                    'description'     => __( '', 'woocommerce'),
                    'default'         => 'Si',
                    'desc_tip'        => true,
                    'options'         => array(
                        'Si' => __('Sí', 'woocommerce'),
                        'No' => __('No' , 'woocommerce')
                    )
                ),
                'tropiexpirationdays' => array(
                    'title'           => __( 'Días de cancelación', 'woocommerce'),
                    'type'            => 'select',
                    'description'     => __( '', 'woocommerce'),
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
                'tropipayactivar_log' => array(
                        'title'       => __( 'Activar Log', 'woocommerce' ),
                        'type'        => 'select',
                        'description' => __( 'Activar trazas de log.', 'woocommerce' ),
                        'default'     => 'no',
                        'desc_tip'    => true,
                        'options'     => array(
                                'no' => __( 'No', 'woocommerce' ),
                                'si' => __( 'Si', 'woocommerce' )
                        )
                ),
				'tropipayestado' => array(
					'title'       => __( 'Estado', 'redsys_wc' ),
					'type'        => 'select',
					'description' => __( 'Estado tras el pago.', 'redsys_wc' ),
					'default'     => 'no',
					'desc_tip'    => true,
					'options'     => array()
				)
			   	);
				
				$tmp_estados=wc_get_order_statuses();
				foreach($tmp_estados as $est_id=>$est_na){
					$this->form_fields['tropipayestado']['options'][substr($est_id,3)]=$est_na;
				}
    }

    function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order($order_id);
        $logActivo=$this->tropipayactivar_log;

        //Esquema de logs de Redsys
        //$this->log->add( 'redsys', 'Acceso a la opción de pago con tarjeta de REDSYS ');
        $this->tropipayescribirLog_wc($this->idLog." -- "."Acceso a la opción de pago con tarjeta de Tropipay",$logActivo);

        // Return receipt_page redirect
        return array(
            'result' 	=> 'success',
            'redirect'	=> $order->get_checkout_payment_url( true )
        );
    }

    function generate_redsys_form( $order_id ) {
            // Version
        $merchantModule = 'woocommerce_tropipay_1.0.0';


        //Recuperamos los datos de config.
        $logActivo=$this->get_option('tropipayactivar_log');
        $mail=$this->get_option('tropipaymail');
        $pass=$this->get_option('tropipaypassw');
        
        $moneda=$this->get_option('tropipaymoneda');
        
        
        $entorno=$this->get_option('tropipayentorno');

        $tropimethod=$this->get_option('tropimethod');
        $tropinotifyclient=$this->get_option('tropinotifyclient');
        if($tropinotifyclient=='Si') {
            $tropinotifyclient=true;
        }
        else {
            $tropinotifyclient=false;
        }


        //Esquema de logs de Redsys
        //$this->log->add( 'redsys', 'Acceso al formulario de pago con tarjeta de REDSYS ');
        $this->tropipayescribirLog_wc($this->idLog." -- "."Acceso al formulario de pago con tarjeta de Tropipay",$logActivo);

        //Callback
        $urltienda = $this -> notify_url;

        //Objeto tipo pedido
        $order = new WC_Order($order_id);

        //Calculo del precio total del pedido
        $transaction_amount = number_format( (float) ($order->get_total()), 2, '.', '' );
        $transaction_amount = str_replace('.','',$transaction_amount);
        $transaction_amount = floatval($transaction_amount);

        // Descripción de los productos
        /*$productos="";
        $products = WC()->cart->cart_contents;
        foreach ($products as $product) {
            $productos .= $product['quantity'].'x'.$product['data']->post->post_title.'/';
        }*/

        $numpedido =  str_pad($order_id, 8, "0", STR_PAD_LEFT).date('is');


        // Generamos la firma	
        /*$miObj = new RedsysAPI;
        $miObj->setParameter("DS_MERCHANT_AMOUNT",$transaction_amount);
        $miObj->setParameter("DS_MERCHANT_ORDER",$numpedido);
        $miObj->setParameter("DS_MERCHANT_MERCHANTCODE",$codigo);
        $miObj->setParameter("DS_MERCHANT_CURRENCY",$moneda);
        $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE",$trans);
        $miObj->setParameter("DS_MERCHANT_TERMINAL",$terminal);
        $miObj->setParameter("DS_MERCHANT_MERCHANTURL",$urltienda);
        $miObj->setParameter("DS_MERCHANT_URLOK",$this->get_return_url($order));
        $miObj->setParameter("DS_MERCHANT_URLKO",$order->get_cancel_order_url());
        $miObj->setParameter("Ds_Merchant_ConsumerLanguage",$idiomaFinal);
        $miObj->setParameter("Ds_Merchant_ProductDescription",$productos);
        $miObj->setParameter("Ds_Merchant_Titular",$order -> billing_first_name." ".$order -> billing_last_name);
        $miObj->setParameter("Ds_Merchant_MerchantData",sha1($urltienda));
        $miObj->setParameter("Ds_Merchant_MerchantName",$nombre);
        $miObj->setParameter("Ds_Merchant_PayMethods",$tipopago);
        $miObj->setParameter("Ds_Merchant_Module",$merchantModule);*/


        

        //Se establece el entorno del SIS
        if($entorno=="Sandbox"){
            $tropipay_server="https://tropipay-dev.herokuapp.com";
        }
        else if($entorno=="Live"){
            $tropipay_server="https://www.tropipay.com";
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $tropipay_server . "/api/access/login",
        //CURLOPT_HTTPHEADER => array ('Content-Type: application/json','Content-Length: ' . strlen($data_string)),
        CURLOPT_HTTPHEADER => array ('Content-Type: application/json'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"email\":\"" . $mail ."\",\"password\":\"" . $pass . "\"}",
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
        echo "cURL Error #:" . $err;
        }
        else {
            $character = json_decode($response);
            $tokent=$character->token;
            $datetime = new DateTime('today');
            //echo $datetime->format('Y-m-d');
            //$customerprofiled=commerce_customer_profile_load($order->commerce_customer_billing['und'][0]['profile_id']);
            $arraycliente["name"]=$order->get_billing_first_name();
            $arraycliente["lastName"]=$order->get_billing_last_name();
            $arraycliente["address"]=$order->get_billing_address_1() . ", " . $order->get_billing_city() . ", " . $order->get_billing_postcode();
            $arraycliente["phone"]=$order->get_billing_phone();
            $arraycliente["email"]=$order->get_billing_email();
            $arraycliente["countryId"] = 1;
            $arraycliente["countrySlug"] = $order->get_billing_country();
            $arraycliente["termsAndConditions"] = true;

            $moneda=$this->get_option('tropipaymoneda');

            $datos=array(
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
              //"urlFailed" => str_replace("&","%26",$order->get_cancel_order_url()),
              "urlFailed" => $order->get_cancel_order_url(),
              "urlNotification" => $urltienda,
              "serviceDate" => $datetime->format('Y-m-d'),
              "directPayment" => true,
              "client" => $arraycliente,
              "notifyClientByEmail" => $tropinotifyclient
            );

            $data_string2 = json_encode($datos);



            $curl = curl_init();
            curl_setopt_array($curl, array(
              CURLOPT_URL => $tropipay_server . "/api/paymentcards/",
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => "",
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 30,
              CURLOPT_CUSTOMREQUEST => "POST",
              CURLOPT_POSTFIELDS => $data_string2,
              CURLOPT_HTTPHEADER => array(
                "authorization: Bearer " . $tokent,
                "content-type: application/json"
              ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
              echo "cURL Error #:" . $err;
            } else {
              //echo $response;
              $character = json_decode($response);
              $shorturl=$character->shortUrl;
              $paymenturl=$character->paymentUrl;

              $action = $shorturl;
              //$form['#action'] = $shorturl;

            }

            if($tropimethod=="embed") {
                return '<iframe style="border:none;with:100%;height:450px;" src="' . urldecode($paymenturl)  .'"></iframe> <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancelar Pedido', 'tropipay').'</a>';
            }
            else {
                //Formulario que envía los datos del pedido y la redirección al formulario de acceso al TPV
                return '<form action="'.$action.'" method="GET" id="tropipay_payment_form">'.
                        '<input type="submit" class="button-alt" id="submit_tropipay_payment_form" value="'.__('Pagar con Tarjeta', 'tropipay').'" />'.
                        '<a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancelar Pedido', 'tropipay').'</a>
                    </form>';
            }
        }
    }

    function tropipay_payments_get_order_id($num) {
      return intval(substr($num,0,-4));
    }

    function check_rds_response() {
        $this->idLog = $this->generateIdLog();
        $logActivo=$this->tropipayactivar_log;
		$estado=$this->tropipayestado;

        $strrrr=file_get_contents('php://input');
        $ppd=json_decode($strrrr,true);
        $ds_amount = $ppd["data"]["amount"];
        $ds_order = $ppd["data"]["reference"];
        $ds_bankordercode = $ppd["data"]["bankOrderCode"];
        $ds_amount = $ppd["data"]["originalCurrencyAmount"];
        $ds_merchant_usermail = $this->tropipaymail;
        $ds_merchant_userpassword = $this->tropipaypassw;
        $ds_reference=$ppd["data"]["reference"];
        $ds_currency = $ppd["data"]["currency"];
        $firma_remota = $ppd["data"]["signature"];
        $firma_local=hash('sha256', $ds_bankordercode . $ds_merchant_usermail . sha1($ds_merchant_userpassword) . $ds_amount);
        if($firma_local==$firma_remota) {
          $order_id = $this->tropipay_payments_get_order_id($ds_reference);
          if ($order_id) {
            $order = new WC_Order($order_id);
            if($ppd["status"]=="OK") {
                $order->update_status($estado,__( 'Awaiting Tropipay payment', 'woocommerce' ));
                //$this->log->add( 'redsys', 'Operación finalizada. PEDIDO ACEPTADO ');
                $this->tropipayescribirLog_wc($this->idLog." -- "."Operación finalizada. PEDIDO ACEPTADO",$logActivo);
                $order->reduce_order_stock();
                add_post_meta( $order_id, 'reference', $ds_order );
                add_post_meta( $order_id, 'bankOrderCode', $ds_bankordercode );
                // Remove cart
                WC()->cart->empty_cart();
            }
            else {
                $order->update_status('cancelled',__( 'Awaiting Tropipay payment', 'woocommerce' ));
                WC()->cart->empty_cart();
                //$this->log->add( 'redsys', 'Operación finalizada. PEDIDO CANCELADO ');
                $this->tropipayescribirLog_wc($this->idLog." -- "."Operación finalizada. PEDIDO CANCELADO",$logActivo);
            }
          }
          else {
                wp_die( '<img src="'.home_url().'/wp-content/plugins/redsys/pages/assets/images/cross.png" alt="Desactivado" title="Desactivado" />
                Fallo en el proceso de pago.<br>Su pedido ha sido cancelado.' );
          }
            
        }
        else {
            wp_die( '<img src="'.home_url().'/wp-content/plugins/redsys/pages/assets/images/cross.png" alt="Desactivado" title="Desactivado" />
                Fallo en el proceso de pago.<br>Su pedido ha sido cancelado.' );
        }
    }

    function receipt_page( $order ) {
        $logActivo=$this->tropipayactivar_log;
        $this->tropipayescribirLog_wc($this->idLog." -- "."Acceso a la página de confirmación de la opción de pago con tarjeta de REDSYS",$logActivo);
        if($this->get_option('tropimethod')!='embed') {
            echo '<p>'.__('Gracias por su pedido, por favor pulsa el botón para pagar con Tarjeta.', 'redsys').'</p>';
        }
        else {
            echo '<p>'.__('Gracias por su pedido, por favor introduzca los datos de su Tarjeta.', 'redsys').'</p>';
        }
        echo $this -> generate_redsys_form($order);
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