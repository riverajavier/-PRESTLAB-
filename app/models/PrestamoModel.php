<?php
/**
 * Clase Modelo para gestionar todas las operaciones de préstamos y devoluciones.
 * Depende de las tablas 'prestamo', 'detalle_prestamo', 'equipo', 'usuario', 
 * 'estado_prestamo', 'devolucion' y 'estado_devolucion'.
 * * Utiliza transacciones para asegurar la integridad de los datos al mover inventario.
 */
class PrestamoModel {
    private $conn;
    private $table_name = "prestamo";

    // IDs de estado de Préstamo (Asunciones comunes en sistemas de inventario)
    const ESTADO_PRESTAMO_ACTIVO = 1; // Préstamo Pendiente/Activo
    const ESTADO_PRESTAMO_COMPLETADO = 3; // Préstamo Finalizado

    // IDs de estado de Devolución (Asunciones comunes)
    const ESTADO_DEVOLUCION_OK = 1; // Devuelto en buen estado
    const ESTADO_DEVOLUCION_MANTENIMIENTO = 2; // Devuelto con necesidad de mantenimiento
    // Cualquier otro ID se asume como Baja/Perdido (ej. 3)

    // IDs de estado de Equipo (Asunciones comunes en sistemas de inventario)
    const ESTADO_EQUIPO_DISPONIBLE = 1; 
    const ESTADO_EQUIPO_PRESTADO = 2;
    const ESTADO_EQUIPO_MANTENIMIENTO = 3;


