<?php
declare(strict_types=1);

final class Validacion
{
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
