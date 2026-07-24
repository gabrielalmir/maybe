# Object Calisthenics con Maybe

Maybe aplica reglas simples para mantener sus primitives pequeños y
componibles: estado privado, métodos cortos, value objects para conceptos
repetidos y colecciones que exponen comportamiento en lugar de arrays internos.

En la práctica:

- modela rutas de error con `ValidationError` y `Path`;
- usa `ValidationErrorBag` como colección iterable y contable;
- conserva la inmutabilidad de schemas y resultados;
- mueve transformaciones complejas a tipos con una sola responsabilidad.

Estas reglas no son una religión de estilo: ayudan a que la frontera de datos
sea visible y comprobable.
