---
layout: home

hero:
  name: Maybe
  text: Haz explícitas la ausencia y el fallo.
  tagline: Option, Result, Schema, DTO y Async para aplicaciones PHP 7.4+ con pocas dependencias. Adopta una frontera cada vez.
  actions:
    - theme: brand
      text: Empieza en 5 minutos
      link: /es/guide/getting-started
    - theme: alt
      text: Ver una migración real
      link: /es/guide/migration

features:
  - icon:
      src: /icons/option.svg
    title: Option
    details: Modela valores opcionales sin repartir comprobaciones de null por toda la aplicación.
    link: /es/guide/option
    linkText: Leer Option
  - icon:
      src: /icons/result.svg
    title: Result
    details: Flujos tipados de éxito y error sin usar excepciones como control de flujo.
    link: /es/guide/result
    linkText: Leer Result
  - icon:
      src: /icons/schema.svg
    title: Schema
    details: Parsing y validación inmutables para strings, ints, enums, arrays y objetos.
    link: /es/guide/schema
    linkText: Leer Schema
  - icon:
      src: /icons/dto.svg
    title: DTO
    details: Convierte input sin confianza en objetos validados e inmutables.
    link: /es/guide/dto
    linkText: Leer DTO
---

## Empieza en la frontera que ya tienes

Sin acoplamiento a frameworks y sin reescritura completa. Valida el input,
modela una operación que puede fallar y decide el resultado en el borde.

<div class="maybe-install-card">
  <div>
    <p>Instala los primitives centrales</p>
    <code>composer require gabrielalmir/maybe</code>
  </div>
  <a class="VPButton medium brand" href="guide/getting-started.html">Lee los primeros pasos →</a>
</div>

¿Nuevo en Maybe? Lee [**¿Por qué Maybe?**](/es/guide/why-maybe), sigue el
[**Tutorial**](/es/guide/tutorial) o deja abierta la [**Referencia de API**](/es/guide/api-reference).
