<?php

interface CancelacionProyectoRepositoryInterface
{
    public function getProyecto(int $proyectoId): ?array;
    public function existeCancelacion(int $proyectoId): bool;
    public function getUltimoAvance(int $proyectoId, array $proyecto): ?string;

    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;

    public function insertarAcuerdo(
        int $proyectoId,
        int $usuarioId,
        string $motivo,
        string $observaciones,
        string $fechaUltimoAvance
    ): void;

    public function marcarProyectoComoCancelado(int $proyectoId): void;
    public function actualizarProposalComoCancelada(int $proposalId): void;
}

?>