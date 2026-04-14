<?php

use PHPUnit\Framework\TestCase;

class CancelacionProyectoServiceTest extends TestCase
{
    public function testCancelaProyectoCorrectamente(): void
    {
        $repo = $this->createMock(CancelacionProyectoRepositoryInterface::class);
        $validator = new CancelacionProyectoValidator();

        $repo->method('getProyecto')->willReturn([
            'id_aprobado' => 7,
            'proposal_id' => 22,
            'estado' => 'APROBADO',
            'fecha_creacion' => '2025-01-01',
            'fecha_ultimo_avance' => '2025-08-01'
        ]);

        $repo->method('existeCancelacion')->willReturn(false);
        $repo->method('getUltimoAvance')->willReturn('2025-08-01');

        $repo->expects($this->once())->method('beginTransaction');
        $repo->expects($this->once())->method('insertarAcuerdo');
        $repo->expects($this->once())->method('marcarProyectoComoCancelado')->with(7);
        $repo->expects($this->once())->method('actualizarProposalComoCancelada')->with(22);
        $repo->expects($this->once())->method('commit');
        $repo->expects($this->never())->method('rollback');

        $service = new CancelacionProyectoService($repo, $validator);

        $resultado = $service->cancelarProyecto(
            7,
            'Inactividad prolongada',
            'Sin avances registrados',
            3,
            new DateTime('2026-04-07')
        );

        $this->assertTrue($resultado['ok']);
        $this->assertEmpty($resultado['errores']);
    }

    public function testNoCancelaSiYaExisteAcuerdo(): void
    {
        $repo = $this->createMock(CancelacionProyectoRepositoryInterface::class);
        $validator = new CancelacionProyectoValidator();

        $repo->method('getProyecto')->willReturn([
            'id_aprobado' => 7,
            'proposal_id' => 22,
            'estado' => 'APROBADO'
        ]);

        $repo->method('existeCancelacion')->willReturn(true);

        $repo->expects($this->never())->method('beginTransaction');

        $service = new CancelacionProyectoService($repo, $validator);

        $resultado = $service->cancelarProyecto(
            7,
            'Inactividad prolongada',
            '',
            3,
            new DateTime('2026-04-07')
        );

        $this->assertFalse($resultado['ok']);
        $this->assertContains('Ya existe un acuerdo de cancelación para este proyecto.', $resultado['errores']);
    }

    public function testNoCancelaSiNoHanPasadoSeisMeses(): void
    {
        $repo = $this->createMock(CancelacionProyectoRepositoryInterface::class);
        $validator = new CancelacionProyectoValidator();

        $repo->method('getProyecto')->willReturn([
            'id_aprobado' => 7,
            'proposal_id' => null,
            'estado' => 'APROBADO'
        ]);

        $repo->method('existeCancelacion')->willReturn(false);
        $repo->method('getUltimoAvance')->willReturn('2026-02-15');

        $repo->expects($this->never())->method('beginTransaction');

        $service = new CancelacionProyectoService($repo, $validator);

        $resultado = $service->cancelarProyecto(
            7,
            'Inactividad prolongada',
            '',
            3,
            new DateTime('2026-04-07')
        );

        $this->assertFalse($resultado['ok']);
        $this->assertContains(
            'No se puede cancelar: el proyecto registra avances en los últimos 6 meses (Art. 73 RGPEA).',
            $resultado['errores']
        );
    }

    public function testHaceRollbackSiFallaInsercion(): void
    {
        $repo = $this->createMock(CancelacionProyectoRepositoryInterface::class);
        $validator = new CancelacionProyectoValidator();

        $repo->method('getProyecto')->willReturn([
            'id_aprobado' => 7,
            'proposal_id' => null,
            'estado' => 'APROBADO'
        ]);

        $repo->method('existeCancelacion')->willReturn(false);
        $repo->method('getUltimoAvance')->willReturn('2025-08-01');

        $repo->expects($this->once())->method('beginTransaction');
        $repo->expects($this->once())
            ->method('insertarAcuerdo')
            ->willThrowException(new Exception('Fallo al insertar acuerdo'));
        $repo->expects($this->once())->method('rollback');
        $repo->expects($this->never())->method('commit');

        $service = new CancelacionProyectoService($repo, $validator);

        $resultado = $service->cancelarProyecto(
            7,
            'Inactividad prolongada',
            '',
            3,
            new DateTime('2026-04-07')
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Error al cancelar: Fallo al insertar acuerdo', $resultado['errores'][0]);
    }
}