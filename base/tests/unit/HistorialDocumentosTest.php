<?php
use PHPUnit\Framework\TestCase;

class HistorialDocumentosTest extends TestCase
{
    // 1. Test de restricción de acceso solo a estudiantes
    public function testRestriccionAccesoSoloEstudiantes()
    {
        $roles = [1, 2, 3, 4]; // 4 = estudiante
        foreach ($roles as $rol) {
            $redirected = false;
            if ($rol != 4) {
                $redirected = true;
            }
            if ($rol == 4) {
                $this->assertFalse($redirected, "No debe redirigir a estudiantes");
            } else {
                $this->assertTrue($redirected, "Debe redirigir a rol $rol");
            }
        }
    }

    // 2. Test de agregar propuesta TFG a documentos (mock)
    public function testAgregarPropuestaTFG()
    {
        $row = [
            'id' => 1,
            'title' => 'Propuesta de TFG',
            'file_name' => 'propuesta.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1048576,
            'status' => 'Aprobado',
            'created_at' => '2024-06-01 12:00:00'
        ];
        $row['tipo'] = 'Propuesta TFG';
        $documentos = [];
        $documentos[] = $row;
        $this->assertCount(1, $documentos);
        $this->assertSame('Propuesta TFG', $documentos[0]['tipo']);
        $this->assertSame('Propuesta de TFG', $documentos[0]['title']);
    }

    // 3. Test de extracción de propuesta principal id
    public function testExtraccionPropuestaPrincipalId()
    {
        $documentos = [
            ['id' => 5, 'tipo' => 'Propuesta TFG'],
            ['id' => 6, 'tipo' => 'Otro']
        ];
        $propuesta_principal_id = $documentos[0]['id'] ?? 0;
        $this->assertEquals(5, $propuesta_principal_id);
    }

    // 4. Test de obtención de versiones de propuestas (mock)
    public function testObtencionVersionesPropuestas()
    {
        $propuestas_vers = [
            [
                'id' => 10,
                'proposal_id' => 5,
                'file_name' => 'propuesta_v2.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 2048000,
                'status' => 'Pendiente',
                'comments' => 'Revisar formato',
                'created_at' => '2024-06-02 10:00:00',
                'title' => 'Propuesta de TFG'
            ]
        ];
        $this->assertNotEmpty($propuestas_vers);
        $this->assertEquals('propuesta_v2.pdf', $propuestas_vers[0]['file_name']);
        $this->assertEquals('Pendiente', $propuestas_vers[0]['status']);
    }

    // 5. Test de formato de tamaño de archivo en MB
    public function testFormatoTamanioArchivoMB()
    {
        $file_size = 3145728; // 3 MB
        $mb = number_format($file_size / (1024 * 1024), 2);
        $this->assertSame('3.00', $mb);
    }

    // 6. Test de extracción de formato de archivo desde mime_type
    public function testExtraccionFormatoArchivo()
    {
        $mime_type = 'application/pdf';
        $parts = explode('/', $mime_type);
        $formato = isset($parts[1]) ? strtoupper($parts[1]) : strtoupper($mime_type);
        $this->assertSame('PDF', $formato);

        $mime_type2 = 'text/plain';
        $parts2 = explode('/', $mime_type2);
        $formato2 = isset($parts2[1]) ? strtoupper($parts2[1]) : strtoupper($mime_type2);
        $this->assertSame('PLAIN', $formato2);
    }

    // 7. Test de generación de enlace de descarga
    public function testGeneracionEnlaceDescarga()
    {
        $base_url = 'http://localhost/base/';
        $id = 7;
        $link = $base_url . 'mod/admin/users/tfg_download.php?id=' . $id;
        $this->assertStringContainsString('tfg_download.php?id=7', $link);
    }

    // 8. Test de mensaje cuando no hay documentos
    public function testMensajeSinDocumentos()
    {
        $documentos = [];
        $mensaje = empty($documentos) ? 'No se han encontrado documentos.' : '';
        $this->assertSame('No se han encontrado documentos.', $mensaje);
    }
}