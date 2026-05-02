<?php

declare(strict_types=1);

namespace Tests\functional;

use Tests\Support\FunctionalTester;

final class HU016AcuerdoDefensaCest
{
    private const CTFG_USER = '110600492';
    private const CTFG_PASS = 'secret123';

    private ?int $proyectoId = null;
    private ?int $proposalId = null;

    public function _before(FunctionalTester $I): void
    {
        $this->loginComoCTFG($I);

        [$this->proyectoId, $this->proposalId] = $this->obtenerProyectoAprobado();
    }

    public function muestraFormularioParaUsuarioCTFG(FunctionalTester $I): void
    {
        $I->amOnPage('/mod/admin/users/tfg_upload_defense_agreement.php?id=' . $this->proyectoId);

        $I->seeResponseCodeIs(200);
        $I->see('Adjuntar acuerdo de defensa pública');
        $I->see('Registro del acuerdo de defensa');

        $I->seeElement('input[name="proyecto_id"]');
        $I->seeElement('input[name="proposal_id"]');
        $I->seeElement('input[name="codigo_acuerdo"]');
        $I->seeElement('input[name="fecha_aprobacion_documento_final"]');
        $I->seeElement('input[name="fecha_defensa"]');
        $I->seeElement('input[name="correo_destino"]');
        $I->seeElement('input[name="documento"]');

        $I->see('Guardar acuerdo');
    }

    public function rechazaArchivoQueNoEsPDF(FunctionalTester $I): void
    {
        $archivo = codecept_data_dir('archivo_no_valido.txt');
        file_put_contents($archivo, 'Esto no es un PDF.');

        $I->amOnPage('/mod/admin/users/tfg_upload_defense_agreement.php?id=' . $this->proyectoId);

        $I->attachFile('input[name="documento"]', 'archivo_no_valido.txt');

        $codigoReemplazo = 'ACUE-REP-' . date('His');

        $I->submitForm('#frmAcuerdo', [
            'codigo_acuerdo' => $codigoReemplazo,
            'fecha_aprobacion_documento_final' => date('Y-m-d'),
            'fecha_defensa' => date('Y-m-d', strtotime('+10 days')),
            'correo_destino' => 'malcolm.chaves.obando@est.una.ac.cr',
        ]);

        $I->seeResponseCodeIs(200);
        $this->verRespuestaContiene($I, 'Solo se permiten archivos PDF');
    }

    public function registraAcuerdoDefensaCorrectamente(FunctionalTester $I): void
    {
        $this->limpiarAcuerdoAnterior();

        $archivo = codecept_data_dir('acuerdo_hu016.pdf');
        $this->crearPdfMinimo($archivo);

        $codigo = 'ACUE-HU016-' . substr((string) time(), -6);

        $I->amOnPage('/mod/admin/users/tfg_upload_defense_agreement.php?id=' . $this->proyectoId);

        $I->attachFile('input[name="documento"]', 'acuerdo_hu016.pdf');

        $I->submitForm('#frmAcuerdo', [
            'codigo_acuerdo' => $codigo,
            'fecha_aprobacion_documento_final' => date('Y-m-d'),
            'fecha_defensa' => date('Y-m-d', strtotime('+40 days')),
            'correo_destino' => 'malcolm.chaves.obando@est.una.ac.cr',
        ]);

        $I->seeResponseCodeIs(200);
        $this->verRespuestaContiene($I, 'Acuerdo registrado');
        $this->verRespuestaContiene($I, 'notificación interna enviada');

        $this->verificarAcuerdoEnBaseDeDatos($codigo);
    }

    public function rechazaRegistroSinCodigoAcuerdo(FunctionalTester $I): void
    {
        $archivo = codecept_data_dir('acuerdo_hu016.pdf');
        $this->crearPdfMinimo($archivo);

        $I->amOnPage('/mod/admin/users/tfg_upload_defense_agreement.php?id=' . $this->proyectoId);

        $I->attachFile('input[name="documento"]', 'acuerdo_hu016.pdf');

        $I->submitForm('#frmAcuerdo', [
            'codigo_acuerdo' => '',
            'fecha_aprobacion_documento_final' => date('Y-m-d'),
            'fecha_defensa' => date('Y-m-d', strtotime('+40 days')),
            'correo_destino' => 'malcolm.chaves.obando@est.una.ac.cr',
        ]);

        $I->seeResponseCodeIs(200);
        $this->verRespuestaContiene($I, 'El código del acuerdo es obligatorio');
    }