    /**
     * Constructor que recibe la conexión a la base de datos (PDO).
     * @param PDO $db Objeto de conexión a la base de datos.
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crea un nuevo préstamo, insertando el registro principal y sus detalles, 
     * y actualizando el inventario de equipos.
     * @param int $id_usuario ID del usuario que solicita el préstamo.
     * @param array $equipos Array de equipos [{id_equipo, cantidad}, ...].
     * @param string $fecha_limite Fecha máxima de devolución.
     * @param string $observaciones Observaciones del préstamo (no utilizado en la query, pero buen parámetro).
     * @return array Resultado de la operación (success/error).
     */
    public function crearPrestamo($id_usuario, $equipos, $fecha_limite, $observaciones = '') {
        try {
            // Inicia la transacción para garantizar que todas las operaciones se completen o ninguna lo haga.
            $this->conn->beginTransaction();

            // 1. Generar código único para el préstamo
            $codigo_prestamo = 'PR' . date('Ymd') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);

            // 2. Insertar el registro principal del préstamo
            $query_prestamo = "INSERT INTO prestamo (id_usuario, fecha_limite_devolucion, id_estado_prestamo, codigo_prestamo) 
                                 VALUES (:id_usuario, :fecha_limite, " . self::ESTADO_PRESTAMO_ACTIVO . ", :codigo_prestamo)";
            
            $stmt_prestamo = $this->conn->prepare($query_prestamo);
            $stmt_prestamo->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $stmt_prestamo->bindParam(":fecha_limite", $fecha_limite);
            $stmt_prestamo->bindParam(":codigo_prestamo", $codigo_prestamo);
            $stmt_prestamo->execute();

            $id_prestamo = $this->conn->lastInsertId();

            // 3. Procesar cada equipo en la lista
            foreach ($equipos as $equipo) {
                // Verificar disponibilidad antes de prestar
                $query_verificar = "SELECT cantidad_disponible FROM equipo WHERE id_equipo = :id_equipo FOR UPDATE"; // Bloquea la fila
                $stmt_verificar = $this->conn->prepare($query_verificar);
                $stmt_verificar->bindParam(":id_equipo", $equipo['id_equipo'], PDO::PARAM_INT);
                $stmt_verificar->execute();
                $disponible = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

                if (!$disponible || $disponible['cantidad_disponible'] < $equipo['cantidad']) {
                    throw new Exception("No hay suficiente cantidad disponible para el equipo ID: " . $equipo['id_equipo']);
                }

                // 4. Insertar el detalle del préstamo
                $query_detalle = "INSERT INTO detalle_prestamo (id_prestamo, id_equipo, cantidad) 
                                 VALUES (:id_prestamo, :id_equipo, :cantidad)";
                $stmt_detalle = $this->conn->prepare($query_detalle);
                $stmt_detalle->bindParam(":id_prestamo", $id_prestamo, PDO::PARAM_INT);
                $stmt_detalle->bindParam(":id_equipo", $equipo['id_equipo'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(":cantidad", $equipo['cantidad'], PDO::PARAM_INT);
                $stmt_detalle->execute();

                // 5. Actualizar el inventario: restar cantidad disponible
                $query_actualizar = "UPDATE equipo 
                                    SET cantidad_disponible = cantidad_disponible - :cantidad,
                                        id_estado_equipo = CASE 
                                            -- Cambia el estado del equipo a PRESTADO (2) si la cantidad disponible llega a 0 o menos.
                                            WHEN (cantidad_disponible - :cantidad) <= 0 THEN " . self::ESTADO_EQUIPO_PRESTADO . " 
                                            ELSE id_estado_equipo 
                                        END
                                    WHERE id_equipo = :id_equipo";
                $stmt_actualizar = $this->conn->prepare($query_actualizar);
                $stmt_actualizar->bindParam(":cantidad", $equipo['cantidad'], PDO::PARAM_INT);
                $stmt_actualizar->bindParam(":id_equipo", $equipo['id_equipo'], PDO::PARAM_INT);
                $stmt_actualizar->execute();
            }

            // Si todo fue bien, confirma la transacción
            $this->conn->commit();
            return [
                'success' => true,
                'id_prestamo' => $id_prestamo,
                'codigo_prestamo' => $codigo_prestamo
            ];

        } catch (Exception $e) {
            // Si algo falla, revierte todos los cambios
            $this->conn->rollBack();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene una lista de préstamos para un usuario específico.
     * @param int $id_usuario ID del usuario.
     * @param bool $solo_activos Si es true, solo trae préstamos con estado ACTIVO.
     * @return array Lista de préstamos resumidos.
     */
    public function obtenerPrestamosUsuario($id_usuario, $solo_activos = false) {
        $query = "SELECT p.*, ep.nombre_estado as estado_prestamo,
                         COUNT(dp.id_detalle) as total_equipos,
                         -- Concatena los nombres de los equipos para una vista rápida
                         GROUP_CONCAT(e.nombre_equipo SEPARATOR ', ') as nombres_equipos
                  FROM prestamo p
                  INNER JOIN estado_prestamo ep ON p.id_estado_prestamo = ep.id_estado_prestamo
                  LEFT JOIN detalle_prestamo dp ON p.id_prestamo = dp.id_prestamo
                  LEFT JOIN equipo e ON dp.id_equipo = e.id_equipo
                  WHERE p.id_usuario = :id_usuario";
        
        if ($solo_activos) {
            $query .= " AND p.id_estado_prestamo IN (" . self::ESTADO_PRESTAMO_ACTIVO . ", 2)"; // 2 podría ser "En Curso" o "Vencido"
        }

        $query .= " GROUP BY p.id_prestamo
                    ORDER BY p.fecha_prestamo DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el detalle de los equipos incluidos en un préstamo específico.
     * @param int $id_prestamo ID del préstamo.
     * @return array Detalles del préstamo.
     */
    public function obtenerDetallePrestamo($id_prestamo) {
        $query = "SELECT dp.*, e.nombre_equipo, e.descripcion, ee.nombre_estado as estado_equipo
                  FROM detalle_prestamo dp
                  INNER JOIN equipo e ON dp.id_equipo = e.id_equipo
                  INNER JOIN estado_equipo ee ON e.id_estado_equipo = ee.id_estado_equipo
                  WHERE dp.id_prestamo = :id_prestamo";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_prestamo", $id_prestamo, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verifica si un usuario tiene préstamos activos que han superado su fecha límite de devolución.
     * @param int $id_usuario ID del usuario.
     * @return bool True si tiene préstamos vencidos, false en caso contrario.
     */
    public function tienePrestamosVencidos($id_usuario) {
        // Asume que el estado 1 es 'Activo/Pendiente'
        $query = "SELECT COUNT(*) as total 
                  FROM prestamo 
                  WHERE id_usuario = :id_usuario 
                  AND id_estado_prestamo = " . self::ESTADO_PRESTAMO_ACTIVO . "
                  AND fecha_limite_devolucion < CURDATE()"; // CURDATE() usa la fecha actual de la BD

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }

    /**
     * Procesa la devolución de una cantidad de equipos de un préstamo específico.
     * Este método es crucial para la lógica de inventario.
     * @param int $id_prestamo ID del préstamo.
     * @param int $id_equipo ID del equipo devuelto (en detalle_prestamo).
     * @param int $id_estado_devolucion Estado en que se devuelve (1: OK, 2: Mantenimiento, etc.).
     * @param string $observaciones Observaciones del proceso de devolución.
     * @param int $id_usuario_admin ID del administrador que registra la devolución.
     * @return array Resultado de la operación (success/error).
     */
    public function procesarDevolucion($id_prestamo, $id_equipo, $id_estado_devolucion, $observaciones, $id_usuario_admin) {
        try {
            $this->conn->beginTransaction();

            // 1. Registrar la devolución en la tabla 'devolucion'
            $query_devolucion = "INSERT INTO devolucion (id_prestamo, id_equipo, id_estado_devolucion, observaciones, id_usuario_admin) 
                                 VALUES (:id_prestamo, :id_equipo, :id_estado_devolucion, :observaciones, :id_usuario_admin)";
            
            $stmt_devolucion = $this->conn->prepare($query_devolucion);
            $stmt_devolucion->bindParam(":id_prestamo", $id_prestamo, PDO::PARAM_INT);
            $stmt_devolucion->bindParam(":id_equipo", $id_equipo, PDO::PARAM_INT);
            $stmt_devolucion->bindParam(":id_estado_devolucion", $id_estado_devolucion, PDO::PARAM_INT);
            $stmt_devolucion->bindParam(":observaciones", $observaciones);
            $stmt_devolucion->bindParam(":id_usuario_admin", $id_usuario_admin, PDO::PARAM_INT);
            $stmt_devolucion->execute();

            // Sub-consulta para obtener la cantidad original prestada de ese equipo en ese préstamo
            $subquery_cantidad = "(SELECT cantidad FROM detalle_prestamo 
                                   WHERE id_prestamo = :id_prestamo AND id_equipo = :id_equipo)";

            // 2. Actualizar inventario de equipo
            if ($id_estado_devolucion == self::ESTADO_DEVOLUCION_OK) {
                // Si la devolución es OK, aumenta la cantidad disponible y potencialmente cambia el estado del equipo a DISPONIBLE (1)
                $query_inventario = "UPDATE equipo 
                                     SET cantidad_disponible = cantidad_disponible + " . $subquery_cantidad . ",
                                     id_estado_equipo = CASE 
                                        WHEN cantidad_disponible + " . $subquery_cantidad . " > 0 THEN " . self::ESTADO_EQUIPO_DISPONIBLE . " 
                                        ELSE id_estado_equipo 
                                     END
                                     WHERE id_equipo = :id_equipo";
            } elseif ($id_estado_devolucion == self::ESTADO_DEVOLUCION_MANTENIMIENTO) {
                // Si necesita Mantenimiento, solo cambia el estado del equipo a MANTENIMIENTO (3)
                // NO se toca cantidad_disponible (se asume que sale del inventario para mantenimiento, debe ser gestionado por un proceso aparte)
                $query_inventario = "UPDATE equipo 
                                     SET id_estado_equipo = " . self::ESTADO_EQUIPO_MANTENIMIENTO . " 
                                     WHERE id_equipo = :id_equipo";
                // NOTA: Para una gestión estricta, la cantidad devuelta para mantenimiento no debería volver a ser 'disponible', 
                // pero el código original sugiere solo un cambio de estado general del tipo de equipo.
            } else { 
                // Asume que otros estados (ej. Perdido/Dada de Baja) implican reducir el stock total
                $query_inventario = "UPDATE equipo 
                                     SET cantidad_total = cantidad_total - " . $subquery_cantidad . ",
                                     cantidad_disponible = GREATEST(0, cantidad_disponible - " . $subquery_cantidad . ")
                                     WHERE id_equipo = :id_equipo";
                // Usa GREATEST(0, ...) para asegurar que cantidad_disponible no sea negativa si el estado actual es 'Agotado/Prestado'
            }

            $stmt_inventario = $this->conn->prepare($query_inventario);
            $stmt_inventario->bindParam(":id_prestamo", $id_prestamo, PDO::PARAM_INT);
            $stmt_inventario->bindParam(":id_equipo", $id_equipo, PDO::PARAM_INT);
            $stmt_inventario->execute();

            // 3. Verificar si todos los equipos del préstamo han sido devueltos
            $query_verificar_completado = "SELECT 
                COUNT(*) as total_equipos,
                SUM(CASE WHEN d.id_devolucion IS NOT NULL THEN 1 ELSE 0 END) as devueltos
                FROM detalle_prestamo dp
                LEFT JOIN devolucion d ON dp.id_prestamo = d.id_prestamo AND dp.id_equipo = d.id_equipo
                WHERE dp.id_prestamo = :id_prestamo";

            $stmt_verificar = $this->conn->prepare($query_verificar_completado);
            $stmt_verificar->bindParam(":id_prestamo", $id_prestamo, PDO::PARAM_INT);
            $stmt_verificar->execute();
            $resultado = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

            // 4. Si todos han sido devueltos, cambia el estado del préstamo a COMPLETADO
            if ($resultado['total_equipos'] == $resultado['devueltos']) {
                $query_completar_prestamo = "UPDATE prestamo SET id_estado_prestamo = " . self::ESTADO_PRESTAMO_COMPLETADO . " WHERE id_prestamo = :id_prestamo";
                $stmt_completar = $this->conn->prepare($query_completar_prestamo);
                $stmt_completar->bindParam(":id_prestamo", $id_prestamo, PDO::PARAM_INT);
                $stmt_completar->execute();
            }

            $this->conn->commit();
            return ['success' => true, 'prestamo_completado' => ($resultado['total_equipos'] == $resultado['devueltos'])];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene una lista detallada de todos los préstamos que aún están activos o en curso, 
     * útil para la vista de administración.
     * @return array Lista de préstamos activos.
     */
    public function obtenerPrestamosActivos() {
        // Asume que los estados 1 y 2 son los que requieren seguimiento (Activo, Vencido, etc.)
        $query = "SELECT p.*, u.nombre, u.apellido, u.correo, ep.nombre_estado as estado_prestamo,
                         COUNT(dp.id_detalle) as total_equipos,
                         GROUP_CONCAT(e.nombre_equipo SEPARATOR ', ') as nombres_equipos
                  FROM prestamo p
                  INNER JOIN usuario u ON p.id_usuario = u.id_usuario
                  INNER JOIN estado_prestamo ep ON p.id_estado_prestamo = ep.id_estado_prestamo
                  LEFT JOIN detalle_prestamo dp ON p.id_prestamo = dp.id_prestamo
                  LEFT JOIN equipo e ON dp.id_equipo = e.id_equipo
                  WHERE p.id_estado_prestamo IN (1, 2)
                  GROUP BY p.id_prestamo
                  ORDER BY p.fecha_limite_devolucion ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una lista de elementos individuales que están prestados y aún no han sido devueltos, 
     * ideal para la interfaz de procesamiento de devoluciones.
     * @return array Lista de ítems pendientes de devolución.
     */
    public function obtenerPrestamosParaDevolucion() {
        $query = "SELECT p.*, u.nombre, u.apellido, u.correo, ep.nombre_estado as estado_prestamo,
                          dp.id_equipo, e.nombre_equipo, dp.cantidad,
                          (SELECT COUNT(*) FROM devolucion d WHERE d.id_prestamo = p.id_prestamo AND d.id_equipo = dp.id_equipo) as ya_devuelto
                  FROM prestamo p
                  INNER JOIN usuario u ON p.id_usuario = u.id_usuario
                  INNER JOIN estado_prestamo ep ON p.id_estado_prestamo = ep.id_estado_prestamo
                  INNER JOIN detalle_prestamo dp ON p.id_prestamo = dp.id_prestamo
                  INNER JOIN equipo e ON dp.id_equipo = e.id_equipo
                  WHERE p.id_estado_prestamo IN (1, 2) -- Préstamos Activos
                  -- Filtra solo los items que NO tienen un registro en la tabla 'devolucion'
                  AND (SELECT COUNT(*) FROM devolucion d WHERE d.id_prestamo = p.id_prestamo AND d.id_equipo = dp.id_equipo) = 0
                  ORDER BY p.fecha_limite_devolucion ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los estados posibles para el proceso de devolución (ej. OK, Mantenimiento, Perdido).
     * @return array Lista de estados de devolución.
     */
    public function obtenerEstadosDevolucion() {
        $query = "SELECT * FROM estado_devolucion ORDER BY id_estado_devolucion";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene estadísticas agregadas sobre el estado de los préstamos.
     * @return array Estadísticas resumidas.
     */
    public function obtenerEstadisticasPrestamos() {
        $query = "SELECT 
                      COUNT(*) as total_prestamos,
                      SUM(CASE WHEN id_estado_prestamo = 1 THEN 1 ELSE 0 END) as prestamos_activos,
                      SUM(CASE WHEN id_estado_prestamo = 2 THEN 1 ELSE 0 END) as prestamos_vencidos,
                      SUM(CASE WHEN id_estado_prestamo = 3 THEN 1 ELSE 0 END) as prestamos_completados,
                      COUNT(DISTINCT id_usuario) as usuarios_con_prestamos
                  FROM prestamo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un préstamo de manera presencial (idéntico a crearPrestamo, pero a veces usado para distinguir la fuente).
     * Mantiene la misma lógica transaccional de verificación de inventario y actualización de stock.
     * @param array $datos Array con 'id_usuario', 'fecha_limite' y 'equipos'.
     * @return array Resultado de la operación (success/error).
     */
    public function crearPrestamoPresencial($datos) {
        // Este método replica la lógica de 'crearPrestamo'
        // Sería recomendable en un entorno real refactorizar para llamar a 'crearPrestamo' y evitar duplicación.
        return $this->crearPrestamo(
            $datos['id_usuario'], 
            $datos['equipos'], 
            $datos['fecha_limite'], 
            $datos['observaciones'] ?? ''
        );
    }

    /**
     * Actualiza la fecha límite de devolución de un préstamo.
     * @param int $id_prestamo ID del préstamo a actualizar.
     * @param string $fecha_limite Nueva fecha límite de devolución.
     * @return array Resultado de la operación (success/error).
     */
    public function actualizarFechaLimite($id_prestamo, $fecha_limite) {
        $query = "UPDATE prestamo SET fecha_limite_devolucion = :fecha_limite WHERE id_prestamo = :id_prestamo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":fecha_limite", $fecha_limite);
        $stmt->bindParam(":id_prestamo", $id_prestamo, PDO::PARAM_INT);
        return $stmt->execute() ? ['success' => true, 'message' => 'Fecha actualizada correctamente'] : ['success' => false, 'error' => 'Error al actualizar'];
    }

    /**
     * Actualiza la cantidad de un equipo en un préstamo específico.
     * @param int $id_prestamo
     * @param int $id_equipo
     * @param int $cantidad
     * @return array
     */
    public function actualizarCantidadEquipo($id_prestamo, $id_equipo, $cantidad) {
        try {
            // Verificar que la cantidad no exceda la disponible
            $query_disponible = "SELECT cantidad_disponible FROM equipo WHERE id_equipo = :id_equipo";
            $stmt = $this->conn->prepare($query_disponible);
            $stmt->bindParam(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();
            $disponible = $stmt->fetchColumn();

            if ($disponible === false) {
                return ['success' => false, 'error' => 'Equipo no encontrado'];
            }

            // Obtener cantidad anterior
            $query_anterior = "SELECT cantidad FROM detalle_prestamo WHERE id_prestamo = :id_prestamo AND id_equipo = :id_equipo";
            $stmt = $this->conn->prepare($query_anterior);
            $stmt->bindParam(':id_prestamo', $id_prestamo, PDO::PARAM_INT);
            $stmt->bindParam(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();
            $cantidad_anterior = $stmt->fetchColumn();

            if ($cantidad_anterior === false) {
                return ['success' => false, 'error' => 'Equipo no encontrado en este préstamo'];
            }

            $diferencia = $cantidad - $cantidad_anterior;

            if ($diferencia > 0 && $disponible < $diferencia) {
                return ['success' => false, 'error' => 'No hay suficiente cantidad disponible'];
            }

            // Actualizar cantidad en detalle_prestamo
            $query = "UPDATE detalle_prestamo SET cantidad = :cantidad 
                      WHERE id_prestamo = :id_prestamo AND id_equipo = :id_equipo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':id_prestamo', $id_prestamo, PDO::PARAM_INT);
            $stmt->bindParam(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();

            // Actualizar inventario
            $query_inventario = "UPDATE equipo SET cantidad_disponible = cantidad_disponible - :diferencia WHERE id_equipo = :id_equipo";
            $stmt = $this->conn->prepare($query_inventario);
            $stmt->bindParam(':diferencia', $diferencia, PDO::PARAM_INT);
            $stmt->bindParam(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();

            return ['success' => true, 'message' => 'Cantidad actualizada correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Elimina un equipo de un préstamo y devuelve la cantidad al inventario.
     * @param int $id_prestamo
     * @param int $id_equipo
     * @return array
     */
    public function eliminarEquipoDePrestamo($id_prestamo, $id_equipo) {
        try {
            // Obtener cantidad antes de eliminar
            $query = "SELECT cantidad FROM detalle_prestamo WHERE id_prestamo = :id_prestamo AND id_equipo = :id_equipo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_prestamo', $id_prestamo, PDO::PARAM_INT);
            $stmt->bindParam(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();
            $cantidad = $stmt->fetchColumn();

            if ($cantidad === false) {
                return ['success' => false, 'error' => 'Equipo no encontrado en este préstamo'];
            }

            // Eliminar de detalle_prestamo
            $query = "DELETE FROM detalle_prestamo WHERE id_prestamo = :id_prestamo AND id_equipo = :id_equipo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_prestamo', $id_prestamo, PDO::PARAM_INT);
            $stmt->bindParam(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();

            // Devolver cantidad al inventario
            $query = "UPDATE equipo SET cantidad_disponible = cantidad_disponible + :cantidad WHERE id_equipo = :id_equipo";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
            $stmt->bindParam(':id_equipo', $id_equipo, PDO::PARAM_INT);
            $stmt->execute();

            return ['success' => true, 'message' => 'Equipo eliminado del préstamo'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>