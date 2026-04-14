<?php

class CancelacionProyectoService
{
    private CancelacionProyectoRepositoryInterface $repository;
    private CancelacionProyectoValidator $validator;

    public function __construct(
        CancelacionProyectoRepositoryInterface $repository,
        CancelacionProyectoValidator $validator
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
    }

    public function cancelarProyecto(
        int $proyectoId,
        string $motivo,
        string $observaciones,
        int $usuarioId,
        ?DateTime $hoy = null
    ): array {
        $errores = $this->validator->validarEntrada($proyectoId, $motivo, $usuarioId);
        if (!empty($errores)) {
            return ['ok' => false, 'errores' => $errores];
        }

        $proyecto = $this->repository->getProyecto($proyectoId);

        $errores = $this->validator->validarProyectoCancelable($proyecto ?? []);
        if (!empty($errores)) {
            return ['ok' => false, 'errores' => $errores];
        }

        if ($this->repository->existeCancelacion($proyectoId)) {
            return ['ok' => false, 'errores' => ['Ya existe un acuerdo de cancelación para este proyecto.']];
        }

        $fechaUltimoAvance = $this->repository->getUltimoAvance($proyectoId, $proyecto);
        if ($fechaUltimoAvance === null) {
            return ['ok' => false, 'errores' => ['No hay fechas para validar 6 meses sin avances.']];
        }

        $errores = $this->validator->validarSeisMeses($fechaUltimoAvance, $hoy);
        if (!empty($errores)) {
            return ['ok' => false, 'errores' => $errores];
        }

        try {
            $this->repository->beginTransaction();

            $this->repository->insertarAcuerdo(
                $proyectoId,
                $usuarioId,
                trim($motivo),
                trim($observaciones),
                $fechaUltimoAvance
            );

            $this->repository->marcarProyectoComoCancelado($proyectoId);

            if (!empty($proyecto['proposal_id'])) {
                $this->repository->actualizarProposalComoCancelada((int)$proyecto['proposal_id']);
            }

            $this->repository->commit();

            return ['ok' => true, 'errores' => []];
        } catch (Throwable $e) {
            $this->repository->rollback();

            return [
                'ok' => false,
                'errores' => ['Error al cancelar: ' . $e->getMessage()]
            ];
        }
    }
}