    public function rechazaReemplazoCuandoFaltanQuinceDiasOMenos(FunctionalTester $I): void
    {
        $this->crearAcuerdoExistenteConDefensaCercana();

        $archivo = codecept_data_dir('acuerdo_hu016.pdf');
        $this->crearPdfMinimo($archivo);

        $I->amOnPage('/mod/admin/users/tfg_upload_defense_agreement.php?id=' . $this->proyectoId);

        $I->attachFile('input[name="documento"]', 'acuerdo_hu016.pdf');

        $I->submitForm('#frmAcuerdo', [
            'codigo_acuerdo' => 'UNA-CTFG-EI-ACUE-REEMPLAZO-' . time(),
            'fecha_aprobacion_documento_final' => date('Y-m-d'),
            'fecha_defensa' => date('Y-m-d', strtotime('+10 days')),
            'correo_destino' => 'malcolm.chaves.obando@est.una.ac.cr',
        ]);

        $I->seeResponseCodeIs(400);
        $this->verRespuestaContiene($I, 'Ya no se permite reemplazar el acuerdo');
    }

    private function loginComoCTFG(FunctionalTester $I): void
    {
        $I->amOnPage('/login.php');

        $I->fillField('user', self::CTFG_USER);
        $I->fillField('pass', self::CTFG_PASS);

        $I->click('#saveForm');
    }

