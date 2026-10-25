<?php

namespace App\Controllers;

use App\Helpers\Response;
use App\Helpers\Validador;
use App\Core\HttpException;
use App\Models\Usuario;

final class AuthController
{
    public function registro(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $faltantes = Validador::requerido($body, ['nombre', 'apellido', 'email', 'password', 'password_confirmacion']);
        if (!empty($faltantes)) {
            throw new HttpException(400, 'CAMPOS_FALTANTES', 'Faltan campos obligatorios.', ['faltantes' => $faltantes]);
        }

        if (!Validador::email($body['email'])) {
            throw new HttpException(400, 'EMAIL_INVALIDO', 'El correo electronico no es valido.');
        }

        $erroresPass = Validador::passwordFuerte($body['password']);
        if (!empty($erroresPass)) {
            throw new HttpException(400, 'PASSWORD_DEBIL', 'La contrasena no cumple con los requisitos.', ['errores' => $erroresPass]);
        }

        if ($body['password'] !== $body['password_confirmacion']) {
            throw new HttpException(400, 'PASSWORD_NO_COINCIDE', 'Las contrasenas no coinciden.');
        }

        // Solo se permite registro para ALUMNOS/ESTUDIANTES
        // Administrador y Maestros/Instructores ya tienen cuentas pre-creadas en la BD
        $rol = 'estudiante';

        $usuarioModel = new Usuario();
        $existe = $usuarioModel->buscarPorEmail($body['email']);

        if ($existe) {
            throw new HttpException(409, 'EMAIL_REGISTRADO', 'Ya existe una cuenta con ese correo electronico.');
        }

        $passwordHash = password_hash($body['password'], PASSWORD_DEFAULT);
        $id = $usuarioModel->crear([
            'nombre'        => trim($body['nombre']),
            'apellido'      => trim($body['apellido']),
            'email'         => $body['email'],
            'password_hash' => $passwordHash,
            'rol'           => $rol,
        ]);

        $_SESSION['usuario_id']  = $id;
        $_SESSION['usuario_rol'] = $rol;
        $_SESSION['usuario_nombre'] = trim($body['nombre']) . ' ' . trim($body['apellido']);

        $usuario = $usuarioModel->buscarPorId($id);

        return Response::creado([
            'usuario' => $usuario,
        ], 'Registro exitoso como estudiante. Sesion iniciada.');
    }

    public function login(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $faltantes = Validador::requerido($body, ['email', 'password']);
        if (!empty($faltantes)) {
            throw new HttpException(400, 'CAMPOS_FALTANTES', 'Ingresa correo y contrasena.', ['faltantes' => $faltantes]);
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorEmail($body['email']);

        if (!$usuario) {
            throw new HttpException(401, 'CREDENCIALES_INVALIDAS', 'Correo o contrasena incorrectos.');
        }

        // Bloqueo al tercer intento fallido consecutivo
        if ($usuarioModel->estaBloqueado($usuario)) {
            throw new HttpException(423, 'CUENTA_BLOQUEADA', 'Cuenta bloqueada temporalmente por multiples intentos fallidos. Intenta mas tarde.');
        }

        if ((int) $usuario['activo'] !== 1) {
            throw new HttpException(403, 'CUENTA_INACTIVA', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
        }

        if (!password_verify($body['password'], $usuario['password_hash'])) {
            $usuarioModel->registrarIntentoFallido((int) $usuario['id']);

            $actual = $usuarioModel->buscarPorId((int) $usuario['id']);
            $intentos = (int) ($actual['intentos_fallidos'] ?? $usuario['intentos_fallidos'] + 1);

            if ($intentos >= 3) {
                $usuarioModel->bloquearCuenta((int) $usuario['id'], 15);
                throw new HttpException(423, 'CUENTA_BLOQUEADA', 'Demasiados intentos fallidos. La cuenta fue bloqueada por 15 minutos.');
            }

            throw new HttpException(401, 'CREDENCIALES_INVALIDAS', "Correo o contrasena incorrectos. Intento {$intentos}/3.");
        }

        // Login correcto
        $usuarioModel->resetearIntentos((int) $usuario['id']);

        $_SESSION['usuario_id']     = (int) $usuario['id'];
        $_SESSION['usuario_rol']    = $usuario['rol'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
        $_SESSION['usuario_email']  = $usuario['email'];

        return Response::ok([
            'usuario' => [
                'id'     => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol'],
                'foto_perfil' => $usuario['foto_perfil'] ?? null,
            ],
        ], 'Inicio de sesion exitoso.');
    }

    public function logout(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        session_destroy();

        return Response::ok([], 'Sesion cerrada.');
    }

    public function yo(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['usuario_id'])) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'No hay sesion activa.');
        }

        $usuario = (new Usuario())->buscarPorId((int) $_SESSION['usuario_id']);

        if (!$usuario) {
            throw new HttpException(401, 'NO_AUTENTICADO', 'Sesion invalida.');
        }

        return Response::ok(['usuario' => $usuario]);
    }

    public function google(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $credential = $body['credential'] ?? '';

        if ($credential === '') {
            throw new HttpException(400, 'CREDENCIAL_FALTANTE', 'Falta el token de Google.');
        }

        // Verificar token con Google
        $clientId = $_ENV['GOOGLE_CLIENT_ID'] ?? (getenv('GOOGLE_CLIENT_ID') ?: '');
        if ($clientId === 'TU_GOOGLE_CLIENT_ID_AQUI' || $clientId === '') {
            throw new HttpException(500, 'GOOGLE_NO_CONFIGURADO', 'Google Client ID no configurado en .env.');
        }

        $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        $ch = curl_init($verifyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            throw new HttpException(401, 'TOKEN_GOOGLE_INVALIDO', 'Token de Google inválido.');
        }

        $payload = json_decode($response, true);
        if (($payload['aud'] ?? '') !== $clientId) {
            throw new HttpException(401, 'TOKEN_GOOGLE_INVALIDO', 'Token de Google no válido para esta aplicación.');
        }

        if (($payload['email_verified'] ?? 'false') !== 'true') {
            throw new HttpException(401, 'EMAIL_NO_VERIFICADO', 'El correo de Google no está verificado.');
        }

        $email = $payload['email'] ?? '';
        $nombre = $payload['given_name'] ?? ($payload['name'] ?? 'Usuario');
        $apellido = $payload['family_name'] ?? '';

        if ($email === '') {
            throw new HttpException(400, 'EMAIL_FALTANTE', 'No se obtuvo el correo de Google.');
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->buscarPorEmail($email);

        if (!$usuario) {
            // Solo permitir registro con Google para estudiantes si no existe
            $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $id = $usuarioModel->crear([
                'nombre'        => trim($nombre),
                'apellido'      => trim($apellido) !== '' ? trim($apellido) : 'Google',
                'email'         => $email,
                'password_hash' => $passwordHash,
                'rol'           => 'estudiante',
            ]);
            $usuario = $usuarioModel->buscarPorId($id);
        }

        if ((int) ($usuario['activo'] ?? 1) !== 1) {
            throw new HttpException(403, 'CUENTA_INACTIVA', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
        }

        $_SESSION['usuario_id']     = (int) $usuario['id'];
        $_SESSION['usuario_rol']    = $usuario['rol'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'] . ' ' . $usuario['apellido'];
        $_SESSION['usuario_email']  = $usuario['email'];

        return Response::ok([
            'usuario' => [
                'id'     => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'email'  => $usuario['email'],
                'rol'    => $usuario['rol'],
                'foto_perfil' => $usuario['foto_perfil'] ?? null,
            ],
        ], 'Inicio de sesion con Google exitoso.');
    }
}
