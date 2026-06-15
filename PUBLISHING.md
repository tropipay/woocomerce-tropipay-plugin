# Guía de Publicación en WordPress.org para Tropipay Gateway for WooCommerce

Esta guía describe el procedimiento paso a paso para realizar el despliegue inicial y futuros lanzamientos de la versión `5.0.0` del plugin **Tropipay Gateway for WooCommerce** a través del repositorio Subversion (SVN) oficial de WordPress.

---

## 📋 Requisitos Previos

Antes de comenzar, asegúrate de contar con lo siguiente:
1. **Credenciales de WordPress.org**:
   - Usuario: `tropipaydev` (distingue mayúsculas y minúsculas).
   - Contraseña de SVN: Genera una contraseña específica para SVN en tu perfil de WordPress.org, en la sección **Account & Security** ([Generar Contraseña SVN](https://make.wordpress.org/meta/handbook/tutorials-guides/svn-access/)).
2. **Subversion (SVN) instalado**:
   - Si no está instalado en tu máquina local, ejecútalo en la terminal:
     ```bash
     sudo apt-get update && sudo apt-get install -y subversion
     ```

---

## 🛠️ Procedimiento de Publicación

Hemos creado un script automatizado `prepare_svn.sh` en la raíz del proyecto para simplificar el proceso.

### Paso 1: Ejecutar el Script de Preparación
Ejecuta el script desde la raíz del proyecto. Este script descargará el repositorio SVN de WordPress.org, creará la estructura de directorios necesaria (`trunk/`, `tags/5.0.0/`, `assets/`) y copiará los archivos correspondientes.

```bash
./prepare_svn.sh
```

### Paso 2: (Opcional) Agregar Elementos de Diseño (Assets)
Si cuentas con imágenes promocionales para la tienda de WordPress (banner, icono o capturas de pantalla), cópialas a la carpeta `svn-checkout/assets/` antes del commit.
Formatos recomendados:
- **Banner principal**: `banner-772x250.png` y `banner-1544x500.png` (alta resolución).
- **Icono del plugin**: `icon-128x128.png` y `icon-256x256.png` (alta resolución).
- **Capturas de pantalla**: `screenshot-1.png`, `screenshot-2.png`, etc. (deben coincidir con el orden listado en la sección `== Screenshots ==` de tu `readme.txt`).

### Paso 3: Revisar el Estado del Repositorio
Cambia al directorio de trabajo de SVN y verifica qué archivos se van a subir:

```bash
cd svn-checkout
svn status
```

Los nuevos archivos deberían aparecer con el prefijo `A` (Added).

### Paso 4: Realizar el Envío (Commit)
Envía los archivos al repositorio oficial de WordPress.org utilizando tu usuario y contraseña:

```bash
svn commit --username tropipaydev -m "Initial release of version 5.0.0"
```

> [!IMPORTANT]
> SVN te solicitará la contraseña. Utiliza la contraseña de SVN que generaste en tu perfil de WordPress.org (no uses tu contraseña normal de inicio de sesión de la web, ni tu correo electrónico como usuario).

---

## ⚙️ Estructura del Repositorio en WordPress.org

El repositorio SVN resultante tendrá la siguiente estructura estándar:

```text
tropipay-gateway-for-woocommerce/
├── assets/         # Banners, iconos y capturas de pantalla de la tienda
├── branches/       # Ramas de desarrollo (generalmente vacío)
├── tags/
│   └── 5.0.0/      # Copia exacta y congelada de la versión 5.0.0
└── trunk/          # Versión de desarrollo más reciente (idéntica a tags/5.0.0)
```

---

## 💡 Recomendaciones para Siguientes Versiones

Cuando vayas a actualizar el plugin en el futuro (ej. versión `5.0.1`):
1. Actualiza el número de versión en `class-wc-tropipay.php` (`Version: 5.0.1`).
2. Actualiza la línea `Stable tag: 5.0.1` en `readme.txt` y describe los cambios en la sección `== Changelog ==`.
3. Vuelve a ejecutar `./prepare_svn.sh` (modificando la variable `VERSION` en el script si es necesario, o lo actualizaremos dinámicamente).
4. Sube la nueva etiqueta ejecutando:
   ```bash
   cd svn-checkout
   svn status
   svn commit --username tropipaydev -m "Release version 5.0.1"
   ```
