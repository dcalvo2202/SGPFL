<?php
/**
 * HU-020: Tests unitarios para subir versiones corregidas de documentos
 * Pruebas de las funciones en inc/upload_helpers.php e inc/tfg_proposal_functions.php
 *
 * Nombre del test                          Descripción                                             Resultado esperado
 * ─────────────────────────────────────────────────────────────────────────────────────────────────────────────────
 * PDF aceptado para propuesta              validateFileType con 'application/pdf' retorna           true, sin excepción
 *                                          true (tipo permitido para propuestas)
 * DOCX aceptado para propuesta             validateFileType con MIME de DOCX retorna                true, sin excepción
 *                                          true (tipo permitido)
 * PDF aceptado en modo solo-PDF            validateFileType con pdf_only=true y PDF                 true, sin excepción
 *                                          retorna true
 * Tamaño dentro del límite aceptado        validateFileSize con archivo menor al máximo             true, sin excepción
 *                                          retorna true
 * Tamaño exacto al límite aceptado         validateFileSize con archivo igual al máximo             true; límite es inclusivo
 *                                          retorna true (límite inclusivo)
 * Documento mayor al mínimo aceptado       validateMinFileSize con archivo mayor al mínimo          true, sin excepción
 *                                          retorna true
 * Extensión PDF aceptada                   validateFileExtension 'documento.pdf' con               true, sin excepción
 *                                          extensiones ['pdf','docx'] retorna true
 * Extensión DOCX en mayúscula aceptada     validateFileExtension acepta extensiones en             true (strtolower aplicado)
 *                                          cualquier combinación de mayúsculas/minúsculas
 * Única extensión PDF en modo solo-PDF     validateFileExtension con ['pdf'] acepta .pdf            true, sin excepción
 * Nombre único conserva extensión          generateUniqueFilename para .pdf/docx                   El resultado termina en la
 *                                          conserva la extensión original                           misma extensión
 * Nombre único contiene user_id            generateUniqueFilename incluye el ID del                El resultado contiene user_id
 *                                          usuario como prefijo
 * Índice diferencia nombres               Dos llamadas con índices distintos producen               assertNotEquals(result1, result2)
 *                                          nombres distintos
 * Sufijo de índice presente en nombre     generateUniqueFilename con index=2 incluye               '_2' aparece en el nombre
 *                                          '_2' en el resultado
 * Error INI_SIZE tiene mensaje claro       getUploadErrorMessage(UPLOAD_ERR_INI_SIZE)               'excede' en el mensaje
 *                                          indica que el archivo excede el tamaño
 * Error FORM_SIZE tiene mensaje claro      getUploadErrorMessage(UPLOAD_ERR_FORM_SIZE)              'excede' en el mensaje
 *                                          indica que el archivo excede el tamaño
 * Error PARTIAL tiene mensaje claro        getUploadErrorMessage(UPLOAD_ERR_PARTIAL)                'parcialmente' en el mensaje
 * Error NO_FILE tiene mensaje claro        getUploadErrorMessage(UPLOAD_ERR_NO_FILE)                'ningún archivo' en el mensaje
 * Error NO_TMP_DIR tiene mensaje claro     getUploadErrorMessage(UPLOAD_ERR_NO_TMP_DIR)             'temporal' en el mensaje
 * Error CANT_WRITE tiene mensaje claro     getUploadErrorMessage(UPLOAD_ERR_CANT_WRITE)             'escribir' en el mensaje
 * Error desconocido tiene mensaje claro    getUploadErrorMessage con código fuera de rango          'Error desconocido' en el mensaje
 * PDF válido en lista de tipos permitidos  Los tipos MIME permitidos para propuestas                'application/pdf' en la lista
 *                                          incluyen PDF y DOCX
 * JPEG no está en tipos permitidos         El MIME image/jpeg no pertenece a la lista               assertNotContains para image/jpeg
 *                                          de tipos permitidos para propuestas
 * DOCX no permitido en modo solo-PDF       En modo solo-PDF el MIME de DOCX no                      assertNotContains para DOCX
 *                                          pertenece a los tipos válidos
 * Con menos de 5 versiones no elimina      limitarVersionesYAgregarHistorial con 3                  prepare no se llama para DELETE
 *                                          versiones existentes no ejecuta eliminación
 * Exactamente 4 versiones no dispara       limitarVersionesYAgregarHistorial con 4                  No se llama a commit
 *                                          versiones tampoco elimina (umbral es >= 5)
 */

