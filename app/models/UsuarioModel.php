<?php
class UsuarioModel {
    private $conn;
    private $table_name = "usuario";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Método para buscar usuario por correo
    public function buscarPorCorreo($correo) {
        $query = "SELECT id_usuario, nombre, apellido, correo, contrasena, id_rol, estado 
                  FROM " . $this->table_name . " 
                  WHERE correo = :correo AND estado = 'activo' 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":correo", $correo);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Método para registrar un intento de acceso
    public function registrarIntentoAcceso($id_usuario, $ip, $estado, $rol) {
        $query = "INSERT INTO logs_acceso (id_usuario, direccion_ip, estado, rol_esperado) 
                  VALUES (:id_usuario, :ip, :estado, :rol)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":ip", $ip);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":rol", $rol);
        return $stmt->execute();
    }

    public function contarIntentosFallidos($correo, $minutos = 15) {
        $query = "SELECT COUNT(*) as intentos 
                  FROM logs_acceso 
                  WHERE id_usuario = (SELECT id_usuario FROM usuario WHERE correo = :correo) 
                  AND estado = 'fallido' 
                  AND fecha_hora > DATE_SUB(NOW(), INTERVAL :minutos MINUTE)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":correo", $correo);
        $stmt->bindParam(":minutos", $minutos);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['intentos'];
    }

    public function estaBloqueado($correo) {
        $intentos = $this->contarIntentosFallidos($correo);
        return $intentos >= 3;
    }

    // Crear usuario
    public function crearUsuario($nombre, $apellido, $correo, $contrasena, $id_rol = 2) {
        if ($this->buscarPorCorreo($correo)) {
            return false;
        }

        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table_name . " 
                  (nombre, apellido, correo, contrasena, id_rol) 
                  VALUES (:nombre, :apellido, :correo, :contrasena, :id_rol)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $nombre);
        $stmt->bindParam(":apellido", $apellido);
        $stmt->bindParam(":correo", $correo);
        $stmt->bindParam(":contrasena", $contrasena_hash);
        $stmt->bindParam(":id_rol", $id_rol);

        return $stmt->execute();
    }

    public function obtenerUsuarios($filtros = []) {
        $query = "SELECT u.*, r.nombre_rol 
                  FROM " . $this->table_name . " u 
                  INNER JOIN rol r ON u.id_rol = r.id_rol 
                  WHERE 1=1";
        
        $params = [];

        if (!empty($filtros['busqueda'])) {
            $query .= " AND (u.nombre LIKE :busqueda OR u.apellido LIKE :busqueda OR u.correo LIKE :busqueda)";
            $params[':busqueda'] = '%' . $filtros['busqueda'] . '%';
        }

        if (!empty($filtros['rol'])) {
            $query .= " AND u.id_rol = :rol";
            $params[':rol'] = $filtros['rol'];
        }

        if (!empty($filtros['estado'])) {
            $query .= " AND u.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        $query .= " ORDER BY u.fecha_creacion DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener usuario por ID
    public function obtenerUsuarioPorId($id_usuario) {
        $query = "SELECT u.*, r.nombre_rol 
                  FROM " . $this->table_name . " u 
                  INNER JOIN rol r ON u.id_rol = r.id_rol 
                  WHERE u.id_usuario = :id_usuario 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    /**
     * ✅ Método actualizado: actualiza solo los campos NO nulos
     */
    public function actualizarUsuario($id_usuario, $datos) {
        // Solo actualiza los campos que no sean null
        $setParts = [];
        $params = [':id_usuario' => $id_usuario];

        if (!is_null($datos['nombre'])) {
            $setParts[] = "nombre = :nombre";
            $params[':nombre'] = $datos['nombre'];
        }
        if (!is_null($datos['apellido'])) {
            $setParts[] = "apellido = :apellido";
            $params[':apellido'] = $datos['apellido'];
        }
        if (!is_null($datos['id_rol'])) {
            $setParts[] = "id_rol = :id_rol";
            $params[':id_rol'] = $datos['id_rol'];
        }
        if (!is_null($datos['estado'])) {
            $setParts[] = "estado = :estado";
            $params[':estado'] = $datos['estado'];
        }

        // Si no hay cambios, no ejecuta nada
        if (empty($setParts)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET " . implode(', ', $setParts) . " 
                  WHERE id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function eliminarUsuario($id_usuario) {
        $query_verificar = "SELECT COUNT(*) as total 
                            FROM prestamo 
                            WHERE id_usuario = :id_usuario 
                            AND id_estado_prestamo IN (1, 2)";
        $stmt_verificar = $this->conn->prepare($query_verificar);
        $stmt_verificar->bindParam(":id_usuario", $id_usuario);
        $stmt_verificar->execute();
        $resultado = $stmt_verificar->fetch(PDO::FETCH_ASSOC);

        if ($resultado['total'] > 0) {
            return ['success' => false, 'error' => 'No se puede eliminar el usuario porque tiene préstamos activos'];
        }

        $query_eliminar = "DELETE FROM " . $this->table_name . " WHERE id_usuario = :id_usuario";
        $stmt_eliminar = $this->conn->prepare($query_eliminar);
        $stmt_eliminar->bindParam(":id_usuario", $id_usuario);
        
        if ($stmt_eliminar->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Error al eliminar el usuario'];
        }
    }

    public function obtenerRoles() {
        $query = "SELECT * FROM rol ORDER BY nombre_rol";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEstadisticasUsuarios() {
        $query = "SELECT 
                    COUNT(*) as total_usuarios,
                    SUM(CASE WHEN id_rol = 1 THEN 1 ELSE 0 END) as total_administradores,
                    SUM(CASE WHEN id_rol = 2 THEN 1 ELSE 0 END) as total_usuarios_normales,
                    SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as usuarios_activos,
                    SUM(CASE WHEN estado = 'inactivo' THEN 1 ELSE 0 END) as usuarios_inactivos
                  FROM usuario";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerTodosUsuarios() {
        $query = "SELECT id_usuario, nombre, apellido, correo 
                  FROM usuario 
                  WHERE estado = 'activo'
                  ORDER BY nombre, apellido";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
