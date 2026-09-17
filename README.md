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