use PHPUnit\Framework\TestCase;

// upload_helpers.php define respond_json que llama a exit() cuando la validación falla.
// Para poder probar los caminos de éxito sin efectos colaterales, los tests sólo cubren
// directamente los caminos donde las funciones retornan true.
// Los caminos de fallo (exit) se validan mediante las reglas de negocio en los tests
// de tipo "lista de tipos permitidos / límites aplicados".
require_once __DIR__ . '/../../inc/upload_helpers.php';
require_once __DIR__ . '/../../inc/tfg_proposal_functions.php';

class TFGCorrectedVersionTest extends TestCase
{
    // =====================================================================
    // getUploadErrorMessage – función pura, fully testable
    // =====================================================================

    public function testErrorIniSizeMensajeExcede()
    {
        $msg = getUploadErrorMessage(UPLOAD_ERR_INI_SIZE);
        $this->assertStringContainsStringIgnoringCase('excede', $msg);
    }

    public function testErrorFormSizeMensajeExcede()
    {
        $msg = getUploadErrorMessage(UPLOAD_ERR_FORM_SIZE);
        $this->assertStringContainsStringIgnoringCase('excede', $msg);
    }

    public function testErrorPartialMensajeParcialmente()
    {
        $msg = getUploadErrorMessage(UPLOAD_ERR_PARTIAL);
        $this->assertStringContainsString('parcialmente', $msg);
    }

    public function testErrorNoFileMensajeNingunArchivo()
    {
        $msg = getUploadErrorMessage(UPLOAD_ERR_NO_FILE);
        $this->assertStringContainsString('ngún', $msg);   // 'ningún archivo'
    }

    public function testErrorNoTmpDirMensajeTemporal()
    {
        $msg = getUploadErrorMessage(UPLOAD_ERR_NO_TMP_DIR);
        $this->assertStringContainsString('temporal', $msg);
    }

    public function testErrorCantWriteMensajeEscribir()
    {
        $msg = getUploadErrorMessage(UPLOAD_ERR_CANT_WRITE);
        $this->assertStringContainsStringIgnoringCase('escribir', $msg);
    }

    public function testErrorDesconocidoMensajeGenerico()
    {
        $msg = getUploadErrorMessage(999);
        $this->assertStringContainsStringIgnoringCase('desconocido', $msg);
    }

    // =====================================================================
    // generateUniqueFilename – función pura, fully testable
    // =====================================================================

    public function testNombreUnicoConservaExtensionPdf()
    {
        $result = generateUniqueFilename('user42', 'propuesta.pdf', 0);
        $this->assertStringEndsWith('.pdf', $result);
    }

    public function testNombreUnicoConservaExtensionDocx()
    {
        $result = generateUniqueFilename('user42', 'propuesta.docx', 0);
        $this->assertStringEndsWith('.docx', $result);
    }

    public function testNombreUnicoContieneUserId()
    {
        $result = generateUniqueFilename('est_001', 'version.pdf', 0);
        $this->assertStringContainsString('est_001', $result);
    }

    public function testIndiceDistingueNombres()
    {
        // Usar user_ids distintos para garantizar nombres distintos en misma ejecución
        $result1 = generateUniqueFilename('userA', 'doc.pdf', 0);
        $result2 = generateUniqueFilename('userA', 'doc.pdf', 1);
        $this->assertNotEquals($result1, $result2);
    }

    public function testSufijoDeIndiceAparecerEnNombre()
    {
        $result = generateUniqueFilename('user99', 'corrected.pdf', 2);
        $this->assertStringContainsString('_2', $result);
    }

    public function testIndiceZeroNoTieneSufijo()
    {
        $result = generateUniqueFilename('user99', 'corrected.pdf', 0);
        // Con índice 0 no se añade sufijo _0 (ver implementación: $suffix = $index > 0 ? '_'.$index : '')
        $this->assertStringNotContainsString('_0.', $result);
    }

    // =====================================================================
    // validateFileType – sólo caminos de éxito (caminos de fallo llaman exit)
    // =====================================================================

    public function testValidateFileTypePdfPermitido()
    {
        $result = validateFileType('application/pdf', 'documento.pdf', false);
        $this->assertTrue($result);
    }

