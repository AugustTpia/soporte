<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        @page {
            margin: 28px 26px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 0;
        }

        h1 {
            font-size: 19px;
            margin: 0 0 3px;
            color: #102a43;
        }

        .muted {
            color: #6b7280;
        }

        .brand {
            border-bottom: 3px solid #102a43;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .brand-company {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta td {
            width: 50%;
            padding: 3px 6px 3px 0;
            vertical-align: top;
        }

        .label {
            color: #6b7280;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .value {
            font-weight: bold;
            color: #111827;
        }

        .metrics {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
            margin: 4px -5px 12px;
        }

        .metrics td {
            width: 25%;
            background: #f3f6f9;
            border: 1px solid #dbe3ec;
            border-radius: 4px;
            padding: 7px 8px;
            vertical-align: top;
        }

        .metric-label {
            color: #52606d;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .metric-value {
            margin-top: 3px;
            font-size: 14px;
            font-weight: bold;
            color: #102a43;
        }

        .metric-hint {
            margin-top: 2px;
            color: #7b8794;
            font-size: 6.5px;
        }

        .filters {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background: #fbfcfd;
            border: 1px solid #e4e9ef;
        }

        .filters td {
            padding: 5px 7px;
            border-bottom: 1px solid #eef2f6;
        }

        .filters tr:last-child td {
            border-bottom: 0;
        }

        .filters .filter-key {
            width: 16%;
            color: #6b7280;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .filters .filter-value {
            font-weight: bold;
            color: #1f2937;
        }

        .data {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }

        .data thead th {
            background: #102a43;
            color: #ffffff;
            text-align: left;
            padding: 6px 5px;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .data tbody td {
            border-bottom: 1px solid #e5e7eb;
            padding: 5px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .data tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .data tbody tr {
            page-break-inside: avoid;
        }

        .empty {
            padding: 26px;
            text-align: center;
            color: #6b7280;
            border: 1px dashed #cbd5e1;
            margin-top: 8px;
        }

        .notice {
            margin: 8px 0 0;
            padding: 7px 9px;
            border: 1px solid #f2c94c;
            background: #fff9e6;
            color: #7c5e10;
            font-size: 8px;
        }

        .footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 7px;
        }
    </style>
</head>
<body>
    <div class="brand">
        <h1>{{ $document->title }}</h1>
        <div class="brand-company">{{ $document->companyName }}</div>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="label">Periodo</div>
                <div class="value">{{ $document->period->label() }}</div>
            </td>
            <td>
                <div class="label">Generado</div>
                <div class="value">{{ $document->generatedAt->format('d/m/Y H:i') }} · {{ $document->generatedBy }}</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Registros</div>
                <div class="value">{{ number_format($document->totalRows) }}</div>
            </td>
            <td>
                <div class="label">Tipo de reporte</div>
                <div class="value">{{ $document->type->label() }}</div>
            </td>
        </tr>
    </table>

    @if($document->metrics !== [])
        <table class="metrics">
            @foreach(array_chunk($document->metrics, 4) as $metricRow)
                <tr>
                    @foreach($metricRow as $metric)
                        <td>
                            <div class="metric-label">{{ $metric->label }}</div>
                            <div class="metric-value">{{ $metric->value }}</div>
                            @if($metric->hint)
                                <div class="metric-hint">{{ $metric->hint }}</div>
                            @endif
                        </td>
                    @endforeach
                    @for($i = count($metricRow); $i < 4; $i++)
                        <td></td>
                    @endfor
                </tr>
            @endforeach
        </table>
    @endif

    @if($document->filterLabels !== [])
        <table class="filters">
            @foreach($document->filterLabels as $label => $value)
                <tr>
                    <td class="filter-key">{{ $label }}</td>
                    <td class="filter-value">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if($rows === [])
        <div class="empty">No hay información para los filtros seleccionados.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    @foreach($document->headings as $heading)
                        <th>{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($truncated)
            <div class="notice">
                El reporte contiene {{ number_format($document->totalRows) }} registros. Este PDF muestra los primeros {{ number_format($maxRows) }}. Usa la exportación CSV para el detalle completo.
            </div>
        @endif
    @endif

    <div class="footer">
        Documento generado automáticamente por la plataforma de gestión de servicios TI. La información mostrada está limitada a la empresa y a los filtros aplicados.
    </div>
</body>
</html>
