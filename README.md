<<<<<<< HEAD
# Sistema de Préstamo de Equipos Tecnológicos

Proyecto académico PHP + MySQL + PDO + Composer + NuSOAP + Laragon.

## Instalación
1. Copiar la carpeta en `C:\laragon\www\prestamo-equipos`.
2. Ejecutar `composer install` dentro de la carpeta.
3. Abrir MySQL desde Laragon y ejecutar `database/schema.sql`.
4. Revisar credenciales en `config/database.php`.
5. URL del WSDL: `http://localhost/prestamo-equipos/servicio/server.php?wsdl`.
6. Importar esa URL en SoapUI.

## Importante
El archivo actual devuelve una envoltura XML sencilla para facilitar las pruebas académicas. Si se exige XML con tipos complejos completos sin texto serializado, debe ampliarse el WSDL y la conversión de respuestas.
=======
# Sistema de Préstamo de Equipos Tecnológicos — Servicio SOAP

Aplicación backend en PHP que expone, **exclusivamente vía SOAP** (NuSOAP),
la gestión de préstamos de equipos tecnológicos (computadores, proyectores,
tablets, cámaras, etc.) a estudiantes y docentes. No hay acceso directo a la
base de datos desde ningún cliente: todo pasa por el contrato WSDL.

## Arquitectura

```
public/server.php        -> Endpoint SOAP (WSDL + despacho de operaciones)
src/Services/
  PrestamoService.php     -> Lógica de negocio (reglas, validaciones, transacciones)
src/Models/
  Equipo.php              -> Acceso a datos: tabla `equipos`
  Usuario.php             -> Acceso a datos: tabla `usuarios` (solicitantes)
  Prestamo.php             -> Acceso a datos: tabla `prestamos`
config/database.php       -> Conexión PDO (singleton)
database/schema.sql       -> Script de creación de la BD + datos de prueba
examples/cliente_prueba.php -> Cliente SOAP de ejemplo (ext-soap nativa de PHP)
```

Flujo de una petición: `Cliente SOAP -> server.php -> PrestamoService ->
Modelo (PDO) -> MySQL`. `server.php` nunca ejecuta SQL directamente; toda la
lógica de negocio (unicidad de código, disponibilidad, transacciones) vive en
`PrestamoService`.

## Operaciones SOAP expuestas

| Operación            | Parámetros                                                              | Descripción |
|-----------------------|---------------------------------------------------------------------------|-------------|
| `registrarEquipo`     | codigo, nombre, tipo, estado                                            | Crea un equipo. Falla si el código ya existe. |
| `consultarEquipo`     | id                                                                       | Devuelve un equipo por id. |
| `listarEquipos`       | —                                                                         | Lista todos los equipos. |
| `actualizarEquipo`    | id, codigo, nombre, tipo, estado                                        | Actualiza datos/estado (parámetros vacíos = sin cambio). |
| `registrarPrestamo`   | equipoId, documento, nombreSolicitante, correoSolicitante                | Solo permite prestar equipos `Disponible`; el equipo pasa a `Prestado`; el solicitante se registra automáticamente si no existía. |
| `consultarPrestamos`  | estado (opcional: Activo, Devuelto, Cancelado, o vacío = todos)          | Lista préstamos con los datos de equipo y solicitante. |
| `registrarDevolucion` *(extra)* | prestamoId                                                    | Cierra un préstamo activo y libera el equipo (vuelve a `Disponible`). |

Todas las reglas de los criterios de aceptación están implementadas:
- Persistencia en MySQL vía PDO con sentencias preparadas.
- Código de equipo único, validado en `registrarEquipo` y `actualizarEquipo`.
- `registrarPrestamo` verifica `estado = 'Disponible'` antes de prestar y
  usa una transacción (crear préstamo + actualizar estado del equipo) para
  evitar estados inconsistentes.
- El solicitante siempre queda registrado en `usuarios` antes de crear el
  préstamo (se busca por documento; si no existe, se crea).

## Requisitos

- PHP 8.x con la extensión `pdo_mysql` habilitada.
- MySQL 8.x.
- Composer (las dependencias, incluida NuSOAP, ya están vendorizadas en este
  repo, así que `composer install` es opcional salvo que borres `vendor/`).
- Si vas a usar el cliente de ejemplo (`examples/cliente_prueba.php`), la
  extensión nativa `soap` de PHP también debe estar habilitada.

## Puesta en marcha

1. **Base de datos**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. **Configurar la conexión** en `config/database.php` (host, puerto, usuario,
   contraseña). Por defecto está pensado para un entorno Laragon
   (`127.0.0.1:3307`, usuario `root`, sin contraseña) — ajústalo a tu entorno.

3. **Levantar el servidor** (con el servidor embebido de PHP, o Apache/Nginx
   apuntando a `public/`):
   ```bash
   php -S localhost:8000 -t public
   ```

4. **WSDL disponible en:**
   ```
   http://localhost:8000/server.php?wsdl
   ```

5. **Probar con el cliente de ejemplo:**
   ```bash
   php examples/cliente_prueba.php
   ```
   Ejecuta las 6 operaciones en orden (incluida la operación extra de
   devolución) e imprime cada respuesta, incluyendo un intento fallido de
   volver a prestar un equipo ya prestado (para verificar la validación de
   disponibilidad).

## Notas de diseño

- El servicio se registró en modo `rpc/encoded` (clásico de NuSOAP) para
  máxima compatibilidad con clientes SOAP genéricos.
- Los préstamos nunca se eliminan de la base de datos: se conserva el
  historial completo cambiando el campo `estado`.
- Las excepciones de negocio (`equipo no disponible`, `código duplicado`,
  `equipo/préstamo inexistente`, etc.) se traducen en un SOAP Fault legible
  para el cliente, sin exponer detalles internos de la base de datos.
>>>>>>> 4aeddd56c171b86cb07c64082ff6ee02708d0c56