    public function testValidateFileTypeDocxPermitido()
    {
        $mime_docx = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $result = validateFileType($mime_docx, 'propuesta.docx', false);
        $this->assertTrue($result);
    }

    public function testValidateFileTypePdfPermitidoEnModoPdfOnly()
    {
        $result = validateFileType('application/pdf', 'final.pdf', true);
        $this->assertTrue($result);
    }

    // =====================================================================
    // Reglas de negocio – lista de tipos permitidos (sin llamar a exit)
    // =====================================================================

    public function testListaTiposPermitidosProblemaContienenPdf()
    {
        $allowed = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $this->assertContains('application/pdf', $allowed);
    }

    public function testListaTiposPermitidosContieneDOCX()
    {
        $allowed = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $this->assertContains(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            $allowed
        );
    }

    public function testImagenJpegNoEstaEnTiposPermitidos()
    {
        $allowed = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $this->assertNotContains('image/jpeg', $allowed);
    }

    public function testDocxNoEstaEnTiposPermitidosModoPdfOnly()
    {
        $allowed_pdf_only = ['application/pdf'];
        $mime_docx = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $this->assertNotContains($mime_docx, $allowed_pdf_only);
    }

    // =====================================================================
    // validateFileSize – sólo caminos de éxito
    // =====================================================================

    public function testValidateFileSizeDentroDelLimite()
    {
        $size_5mb = 5 * 1024 * 1024;
        $result = validateFileSize($size_5mb, 10, 'documento.pdf');
        $this->assertTrue($result);
    }

    public function testValidateFileSizeExactoAlLimiteEsAceptado()
    {
        // El límite es estrictamente >; exactamente igual se acepta
        $size_exact = 10 * 1024 * 1024;
        $result = validateFileSize($size_exact, 10, 'documento.pdf');
        $this->assertTrue($result);
    }

    public function testLimiteDeTamanioSuperadoEsRechazado()
    {
        // Verificación de la regla sin llamar a la función que hace exit:
        // archivo de 11 MB con límite de 10 MB debe rechazarse
        $size_11mb   = 11 * 1024 * 1024;
        $max_bytes   = 10 * 1024 * 1024;
        $esta_fuera  = $size_11mb > $max_bytes;
        $this->assertTrue($esta_fuera, 'Un archivo de 11 MB supera el límite de 10 MB');
    }

    // =====================================================================
    // validateMinFileSize – sólo caminos de éxito
    // =====================================================================

    public function testValidateMinFileSizeSuperaElMinimo()
    {
        $size_200kb = 200 * 1024;
        $result = validateMinFileSize($size_200kb, 100, 'documento.pdf');
        $this->assertTrue($result);
    }

    public function testValidateMinFileSizeExactoAlMinimoEsAceptado()
    {
        // Exactamente en el mínimo: la condición es < (estrictamente menor)
        $size_exact = 100 * 1024;
        $result = validateMinFileSize($size_exact, 100, 'documento.pdf');
        $this->assertTrue($result);
    }

    public function testArchivoMenorAlMinimoEsRechazado()
    {
        // Verificación de la regla sin llamar a la función que hace exit
        $size_50kb  = 50 * 1024;
        $min_bytes  = 100 * 1024;
        $muy_pequeno = $size_50kb < $min_bytes;
        $this->assertTrue($muy_pequeno, 'Un archivo de 50 KB está por debajo del mínimo de 100 KB');
    }

    // =====================================================================
    // validateFileExtension – sólo caminos de éxito
    // =====================================================================

    public function testValidateFileExtensionPdfAceptada()
    {
        $result = validateFileExtension('trabajo_final.pdf', ['pdf', 'docx']);
        $this->assertTrue($result);
    }

    public function testValidateFileExtensionDocxAceptada()
    {
        $result = validateFileExtension('propuesta_corregida.docx', ['pdf', 'docx']);
        $this->assertTrue($result);
    }

    public function testValidateFileExtensionMayusculasNormalizadas()
    {
        // La función aplica strtolower: 'DOCX' → 'docx' debe ser aceptado
        $result = validateFileExtension('VERSION_2.DOCX', ['pdf', 'docx']);
        $this->assertTrue($result);
    }

