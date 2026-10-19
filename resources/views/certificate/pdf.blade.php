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

        /* Teks Nomor dinaikkan ke atas agar lebih dekat dengan tulisan SERTIFIKAT */
        .nomor {
            position: absolute;
            top: 21%; 
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 16px;
            color: #444;
        }

        /* Teks Nama dinaikkan ke atas agar pas berada di atas garis */
        .nama {
            position: absolute;
            top: 41%; 
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 38px;
            font-weight: bold;
            color: #111;
        }

        /* Teks Tanggal diturunkan sedikit menyesuaikan posisi QR Code yang baru */
        .tanggal {
            position: absolute;
            bottom: 27%; 
            right: 9%;  
            width: 250px; 
            text-align: center;
            font-size: 16px;
            color: #222;
        }

        /* QR Code diturunkan posisinya mendekati sudut bawah kertas */
        .qrcode {
            position: absolute;
            bottom: 8%; 
            right: 14%;  
            width: 130px;
            height: 130px;
        }
    </style>
</head>
<body>
    <!-- HALAMAN 1 (DEPAN) -->
    <div class="page">
        <!-- Menggunakan variabel Base64 dari Controller agar terhindar dari Error X -->
        <img class="bg-template" src="{{ $frontTemplate }}" alt="">
        
        <!-- Teks Dinamis -->
        <div class="nomor">No: {{ $registration->certificate_number }}</div>
        <div class="nama">{{ $registration->name }}</div>
        <div class="tanggal">Cirebon, {{ \Carbon\Carbon::parse($registration->certificate_issued_at ?? now())->isoFormat('D MMMM Y') }}</div>
        
        <div class="qrcode">
            <img src="data:image/svg+xml;base64,{!! $qrcode !!}" style="width: 100%;" alt="">
        </div>
    </div>

    <!-- HALAMAN 2 (BELAKANG STATIS) -->
    <div class="page">
        <img class="bg-template" src="{{ $backTemplate }}" alt="">
    </div>
</body>
</html>