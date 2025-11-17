<?php
// database.php - Clase para manejar la Conexión a la Base de Datos (BD)

// Utilizo el patrón de diseño Singleton o Factory (depende de cómo se instancie)
// para asegurar una forma consistente y centralizada de conectarme a la BD usando PDO.

class Database {
    
    // --- 1. Propiedades de Conexión (Credenciales) ---
    
    // Estos son los parámetros necesarios para que PDO sepa dónde y cómo conectar.
    private $host = "localhost";    // La ubicación de mi servidor de base de datos (generalmente local en desarrollo).
    private $db_name = "prestlab_db"; // El nombre de la base de datos de mi proyecto PRESTLAB.
    private $username = "root";     // El usuario de la BD (configuración por defecto de XAMPP/WAMP).
    private $password = "";         // La contraseña del usuario (configuración por defecto de XAMPP/WAMP, vacía).
    
    public $conn; // Esta propiedad pública almacenará el objeto de conexión real (instancia de PDO).

    // --- 2. Método de Conexión ---
    
    /**
     * Obtiene una conexión activa a la base de datos utilizando PDO.
     * @return PDO|null El objeto de conexión PDO o null si la conexión falla.
     */
    public function getConnection() {
        // Inicializo la conexión a null cada vez que se llama al método.
        $this->conn = null;
        
        try {
            // Creo una nueva instancia de PDO (PHP Data Objects).
            // La cadena de conexión DSN especifica el tipo de BD (mysql), host y nombre de la BD.
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            
            // Defino el juego de caracteres a UTF-8. 
            // Esto es crucial para manejar correctamente caracteres especiales (tildes, ñ, etc.).
            $this->conn->exec("set names utf8");
            
            // echo "Conexión exitosa"; // (¡Lo dejo comentado, pero lo uso para debug!)
            
        } catch(PDOException $exception) {
            // Si algo sale mal (credenciales incorrectas, BD no existe, etc.), capturo la excepción
            // y muestro un mensaje de error para debug.
            echo "Error de conexión: " . $exception->getMessage();
        }
        
        // Devuelvo el objeto de conexión (sea una instancia de PDO o null si falló).
        return $this->conn;
    }
}
?>