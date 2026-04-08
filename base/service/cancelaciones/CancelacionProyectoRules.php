<?php

class CancelacionProyectoRules
{
    public static function validarEntrada(int $proyectoId, string $motivo, $usuarioId): array
    {
        $errores = [];

        if ($proyectoId <= 0) {
            $errores[] = 'ID de proyecto no válido.';
        }

        $motivo = trim($motivo);

        if ($motivo === '' || mb_strlen($motivo) > 500) {
            $errores[] = 'Motivo es requerido (máx. 500 caracteres).';
        }

        if (!$usuarioId) {
            $errores[] = 'Sesión inválida.';
        }

        return $errores;
    }

    public static function validarSeisMeses(string $fechaUltimoAvance, ?DateTime $hoy = null): array
    {
        $errores = [];
        $hoy = $hoy ?? new DateTime('now');
        $base = new DateTime($fechaUltimoAvance);
        $limite = (clone $base)->modify('+6 months');

        if ($hoy < $limite) {
            $errores[] = 'No se puede cancelar: el proyecto registra avances en los últimos 6 meses (Art. 73 RGPEA).';
        }

        return $errores;
    }
}

?>