<?php
// Carga la clase de Sesión para manejar la autenticación
require_once '../app/core/Session.php';

/**
 * Clase controladora para manejar todas las operaciones de préstamos.
 * Incluye solicitudes de usuario, visualización de préstamos y gestión de administración.
 */
class PrestamoController {
    private $prestamoModel;
    private $equipoModel;
    private $usuarioModel;

    /**
     * Constructor del controlador.
     * @param object $db La conexión a la base de datos.
     */
    public function __construct($db) {
        // Inicializa los modelos necesarios
        $this->prestamoModel = new PrestamoModel($db);
        $this->equipoModel = new EquipoModel($db);
        $this->usuarioModel = new UsuarioModel($db);
    }

    // --- Funciones de Usuario ---

    /**
     * Procesa la solicitud de un nuevo préstamo enviada por un usuario.
     * Espera una solicitud POST (típicamente desde la vista de inventario/consulta).
     */
    public function solicitarPrestamo() {
        Session::checkAuth(); // 1. Verifica la autenticación

        $mensaje = '';
        $tipo_mensaje = '';

        if ($_POST && isset($_POST['equipo_id']) && isset($_POST['cantidad'])) {
            $id_usuario = Session::getUserId();
            $equipo_id = (int)$_POST['equipo_id'];
            $cantidad = (int)$_POST['cantidad'];
            $fecha_limite = $_POST['fecha_limite'] ?? '';
            $observaciones = $_POST['observaciones'] ?? '';

            // 2. Validaciones de datos
            if ($cantidad <= 0) {
                $mensaje = "La cantidad debe ser mayor a 0.";
                $tipo_mensaje = 'danger';
            } elseif (empty($fecha_limite)) {
                $mensaje = "La fecha límite de devolución es obligatoria.";
                $tipo_mensaje = 'danger';
            } elseif (strtotime($fecha_limite) <= strtotime('today')) {
                $mensaje = "La fecha límite debe ser futura.";
                $tipo_mensaje = 'danger';
            } else {
                // 3. Verifica si el usuario tiene préstamos vencidos (Regla de negocio)
                if ($this->prestamoModel->tienePrestamosVencidos($id_usuario)) {
                    $mensaje = "No puedes solicitar nuevos préstamos porque tienes préstamos vencidos. Por favor, devuelve los equipos pendientes.";
                    $tipo_mensaje = 'warning';
                } else {
                    // 4. Estructura los datos del equipo para el modelo
                    $equipos = [
                        [
                            'id_equipo' => $equipo_id,
                            'cantidad' => $cantidad
                        ]
                    ];

                    // 5. Llama al modelo para crear el préstamo
                    $resultado = $this->prestamoModel->crearPrestamo(
                        $id_usuario, 
                        $equipos, 
                        $fecha_limite, 
                        $observaciones
                    );

                    // 6. Prepara el mensaje de sesión según el resultado del modelo
                    if ($resultado['success']) {
                        $mensaje = "¡Préstamo solicitado exitosamente! Código de préstamo:{$resultado['codigo_prestamo']}";
                        $tipo_mensaje = 'success';
                    } else {
                        $mensaje = "Error al solicitar el préstamo: " . $resultado['error'];
                        $tipo_mensaje = 'danger';
                    }
                }
            }
        }

        // 7. Almacena el mensaje en la sesión para mostrarlo después de la redirección
        $_SESSION['mensaje_prestamo'] = $mensaje;
        $_SESSION['tipo_mensaje_prestamo'] = $tipo_mensaje;
        
        // 8. Redirección final: Vuelve al inventario para mostrar el mensaje
        header("Location: /prestlab/public/index.php?controller=inventario&action=consultar"); // Ruta coherente
        exit();
    }

