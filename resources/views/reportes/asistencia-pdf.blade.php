<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Asistencia</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { max-width: 150px; height: auto; }
        .title { font-size: 24px; font-weight: bold; margin: 10px 0; }
        .subtitle { font-size: 16px; color: #666; }
        .stats { margin: 20px 0; }
        .stat-card { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .stat-title { font-weight: bold; margin-bottom: 5px; }
        .stat-value { font-size: 20px; color: #333; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f5f5f5; font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('img/elsembrador.jpeg') }}" alt="Logo" class="logo">
        <div class="title">Sistema de Docentes</div>
        <div class="subtitle">Reporte de Asistencia</div>
        <div class="subtitle">Período: {{ $fechaInicio }} - {{ $fechaFin }}</div>
    </div>

    <div class="stats">
        <h3>Estadísticas Generales</h3>
        <div class="stat-card">
            <div class="stat-title">Total Asistencias</div>
            <div class="stat-value">{{ $estadisticas['total_asistencias'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Total Docentes</div>
            <div class="stat-value">{{ $estadisticas['total_docentes'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-title">Docentes con Asistencia Hoy</div>
            <div class="stat-value">{{ $estadisticas['docentes_con_asistencia_hoy'] }} ({{ $estadisticas['porcentaje_asistencia_hoy'] }}%)</div>
        </div>
    </div>

    @if(isset($estadisticas['asistencias_por_tipo']) && count($estadisticas['asistencias_por_tipo']) > 0)
    <h3>Asistencias por Tipo</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Total</th>
                <th>Porcentaje</th>
            </tr>
        </thead>
        <tbody>
            @foreach($estadisticas['asistencias_por_tipo'] as $tipo)
            <tr>
                <td>{{ $tipo['tipo'] }}</td>
                <td>{{ $tipo['total'] }}</td>
                <td>{{ $tipo['porcentaje'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if(isset($docente))
    <h3>Reporte de Asistencia - {{ $docente->nombres }} {{ $docente->apellidos }}</h3>
    @else
    <h3>Reporte General de Asistencias</h3>
    @endif

    @if(isset($asistenciasPorFecha) && count($asistenciasPorFecha) > 0)
    @foreach($asistenciasPorFecha as $fechaData)
    <h4>Fecha: {{ \Carbon\Carbon::parse($fechaData['fecha'])->format('d/m/Y') }}</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Docente</th>
                <th>Fecha Registro</th>
                <th>Hora Entrada</th>
                <th>Hora Salida</th>
                <th>Horas Presentes</th>
                <th>Minutos Tarde</th>
                <th>Minutos Extra</th>
                <th>Tipo Asistencia</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fechaData['asistencias'] as $asistencia)
            <tr>
                <td>{{ $asistencia['docente'] }}</td>
                <td>{{ $asistencia['fecha_registro'] ?? 'N/A' }}</td>
                <td>{{ $asistencia['hora_entrada'] }}</td>
                <td>{{ $asistencia['hora_salida'] ?? 'N/A' }}</td>
                <td>{{ $asistencia['horas_presentes'] ?? 'N/A' }}</td>
                <td>{{ $asistencia['minutos_tarde'] ?? 0 }}</td>
                <td>{{ $asistencia['minutos_extra'] ?? 0 }}</td>
                <td>{{ $asistencia['tipo_asistencia'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
    @else
    <p>No hay asistencias registradas en el período seleccionado.</p>
    @endif

    <div class="footer">
        <p>Reporte generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>