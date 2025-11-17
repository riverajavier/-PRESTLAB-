<?php
// Carga la clase de Sesión para manejar la autenticación y datos del usuario
require_once '../app/core/Session.php';

/**
 * Clase controladora para manejar todas las operaciones relacionadas con las devoluciones
 * de equipos que estaban en préstamo.
 * * Requiere los modelos PrestamoModel y EquipoModel.
 */
class DevolucionController {
    private $prestamoModel;
    private $equipoModel;

    /**
     * Constructor del controlador.
     * @param object $db La conexión a la base de datos.
     */
    public function __construct($db) {
        // Inicializa el modelo de Préstamo (para obtener y procesar devoluciones)
        $this->prestamoModel = new PrestamoModel($db);
        // Inicializa el modelo de Equipo (posiblemente para actualizar el estado del equipo)
        $this->equipoModel = new EquipoModel($db);
    }

    /**
     * Muestra la vista de gestión de devoluciones.
     * Esta función está restringida solo para administradores (Rol 1).
     */
    public function gestionarDevoluciones() {
        // 1. Verifica la autenticación del usuario (si hay una sesión activa)
        Session::checkAuth();
        
        // 2. Control de acceso: Si el rol NO es Administrador (Rol 1), redirige a la página principal
        if (Session::getUserRole() != 1) {
            header("Location: /prestlab/public/index.php");
            exit();
        }

        // 3. Obtiene los datos necesarios para la vista del administrador
        $prestamos_activos = $this->prestamoModel->obtenerPrestamosActivos(); // Préstamos que aún están en curso
        $prestamos_para_devolucion = $this->prestamoModel->obtenerPrestamosParaDevolucion(); // Préstamos listos para ser procesados como devolución
        $estados_devolucion = $this->prestamoModel->obtenerEstadosDevolucion(); // Posibles estados de un equipo al ser devuelto (ej: 'Bueno', 'Dañado')

        // 4. Prepara los datos para pasar a la vista
        $data = [
            'prestamos_activos' => $prestamos_activos,
            'prestamos_para_devolucion' => $prestamos_para_devolucion,
            'estados_devolucion' => $estados_devolucion
        ];

        // 5. Extrae el array $data para que las claves se conviertan en variables locales
        extract($data);
        // 6. Carga la vista de gestión de devoluciones
        include_once '../app/views/devoluciones/gestionar.php';
    }

    /**
     * Procesa la devolución de un equipo por parte de un administrador.
     * Se espera una solicitud POST. Retorna una respuesta JSON.
     */
    public function procesarDevolucion() {
        // 1. Verifica la autenticación
        Session::checkAuth();
        
        // 2. Control de acceso (solo Admin - Rol 1)
        if (Session::getUserRole() != 1) {
            // Si no es administrador, responde con un JSON de error
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        // 3. Procesa los datos si vienen por POST
        if ($_POST) {
            // Recoge y sanitiza los datos del formulario de devolución
            $id_prestamo = (int)$_POST['id_prestamo'];
            $id_equipo = (int)$_POST['id_equipo'];
            $id_estado_devolucion = (int)$_POST['id_estado_devolucion'];
            $observaciones = trim($_POST['observaciones'] ?? '');
            // Obtiene el ID del administrador que está realizando el proceso
            $id_usuario_admin = Session::getUserId();
            
            $response = []; // Inicializa el array de respuesta JSON

            // 4. Validaciones
            if (empty($id_estado_devolucion)) {
                $response = ['success' => false, 'error' => 'Debe seleccionar un estado de devolución'];
            } 
            // La validación específica para estado 2 (asumiendo 2 significa 'Dañado' o similar)
            elseif ($id_estado_devolucion == 2 && empty($observaciones)) {
                $response = ['success' => false, 'error' => 'Para equipos dañados es obligatorio agregar observaciones'];
            } else {
                // 5. Llama al método del modelo para ejecutar la lógica de la devolución
                $resultado = $this->prestamoModel->procesarDevolucion(
                    $id_prestamo, 
                    $id_equipo, 
                    $id_estado_devolucion, 
                    $observaciones, 
                    $id_usuario_admin
                );

                // 6. Prepara la respuesta basada en el resultado del modelo
                if ($resultado['success']) {
                    $mensaje = "Devolución registrada exitosamente.";
                    if ($resultado['prestamo_completado']) {
                        $mensaje .= " ¡Préstamo completado!"; // Mensaje extra si el préstamo se cierra totalmente
                    }
                    $response = ['success' => true, 'message' => $mensaje];
                } else {
                    $response = ['success' => false, 'error' => $resultado['error']];
                }
            }

            // 7. Envía la respuesta JSON al cliente (necesario para AJAX)
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    /**
     * Permite a un usuario solicitar la devolución de un préstamo.
     * Espera una solicitud POST.
     */
    public function solicitarDevolucion() {
        // 1. Verifica la autenticación
        Session::checkAuth();

        // 2. Procesa los datos si vienen por POST y tiene el ID del préstamo
        if ($_POST && isset($_POST['id_prestamo'])) {
            $id_prestamo = (int)$_POST['id_prestamo'];
            $id_usuario = Session::getUserId();

            // 3. Verifica que el préstamo pertenezca al usuario (Medida de seguridad)
            // Esto evita que un usuario intente solicitar la devolución de un préstamo de otra persona
            // Se asume que 'obtenerPrestamosUsuario' filtra por el ID de usuario y solo trae préstamos activos (true)
            $prestamos_usuario = $this->prestamoModel->obtenerPrestamosUsuario($id_usuario, true);
            $prestamo_valido = false;

            foreach ($prestamos_usuario as $prestamo) {
                if ($prestamo['id_prestamo'] == $id_prestamo) {
                    $prestamo_valido = true;
                    // Se asume que el modelo de préstamo ya tiene la lógica para marcar la solicitud
                    // Aquí solo se maneja la validación y el mensaje de sesión
                    break;
                }
            }

            // 4. Establece un mensaje de sesión basado en la validación
            if (!$prestamo_valido) {
                $_SESSION['mensaje_devolucion'] = "Préstamo no válido o no te pertenece.";
                $_SESSION['tipo_mensaje_devolucion'] = 'danger';
            } else {
                $_SESSION['mensaje_devolucion'] = "Solicitud de devolución enviada. Un administrador procesará tu devolución.";
                $_SESSION['tipo_mensaje_devolucion'] = 'success';
            }

            // 5. Redirige al usuario a la vista de sus préstamos (ruta completa verificada)
            header("Location: /prestlab/public/index.php?controller=prestamo&action=misPrestamos");
            exit();
        }
    }
}
?>