    private function obtenerConexion(): \mysqli
    {
        include codecept_root_dir() . 'inc/db/bdcommon.inc';

        $conn = new \mysqli($db_host, $usuario, $clave, $db);

        if ($conn->connect_error) {
            throw new \RuntimeException('Error conectando a la base de datos: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');

        return $conn;
    }

    private function obtenerProyectoAprobado(): array
    {
        $conn = $this->obtenerConexion();

        $sql = "
            SELECT id_aprobado, proposal_id
            FROM proyecto_aprobado
            WHERE proposal_id IS NOT NULL
            ORDER BY id_aprobado DESC
            LIMIT 1
        ";

        $resultado = $conn->query($sql);

        if (!$resultado || $resultado->num_rows === 0) {
            $conn->close();

            throw new \RuntimeException(
                'No se encontró un proyecto aprobado con proposal_id para probar la HU-016.'
            );
        }

        $proyecto = $resultado->fetch_assoc();

        $conn->close();

        return [
            (int) $proyecto['id_aprobado'],
            (int) $proyecto['proposal_id'],
        ];
    }

    private function limpiarAcuerdoAnterior(): void
    {
        $conn = $this->obtenerConexion();

        $stmtBuscar = $conn->prepare("
            SELECT archivo_ruta
            FROM acuerdo_defensa_publica
            WHERE proyecto_id = ?
        ");

        $stmtBuscar->bind_param('i', $this->proyectoId);
        $stmtBuscar->execute();

        $resultado = $stmtBuscar->get_result();

        while ($row = $resultado->fetch_assoc()) {
            if (!empty($row['archivo_ruta'])) {
                $rutaArchivo = codecept_root_dir() . $row['archivo_ruta'];

                if (is_file($rutaArchivo)) {
                    @unlink($rutaArchivo);
                }
            }
        }

        $stmtBuscar->close();

        $stmtEliminar = $conn->prepare("
            DELETE FROM acuerdo_defensa_publica
            WHERE proyecto_id = ?
        ");

        $stmtEliminar->bind_param('i', $this->proyectoId);
        $stmtEliminar->execute();
        $stmtEliminar->close();

        $conn->close();
    }

    private function verificarAcuerdoEnBaseDeDatos(string $codigoEsperado): void
    {
        $conn = $this->obtenerConexion();

        $stmt = $conn->prepare("
            SELECT codigo_acuerdo, archivo_ruta, correo_destino, enviado_correo
            FROM acuerdo_defensa_publica
            WHERE proyecto_id = ?
              AND codigo_acuerdo = ?
            LIMIT 1
        ");

        $stmt->bind_param('is', $this->proyectoId, $codigoEsperado);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $acuerdo = $resultado->fetch_assoc();

        $stmt->close();

        if (!$acuerdo) {
            $codigoActual = $this->obtenerCodigoActualDelAcuerdo($conn);

            $conn->close();

            throw new \RuntimeException(
                "No se encontró el acuerdo con el código esperado.\n" .
                "Código esperado: {$codigoEsperado}\n" .
                "Código encontrado en BD: {$codigoActual}"
            );
        }

        $conn->close();

        if (empty($acuerdo['archivo_ruta'])) {
            throw new \RuntimeException('No se guardó la ruta del archivo PDF.');
        }

        $rutaArchivo = codecept_root_dir() . $acuerdo['archivo_ruta'];

        if (!is_file($rutaArchivo)) {
            throw new \RuntimeException('El archivo PDF no existe en la ruta registrada.');
        }
    }

    private function obtenerCodigoActualDelAcuerdo(\mysqli $conn): string
    {
        $stmt = $conn->prepare("
            SELECT codigo_acuerdo
            FROM acuerdo_defensa_publica
            WHERE proyecto_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");

        $stmt->bind_param('i', $this->proyectoId);
        $stmt->execute();

        $resultado = $stmt->get_result();
        $row = $resultado->fetch_assoc();

        $stmt->close();

        return $row['codigo_acuerdo'] ?? 'No existe acuerdo registrado para este proyecto';
    }

    private function crearAcuerdoExistenteConDefensaCercana(): void
    {
        $this->limpiarAcuerdoAnterior();

        $conn = $this->obtenerConexion();

        $codigo = 'ACUE-HU016-EXIST';
        $fechaAprobacion = date('Y-m-d');
        $fechaDefensa = date('Y-m-d', strtotime('+10 days'));
        $archivoNombre = 'acuerdo_hu016_existente.pdf';
        $archivoRuta = 'uploads/acuerdos_defensa/' . $archivoNombre;
        $mimeType = 'application/pdf';
        $fileSize = 1000;
        $correoDestino = 'malcolm.chaves.obando@est.una.ac.cr';
        $subidoPor = self::CTFG_USER;

        $uploadDir = codecept_root_dir() . 'uploads/acuerdos_defensa/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $this->crearPdfMinimo($uploadDir . $archivoNombre);

        $stmt = $conn->prepare("
            INSERT INTO acuerdo_defensa_publica
            (
                proyecto_id,
                proposal_id,
                codigo_acuerdo,
                fecha_aprobacion_documento_final,
                fecha_defensa,
                archivo_nombre,
                archivo_ruta,
                mime_type,
                file_size,
                correo_destino,
                subido_por,
                enviado_correo
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
        ");

        $stmt->bind_param(
            'iissssssiss',
            $this->proyectoId,
            $this->proposalId,
            $codigo,
            $fechaAprobacion,
            $fechaDefensa,
            $archivoNombre,
            $archivoRuta,
            $mimeType,
            $fileSize,
            $correoDestino,
            $subidoPor
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            $conn->close();

            throw new \RuntimeException('No se pudo crear el acuerdo existente: ' . $error);
        }

        $stmt->close();
        $conn->close();
    }

    private function verRespuestaContiene(FunctionalTester $I, string $textoEsperado): void
    {
        $respuesta = $I->grabPageSource();

        $textoBusqueda = $respuesta;

        $json = json_decode($respuesta, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $valoresJson = [];

            array_walk_recursive($json, function ($valor) use (&$valoresJson): void {
                if (is_scalar($valor)) {
                    $valoresJson[] = (string) $valor;
                }
            });

            $textoBusqueda .= "\n" . implode("\n", $valoresJson);
        }

        if (strpos($textoBusqueda, $textoEsperado) === false) {
            throw new \RuntimeException(
                "La respuesta no contiene el texto esperado: {$textoEsperado}\n\nRespuesta recibida:\n{$respuesta}"
            );
        }
    }

    private function crearPdfMinimo(string $ruta): void
    {
        $contenido = "%PDF-1.4\n";
        $contenido .= "1 0 obj\n";
        $contenido .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $contenido .= "endobj\n";
        $contenido .= "2 0 obj\n";
        $contenido .= "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n";
        $contenido .= "endobj\n";
        $contenido .= "3 0 obj\n";
        $contenido .= "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\n";
        $contenido .= "endobj\n";
        $contenido .= "trailer\n";
        $contenido .= "<< /Root 1 0 R >>\n";
        $contenido .= "%%EOF\n";

        file_put_contents($ruta, $contenido);
    }
}
?>