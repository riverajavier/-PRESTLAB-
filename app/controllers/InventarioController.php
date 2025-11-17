<?php
// Carga la clase de Sesión para manejar la autenticación
require_once '../app/core/Session.php';

/**
 * Clase controladora para manejar todas las operaciones de inventario de equipos.
 * Incluye consulta pública (con filtros/paginación) y gestión de administración.
 */
class InventarioController {
    private $equipoModel;

    /**
     * Constructor del controlador.
     * @param object $db La conexión a la base de datos.
     */
    public function __construct($db) {
        // Inicializa el modelo de Equipo, que interactúa con la base de datos.
        $this->equipoModel = new EquipoModel($db);
    }

    // --- Funciones de Consulta Pública ---

    /**
     * Muestra la lista de equipos del inventario, con soporte para filtros y paginación.
     * Accessible por cualquier usuario autenticado.
     */
    public function consultarInventario() {
        Session::checkAuth();
        $user_rol = Session::getUserRole();

        // ✅ AGREGADO: Redirección según rol
        if ($user_rol == 1) {
            // Admin - redirigir a gestión de inventario
            header("Location: /prestlab/public/index.php?controller=inventario&action=gestionarInventario");
            exit();
        }

        // 2. Procesa los filtros enviados por GET (URL)
        $filtros = [];
        if (!empty($_GET['nombre'])) {
            $filtros['nombre'] = trim($_GET['nombre']); // Filtro por nombre de equipo
        }
        if (!empty($_GET['estado']) && is_numeric($_GET['estado'])) {
            $filtros['estado'] = (int)$_GET['estado']; // Filtro por estado del equipo
        }

        // 3. Lógica de Paginación
        $pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $elementos_por_pagina = 20;

        // 4. Obtiene los equipos, el total y calcula el total de páginas
        $equipos = $this->equipoModel->obtenerEquipos($filtros, $pagina_actual, $elementos_por_pagina);
        $total_equipos = $this->equipoModel->contarEquipos($filtros);
        // Calcula el total de páginas, asegurando que sea al menos 1
        $total_paginas = $total_equipos > 0 ? ceil($total_equipos / $elementos_por_pagina) : 1;

        // Ajusta la página actual si el usuario intenta acceder a una página inexistente
        if ($pagina_actual > $total_paginas) {
            $pagina_actual = $total_paginas;
        }

        // 5. Obtiene la lista de estados de equipo para los filtros de la vista
        $estados_equipo = $this->equipoModel->obtenerEstadosEquipo();

        // 6. Prepara los datos para la vista
        $data = [
            'equipos' => $equipos,
            'estados_equipo' => $estados_equipo,
            'filtros_actuales' => $filtros,
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas,
            'total_equipos' => $total_equipos,
            'elementos_por_pagina' => $elementos_por_pagina,
            'equipoModel' => $this->equipoModel // Pasa el modelo por si se necesitan funciones en la vista
        ];

        // 7. Carga la vista de consulta de inventario
        extract($data);
        include_once '../app/views/inventario/consultar.php';
    }

    /**
     * Verifica la disponibilidad de un equipo específico vía AJAX/JSON.
     */
    public function verificarDisponibilidad() {
        Session::checkAuth();
        
        // Verifica si se envió un ID de equipo válido por GET
        if (isset($_GET['id_equipo']) && is_numeric($_GET['id_equipo'])) {
            $disponibilidad = $this->equipoModel->verificarDisponibilidad($_GET['id_equipo']);
            
            // Establece la cabecera para respuesta JSON
            header('Content-Type: application/json');
            if ($disponibilidad) {
                // Devuelve los datos de disponibilidad (e.g., cantidad disponible)
                echo json_encode($disponibilidad);
            } else {
                echo json_encode(['error' => 'Equipo no encontrado']);
            }
        } else {
            // Error si el ID es inválido o falta
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID de equipo no válido']);
        }
        exit();
    }

    // --- Métodos de Administración de Inventario (Solo Rol 1) ---

