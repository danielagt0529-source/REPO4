# AquaStock — Gestión de artículos

Aplicación web responsive en PHP + MySQL con estética inspirada en Frutiger Aero: tonos aqua y menta, vidrio translúcido, brillos suaves y formas orgánicas.

## Funciones
- Crear, consultar, editar y eliminar artículos.
- Campos: nombre, marca, unidades y ubicación.
- Estadísticas de referencias, unidades, marcas y ubicaciones.
- Búsqueda por nombre, marca o ubicación; filtro por ubicación.
- Creación automática de la tabla `articulos` si no existe.
- Consultas PDO preparadas, escape HTML y token CSRF en formularios.

## Instalación
1. Sube `index.php`, `config.php` y `styles.css` a un servidor con PHP 8.1+ y PDO MySQL.
2. Configura las variables de entorno `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, o edita los valores en `config.php`.
3. Confirma que la base de datos exista y que el usuario tenga permisos para crear tablas.
4. Abre `index.php` en el navegador. La tabla se crea automáticamente al conectar.

## Seguridad
La contraseña no se incluye en el ZIP: `DB_PASS` está como `CAMBIA_ESTA_CLAVE`. Reemplázalo antes de usar la aplicación. Evita publicar credenciales reales en repositorios. Si una contraseña real se compartió en un chat o repositorio, cámbiala desde el proveedor de base de datos.
