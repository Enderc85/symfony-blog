# Integración del formulario CategoryFormType en categories.html.twig

## Contexto del problema

La vista `templates/admin/categories.html.twig` intentaba renderizar un formulario
de Symfony (`form_start(form)`, `form_widget(form.name)`, etc.), pero el controlador
`AdminController::categories()` **no le pasaba ninguna variable `form`**, lo que
provocaba un error de Twig en tiempo de ejecución.

Además, el controlador no manejaba el envío del formulario (método POST), por lo que
aunque el formulario se hubiera renderizado, los datos nunca se habrían persistido en
la base de datos.

---

## Paso a paso de los cambios realizados

### 1. Análisis de `CategoryFormType.php`

Se revisó `src/Form/CategoryFormType.php` para conocer exactamente qué campos define
el formulario:

```php
$builder
    ->add('name')                                          // campo de texto para el nombre
    ->add('Send', SubmitType::class, ['label' => 'Send']); // botón de envío
```

El formulario está vinculado a la entidad `App\Entity\Category` mediante:

```php
$resolver->setDefaults(['data_class' => Category::class]);
```

Esto significa que al hacer submit, el formulario populará automáticamente
un objeto `Category` con el valor del campo `name`.

---

### 2. Actualización de `AdminController.php`

**Archivo:** `src/Controller/AdminController.php`

Se añadieron los siguientes cambios al método `categories()`:

#### 2a. Nuevos `use` (imports)

```php
use App\Entity\Category;
use App\Form\CategoryFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
```

| Import | Por qué se necesita |
|--------|---------------------|
| `Category` | Entidad que el formulario populará |
| `CategoryFormType` | Clase del formulario a crear |
| `EntityManagerInterface` | Para persistir la categoría en la base de datos |
| `Request` | Para que Symfony pueda leer los datos POST enviados |

#### 2b. Firma del método actualizada

```diff
- public function categories(): Response
+ public function categories(Request $request, EntityManagerInterface $em): Response
```

Symfony inyecta automáticamente `Request` y `EntityManagerInterface` por
**inyección de dependencias** en los argumentos del método del controlador.

#### 2c. Lógica del formulario añadida

```php
// 1. Crear una instancia vacía de Category que el formulario populará
$category = new Category();

// 2. Crear el formulario ligado a esa instancia
$form = $this->createForm(CategoryFormType::class, $category);

// 3. Leer los datos del Request (POST) y mapearlos al objeto $category
$form->handleRequest($request);

// 4. Si el formulario fue enviado Y los datos son válidos...
if ($form->isSubmitted() && $form->isValid()) {
    $em->persist($category);   // Marcar la entidad para ser guardada
    $em->flush();              // Ejecutar el INSERT en la base de datos

    $this->addFlash('success', 'Category created successfully!');

    return $this->redirectToRoute('app_categories'); // POST-Redirect-GET
}

// 5. Pasar la "vista" del formulario a Twig
return $this->render('admin/categories.html.twig', [
    'form' => $form->createView(),
]);
```

> **Patrón POST-Redirect-GET**: tras un submit exitoso se redirige en lugar de volver
> a renderizar, lo que evita que el navegador reenvíe el formulario al refrescar la página.

---

### 3. Actualización de `categories.html.twig`

**Archivo:** `templates/admin/categories.html.twig`

Los campos del formulario (`form.name` y `form.Send`) ya coincidían con los
definidos en `CategoryFormType`, por lo que **no se cambiaron**. Solo se añadieron
dos mejoras:

#### 3a. Mensajes flash de éxito

```twig
{% for message in app.flashes('success') %}
    <div class="alert alert-success">{{ message }}</div>
{% endfor %}
```

Muestra el mensaje "Category created successfully!" después de un submit exitoso.

#### 3b. Errores de validación inline

```twig
{{ form_errors(form.name) }}
{{ form_widget(form.name, {'attr': {'class':'form-control', 'placeholder':'Category Name'}}) }}
```

`form_errors()` renderiza los mensajes de error de validación justo encima del campo
correspondiente (p. ej., si el campo `name` está vacío).

---

## Resumen de archivos modificados

| Archivo | Qué se cambió |
|---------|--------------|
| `src/Controller/AdminController.php` | Se añadieron imports, `Request` y `EntityManagerInterface` como parámetros, creación del formulario, `handleRequest`, persistencia en BD y paso de `form` a la vista |
| `templates/admin/categories.html.twig` | Se añadió renderizado de mensajes flash y `form_errors()` para errores inline |

## Archivos **no** modificados

| Archivo | Motivo |
|---------|--------|
| `src/Form/CategoryFormType.php` | Ya definía correctamente los campos `name` y `Send` vinculados a `Category` |
| `src/Entity/Category.php` | La entidad ya tenía el campo `name` con getter/setter |
