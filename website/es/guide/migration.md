# Migración incremental

Adopta Maybe en fronteras pequeñas:

1. Sustituye comprobaciones de `null` por `Option`.
2. Envuelve una operación falible con `Result`.
3. Valida request, CSV o mensajes de cola con `Schema`.
4. Construye un `DTO` después de validar.
5. Evalúa `Async` solo para trabajo independiente y serializable.

En la versión `0.4.0`, revisa especialmente la Async: sus límites de IPC son
16 MiB de entrada y 64 MiB de salida por defecto. Configura límites y timeout
desde código confiable, no desde parámetros de la petición.
