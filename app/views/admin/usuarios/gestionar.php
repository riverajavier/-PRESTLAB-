<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRESTLAB - Gestión de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .navbar-custom { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); }
        .sidebar { background-color: #2c3e50; min-height: calc(100vh - 76px); padding: 0; }
        .sidebar .nav-link { color: rgba(255,255,255,0.8); padding: 15px 20px; border-left: 3px solid transparent; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(255,255,255,0.1); border-left-color: #3498db; color: white; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><i class="bi bi-person-badge"></i> PRESTLAB - Admin</a>
        <div class="d-flex align-items-center">
            <span class="navbar-text me-3"><i class="bi bi-person-circle"></i> <?php echo Session::get('user_nombre'); ?></span>
            <a href="/prestlab/public/index.php?action=logout" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Salir</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <nav class="nav flex-column">
                <a class="nav-link" href="/prestlab/public/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link" href="/prestlab/public/index.php?controller=inventario&action=gestionarInventario"><i class="bi bi-box-seam"></i> Inventario</a>
                <a class="nav-link" href="/prestlab/public/index.php?controller=prestamo&action=gestionarPrestamos"><i class="bi bi-clipboard-check"></i> Préstamos</a>
                <a class="nav-link" href="/prestlab/public/index.php?controller=devolucion&action=gestionarDevoluciones"><i class="bi bi-arrow-return-left"></i> Devoluciones</a>
                <a class="nav-link active" href="/prestlab/public/index.php?controller=usuario&action=gestionarUsuarios"><i class="bi bi-people"></i> Usuarios</a>
            </nav>
        </div>

        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2>Gestión de Usuarios</h2>
                    <p class="text-muted">Administra los usuarios del sistema</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
                    <i class="bi bi-person-plus"></i> Crear Usuario
                </button>
            </div>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3><?php echo $estadisticas['total_usuarios']; ?></h3>
                        <p class="text-muted mb-0">Total Usuarios</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3><?php echo $estadisticas['total_administradores']; ?></h3>
                        <p class="text-muted mb-0">Administradores</p>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card">
                        <h3><?php echo $estadisticas['total_usuarios_normales']; ?></h3>
                        <p class="text-muted mb-0">Usuarios Normales</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?php echo $estadisticas['usuarios_activos']; ?></h3>
                        <p class="text-muted mb-0">Usuarios Activos</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <h3><?php echo $estadisticas['usuarios_inactivos']; ?></h3>
                        <p class="text-muted mb-0">Usuarios Inactivos</p>
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card">
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id_usuario']; ?></td>
                                    <td><?php echo $usuario['nombre'] . ' ' . $usuario['apellido']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['correo']); ?></td>

                                    <td>
                                        <span class="badge bg-<?php echo $usuario['id_rol']==1 ? 'danger':'primary'; ?>">
                                            <?php echo $usuario['nombre_rol']; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-<?php echo $usuario['estado']=='activo' ? 'success':'secondary'; ?>">
                                            <?php echo ucfirst($usuario['estado']); ?>
                                        </span>
                                    </td>

                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>

                                    <td>

                                        <!-- Botón Editar -->
                                        <button class="btn btn-sm btn-outline-primary editar-usuario"
                                                data-usuario-id="<?php echo $usuario['id_usuario']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <!-- Botón toggle estado -->
                                        <button class="btn btn-sm btn-outline-warning toggle-estado" 
                                                data-usuario-id="<?php echo $usuario['id_usuario']; ?>"
                                                data-estado-actual="<?php echo $usuario['estado']; ?>"
                                                title="<?php echo $usuario['estado']=='activo' ? 'Bloquear' : 'Desbloquear'; ?>">
                                            <i class="bi bi-<?php echo $usuario['estado']=='activo' ? 'lock':'unlock'; ?>"></i>
                                        </button>

                                        <!-- Botón eliminar -->
                                        <button class="btn btn-sm btn-outline-danger eliminar-usuario"
                                                data-usuario-id="<?php echo $usuario['id_usuario']; ?>"
                                                data-usuario-nombre="<?php echo $usuario['nombre'].' '.$usuario['apellido']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrearUsuario">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Crear Nuevo Usuario</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="form-crear-usuario">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Nombre</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Apellido</label>
                            <input type="text" class="form-control" name="apellido" required>
                        </div>
                    </div>

                    <label>Correo</label>
                    <input type="email" class="form-control mb-3" name="correo" required>

                    <label>Contraseña Temporal</label>
                    <input type="password" class="form-control mb-3" name="contrasena" minlength="8" required>

                    <label>Rol</label>
                    <select class="form-select mb-3" name="id_rol">
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?php echo $rol['id_rol']; ?>">
                                <?php echo $rol['nombre_rol']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Estado</label>
                    <select class="form-select" name="estado">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" id="btn-crear-usuario">Crear Usuario</button>
            </div>

        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Editar Usuario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="form-editar-usuario">
          <input type="hidden" id="edit_id_usuario" name="id_usuario">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Apellido</label>
              <input type="text" class="form-control" id="edit_apellido" name="apellido" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Rol</label>
            <select class="form-select" id="edit_id_rol" name="id_rol" required>
              <?php foreach ($roles as $rol): ?>
                <option value="<?php echo $rol['id_rol']; ?>"><?php echo htmlspecialchars($rol['nombre_rol']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btn-guardar-cambios">Guardar Cambios</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ✅ TODO EL JAVASCRIPT UNIFICADO EN UN SOLO DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
  const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));

  // ------------------------------
  // CREAR USUARIO
  // ------------------------------
  document.querySelector("#btn-crear-usuario").addEventListener("click", () => {
    const formData = new FormData(document.querySelector("#form-crear-usuario"));
    
    fetch("/prestlab/public/index.php?controller=usuario&action=crearUsuario", {
      method: "POST",
      body: formData
    })
    .then(r => r.json())
    .then(d => {
      alert(d.message);
      if (d.success) location.reload();
    });
  });

  // ------------------------------
  // EDITAR USUARIO - Cargar datos
  // ------------------------------
  document.querySelectorAll('.editar-usuario').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.getAttribute('data-usuario-id');
      fetch(`/prestlab/public/index.php?controller=usuario&action=obtenerUsuario&id_usuario=${id}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            const u = data.usuario;
            document.getElementById('edit_id_usuario').value = u.id_usuario;
            document.getElementById('edit_nombre').value = u.nombre;
            document.getElementById('edit_apellido').value = u.apellido;
            document.getElementById('edit_id_rol').value = u.id_rol;
            modalEditar.show();
          }
        });
    });
  });

  // ------------------------------
  // GUARDAR CAMBIOS (Edición)
  // ------------------------------
  document.getElementById('btn-guardar-cambios').addEventListener('click', function () {
    const formData = new FormData(document.getElementById('form-editar-usuario'));

    fetch('/prestlab/public/index.php?controller=usuario&action=actualizarUsuario', {
      method: 'POST',
      body: formData
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert(data.message);
          location.reload();
        } else {
          alert('Error: ' + data.error);
        }
      });
  });

  // ------------------------------
  // ELIMINAR USUARIO (con mensaje de error si tiene préstamos activos)
  // ------------------------------
  document.querySelectorAll(".eliminar-usuario").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.usuarioId;
      const nombre = btn.dataset.usuarioNombre;

      if (!confirm(`¿Eliminar al usuario "${nombre}"?`)) return;

      const fd = new FormData();
      fd.append("id_usuario", id);

      fetch("/prestlab/public/index.php?controller=usuario&action=eliminarUsuario", {
        method: "POST",
        body: fd
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          alert("Usuario eliminado correctamente.");
          location.reload();
        } else {
          alert("Error: " + d.error); // ✅ Ahora sí mostrará el mensaje de préstamos activos
        }
      })
      .catch(err => {
        console.error(err);
        alert("Error inesperado al eliminar.");
      });
    });
  });

  // ------------------------------
  // TOGGLE ESTADO (activar/desactivar)
  // ------------------------------
  document.querySelectorAll('.toggle-estado').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.getAttribute('data-usuario-id');
      const estadoActual = this.getAttribute('data-estado-actual');
      const nuevoEstado = estadoActual === 'activo' ? 'inactivo' : 'activo';

      if (confirm(`¿Estás seguro de ${nuevoEstado === 'activo' ? 'desbloquear' : 'bloquear'} este usuario?`)) {
        const formData = new FormData();
        formData.append('id_usuario', id);
        formData.append('estado', nuevoEstado);

        fetch('/prestlab/public/index.php?controller=usuario&action=toggleEstadoUsuario', {
          method: 'POST',
          body: formData
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              location.reload();
            } else {
              alert('Error: ' + data.error);
            }
          });
      }
    });
  });
});
</script>

</body>
</html>