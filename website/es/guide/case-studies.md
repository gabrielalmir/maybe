# Casos de estudio

## Email transaccional

Valida el contrato del pedido antes de enviar el correo. Representa el envío
como `Result` y permite que una confirmación persistida no dependa de que el
SMTP haya respondido a tiempo.

## Integración SAP

Convierte respuestas ambiguas y excepciones del cliente legado en un `Result`
con una causa estable. Así los reintentos distinguen un timeout temporal de un
error de datos que necesita corrección.

## Validación de contratos

Usa `Schema` en el borde y crea un `DTO` solo después de validar. El resto del
dominio trabaja con datos tipados y no con arrays de request.
