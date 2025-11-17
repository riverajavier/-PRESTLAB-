<?php
require_once '../app/core/Session.php';

class UsuarioController {
    private $usuarioModel;
    private $prestamoModel;

    public function __construct($db) {
        $this->usuarioModel = new UsuarioModel($db);
        $this->prestamoModel = new PrestamoModel($db);
    }

    public function gestionarUsuarios() {
        Session::checkAuth();
        
        if (Session::getUserRole() != 1) {
            header("Location: /prestlab/public/index.php");
            exit();
        }

        $filtros = [];
        if (!empty($_GET['busqueda'])) {
            $filtros['busqueda'] = trim($_GET['busqueda']);
        }
        if (!empty($_GET['rol']) && is_numeric($_GET['rol'])) {
            $filtros['rol'] = (int)$_GET['rol'];
        }
        if (!empty($_GET['estado'])) {
            $filtros['estado'] = $_GET['estado'];
        }

        $usuarios = $this->usuarioModel->obtenerUsuarios($filtros);
        $roles = $this->usuarioModel->obtenerRoles();
        $estadisticas = $this->usuarioModel->obtenerEstadisticasUsuarios();

        $data = [
            'usuarios' => $usuarios,
            'roles' => $roles,
            'estadisticas' => $estadisticas,
            'filtros_actuales' => $filtros
        ];

        extract($data);
        include_once '../app/views/admin/usuarios/gestionar.php';
    }

    public function crearUsuario() {
        Session::checkAuth();
        
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST) {
            $datos = [
                'nombre' => trim($_POST['nombre']),
                'apellido' => trim($_POST['apellido']),
                'correo' => trim($_POST['correo']),
                'contrasena' => $_POST['contrasena'],
                'id_rol' => (int)$_POST['id_rol'],
                'estado' => $_POST['estado']
            ];

            if (empty($datos['nombre']) || empty($datos['apellido']) || empty($datos['correo']) || empty($datos['contrasena'])) {
                $response = ['success' => false, 'error' => 'Todos los campos son obligatorios'];
            } elseif (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
                $response = ['success' => false, 'error' => 'El formato del correo electrónico no es válido'];
            } elseif (strlen($datos['contrasena']) < 8) {
                $response = ['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres'];
            } else {
                $resultado = $this->usuarioModel->crearUsuario(
                    $datos['nombre'], 
                    $datos['apellido'], 
                    $datos['correo'], 
                    $datos['contrasena'], 
                    $datos['id_rol']
                );

                if ($resultado) {
                    $response = ['success' => true, 'message' => 'Usuario creado exitosamente'];
                } else {
                    $response = ['success' => false, 'error' => 'El correo electrónico ya está registrado'];
                }
            }

            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    public function actualizarUsuario() {
        Session::checkAuth();

        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST && isset($_POST['id_usuario'])) {
            $id_usuario = (int)$_POST['id_usuario'];

            $datos = [
                'nombre' => trim($_POST['nombre']),
                'apellido' => trim($_POST['apellido']),
                'id_rol' => (int)$_POST['id_rol'],
                'estado' => null // no cambiamos estado aquí
            ];

            // Validaciones básicas
            if (empty($datos['nombre']) || empty($datos['apellido'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Nombre y apellido son obligatorios']);
                exit();
            }

            $resultado = $this->usuarioModel->actualizarUsuario($id_usuario, $datos);

            header('Content-Type: application/json');
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se pudo actualizar el usuario']);
            }
            exit();
        }
    }

    public function eliminarUsuario() {
        Session::checkAuth();
        
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST && isset($_POST['id_usuario'])) {
            $id_usuario = (int)$_POST['id_usuario'];

            if ($id_usuario == Session::getUserId()) {
                $response = ['success' => false, 'error' => 'No puedes eliminar tu propia cuenta'];
            } else {
                $resultado = $this->usuarioModel->eliminarUsuario($id_usuario);
                $response = $resultado;
            }

            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        }
    }

    public function obtenerUsuario() {
        Session::checkAuth();
        
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if (isset($_GET['id_usuario']) && is_numeric($_GET['id_usuario'])) {
            $usuario = $this->usuarioModel->obtenerUsuarioPorId($_GET['id_usuario']);
            
            header('Content-Type: application/json');
            if ($usuario) {
                echo json_encode(['success' => true, 'usuario' => $usuario]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'ID de usuario no válido']);
        }
        exit();
    }

    public function obtenerUsuarios() {
        Session::checkAuth();
        
        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        $usuarios = $this->usuarioModel->obtenerUsuarios([]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'usuarios' => $usuarios]);
        exit();
    }

    /**
     * ✅ Nuevo método: activar/desactivar usuario (toggle)
     */
    public function toggleEstadoUsuario() {
        Session::checkAuth();

        if (Session::getUserRole() != 1) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado']);
            exit();
        }

        if ($_POST && isset($_POST['id_usuario'])) {
            $id_usuario = (int)$_POST['id_usuario'];
            $nuevo_estado = $_POST['estado'] ?? 'activo';

            // No permitir desactivarse a sí mismo
            if ($id_usuario == Session::getUserId()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'No puedes cambiar tu propio estado']);
                exit();
            }

            $resultado = $this->usuarioModel->actualizarUsuario($id_usuario, [
                'nombre' => null,
                'apellido' => null,
                'id_rol' => null,
                'estado' => $nuevo_estado
            ]);

            header('Content-Type: application/json');
            if ($resultado) {
                echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al actualizar estado']);
            }
            exit();
        }
    }
}
?>