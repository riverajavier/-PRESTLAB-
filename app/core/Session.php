<?php
// Session.php - Clase Estática para la Gestión Segura de Sesiones

/**
 * Esta clase centraliza todas las interacciones con la sesión de PHP (superglobal $_SESSION).
 * Al ser estática, sus métodos pueden ser llamados directamente sin necesidad de crear una instancia: 
 * Ej: Session::start();
 * * Se utiliza para manejar la autenticación, el control de acceso y el almacenamiento temporal de datos de usuario.
 */
class Session {
    
    // --------------------------------------------------------------------------
    // Métodos Base de la Sesión
    // --------------------------------------------------------------------------

    /**
     * Inicia la sesión de PHP si aún no ha sido iniciada.
     * Esto previene errores de "Cannot send session cookie" si session_start() se llama dos veces.
     */
    public static function start() {
        // Verifica si el estado actual de la sesión es PHP_SESSION_NONE (no iniciada).
        if (session_status() == PHP_SESSION_NONE) {
            // Recomendación de seguridad: Mejora la protección contra XSS a través de cookies.
            // ini_set('session.cookie_httponly', 1);
            // ini_set('session.cookie_secure', 1); // Solo para HTTPS
            session_start();
        }
    }

    /**
     * Almacena un valor en la sesión.
     * @param string $key La clave bajo la cual se almacenará el valor.
     * @param mixed $value El valor a almacenar.
     */
    public static function set($key, $value) {
        self::start(); // Asegura que la sesión esté iniciada.
        $_SESSION[$key] = $value;
    }

    /**
     * Recupera un valor de la sesión, o un valor por defecto si la clave no existe.
     * @param string $key La clave del valor a recuperar.
     * @param mixed $default El valor a devolver si la clave no está presente.
     * @return mixed El valor de la sesión o el valor por defecto.
     */
    public static function get($key, $default = null) {
        self::start(); // Asegura que la sesión esté iniciada.
        // Uso del operador de coalescencia (??) para devolver el valor por defecto si la clave no existe.
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Destruye completamente la sesión activa y elimina todos los datos de autenticación.
     */
    public static function destroy() {
        self::start(); // Asegura que la sesión esté iniciada.
        
        // 1. Borra todas las variables de sesión
        $_SESSION = array();
        
        // 2. Elimina la cookie de sesión del cliente (si existe)
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // 3. Destruye la sesión de PHP en el servidor
        session_destroy();
    }

    // --------------------------------------------------------------------------
    // Métodos de Seguridad y Autenticación
    // --------------------------------------------------------------------------
    
    /**
     * Verifica si el usuario está autenticado.
     * Si no lo está, redirige al usuario a la página de login y detiene la ejecución.
     * Este es el método de seguridad que se usa al comienzo de cada controlador/página protegida.
     * @return bool Siempre devuelve true si el usuario está autenticado.
     */
    public static function checkAuth() {
        self::start();
        // Verificación clave: Si 'user_id' no existe, el usuario no está logueado.
        if (!isset($_SESSION['user_id'])) {
            // Redirige al Front Controller (que manejará la acción de login).
            header("Location: /prestlab/public/index.php");
            exit(); // Crítico: Detiene el script inmediatamente.
        }
        return true;
    }

    // --------------------------------------------------------------------------
    // Métodos de Acceso Rápido a Datos de Usuario
    // --------------------------------------------------------------------------

    /**
     * Obtiene el rol del usuario logueado.
     * @return mixed|null El rol del usuario o null.
     */
    public static function getUserRole() {
        return self::get('user_rol');
    }

    /**
     * Obtiene el nombre completo del usuario logueado.
     * @return mixed|null El nombre del usuario o null.
     */
    public static function getUserName() {
        return self::get('user_nombre');
    }

    /**
     * Obtiene la ID del usuario logueado. Es la clave principal de la autenticación.
     * @return mixed|null La ID del usuario o null.
     */
    public static function getUserId() {
        return self::get('user_id');
    }
}
?>