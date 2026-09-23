# Núcleo de Pagos

Aplicación web para llevar el control de **préstamos** y sus **abonos**: registras
cuánto prestaste, cómo y por cuánto tiempo te pagarán, y cada abono descuenta del
saldo total en tiempo real.

## Características

- Alta de préstamos con **concepto**, deudor, cantidad prestada, **frecuencia**
  (diario, semanal, quincenal, mensual, anual), **cuota por periodo** y **número de periodos**.
- Cálculo automático de la **cantidad total a pagar** (cuota × periodos), tanto en el
  navegador (en vivo) como en la base de datos (columna generada).
- Registro de **abonos** que restan del saldo al instante (AJAX) con anillo de progreso.
- Historial de pagos, indicador de préstamo **liquidado** y métricas globales.
- Interfaz oscura "consola financiera": aurora animada, glassmorphism y tipografía
  monoespaciada con figuras tabulares para el dinero.

## Stack

- **PHP 8.3** (PDO, tipado estricto, CSRF, sentencias preparadas) sobre Apache
- **PostgreSQL 17**
- **Bootstrap 5** + CSS propio + JavaScript vanilla
- **Docker Compose**

## Cómo ejecutarlo

```bash
docker compose up -d --build
```

Luego abre <http://localhost:8080>.

Para cambiar el puerto, la base o las credenciales, edita el archivo `.env`.

### Comandos útiles

```bash
docker compose logs -f app     # ver logs de la app
docker compose down            # detener
docker compose down -v         # detener y borrar la base de datos
```

## Estructura

```
pagos/
├─ docker-compose.yml
├─ .env
├─ db/
│  └─ init.sql              # esquema + vista de resumen + datos de ejemplo
└─ app/
   ├─ Dockerfile
   ├─ apache.conf
   ├─ public/              # document root
   │  ├─ index.php         # front controller / enrutado
   │  └─ assets/{css,js}
   ├─ src/                 # Database, PrestamoRepository, helpers
   └─ views/               # layout, home, prestamo, error
```
