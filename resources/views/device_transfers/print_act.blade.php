{{--
    Drukājamais nodošanas akts.
    Atbildība: parāda ierīces nodošanas aktu drukāšanai. Ielādē bez galvenā app izkārtojuma.
    Datu avots: DeviceTransferController@printAct.
--}}
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nodošanas akts Nr. {{ $transfer->id }} — {{ $transfer->device->name ?? 'Ierīce' }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        @page {
            size: A4 portrait;
            margin: 2cm 2.5cm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background: #fff;
        }

        /* ── Drukāt pogu ── */
        .no-print {
            background: #1e40af;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 24px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0 auto 32px;
        }
        .no-print:hover { background: #1d4ed8; }

        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; }
        }

        /* ── Galvene ── */
        .doc-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 12pt;
            margin-bottom: 18pt;
        }
        .doc-org {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .doc-org-sub {
            font-size: 10.5pt;
            color: #333;
            margin-top: 2pt;
        }

        /* ── Dokumenta virsraksts ── */
        .doc-title {
            text-align: center;
            margin-bottom: 6pt;
        }
        .doc-title h1 {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .doc-meta {
            text-align: center;
            font-size: 10.5pt;
            color: #333;
            margin-bottom: 20pt;
        }

        /* ── Tabulas sekcija ── */
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #555;
            padding-bottom: 3pt;
            margin-bottom: 8pt;
            margin-top: 18pt;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }
        .info-table td {
            padding: 4pt 6pt;
            vertical-align: top;
        }
        .info-table td:first-child {
            width: 38%;
            font-weight: bold;
            color: #222;
            white-space: nowrap;
        }
        .info-table tr:nth-child(even) td {
            background: #f5f5f5;
        }

        /* ── Nodošanas iemesls ── */
        .reason-block {
            border: 1px solid #bbb;
            padding: 8pt 10pt;
            font-size: 11pt;
            margin-top: 6pt;
            min-height: 40pt;
            background: #fafafa;
        }

        /* ── Pušu un parakstu bloki ── */
        .parties-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24pt;
            margin-top: 6pt;
        }
        .party-block {
            border: 1px solid #999;
            padding: 10pt 12pt;
        }
        .party-label {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #444;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4pt;
            margin-bottom: 8pt;
        }
        .party-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 3pt;
        }
        .party-detail {
            font-size: 10pt;
            color: #444;
            margin-bottom: 2pt;
        }
        .signature-row {
            margin-top: 20pt;
            display: flex;
            align-items: flex-end;
            gap: 8pt;
        }
        .signature-label {
            font-size: 10pt;
            white-space: nowrap;
            color: #555;
        }
        .signature-line {
            flex: 1;
            border-bottom: 1px solid #000;
            min-width: 80pt;
        }
        .date-row {
            margin-top: 8pt;
            display: flex;
            align-items: flex-end;
            gap: 8pt;
        }
        .date-label {
            font-size: 10pt;
            white-space: nowrap;
            color: #555;
        }
        .date-line {
            width: 80pt;
            border-bottom: 1px solid #000;
        }

        /* ── Apstiprinājuma josla ── */
        .approval-bar {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 4px;
            padding: 8pt 12pt;
            font-size: 10.5pt;
            display: flex;
            gap: 16pt;
            flex-wrap: wrap;
            margin-top: 8pt;
        }
        .approval-bar-item strong {
            font-weight: bold;
        }

        /* ── Kājene ── */
        .doc-footer {
            margin-top: 28pt;
            border-top: 1px solid #ccc;
            padding-top: 6pt;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- Drukāt poga, neredzama printā --}}
    <div style="text-align:center; margin: 24px 0 0;">
        <button class="no-print" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6v-8z"/>
            </svg>
            Drukāt aktu
        </button>
    </div>

    {{-- Galvene --}}
    <div class="doc-header">
        <div class="doc-org">Ludzas novada pašvaldība</div>
        <div class="doc-org-sub">IT inventāra uzskaites sistēma</div>
    </div>

    {{-- Virsraksts --}}
    <div class="doc-title">
        <h1>Ierīces nodošanas akts</h1>
    </div>
    <div class="doc-meta">
        Akts Nr.&nbsp;<strong>{{ $transfer->id }}</strong>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Datums: <strong>{{ $transfer->updated_at?->format('d.m.Y') ?? $transfer->created_at?->format('d.m.Y') }}</strong>
    </div>

    {{-- Ierīces informācija --}}
    <div class="section-title">Ierīces informācija</div>
    <table class="info-table">
        <tbody>
            <tr>
                <td>Ierīces nosaukums</td>
                <td>{{ $transfer->device->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>Inventāra kods</td>
                <td>{{ $transfer->device->code ?: '—' }}</td>
            </tr>
            <tr>
                <td>Sērijas numurs</td>
                <td>{{ $transfer->device->serial_number ?: '—' }}</td>
            </tr>
            <tr>
                <td>Tips</td>
                <td>{{ $transfer->device->type?->type_name ?: '—' }}</td>
            </tr>
            <tr>
                <td>Ražotājs / Modelis</td>
                <td>
                    @php
                        $mfg = collect([$transfer->device->manufacturer, $transfer->device->model])->filter()->implode(' / ');
                    @endphp
                    {{ $mfg ?: '—' }}
                </td>
            </tr>
            <tr>
                <td>Ēka / Telpa</td>
                <td>
                    @php
                        $location = collect([
                            $transfer->device->building?->building_name,
                            $transfer->device->room?->room_number ? 'telpa ' . $transfer->device->room->room_number : null,
                            $transfer->device->room?->room_name,
                        ])->filter()->implode(', ');
                    @endphp
                    {{ $location ?: '—' }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Nodošanas apraksts --}}
    <div class="section-title">Nodošanas pamatojums</div>
    <div class="reason-block">{{ $transfer->transfer_reason ?: '—' }}</div>

    {{-- Apstiprinājuma informācija --}}
    <div class="section-title">Apstiprinājuma dati</div>
    <div class="approval-bar">
        <div class="approval-bar-item">
            <strong>Statuss:</strong> Apstiprināts
        </div>
        <div class="approval-bar-item">
            <strong>Iesniegts:</strong> {{ $transfer->created_at?->format('d.m.Y H:i') ?? '—' }}
        </div>
        <div class="approval-bar-item">
            <strong>Apstiprināts:</strong> {{ $transfer->updated_at?->format('d.m.Y H:i') ?? '—' }}
        </div>
    </div>

    {{-- Pušu parakstu bloki --}}
    <div class="section-title">Pušu apliecinājums un paraksti</div>

    <div class="parties-grid">
        {{-- Nodevējs --}}
        <div class="party-block">
            <div class="party-label">Nodevējs (ierīci nodeva)</div>
            <div class="party-name">{{ $transfer->responsibleUser?->full_name ?? '—' }}</div>
            @if ($transfer->responsibleUser?->job_title)
                <div class="party-detail">{{ $transfer->responsibleUser->job_title }}</div>
            @endif
            @if ($transfer->responsibleUser?->email)
                <div class="party-detail">{{ $transfer->responsibleUser->email }}</div>
            @endif
            <div class="signature-row">
                <span class="signature-label">Paraksts:</span>
                <span class="signature-line"></span>
            </div>
            <div class="date-row">
                <span class="date-label">Datums:</span>
                <span class="date-line"></span>
            </div>
        </div>

        {{-- Saņēmējs --}}
        <div class="party-block">
            <div class="party-label">Saņēmējs (ierīci saņēma)</div>
            <div class="party-name">{{ $transfer->transferTo?->full_name ?? '—' }}</div>
            @if ($transfer->transferTo?->job_title)
                <div class="party-detail">{{ $transfer->transferTo->job_title }}</div>
            @endif
            @if ($transfer->transferTo?->email)
                <div class="party-detail">{{ $transfer->transferTo->email }}</div>
            @endif
            <div class="signature-row">
                <span class="signature-label">Paraksts:</span>
                <span class="signature-line"></span>
            </div>
            <div class="date-row">
                <span class="date-label">Datums:</span>
                <span class="date-line"></span>
            </div>
        </div>
    </div>

    {{-- Kājene --}}
    <div class="doc-footer">
        Dokuments ģenerēts automātiski no IT inventāra uzskaites sistēmas
        &bull; {{ now()->format('d.m.Y H:i') }}
        &bull; Akts Nr. {{ $transfer->id }}
    </div>

</body>
</html>