    /**
     * Muestra la vista de gestión del inventario (CRUD). Solo accesible para administradores.
     */
    public function gestionarInventario() {
        Session::checkAuth();
        $user_rol = Session::getUserRole();

        // ✅ AGREGADO: Redirección según rol
        if ($user_rol != 1) {
            // Usuario normal - redirigir a consulta
            header("Location: /prestlab/public/index.php?controller=inventario&action=consultar");
            exit();
        }

        // 1. Lógica de filtros, paginación y obtención de datos (similar a consultarInventario)
        $filtros = [];
        if (!empty($_GET['nombre'])) {
            $filtros['nombre'] = trim($_GET['nombre']);
        }
        if (!empty($_GET['estado']) && is_numeric($_GET['estado'])) {
            $filtros['estado'] = (int)$_GET['estado'];
        }

        $pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
        $elementos_por_pagina = 20;

        $equipos = $this->equipoModel->obtenerEquipos($filtros, $pagina_actual, $elementos_por_pagina);
        $total_equipos = $this->equipoModel->contarEquipos($filtros);
        $total_paginas = $total_equipos > 0 ? ceil($total_equipos / $elementos_por_pagina) : 1;

        $estados_equipo = $this->equipoModel->obtenerEstadosEquipo();

        // 2. Prepara y carga la vista de administración
        $data = [
            'equipos' => $equipos,
            'estados_equipo' => $estados_equipo,
            'filtros_actuales' => $filtros,
            'pagina_actual' => $pagina_actual,
            'total_paginas' => $total_paginas,
            'total_equipos' => $total_equipos
        ];

        extract($data);
        include_once '../app/views/admin/inventario/gestionar.php'; // Vista específica de administración
    }

    /**
     * Maneja la creación de un nuevo equipo. (Ruta AJAX que devuelve JSON).
     */
    public function crearEquipo() {
        Session::checkAuth();
        
        // Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST) {
            // 1. Recoge los datos del formulario POST
            $datos = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion']),
                'fecha_adquisicion' => $_POST['fecha_adquisicion'],
                'estado' => (int)$_POST['estado'],
                'cantidad_total' => (int)$_POST['cantidad_total'],
                'cantidad_disponible' => (int)$_POST['cantidad_disponible'],
                'imagen_url' => $_POST['imagen_url'] ?? ''
            ];

            // 2. Validaciones de datos
            if (empty($datos['nombre']) || empty($datos['fecha_adquisicion'])) {
                $response = ['success' => false, 'error' => 'Nombre y fecha de adquisición son obligatorios'];
            } elseif ($datos['cantidad_total'] < 0 || $datos['cantidad_disponible'] < 0) {
                $response = ['success' => false, 'error' => 'Las cantidades no pueden ser negativas'];
            } elseif ($datos['cantidad_disponible'] > $datos['cantidad_total']) {
                $response = ['success' => false, 'error' => 'La cantidad disponible no puede ser mayor que la cantidad total'];
            } else {
                // 3. Llama al modelo para crear el equipo
                $id_equipo = $this->equipoModel->crearEquipo($datos);
                
                // 4. Prepara la respuesta de éxito o error
                if ($id_equipo) {
                    $response = ['success' => true, 'message' => 'Equipo creado exitosamente', 'id_equipo' => $id_equipo];
                } else {
                    $response = ['success' => false, 'error' => 'Error al crear el equipo'];
                }
            }

            // 5. Devuelve la respuesta JSON
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    /**
     * Maneja la actualización de un equipo existente. (Ruta AJAX que devuelve JSON).
     */
    public function actualizarEquipo() {
        Session::checkAuth();
        
        // Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST && isset($_POST['id_equipo'])) {
            $id_equipo = (int)$_POST['id_equipo'];
            // 1. Recoge los datos del formulario POST
            $datos = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion']),
                'fecha_adquisicion' => $_POST['fecha_adquisicion'],
                'estado' => (int)$_POST['estado'],
                'cantidad_total' => (int)$_POST['cantidad_total'],
                'cantidad_disponible' => (int)$_POST['cantidad_disponible'],
                'imagen_url' => $_POST['imagen_url'] ?? ''
            ];