    /**
     * Muestra la lista de préstamos de un usuario.
     */
    public function misPrestamos() {
        Session::checkAuth(); // 1. Verifica la autenticación

        $id_usuario = Session::getUserId();
        // Determina si solo se deben mostrar los activos (útil para botones de filtro)
        $solo_activos = isset($_GET['activos']) ? (bool)$_GET['activos'] : false;

        // 2. Obtiene los préstamos del usuario
        $prestamos = $this->prestamoModel->obtenerPrestamosUsuario($id_usuario, $solo_activos);

        // 3. Prepara y carga la vista
        $data = [
            'prestamos' => $prestamos,
            'solo_activos' => $solo_activos
        ];

        extract($data);
        include_once '../app/views/prestamos/mis_prestamos.php';
    }

    /**
     * Muestra el detalle de un préstamo específico.
     * Requiere que el ID del préstamo pertenezca al usuario actual (medida de seguridad).
     */
    public function verDetallePrestamo() {
        Session::checkAuth(); // 1. Verifica la autenticación

        // 2. Validaciones iniciales del ID
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header("Location: /prestlab/public/index.php?controller=prestamo&action=misPrestamos"); // Redirección de error
            exit();
        }

        $id_prestamo = (int)$_GET['id'];
        $id_usuario = Session::getUserId();
        $prestamo_valido = false;

        // 3. Verifica que el préstamo realmente pertenezca al usuario (evita acceso a datos ajenos)
        // Se asume que 'obtenerPrestamosUsuario' trae todos los préstamos del usuario
        $prestamos = $this->prestamoModel->obtenerPrestamosUsuario($id_usuario);
        
        foreach ($prestamos as $prestamo) {
            if ($prestamo['id_prestamo'] == $id_prestamo) {
                $prestamo_valido = true;
                $prestamo_principal = $prestamo; // Guarda el registro principal del préstamo
                break;
            }
        }

        // 4. Si el préstamo no es del usuario o no existe, redirige
        if (!$prestamo_valido) {
            header("Location: /prestlab/public/index.php?controller=prestamo&action=misPrestamos"); // Redirección de error verificada
            exit();
        }

        // 5. Obtiene los detalles (ítems) del préstamo
        $detalles = $this->prestamoModel->obtenerDetallePrestamo($id_prestamo);

        // 6. Prepara y carga la vista
        $data = [
            'prestamo' => $prestamo_principal,
            'detalles' => $detalles
        ];

