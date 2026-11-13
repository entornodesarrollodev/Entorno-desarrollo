<?php
declare(strict_types=1);

final class Validacion
{
    // Este bloque valida nombres personales con letras y separadores habituales.
    public static function nombrePersonaValido(string $nombre, int $maximo = 100): bool
    {
        $nombre = trim($nombre);

        return $nombre !== ''
            && mb_strlen($nombre) <= $maximo
            && preg_match('/^[\p{L}\p{M}][\p{L}\p{M} .\x{27}\x{2019}-]*$/uD', $nombre) === 1;
    }

    // Este bloque valida DUI salvadoreño de nueve dígitos con guion opcional.
    public static function duiValido(string $dui): bool
    {
        return $dui === '' || preg_match('/^\d{8}-?\d$/D', $dui) === 1;
    }

    // Este bloque limita teléfonos a ocho dígitos locales, con prefijo opcional 503.
    public static function telefonoValido(string $telefono): bool
    {
        if ($telefono === '') {
            return true;
        }

        return strlen($telefono) <= 20
            && preg_match('/^(?:\+?503[\s-]?)?\d{4}[\s-]?\d{4}$/D', $telefono) === 1;
    }

    // Este bloque limita la carrera del registro a las opciones del selector público.
    public static function carreraRegistroValida(string $carrera): bool
    {
        return in_array($carrera, ['sistemas', 'software', 'mantenimiento', 'otro'], true);
    }

    // Este bloque valida horas de estudiante con precisión de centésimas.
    public static function horasRegistroValida($horas): bool
    {
        return (is_string($horas) || is_int($horas) || is_float($horas))
            && preg_match('/^\d+(?:\.\d{1,2})?$/D', (string) $horas) === 1
            && (float) $horas > 0
            && (float) $horas <= 8;
    }

    // Este bloque limita horas estimadas a intervalos de media hora.
    public static function horasEstimadasValidas($horas): bool
    {
        if (!is_string($horas) && !is_int($horas) && !is_float($horas)) {
            return false;
        }

        $valor = (float) $horas;

        return is_numeric($horas)
            && $valor >= 0.5
            && $valor <= 24
            && abs($valor * 2 - round($valor * 2)) < 0.000001;
    }

    // Este bloque exige al menos dos componentes de nombre para crear un expediente.
    public static function nombreCompletoValido(string $nombre): bool
    {
        $partes = preg_split('/\s+/u', trim($nombre), -1, PREG_SPLIT_NO_EMPTY);

        return $partes !== false
            && count($partes) >= 2
            && self::nombrePersonaValido($nombre, 150);
    }

    // Este bloque valida que el correo pertenezca al dominio institucional permitido.
    public static function correoItcaValido(string $correo): bool
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false
            && preg_match('/@itca\.edu\.sv$/iD', $correo) === 1;
    }

    // Este bloque detecta emojis y texto mal codificado en los campos recibidos.
    public static function tieneEmoji(string $texto): bool
    {
        $resultado = preg_match(
            '/(?:\p{Extended_Pictographic}|\p{Regional_Indicator}|\p{Emoji_Modifier}|\x{20E3})/u',
            $texto
        );

        // Tambien bloqueamos texto con codificacion rota para evitar saltarnos la revision.
        return $resultado === false || $resultado === 1;
    }

    // Este bloque valida fechas con formato exacto para evitar normalizaciones ambiguas.
    public static function fechaValida(string $valor, string $formato = '!Y-m-d'): bool
    {
        $fecha = DateTimeImmutable::createFromFormat($formato, $valor);
        $errores = DateTimeImmutable::getLastErrors();

        return $fecha !== false
            && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $fecha->format(ltrim($formato, '!')) === $valor;
    }

    // Este bloque comprueba los formatos de fecha y hora usados por el navegador.
    public static function fechaHoraLocalValida(string $valor): bool
    {
        return self::fechaValida($valor, '!Y-m-d\TH:i')
            || self::fechaValida($valor, '!Y-m-d\TH:i:s');
    }

    // Este bloque convierte fecha y hora local válida al formato DATETIME de SQL.
    public static function normalizarFechaHoraLocal(string $valor): string
    {
        $formato = strlen($valor) === 16 ? '!Y-m-d\TH:i' : '!Y-m-d\TH:i:s';
        $fecha = DateTimeImmutable::createFromFormat($formato, $valor);
        $errores = DateTimeImmutable::getLastErrors();
        if ($fecha === false || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
            || $fecha->format(ltrim($formato, '!')) !== $valor) {
            throw new InvalidArgumentException('La fecha y hora no es válida.');
        }

        return $fecha->format('Y-m-d H:i:s');
    }
}
