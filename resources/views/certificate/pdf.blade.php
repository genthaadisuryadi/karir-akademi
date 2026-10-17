<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat {{ $registration->certificate_number }}</title>
    <style>
        @page { margin: 0px; }
        body { margin: 0px; padding: 0px; font-family: 'Helvetica', 'Arial', sans-serif; }
        
        .page {
            width: 100%;
            height: 100%;
            position: relative;
            page-break-after: always;
            overflow: hidden;
        }

        .bg-template {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        /* Koordinat posisi text. Nanti bisa digeser top/left-nya sesuai desain PNG Anda */
        .nama {
            position: absolute;
            top: 45%; 
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 38px;
            font-weight: bold;
            color: #111;
        }

        .nomor {
            position: absolute;
            top: 36%;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 16px;
            color: #444;
        }

        .tanggal {
            position: absolute;
            bottom: 22%;
            left: 10%;
            font-size: 16px;
            color: #222;
        }

        .qrcode {
            position: absolute;
            bottom: 15%;
            right: 15%;
            width: 120px;
            height: 120px;
        }
    </style>
</head>
<body>
    <!-- HALAMAN 1 (DEPAN) -->
    <div class="page">
        <!-- Menggunakan storage_path agar DomPDF bisa baca file di storage privat -->
        <img class="bg-template" src="{{ storage_path('app/' . $program->certificate_front) }}" alt="Template Depan">
        
        <div class="nomor">No: {{ $registration->certificate_number }}</div>
        <div class="nama">{{ $registration->name }}</div>
        <div class="tanggal">Cirebon, {{ \Carbon\Carbon::parse($registration->certificate_issued_at ?? now())->translatedFormat('d F Y') }}</div>
        
        <div class="qrcode">
            <img src="data:image/svg+xml;base64,{!! $qrcode !!}" style="width: 100%;" alt="QR Code">
        </div>
    </div>

    <!-- HALAMAN 2 (BELAKANG STATIS) -->
    <div class="page">
        <img class="bg-template" src="{{ storage_path('app/' . $program->certificate_back) }}" alt="Template Belakang">
    </div>
</body>
</html>