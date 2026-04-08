<?php

class CancelacionProyectoValidator
{
    public function validarEntrada(int $proyectoId, string $motivo, $usuarioId): array
    {
        $errores = [];

        if ($proyectoId <= 0) {
            $errores[] = 'ID de proyecto no válido.';
        }

        $motivo = trim($motivo);
        if ($motivo === '') {
            $errores[] = 'Motivo es requerido (máx. 500 caracteres).';
        } elseif (mb_strlen($motivo) > 500) {
            $errores[] = 'Motivo es requerido (máx. 500 caracteres).';
        }

        if (empty($usuarioId)) {
            $errores[] = 'Sesión inválida.';
        }

        return $errores;
    }

    public function validarProyectoCancelable(array $proyecto): array
    {
        $errores = [];

        if (!$proyecto) {
            $errores[] = 'Proyecto no encontrado.';
            return $errores;
        }

        if (strtoupper($proyecto['estado'] ?? '') === 'CANCELADO') {
            $errores[] = 'El proyecto ya está CANCELADO.';
        }

        return $errores;
    }

    public function validarSeisMeses(string $fechaUltimoAvance, ?DateTime $hoy = null): array
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