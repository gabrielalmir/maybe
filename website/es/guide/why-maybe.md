# ¿Por qué Maybe?

El código de negocio suele esconder decisiones importantes detrás de `null`,
excepciones genéricas y arrays sin forma. Maybe convierte esas decisiones en
valores explícitos y composables.

| Necesidad | Primitive |
| --- | --- |
| Un valor puede no existir | `Option<T>` |
| Una operación puede fallar | `Result<T, E>` |
| Input externo debe validarse | `Schema` |
| Input validado debe mapearse a un objeto | `DTO` |
| Trabajo independiente necesita otro proceso | `Async` |

Maybe no sustituye autenticación, autorización, escaping, CSRF, SQL seguro,
gestión de secretos ni observabilidad de la aplicación.

Siguiente: [Tutorial](/es/guide/tutorial) o [Referencia de API](/es/guide/api-reference).
