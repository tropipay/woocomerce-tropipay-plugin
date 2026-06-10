=== Tropipay WooCommerce ===
Contributors: tropipay
Donate link: https://www.tropipay.com/
Tags: woocommerce, tropipay, payment gateway, gateway, payments
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 5.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Acepta pagos con tarjeta de crédito, débito y saldo TropiPay de forma segura y moderna en tu tienda WooCommerce, compatible con Gutenberg Blocks.

== Description ==

**Tropipay WooCommerce** es la pasarela de pago oficial para conectar tu tienda de comercio electrónico con la plataforma de pagos internacionales **TropiPay**. 

Permite a tus clientes pagar de manera sumamente rápida, intuitiva y segura a través de tarjetas de crédito o débito, así como directamente utilizando su saldo de cuenta TropiPay.

Esta versión 5.0.0 ha sido rediseñada desde cero cumpliendo con los estándares de rendimiento más estrictos y es **100% compatible con el nuevo Checkout de Bloques de WooCommerce (Gutenberg)**.

### Características Principales:
* **Checkout de Bloques Avanzado**: Totalmente compatible con la experiencia moderna de pago por bloques en WooCommerce.
* **Sincronización Inteligente de Cuentas**: Conecta tu tienda con tu cuenta TropiPay y carga de forma segura tus cuentas activas de destino con un solo clic.
* **Formulario Dinámico y Reactivo**: Interfaz administrativa fluida que oculta los campos de comisiones (fees) de forma interactiva si no deseas cobrarlas.
* **Rendimiento de Clase Empresarial**: Reutilización inteligente de enlaces de pago generados (Paylinks) para optimizar la velocidad de carga de la página del recibo a 0 ms.
* **Seguridad Máxima**: Validación de firmas criptográficas (SHA256) e inyección de Nonces para proteger la comunicación contra ataques y transacciones duplicadas.
* **Idempotencia Completa**: Webhook asíncrono y robusto que gestiona correctamente el stock y los estados de pedidos de forma segura e impermeable.

== Installation ==

1. Sube la carpeta del plugin `tropipay` al directorio `/wp-content/plugins/`.
2. Activa el plugin a través de la sección 'Plugins' en el panel de administración de WordPress.
3. Ve a **WooCommerce > Ajustes > Pagos** y selecciona **Tropipay**.
4. Configura tu entorno (Sandbox o Live) e introduce tu Client ID y Client Secret de TropiPay.
5. Haz clic en "Guardar cambios" y luego presiona "Sincronizar con TropiPay" para cargar de forma segura tus cuentas destino disponibles.
6. ¡Listo! Tu tienda ya puede aceptar pagos internacionales con TropiPay.

== Frequently Asked Questions ==

= ¿Necesito una cuenta de TropiPay? =
Sí, necesitas tener una cuenta de empresa activa en TropiPay. Puedes registrarte en [tropipay.com](https://www.tropipay.com/) para obtener tus claves de API (Client ID y Client Secret).

= ¿Es compatible con el nuevo checkout por bloques de WooCommerce? =
¡Totalmente! Tropipay WooCommerce incluye integración nativa con WooCommerce Blocks, asegurando que tu proceso de pago luzca moderno y sofisticado.

= ¿Qué ocurre si elimino el plugin? =
El plugin incluye un mecanismo de desinstalación limpia que elimina de forma segura de tu base de datos todas las claves configuradas y las cuentas sincronizadas para no dejar datos huérfanos.

== Screenshots ==

1. Configuración de la pasarela de pago en el panel de administración de WooCommerce.
2. Sincronización inteligente de cuentas destino mediante conexión directa de API.
3. Vista del checkout moderno por bloques de WooCommerce con las opciones de pago con tarjeta y saldo.

== Changelog ==

= 5.0.0 =
* Lanzamiento principal mayor.
* Compatibilidad total y nativa con WooCommerce Blocks (Checkout de Bloques).
* Implementación de sincronización inteligente de cuentas asíncrona mediante AJAX.
* Refactorización completa del Webhook asíncrono haciéndolo 100% seguro, robusto e de tipo idempotente.
* Adición de persistencia y reutilización de Paylinks optimizando el rendimiento de carga.
* Integración de script de desinstalación limpia (`uninstall.php`).