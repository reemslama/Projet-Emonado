<?php

namespace App\Service;

use App\Entity\AuditLog;

/**
 * Lit le cycle de vie d’un rendez-vous à partir des lignes audit_log (appointment).
 * Les entrées sont supposées triées par createdAt croissant (comme renvoyé par AuditLogRepository).
 */
final class AppointmentAuditStatusReader
{
    /**
     * @param iterable<AuditLog> $logs
     */
    public function decisionStatus(iterable $logs): string
    {
        $status = 'en_attente';
        foreach ($logs as $log) {
            if (!$log instanceof AuditLog) {
                continue;
            }
            $action = $log->getAction();
            if ($action === 'rdv_created' && $log->getDetails()) {
                $d = json_decode((string) $log->getDetails(), true);
                if (is_array($d) && isset($d['status']) && is_string($d['status'])) {
                    $status = $d['status'];
                }
            }
            if ($action === 'rdv_accept') {
                $status = 'accepte';
            }
            if ($action === 'rdv_reject') {
                $status = 'rejete';
            }
        }

        return $status;
    }

    /**
     * @param iterable<AuditLog> $logs
     */
    public function patientNote(iterable $logs): ?string
    {
        $note = null;
        foreach ($logs as $log) {
            if (!$log instanceof AuditLog || $log->getAction() !== 'rdv_created' || !$log->getDetails()) {
                continue;
            }
            $d = json_decode((string) $log->getDetails(), true);
            if (is_array($d) && array_key_exists('patient_note', $d)) {
                $note = $d['patient_note'];
            }
        }

        return \is_string($note) ? $note : null;
    }

    /**
     * @param iterable<AuditLog> $logs
     */
    public function psychologueNote(iterable $logs): ?string
    {
        $note = null;
        foreach ($logs as $log) {
            if (!$log instanceof AuditLog || $log->getAction() !== 'rdv_psy_note' || !$log->getDetails()) {
                continue;
            }
            $d = json_decode((string) $log->getDetails(), true);
            if (!is_array($d)) {
                continue;
            }
            if (isset($d['psy_note']) && (\is_string($d['psy_note']) || $d['psy_note'] === null)) {
                $note = $d['psy_note'];
            }
        }

        return \is_string($note) && $note !== '' ? $note : null;
    }
}