            // 2. Validaciones
            if (empty($datos['nombre']) || empty($datos['fecha_adquisicion'])) {
                $response = ['success' => false, 'error' => 'Nombre y fecha de adquisición son obligatorios'];
            } elseif ($datos['cantidad_total'] < 0 || $datos['cantidad_disponible'] < 0) {
                $response = ['success' => false, 'error' => 'Las cantidades no pueden ser negativas'];
            } elseif ($datos['cantidad_disponible'] > $datos['cantidad_total']) {
                $response = ['success' => false, 'error' => 'La cantidad disponible no puede ser mayor que la cantidad total'];
            } else {
                // 3. Llama al modelo para actualizar el equipo
                $resultado = $this->equipoModel->actualizarEquipo($id_equipo, $datos);
                
                // 4. Prepara la respuesta de éxito o error
                if ($resultado) {
                    $response = ['success' => true, 'message' => 'Equipo actualizado exitosamente'];
                } else {
                    $response = ['success' => false, 'error' => 'Error al actualizar el equipo'];
                }
            }

            // 5. Devuelve la respuesta JSON
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    /**
     * ✅ AGREGADO: Maneja la edición de equipos con soporte para imágenes
     */
    public function editarEquipo() {
        Session::checkAuth();
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST && isset($_POST['id_equipo'])) {
            $id_equipo = (int)$_POST['id_equipo'];
            $datos = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion']),
                'fecha_adquisicion' => $_POST['fecha_adquisicion'],
                'estado' => (int)$_POST['estado'],
                'cantidad_total' => (int)$_POST['cantidad_total'],
                'cantidad_disponible' => (int)$_POST['cantidad_disponible'],
                'imagen_url' => $_POST['imagen_url_existente'] ?? ''
            ];

            // Si se subió una nueva imagen
            if (!empty($_FILES['imagen']['name'])) {
                $rutaImagen = $this->guardarImagen($_FILES['imagen']);
                if ($rutaImagen) {
                    $datos['imagen_url'] = $rutaImagen;
                }
            }

            $resultado = $this->equipoModel->actualizarEquipo($id_equipo, $datos);

            header('Content-Type: application/json');
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Equipo actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar el equipo']);
            }
            exit();
        }
    }

    /**
     * Maneja la eliminación de un equipo por ID. (Ruta AJAX que devuelve JSON).
     */
    public function eliminarEquipo() {
        Session::checkAuth();
        
        // Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST && isset($_POST['id_equipo'])) {
            $id_equipo = (int)$_POST['id_equipo'];
            // Llama al modelo para eliminar el equipo
            $resultado = $this->equipoModel->eliminarEquipo($id_equipo);
            
            // Devuelve la respuesta del modelo (debe ser un array con 'success' y 'message'/'error')
            header('Content-Type: application/json');
            echo json_encode($resultado);
            exit();
        }
    }

    /**
     * Obtiene los datos de un equipo por su ID. Utilizado típicamente para cargar formularios de edición. (Ruta AJAX que devuelve JSON).
     */
    public function obtenerEquipo() {
        Session::checkAuth();
        
        // Control de acceso: Solo Admin
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if (isset($_GET['id_equipo']) && is_numeric($_GET['id_equipo'])) {
            // Llama al modelo para obtener los datos
            $equipo = $this->equipoModel->obtenerEquipoPorId($_GET['id_equipo']);
            
            header('Content-Type: application/json');
            if ($equipo) {
                echo json_encode(['success' => true, 'equipo' => $equipo]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Equipo no encontrado']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID de equipo no válido']);
        }
        exit();
    }

    /**
     * Obtiene una lista completa de equipos (sin filtros ni paginación) para ser usada en selectores o listados rápidos.
     * (Ruta AJAX que devuelve JSON).
     */
    public function obtenerEquipos() {
        Session::checkAuth();
        
        // Obtiene todos los equipos (o un límite alto como 1000)
        $equipos = $this->equipoModel->obtenerEquipos([], 1, 1000); 
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'equipos' => $equipos]);
        exit();
    }

    /**
     * ✅ AGREGADO: Guarda la imagen subida y devuelve la ruta relativa
     * @param array $archivo $_FILES['imagen']
     * @return string Ruta relativa del archivo guardado
     */
    private function guardarImagen($archivo) {
        $carpetaDestino = realpath(__DIR__ . '/../../public/uploads/equipos/') . '/';
        $nombreArchivo = uniqid('eq_') . '_' . basename($archivo['name']);
        $rutaDestino = $carpetaDestino . $nombreArchivo;
        $rutaRelativa = '/prestlab/public/uploads/equipos/' . $nombreArchivo;

        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($archivo['type'], $tiposPermitidos)) {
            return null;
        }

        if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            return $rutaRelativa;
        }

        return null;
    }
}
?>