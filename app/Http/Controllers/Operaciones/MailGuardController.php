<?php

namespace App\Http\Controllers\Operaciones;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Support\Facades\Mail;

class MailGuardController extends Controller
{
public function send(Request $request, $guardia)
{
    $data = $request->all();

    Log::info('guardia-email: JSON recibido desde frontend', [
        'guardia_route_id' => $guardia,
        'guardia_payload_id' => $data['guardia']['id'] ?? null,
        'user_name' => $data['guardia']['user_name'] ?? null,
        'okItemsVeeam_count' => count($data['okItemsVeeam'] ?? []),
        'pendingVeeamRows_count' => count($data['pendingVeeamRows'] ?? []),
        'tickets_pending_count' => count($data['ticketsResume']['pending'] ?? []),
        'tickets_concluded_users_count' => count($data['ticketsResume']['concludedByUser'] ?? []),
        'tickets_counters' => $data['ticketsResume']['counters'] ?? null,
    ]);

    try {
        $html = $this->buildGuardiaCloseEmailHtml($data);

        $htmlBytes = strlen($html);
        $htmlKb = round($htmlBytes / 1024, 2);

        Log::info('guardia-email: HTML generado en backend', [
            'guardia_id' => $guardia,
            'html_bytes' => $htmlBytes,
            'html_kb' => $htmlKb,
        ]);

        $toEmail = $this->guardiaCloseRecipient();
        $subject = 'Cierre de Guardia #' . $guardia;

        Log::info('guardia-email: enviando correo SMTP Brevo', [
            'guardia_id' => $guardia,
            'to_email' => $toEmail,
            'from_email' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'subject' => $subject,
            'html_kb' => $htmlKb,
        ]);

        Mail::html($html, function ($message) use ($toEmail, $subject) {
            $message
                ->to($toEmail)
                ->subject($subject);
        });

        Log::info('guardia-email: correo enviado correctamente por SMTP Brevo', [
            'guardia_id' => $guardia,
            'to_email' => $toEmail,
            'subject' => $subject,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Correo enviado correctamente por SMTP Brevo.',
            'guardia_id' => $guardia,
            'html_bytes' => $htmlBytes,
            'html_kb' => $htmlKb,
        ]);
    } catch (Throwable $e) {
        Log::error('guardia-email: error enviando correo SMTP Brevo', [
            'guardia_id' => $guardia,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'ok' => false,
            'message' => 'Error enviando correo por SMTP Brevo.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

private function guardiaCloseRecipient(): string
{
    return 'operationsstratosphere@stratospherecorp.com';
    
}

    private function esc($value): string
    {
        return e((string) ($value ?? ''));
    }

    private function text($value, string $default = '—'): string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : $default;
    }

    private function formatDateTime($value): string
    {
        if (!$value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    private function formatDateOnly($value): string
    {
        if (!$value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (Throwable $e) {
            return (string) $value;
        }
    }

    private function veeamLabel($value): string
    {
        $statuses = [
            '1' => 'Completado Exitoso - Backup finalizado sin errores',
            '2' => 'Completado con Advertencias - Backup terminado con observaciones',
            '3' => 'Fallido - Backup no se completó correctamente',
            '4' => 'En Progreso - Backup actualmente en ejecución',
            '5' => 'Pausado - Backup detenido temporalmente',
            '6' => 'Pendiente - Programado pero no iniciado',
        ];

        $key = trim((string) ($value ?? ''));

        return $statuses[$key] ?? ($key ?: '—');
    }

    private function clientDisplay(array $row): string
    {
        $num = trim((string) ($row['numCV'] ?? ''));
        $name = trim((string) ($row['nameCV'] ?? ''));
        $label = trim((string) ($row['client_label'] ?? ''));

        if ($num && $name) {
            return "{$num} - {$name}";
        }

        if ($label) {
            return $label;
        }

        if ($num) {
            return $num;
        }

        if ($name) {
            return $name;
        }

        return '—';
    }

    private function ticketNum(array $ticket): string
    {
        $a = trim((string) ($ticket['numTicket'] ?? ''));
        $b = trim((string) ($ticket['numTicketNoct'] ?? ''));

        if ($a && $b) {
            return "{$a} / {$b}";
        }

        if ($a) {
            return $a;
        }

        if ($b) {
            return $b;
        }

        return '—';
    }

    private function splitVeeamRowsByGuardCategory(array $rows): array
    {
        $closedInGuard = [];
        $pendingUpdatedInGuard = [];
        $unchangedOrNewInGuard = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $category = trim((string) ($row['guard_category'] ?? ''));

            if ($category === 'closed_in_guard') {
                $closedInGuard[] = $row;
                continue;
            }

            if ($category === 'pending_updated_in_guard') {
                $pendingUpdatedInGuard[] = $row;
                continue;
            }

            $unchangedOrNewInGuard[] = $row;
        }

        return [
            'closedInGuard' => $closedInGuard,
            'pendingUpdatedInGuard' => $pendingUpdatedInGuard,
            'unchangedOrNewInGuard' => $unchangedOrNewInGuard,
        ];
    }

    private function buildOkGroups(array $okItemsVeeam): array
    {
        $groups = [];

        foreach ($okItemsVeeam as $item) {
            if (!is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['veeam_name'] ?? $item['siteApp_name'] ?? ''));

            if ($name === '') {
                $name = 'VEEAM sin nombre';
            }

            $groups[$name] = ($groups[$name] ?? 0) + 1;
        }

        arsort($groups);

        return $groups;
    }

    private function buildGuardiaCloseEmailHtml(array $data): string
    {
        $guardiaData = $data['guardia'] ?? [];
        $okItemsVeeam = $data['okItemsVeeam'] ?? [];
        $pendingVeeamRows = $data['pendingVeeamRows'] ?? [];

        $ticketsResume = $data['ticketsResume'] ?? [];
        $ticketsPending = $ticketsResume['pending'] ?? [];
        $ticketsConcludedByUser = $ticketsResume['concludedByUser'] ?? [];
        $ticketCounters = $ticketsResume['counters'] ?? [];

        if (!is_array($okItemsVeeam)) {
            $okItemsVeeam = [];
        }

        if (!is_array($pendingVeeamRows)) {
            $pendingVeeamRows = [];
        }

        if (!is_array($ticketsPending)) {
            $ticketsPending = [];
        }

        if (!is_array($ticketsConcludedByUser)) {
            $ticketsConcludedByUser = [];
        }

        $guardiaId = $this->text($guardiaData['id'] ?? null);
        $userName = $this->text($guardiaData['user_name'] ?? null);
        $entrada = $this->formatDateTime($guardiaData['dateInit'] ?? null);
        $salida = now()->format('d/m/Y H:i');

        $groups = $this->buildOkGroups($okItemsVeeam);
        $monitoreoGroups = $this->splitVeeamRowsByGuardCategory($pendingVeeamRows);

        $totalConcluded = 0;

        foreach ($ticketsConcludedByUser as $items) {
            if (is_array($items)) {
                $totalConcluded += count($items);
            }
        }

        $ticketTotal = $ticketCounters['total'] ?? (count($ticketsPending) + $totalConcluded);
        $ticketPendingTotal = $ticketCounters['pending'] ?? count($ticketsPending);
        $ticketConcludedTotal = $ticketCounters['concluded'] ?? $totalConcluded;
        $ticketNewTotal = $ticketCounters['newTickets'] ?? 0;

        Log::info('guardia-email: datos preparados para HTML', [
            'guardia_id' => $guardiaId,
            'ok_groups_count' => count($groups),
            'closed_in_guard' => count($monitoreoGroups['closedInGuard']),
            'pending_updated_in_guard' => count($monitoreoGroups['pendingUpdatedInGuard']),
            'unchanged_or_new_in_guard' => count($monitoreoGroups['unchangedOrNewInGuard']),
            'tickets_pending' => count($ticketsPending),
            'tickets_concluded' => $totalConcluded,
        ]);

        $html = '
        <div style="background:#242424;color:#ffffff;font-family:Arial,Helvetica,sans-serif;padding:18px;">
            <div style="text-align:center;margin-bottom:16px;">
                <span style="display:inline-block;background:#123f33;color:#7ee7c0;padding:12px 16px;border-radius:8px;font-weight:bold;">
                    CIERRE DE GUARDIA #' . $this->esc($guardiaId) . ' REALIZADO EXITOSAMENTE
                </span>
            </div>

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-bottom:18px;">
                <tr>
                    <td style="background:#5a5b5d;color:#ffffff;"><b>Usuario:</b> ' . $this->esc($userName) . '</td>
                    <td style="background:#5a5b5d;color:#ffffff;"><b>Entrada:</b> ' . $this->esc($entrada) . '</td>
                    <td style="background:#5a5b5d;color:#ffffff;"><b>Salida:</b> ' . $this->esc($salida) . '</td>
                </tr>
            </table>

            <h3 style="margin:18px 0 8px;color:#ffffff;">RESUMEN MONITOREOS VEEAM</h3>

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-bottom:18px;">
                <tr>
                    <td style="background:#5a5b5d;color:#ffffff;"><b>Clientes sin incidencia</b><br>' . count($okItemsVeeam) . '</td>
                    <td style="background:#123f33;color:#7ee7c0;"><b>Cerrados en guardia</b><br>' . count($monitoreoGroups['closedInGuard']) . '</td>
                    <td style="background:#4a3510;color:#fbbf24;"><b>Pendientes actualizados</b><br>' . count($monitoreoGroups['pendingUpdatedInGuard']) . '</td>
                    <td style="background:#5a5b5d;color:#ffffff;"><b>Sin cambios / nuevos</b><br>' . count($monitoreoGroups['unchangedOrNewInGuard']) . '</td>
                </tr>
            </table>
        ';

        $html .= $this->buildOkGroupsHtml($groups);

        $html .= $this->buildVeeamTableHtml(
            'MONITOREOS CERRADOS EN ESTA GUARDIA',
            $monitoreoGroups['closedInGuard']
        );

        $html .= $this->buildVeeamTableHtml(
            'MONITOREOS PENDIENTES ACTUALIZADOS',
            $monitoreoGroups['pendingUpdatedInGuard']
        );

        $html .= $this->buildVeeamTableHtml(
            'MONITOREOS SIN MODIFICACIÓN / AGREGADOS',
            $monitoreoGroups['unchangedOrNewInGuard']
        );

        $html .= '
            <h3 style="margin:22px 0 8px;color:#ffffff;">RESUMEN TICKETS</h3>

            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;margin-bottom:18px;">
                <tr>
                    <td style="background:#5a5b5d;color:#ffffff;"><b>Total</b><br>' . $this->esc($ticketTotal) . '</td>
                    <td style="background:#4a3510;color:#fbbf24;"><b>Pendientes</b><br>' . $this->esc($ticketPendingTotal) . '</td>
                    <td style="background:#123f33;color:#7ee7c0;"><b>Concluidos</b><br>' . $this->esc($ticketConcludedTotal) . '</td>
                    <td style="background:#5a5b5d;color:#ffffff;"><b>Nuevos</b><br>' . $this->esc($ticketNewTotal) . '</td>
                </tr>
            </table>
        ';

        $html .= $this->buildTicketsPendingTableHtml('TICKETS PENDIENTES', $ticketsPending);
        $html .= $this->buildTicketsConcludedTableHtml('TICKETS CONCLUIDOS', $ticketsConcludedByUser);

        $html .= '
            <div style="margin-top:18px;color:#b6c2d2;font-size:12px;text-align:center;">
            </div>
        </div>';

        return trim($html);
    }

    private function buildOkGroupsHtml(array $groups): string
    {
        $html = '
            <h3 style="margin:22px 0 8px;color:#ffffff;">MONITOREOS VEEAM EXITOSOS</h3>
        ';

        if (empty($groups)) {
            return $html . '
                <div style="background:#5a5b5d;color:#ffffff;padding:10px;margin-bottom:18px;">
                    Sin clientes OK por sección VEEAM.
                </div>
            ';
        }

        $html .= '
            <table width="100%" cellpadding="7" cellspacing="0" style="border-collapse:collapse;font-size:12px;margin-bottom:18px;">
                <tbody>
        ';

        foreach ($groups as $name => $count) {
            $html .= '
                <tr>
                    <td style="background:#5a5b5d;color:#ffffff;border-bottom:4px solid #242424;">
                        <b>' . $this->esc($name) . '</b> — ' . $this->esc($count) . ' OK
                    </td>
                </tr>
            ';
        }

        $html .= '
                </tbody>
            </table>
        ';

        return $html;
    }

    private function buildVeeamTableHtml(string $title, array $rows): string
    {
        $html = '
            <h3 style="margin:22px 0 8px;color:#ffffff;">' . $this->esc($title) . '</h3>

            <table width="100%" cellpadding="7" cellspacing="0" style="border-collapse:collapse;font-size:12px;margin-bottom:18px;">
                <thead>
                    <tr>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">CLIENTE</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">SITE</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">BACKUP</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">RESTAURACIÓN</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">ESTATUS</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">OBSERVACIÓN</th>
                    </tr>
                </thead>
                <tbody>
        ';

        if (empty($rows)) {
            $html .= '
                <tr>
                    <td colspan="6" style="background:#242424;color:#b6c2d2;padding:10px;">
                        Sin datos para mostrar.
                    </td>
                </tr>
            ';
        }

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $html .= '
                <tr>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($this->clientDisplay($row)) . '</td>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($row['veeam_name'] ?? $row['site'] ?? '—') . '</td>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($row['backup'] ?? '—') . '</td>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($this->formatDateOnly($row['last_restore_date'] ?? null)) . '</td>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($this->veeamLabel($row['estatus_veeam'] ?? $row['estatus'] ?? null)) . '</td>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($row['observacion'] ?? '—') . '</td>
                </tr>
            ';
        }

        $html .= '
                </tbody>
            </table>
        ';

        return $html;
    }

    private function buildTicketsPendingTableHtml(string $title, array $tickets): string
    {
        $html = '
            <h3 style="margin:22px 0 8px;color:#ffffff;">' . $this->esc($title) . '</h3>

            <table width="100%" cellpadding="7" cellspacing="0" style="border-collapse:collapse;font-size:12px;margin-bottom:18px;">
                <thead>
                    <tr>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;"># TICKET</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">TÍTULO</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
        ';

        if (empty($tickets)) {
            $html .= '
                <tr>
                    <td colspan="3" style="background:#242424;color:#b6c2d2;padding:10px;">
                        Sin tickets pendientes.
                    </td>
                </tr>
            ';
        }

        foreach ($tickets as $ticket) {
            if (!is_array($ticket)) {
                continue;
            }

            $html .= '
                <tr>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($this->ticketNum($ticket)) . '</td>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($ticket['titleTicket'] ?? $ticket['title'] ?? '—') . '</td>
                    <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($ticket['descriptionTicket'] ?? $ticket['description'] ?? '—') . '</td>
                </tr>
            ';
        }

        $html .= '
                </tbody>
            </table>
        ';

        return $html;
    }

    private function buildTicketsConcludedTableHtml(string $title, array $concludedByUser): string
    {
        $html = '
            <h3 style="margin:22px 0 8px;color:#ffffff;">' . $this->esc($title) . '</h3>

            <table width="100%" cellpadding="7" cellspacing="0" style="border-collapse:collapse;font-size:12px;margin-bottom:18px;">
                <thead>
                    <tr>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;"># TICKET</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">TÍTULO</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">OBSERVACIONES</th>
                        <th align="left" style="background:#5a5b5d;color:#cbd5e1;">ESTATUS</th>
                    </tr>
                </thead>
                <tbody>
        ';

        $hasRows = false;

        foreach ($concludedByUser as $tickets) {
            if (!is_array($tickets)) {
                continue;
            }

            foreach ($tickets as $ticket) {
                if (!is_array($ticket)) {
                    continue;
                }

                $hasRows = true;

                $html .= '
                    <tr>
                        <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($this->ticketNum($ticket)) . '</td>
                        <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($ticket['titleTicket'] ?? $ticket['title'] ?? '—') . '</td>
                        <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">' . $this->esc($ticket['descriptionTicket'] ?? $ticket['description'] ?? '—') . '</td>
                        <td style="background:#242424;color:#ffffff;border-bottom:1px solid #444;">Concluido</td>
                    </tr>
                ';
            }
        }

        if (!$hasRows) {
            $html .= '
                <tr>
                    <td colspan="4" style="background:#242424;color:#b6c2d2;padding:10px;">
                        Sin tickets concluidos.
                    </td>
                </tr>
            ';
        }

        $html .= '
                </tbody>
            </table>
        ';

        return $html;
    }
}