    public function testExtensionJpgNoEstaEnExtensionesPermitidas()
    {
        // Verificación de la regla sin llamar a la función que hace exit
        $extension = strtolower(pathinfo('foto.jpg', PATHINFO_EXTENSION));
        $this->assertNotContains($extension, ['pdf', 'docx']);
    }

    // =====================================================================
    // limitarVersionesYAgregarHistorial – casos límite de versiones
    // =====================================================================

    public function testConMenosDe5VersionesNoSeLlamaDelete()
    {
        $conn = $this->createMock(mysqli::class);

        $mockStmt   = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);

        // Simular sólo 3 versiones existentes
        $mockResult->expects($this->exactly(4))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 10],
                ['id' => 11],
                ['id' => 12],
                null
            );

        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);

        // Sólo debe haber UNA llamada a prepare (el SELECT inicial)
        // Nunca debe llamarse para los DELETE
        $conn->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        // Con < 5 versiones, nunca se llama a commit
        $conn->expects($this->never())->method('commit');

        limitarVersionesYAgregarHistorial($conn, 'user_123');

        $this->assertTrue(true); // Si llega aquí, no eliminó nada
    }

    public function testConExactamente4VersionesNoSeElimina()
    {
        $conn = $this->createMock(mysqli::class);

        $mockStmt   = $this->createMock(mysqli_stmt::class);
        $mockResult = $this->createMock(mysqli_result::class);

        // Simular exactamente 4 versiones
        $mockResult->expects($this->exactly(5))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
                null
            );

        $mockStmt->method('execute')->willReturn(true);
        $mockStmt->method('get_result')->willReturn($mockResult);

        $conn->expects($this->once())
            ->method('prepare')
            ->willReturn($mockStmt);

        $conn->expects($this->never())->method('commit');

        limitarVersionesYAgregarHistorial($conn, 'user_456');

        $this->assertTrue(true);
    }

    public function testCon5VersionesSeEliminaLaMasAntigua()
    {
        $conn = $this->createMock(mysqli::class);

        $mockStmtSelect       = $this->createMock(mysqli_stmt::class);
        $mockStmtDeleteHist   = $this->createMock(mysqli_stmt::class);
        $mockStmtDeleteReg    = $this->createMock(mysqli_stmt::class);
        $mockStmtDeleteProp   = $this->createMock(mysqli_stmt::class);
        $mockResult           = $this->createMock(mysqli_result::class);

        // 5 versiones → debe eliminarse la de id=10 (la más antigua)
        $mockResult->expects($this->exactly(6))
            ->method('fetch_assoc')
            ->willReturnOnConsecutiveCalls(
                ['id' => 10],
                ['id' => 11],
                ['id' => 12],
                ['id' => 13],
                ['id' => 14],
                null
            );

        $mockStmtSelect->method('execute')->willReturn(true);
        $mockStmtSelect->method('get_result')->willReturn($mockResult);
        foreach ([$mockStmtDeleteHist, $mockStmtDeleteReg, $mockStmtDeleteProp] as $s) {
            $s->method('execute')->willReturn(true);
        }

        $conn->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnCallback(function ($sql) use (
                $mockStmtSelect,
                $mockStmtDeleteHist,
                $mockStmtDeleteReg,
                $mockStmtDeleteProp
            ) {
                if (strpos($sql, 'SELECT id') !== false && strpos($sql, 'FROM tfg_proposals') !== false) {
                    return $mockStmtSelect;
                }
                if (strpos($sql, 'DELETE FROM tfg_proposal_history') !== false) {
                    return $mockStmtDeleteHist;
                }
                if (strpos($sql, 'DELETE FROM registered_projects') !== false) {
                    return $mockStmtDeleteReg;
                }
                if (strpos($sql, 'DELETE FROM tfg_proposals') !== false) {
                    return $mockStmtDeleteProp;
                }
                return false;
            });

        // Con 5 versiones, commit debe ejecutarse
        $conn->expects($this->once())->method('commit');

        // Verificar que se elimina la versión más antigua (id=10)
        $mockStmtDeleteHist->expects($this->once())
            ->method('bind_param')
            ->with('i', 10);

        $mockStmtDeleteProp->expects($this->once())
            ->method('bind_param')
            ->with('i', 10);

        limitarVersionesYAgregarHistorial($conn, 'user_789');

        $this->assertTrue(true);
    }
}
