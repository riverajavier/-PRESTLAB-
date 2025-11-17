<?php
/**
 * Clase Modelo para interactuar con la tabla 'equipo' y gestionar el inventario de elementos.
 * Utiliza PDO para todas las operaciones de base de datos, garantizando la seguridad mediante Prepared Statements.
 */
class EquipoModel {
    private $conn;
    private $table_name = "equipo";
    // Asumimos que el estado 1 es 'Disponible' (necesario para verificarDisponibilidad)
    private $estado_disponible_id = 1;

    /**
     * Constructor que recibe la conexión a la base de datos (PDO).
     * @param PDO $db Objeto de conexión a la base de datos.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Obtiene todos los equipos con soporte para filtros y paginación.
     * @param array $filtros Array asociativo con filtros (nombre, estado).
     * @param int $pagina Número de página actual.
     * @param int $elementos_por_pagina Cantidad de registros a mostrar.
     * @return array Array de equipos.
     */
    public function obtenerEquipos($filtros = [], $pagina = 1, $elementos_por_pagina = 20) {
        $offset = ($pagina - 1) * $elementos_por_pagina;
        
        $query = "SELECT e.*, ee.nombre_estado 
                    FROM " . $this->table_name . " e 
                    INNER JOIN estado_equipo ee ON e.id_estado_equipo = ee.id_estado_equipo 
                    WHERE 1=1";
        
        $params = [];

        // Filtro por nombre (búsqueda parcial)
        if (!empty($filtros['nombre'])) {
            $query .= " AND e.nombre_equipo LIKE :nombre";
            $params[':nombre'] = '%' . $filtros['nombre'] . '%';
        }

        // Filtro por estado de equipo
        if (!empty($filtros['estado'])) {
            $query .= " AND e.id_estado_equipo = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        // Ordenamiento y Paginación
        $query .= " ORDER BY e.nombre_equipo ASC 
                    LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        // Asignación de valores para los filtros
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Asignación de valores para la paginación (siempre como enteros)
        $stmt->bindValue(':limit', (int)$elementos_por_pagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Cuenta el total de equipos en la base de datos, aplicando los mismos filtros.
     * Es crucial para calcular el número total de páginas en la paginación.
     * @param array $filtros Array asociativo con filtros (nombre, estado).
     * @return int El número total de equipos que cumplen con los filtros.
     */
    public function contarEquipos($filtros = []) {
        $query = "SELECT COUNT(*) as total 
                    FROM " . $this->table_name . " e 
                    WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['nombre'])) {
            $query .= " AND e.nombre_equipo LIKE :nombre";
            $params[':nombre'] = '%' . $filtros['nombre'] . '%';
        }

        if (!empty($filtros['estado'])) {
            $query .= " AND e.id_estado_equipo = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'];
    }

    /**
     * Obtiene los detalles de un equipo específico por su ID.
     * @param int $id_equipo ID del equipo a buscar.
     * @return array|false Datos del equipo o false si no se encuentra.
     */
    public function obtenerEquipoPorId($id_equipo) {
        $query = "SELECT e.*, ee.nombre_estado 
                    FROM " . $this->table_name . " e 
                    INNER JOIN estado_equipo ee ON e.id_estado_equipo = ee.id_estado_equipo 
                    WHERE e.id_equipo = :id_equipo 
                    LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_equipo", $id_equipo, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * Obtiene todos los estados posibles para un equipo (Ej: Disponible, Prestado, Mantenimiento).
     * @return array Array de estados.
     */
    public function obtenerEstadosEquipo() {
        $query = "SELECT * FROM estado_equipo ORDER BY nombre_estado";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica la disponibilidad de un equipo específico para ser prestado.
     * Considera la cantidad disponible y el estado general del equipo.
     * @param int $id_equipo ID del equipo.
     * @return array|false Información de disponibilidad o false si el equipo no existe.
     */
    public function verificarDisponibilidad($id_equipo) {
        $equipo = $this->obtenerEquipoPorId($id_equipo);
        
        if ($equipo) {
            // Un equipo está disponible si su cantidad_disponible > 0 Y su estado es 'Disponible' (id_estado_equipo = 1)
            return [
                'disponible' => $equipo['cantidad_disponible'] > 0 && $equipo['id_estado_equipo'] == $this->estado_disponible_id,
                'cantidad_disponible' => (int)$equipo['cantidad_disponible'],
                'estado' => $equipo['nombre_estado']
            ];
        }
        
        return false;
    }

    /**
     * Crea un nuevo registro de equipo.
     * @param array $datos Array asociativo con los datos del equipo.
     * @return int|false El ID del equipo insertado o false en caso de error.
     */
    public function crearEquipo($datos) {
        $query = "INSERT INTO " . $this->table_name . " 
                    (nombre_equipo, descripcion, fecha_adquisicion, id_estado_equipo, cantidad_total, cantidad_disponible, imagen_url) 
                    VALUES (:nombre, :descripcion, :fecha_adquisicion, :estado, :cantidad_total, :cantidad_disponible, :imagen_url)";
        
        $stmt = $this->conn->prepare($query);
        
        // Uso de bindParam para vincular las variables a la consulta SQL
        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":descripcion", $datos['descripcion']);
        $stmt->bindParam(":fecha_adquisicion", $datos['fecha_adquisicion']);
        $stmt->bindParam(":estado", $datos['estado'], PDO::PARAM_INT);
        $stmt->bindParam(":cantidad_total", $datos['cantidad_total'], PDO::PARAM_INT);
        // Al crear, la cantidad disponible suele ser igual a la cantidad total.
        $stmt->bindParam(":cantidad_disponible", $datos['cantidad_disponible'], PDO::PARAM_INT);
        $stmt->bindParam(":imagen_url", $datos['imagen_url']);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId(); // Devuelve el ID generado
        }
        return false;
    }

    /**
     * Actualiza los datos de un equipo existente.
     * @param int $id_equipo ID del equipo a actualizar.
     * @param array $datos Array asociativo con los nuevos datos.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    public function actualizarEquipo($id_equipo, $datos) {
        $query = "UPDATE " . $this->table_name . " 
                    SET nombre_equipo = :nombre, 
                        descripcion = :descripcion, 
                        fecha_adquisicion = :fecha_adquisicion, 
                        id_estado_equipo = :estado, 
                        cantidad_total = :cantidad_total, 
                        cantidad_disponible = :cantidad_disponible,
                        imagen_url = :imagen_url
                    WHERE id_equipo = :id_equipo";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":descripcion", $datos['descripcion']);
        $stmt->bindParam(":fecha_adquisicion", $datos['fecha_adquisicion']);
        $stmt->bindParam(":estado", $datos['estado'], PDO::PARAM_INT);
        $stmt->bindParam(":cantidad_total", $datos['cantidad_total'], PDO::PARAM_INT);
        $stmt->bindParam(":cantidad_disponible", $datos['cantidad_disponible'], PDO::PARAM_INT);
        $stmt->bindParam(":imagen_url", $datos['imagen_url']);
        $stmt->bindParam(":id_equipo", $id_equipo, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    /**
     * Elimina un equipo si no tiene préstamos activos o pendientes de devolver.
     * @param int $id_equipo ID del equipo a eliminar.
     * @return array Array con el resultado de la operación (success o error).
     */
    public function eliminarEquipo($id_equipo) {
        // Verificar si el equipo tiene préstamos activos (estados 1 y 2 asumen 'Pendiente' y 'En Curso')
        $query_verificar = "SELECT COUNT(*) as total FROM detalle_prestamo dp 
                            INNER JOIN prestamo p ON dp.id_prestamo = p.id_prestamo 
                            WHERE dp.id_equipo = :id_equipo AND p.id_estado_prestamo IN (1, 2)";
        
        $stmt_verificar = $this->conn->prepare($query_verificar);
        $stmt_verificar->bindParam(":id_equipo", $id_equipo, PDO::PARAM_INT);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

        if ($resultado['total'] > 0) {
            return ['success' => false, 'error' => 'No se puede eliminar el equipo porque tiene préstamos activos (pendientes o en curso)'];
        }

        // Si no hay préstamos activos, se procede a la eliminación
        try {
            $query_eliminar = "DELETE FROM " . $this->table_name . " WHERE id_equipo = :id_equipo";
            $stmt_eliminar = $this->conn->prepare($query_eliminar);
            $stmt_eliminar->bindParam(":id_equipo", $id_equipo, PDO::PARAM_INT);
            
            if ($stmt_eliminar->execute()) {
                return ['success' => true, 'message' => 'Equipo eliminado exitosamente'];
            } else {
                return ['success' => false, 'error' => 'No se pudo eliminar el equipo (posiblemente restricciones de clave foránea)'];
            }
        } catch (PDOException $e) {
            // Capturar errores de BD (ej. violaciones de FK si el chequeo inicial falló por alguna razón)
            return ['success' => false, 'error' => 'Error de base de datos al intentar eliminar: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene estadísticas agregadas sobre el inventario de equipos.
     * @return array Array con las estadísticas (totales, disponibles, en mantenimiento, dados de baja).
     */
    public function obtenerEstadisticasEquipos() {
        $query = "SELECT 
                    COUNT(id_equipo) as total_equipos_distintos,
                    SUM(cantidad_total) as total_unidades,
                    SUM(cantidad_disponible) as unidades_disponibles,
                    (SELECT SUM(cantidad_total) FROM equipo WHERE id_estado_equipo = 3) as en_mantenimiento,
                    (SELECT SUM(cantidad_total) FROM equipo WHERE id_estado_equipo = 4) as dados_baja
                  FROM equipo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Manejar valores NULL si no hay equipos en ciertos estados
        $stats['en_mantenimiento'] = $stats['en_mantenimiento'] ?? 0;
        $stats['dados_baja'] = $stats['dados_baja'] ?? 0;
        
        return $stats;
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