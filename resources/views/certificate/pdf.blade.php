<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat {{ $registration->certificate_number }}</title>
    <style>
        /* Mengamankan ukuran A4 Landscape dan menghilangkan margin */
        @page { 
            margin: 0px; 
            size: A4 landscape;
        }
        body { 
            margin: 0px; 
            padding: 0px; 
            font-family: 'Helvetica', 'Arial', sans-serif; 
        }
        
        .page {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden; 
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .bg-template {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            object-fit: cover; 
        }

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
        <!-- Path gambar sudah dikoreksi ke storage_path() -->
        <img class="bg-template" src="{{ storage_path('app/' . $program->certificate_front) }}" alt="">
        
        <div class="nomor">No: {{ $registration->certificate_number }}</div>
        <div class="nama">{{ $registration->name }}</div>
        <!-- Menampilkan tanggal menggunakan library Carbon -->
        <div class="tanggal">Cirebon, {{ \Carbon\Carbon::parse($registration->certificate_issued_at ?? now())->isoFormat('D MMMM Y') }}</div>
        
        <div class="qrcode">
            <img src="data:image/svg+xml;base64,{!! $qrcode !!}" style="width: 100%;" alt="">
        </div>
    </div>

    <!-- HALAMAN 2 (BELAKANG STATIS) -->
    <div class="page">
        <!-- Path gambar sudah dikoreksi ke storage_path() -->
        <img class="bg-template" src="{{ storage_path('app/' . $program->certificate_back) }}" alt="">
    </div>
</body>
</html>