        extract($data);
        include_once '../app/views/prestamos/detalle_prestamo.php';
    }

    /**
     * Obtiene el detalle de un préstamo específico en formato JSON (para AJAX).
     * @return void
     */
    public function obtenerDetallePrestamo() {
        Session::checkAuth();

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID de préstamo inválido']);
            exit();
        }

        $id_prestamo = (int)$_GET['id'];

        // Obtener detalles del préstamo
        $detalles = $this->prestamoModel->obtenerDetallePrestamo($id_prestamo);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $detalles]);
        exit();
    }

    // --- Métodos de Administración de Préstamos (Solo Rol 1) ---

    /**
     * Muestra la vista de gestión y estadísticas de préstamos activos (Solo Admin).
     */
    public function gestionarPrestamos() {
        Session::checkAuth(); // 1. Verifica la autenticación
        
        // 2. Control de acceso: Si el rol NO es Administrador (Rol 1), redirige
        if (Session::getUserRole() != 1) {
            header("Location: /prestlab/public/index.php"); // Ruta de redirección verificada
            exit();
        }

        // 3. Obtiene datos para la gestión
        $prestamos_activos = $this->prestamoModel->obtenerPrestamosActivos();
        $estadisticas = $this->prestamoModel->obtenerEstadisticasPrestamos();

        // 4. Prepara y carga la vista de administración
        $data = [
            'prestamos_activos' => $prestamos_activos,
            'estadisticas' => $estadisticas
        ];

        extract($data);
        include_once '../app/views/admin/prestamos/gestionar.php';
    }

    /**
     * Crea un préstamo de forma presencial por parte del administrador (Ruta AJAX que devuelve JSON).
     */
    public function crearPrestamoPresencial() {
        Session::checkAuth(); // 1. Verifica la autenticación
        
        // 2. Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST) {
            // 3. Recoge y decodifica los datos del POST
            $datos = [
                'id_usuario' => (int)$_POST['id_usuario'],
                'fecha_limite' => $_POST['fecha_limite'],
                // Se espera que 'equipos' sea un string JSON de un array y debe ser decodificado
                'equipos' => json_decode($_POST['equipos'], true) 
            ];

            // 4. Validaciones de datos
            if (empty($datos['id_usuario']) || empty($datos['fecha_limite'])) {
                $response = ['success' => false, 'error' => 'Usuario y fecha límite son obligatorios'];
            } elseif (empty($datos['equipos'])) {
                $response = ['success' => false, 'error' => 'Debe seleccionar al menos un equipo'];
            } elseif (strtotime($datos['fecha_limite']) <= strtotime('today')) {
                $response = ['success' => false, 'error' => 'La fecha límite debe ser futura'];
            } else {
                // 5. Llama al modelo para crear el préstamo presencial
                $resultado = $this->prestamoModel->crearPrestamoPresencial($datos);

                // 6. Prepara la respuesta JSON
                if ($resultado['success']) {
                    $response = ['success' => true, 'message' => 'Préstamo presencial creado exitosamente. Código: ' . $resultado['codigo_prestamo']];
                } else {
                    $response = ['success' => false, 'error' => $resultado['error']];
                }
            }

            // 7. Devuelve la respuesta JSON
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    /**
     * Actualiza un préstamo existente (Solo Admin).
     */
    public function actualizarPrestamo() {
        Session::checkAuth();
        if (Session::getUserRole() != 1) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST && isset($_POST['id_prestamo'], $_POST['fecha_limite'])) {
            $id_prestamo = (int)$_POST['id_prestamo'];
            $fecha_limite = $_POST['fecha_limite'];

            // Validar fecha futura
            if (strtotime($fecha_limite) <= strtotime('today')) {
                echo json_encode(['success' => false, 'error' => 'La fecha límite debe ser futura']);
                exit();
            }

            // Actualizar fecha límite
            $resultado = $this->prestamoModel->actualizarFechaLimite($id_prestamo, $fecha_limite);

            // Procesar nuevos equipos si se enviaron
            if (!empty($_POST['nuevos_equipos'])) {
                $nuevos = json_decode($_POST['nuevos_equipos'], true);
                foreach ($nuevos as $equipo) {
                    // Agregar equipo al préstamo existente
                    $this->prestamoModel->agregarEquipoAPrestamo($id_prestamo, $equipo['id_equipo'], $equipo['cantidad']);
                }
            }

            echo json_encode($resultado);
            exit();
        }
    }

    /**
     * Actualiza la cantidad de un equipo en un préstamo existente (Solo Admin).
     */
    public function actualizarCantidadEquipo() {
        Session::checkAuth();
        if (Session::getUserRole() != 1) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST) {
            $id_prestamo = (int)($_POST['id_prestamo'] ?? 0);
            $id_equipo = (int)($_POST['id_equipo'] ?? 0);
            $cantidad = (int)($_POST['cantidad'] ?? 0);

            if ($id_prestamo && $id_equipo && $cantidad > 0) {
                // Llamar al método del modelo para actualizar la cantidad
                $resultado = $this->prestamoModel->actualizarCantidadEquipo($id_prestamo, $id_equipo, $cantidad);
                echo json_encode($resultado);
            } else {
                echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            }
        }
        exit();
    }

    /**
     * Elimina un equipo de un préstamo existente (Solo Admin).
     */
    public function eliminarEquipoDePrestamo() {
        Session::checkAuth();
        if (Session::getUserRole() != 1) {
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST) {
            $id_prestamo = (int)($_POST['id_prestamo'] ?? 0);
            $id_equipo = (int)($_POST['id_equipo'] ?? 0);

            if ($id_prestamo && $id_equipo) {
                // Llamar al método del modelo para eliminar el equipo
                $resultado = $this->prestamoModel->eliminarEquipoDePrestamo($id_prestamo, $id_equipo);
                echo json_encode($resultado);
            } else {
                echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
            }
        }
        exit();
    }
}
?>