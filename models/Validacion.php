<?php
declare(strict_types=1);

final class Validacion
{
    public static function correoItcaValido(string $correo): bool
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false
            && preg_match('/@itca\.edu\.sv$/iD', $correo) === 1;
    }

    public static function tieneEmoji(string $texto): bool
    {
        $resultado = preg_match(
            '/(?:\p{Extended_Pictographic}|\p{Regional_Indicator}|\p{Emoji_Modifier}|\x{20E3})/u',
            $texto
        );

        // Tambien bloqueamos texto con codificacion rota para evitar saltarnos la revision.
        return $resultado === false || $resultado === 1;
    }
}
