<?php
// Asegura que la clase de Sesión (usada para guardar datos de usuario) esté disponible
require_once '../app/core/Session.php';

/**
 * Clase controladora responsable de manejar todas las acciones de autenticación
 * de usuario, como iniciar sesión (login), registrarse (registro) y cerrar sesión (logout).
 */
class AuthController {
    // Propiedad para almacenar una instancia del modelo de usuario
    private $usuarioModel;

    /**
     * Constructor del controlador.
     * @param object $db La conexión a la base de datos (inyectada desde el núcleo de la aplicación).
     */
    public function __construct($db) {
        // Inicializa el modelo de usuario, que interactúa con la base de datos.
        $this->usuarioModel = new UsuarioModel($db);
    }

    /**
     * Maneja el proceso de inicio de sesión de los usuarios.
     */
    public function login() {
        // 1. Verifica si se han enviado datos por el método POST (es decir, si se envió el formulario de login)
        if ($_POST) {
            // Recoge el correo y la contraseña del formulario. Usa el operador '??' para asignar una cadena vacía si no existen.
            $correo = $_POST['correo'] ?? '';
            $contrasena = $_POST['contrasena'] ?? '';
            // Obtiene la dirección IP del usuario para registrar los intentos de acceso (seguridad).
            $ip = $_SERVER['REMOTE_ADDR'];
            $error = ''; // Inicializa la variable de error.

            // 2. Comprueba si el correo está temporalmente bloqueado (por intentos fallidos previos)
            if ($this->usuarioModel->estaBloqueado($correo)) {
                $error = "Su cuenta fue bloqueada por múltiples intentos fallidos. Inténtelo más tarde.";
                // Registra el intento fallido en el log, incluso si está bloqueado.
                // Se usa 'null' para id_usuario porque aún no se ha verificado si el usuario existe.
                $this->usuarioModel->registrarIntentoAcceso(null, $ip, 'fallido', 'Usuario');
            } else {
                // 3. Busca el usuario en la base de datos por su correo
                $usuario = $this->usuarioModel->buscarPorCorreo($correo);

                // 4. Si se encontró un usuario con ese correo
                if ($usuario) {
                    // 5. Verifica si la contraseña proporcionada coincide con el hash almacenado en la DB
                    if (password_verify($contrasena, $usuario['contrasena'])) {
                        
                        // --- INICIO DE SESIÓN EXITOSO ---
                        
                        // Establece las variables de sesión para mantener al usuario autenticado y guardar sus datos
                        Session::set('user_id', $usuario['id_usuario']);
                        Session::set('user_nombre', $usuario['nombre'] . ' ' . $usuario['apellido']);
                        Session::set('user_rol', $usuario['id_rol']);
                        Session::set('user_email', $usuario['correo']);

                        // Registra el intento de acceso como exitoso
                        $this->usuarioModel->registrarIntentoAcceso($usuario['id_usuario'], $ip, 'éxito', $usuario['id_rol']);
                        
                        // Redirige al usuario según su rol (Rol 1 = Admin)
                        if ($usuario['id_rol'] == 1) {
                            header("Location: /prestlab/public/admin/dashboard.php");
                        } else {
                            // Rol 2 (o cualquier otro) = Usuario normal
                            header("Location: /prestlab/public/usuario/dashboard.php");
                        }
                        exit(); // Termina la ejecución para asegurar la redirección
                    }
                }

                // --- INICIO DE SESIÓN FALLIDO (Credenciales incorrectas o usuario no existe) ---
                
                // Obtiene el ID del usuario (si se encontró) o null (si no se encontró) para registrar el error.
                $id_usuario = $usuario['id_usuario'] ?? null;
                // Registra el intento de acceso fallido
                $this->usuarioModel->registrarIntentoAcceso($id_usuario, $ip, 'fallido', 'Usuario');
                
                // Cuenta cuántos intentos fallidos ha tenido este correo en un tiempo reciente
                $intentos = $this->usuarioModel->contarIntentosFallidos($correo);
                $intentos_restantes = 3 - $intentos; // Calcula los intentos restantes (asumiendo 3 como límite)
                
                // Muestra el mensaje de error apropiado
                if ($intentos_restantes > 0) {
                    $error = "Credenciales incorrectas. Intentos restantes: " . $intentos_restantes;
                } else {
                    // Si el usuario superó el límite, el mensaje cambia a bloqueo.
                    $error = "Su cuenta fue bloqueada por múltiples intentos fallidos. Inténtelo más tarde.";
                }
            }
        }

        // Carga la vista del formulario de inicio de sesión. La variable $error estará disponible aquí.
        include_once '../app/views/auth/login.php';
    }

    /**
     * Maneja el proceso de registro de nuevos usuarios.
     */
    public function registro() {
        $mensaje = ''; // Mensaje a mostrar al usuario (éxito o error)
        $tipo_mensaje = ''; // Tipo de mensaje (ej: 'success' para verde, 'danger' para rojo)

        // 1. Verifica si se han enviado datos por el método POST (formulario de registro)
        if ($_POST) {
            // Recoge y limpia los datos del formulario (elimina espacios en blanco al inicio/final)
            $nombre = trim($_POST['nombre'] ?? '');
            $apellido = trim($_POST['apellido'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $contrasena = $_POST['contrasena'] ?? '';
            $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

            // 2. Realiza la validación de los datos
            if (empty($nombre) || empty($apellido) || empty($correo) || empty($contrasena)) {
                $mensaje = "Todos los campos son obligatorios.";
                $tipo_mensaje = 'danger';
            } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $mensaje = "El formato del correo electrónico no es válido.";
                $tipo_mensaje = 'danger';
            } elseif (strlen($contrasena) < 8) {
                $mensaje = "La contraseña debe tener al menos 8 caracteres.";
                $tipo_mensaje = 'danger';
            } elseif ($contrasena !== $confirmar_contrasena) {
                $mensaje = "Las contraseñas no coinciden.";
                $tipo_mensaje = 'danger';
            } else {
                // 3. Si la validación pasa, intenta crear el usuario en la base de datos.
                // El rol 2 se asume como el rol por defecto para un usuario recién registrado.
                if ($this->usuarioModel->crearUsuario($nombre, $apellido, $correo, $contrasena, 2)) {
                    $mensaje = "¡Registro exitoso! Serás redirigido al login en un momento...";
                    $tipo_mensaje = 'success';
                    // Nota: No se inicia sesión automáticamente, se espera que el usuario haga login.
                } else {
                    // El método 'crearUsuario' debería devolver 'false' si el correo ya existe.
                    $mensaje = "El correo electrónico ya está registrado.";
                    $tipo_mensaje = 'danger';
                }
            }
        }

        // Carga la vista del formulario de registro. Las variables $mensaje y $tipo_mensaje estarán disponibles.
        include_once '../app/views/auth/registro.php';
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout() {
        // Destruye todas las variables de sesión del usuario.
        Session::destroy();
        // Redirige al usuario a la página principal de la aplicación.
        header("Location: /prestlab/public/index.php");
        exit(); // Termina la ejecución para asegurar la redirección
    }
}
?>
