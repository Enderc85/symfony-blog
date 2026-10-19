# Cambios realizados para corregir el login por email

## Problema

El formulario de login (`templates/security/login.html.twig`) enviaba el campo de usuario con el nombre `email`:

```html
<input type="email" name="email" id="inputEmail" ...>
```

Sin embargo, el componente `form_login` de Symfony busca **por defecto** un campo llamado `_username` en los datos del formulario POST. Al no encontrarlo, el login fallaba aunque el email y la contraseña fueran correctos.

## Causa raíz

El sistema de autenticación de Symfony (`form_login`) tiene los siguientes parámetros por defecto:

| Parámetro            | Valor por defecto |
|----------------------|-------------------|
| `username_parameter` | `_username`       |
| `password_parameter` | `_password`       |

El formulario enviaba `email` y `password`, que **no coincidían** con los valores por defecto.

## Solución aplicada

Se modificó el archivo `config/packages/security.yaml` para indicarle explícitamente a Symfony qué campos del formulario contienen el identificador de usuario y la contraseña.

### Archivo modificado: `config/packages/security.yaml`

```diff
         form_login:
             login_path: app_login
             check_path: app_login
+            username_parameter: email
+            password_parameter: password
             enable_csrf: true
```

Con estos dos parámetros:

- **`username_parameter: email`** — Symfony leerá el campo `email` del formulario POST como el identificador del usuario.
- **`password_parameter: password`** — Symfony leerá el campo `password` del formulario POST como la contraseña (aunque `password` ya era el nombre que se enviaba, se incluye explícitamente para mayor claridad y consistencia).

## Flujo de autenticación completo

```
Usuario introduce email + password
        ↓
POST /login  →  email=user@example.com & password=secret
        ↓
security.yaml (form_login)
  username_parameter: email  →  busca el campo "email"  ✓
  password_parameter: password →  busca el campo "password" ✓
        ↓
UserProvider (app_user_provider)
  entity: App\Entity\User
  property: email  →  busca el usuario por email en la BD ✓
        ↓
Verificación de contraseña hasheada
        ↓
Login exitoso → redirección
```

## Archivos afectados

| Archivo | Cambio |
|--------|--------|
| `config/packages/security.yaml` | Añadidos `username_parameter: email` y `password_parameter: password` en `form_login` |

## Sin cambios necesarios en

- `templates/security/login.html.twig` — el formulario ya enviaba los campos con los nombres correctos.
- `src/Entity/User.php` — la entidad ya implementa `UserInterface` correctamente con `getUserIdentifier()` retornando el email.
- `src/Controller/SecurityController.php` — el controlador ya funcionaba correctamente.
- `src/Repository/UserRepository.php` — el repositorio no requería cambios.
