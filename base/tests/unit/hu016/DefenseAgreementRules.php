<?php

declare(strict_types=1);

namespace Tests\Unit\Hu016;

final class DefenseAgreementRules
{
    public const CTFG_ROLE = 3;
    public const MAX_FILE_SIZE = 10 * 1024 * 1024;

    public function canAccess(?string $userId, int $role): bool
    {
        return !empty($userId) && $role === self::CTFG_ROLE;
    }

    public function validateProjectData(int $proyectoId, int $proposalId): array
    {
        if ($proyectoId <= 0 || $proposalId <= 0) {
            return [
                'valid' => false,
                'message' => 'Proyecto o propuesta inválidos.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'OK',
        ];
    }

    public function validateDates(string $fechaAprobacion, string $fechaDefensa): array
    {
        if (!$this->isValidDateFormat($fechaAprobacion) || !$this->isValidDateFormat($fechaDefensa)) {
            return [
                'valid' => false,
                'message' => 'Fechas inválidas.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'OK',
        ];
    }

    public function validateRequiredFields(string $codigoAcuerdo, string $correoDestino): array
    {
        if (trim($codigoAcuerdo) === '') {
            return [
                'valid' => false,
                'message' => 'El código del acuerdo es obligatorio.',
            ];
        }

        if (trim($correoDestino) === '') {
            return [
                'valid' => false,
                'message' => 'El correo destino es obligatorio.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'OK',
        ];
    }

    public function validateUploadedFile(
        bool $fileWasUploaded,
        int $uploadError,
        int $fileSize,
        string $mimeType
    ): array {
        if (!$fileWasUploaded || $uploadError !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'message' => 'Debe adjuntar el PDF del acuerdo.',
            ];
        }

        if ($fileSize > self::MAX_FILE_SIZE) {
            return [
                'valid' => false,
                'message' => 'El archivo excede el tamaño máximo de 10 MB.',
            ];
        }

        if ($mimeType !== 'application/pdf') {
            return [
                'valid' => false,
                'message' => 'Solo se permiten archivos PDF.',
            ];
        }

        return [
            'valid' => true,
            'message' => 'OK',
        ];
    }

    public function canReplaceAgreement(
        bool $agreementExists,
        string $fechaDefensa,
        string $today
    ): bool {
        if (!$agreementExists) {
            return true;
        }

        $defenseDate = new \DateTimeImmutable($fechaDefensa);
        $currentDate = new \DateTimeImmutable($today);

        $limitDate = $defenseDate->modify('-15 days');

        return $currentDate < $limitDate;
    }

    private function isValidDateFormat(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }

        $parts = explode('-', $date);

        return checkdate(
            (int) $parts[1],
            (int) $parts[2],
            (int) $parts[0]
        );
    }
}
?>