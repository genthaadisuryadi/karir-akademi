<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Sertifikat {{ $number }}</title>
    <style>
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
        .nomor {
            position: absolute;
            top: 21%; 
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 16px;
            color: #444;
        }
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
        .tanggal {
            position: absolute;
            bottom: 27%; 
            right: 9%;  
            width: 250px; 
            text-align: center;
            font-size: 16px;
            color: #222;
        }
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
    <div class="page">
        <img class="bg-template" src="{{ $frontTemplate }}" alt="">
        
        <!-- Teks Dinamis kini menggunakan variabel langsung -->
        <div class="nomor">No: {{ $number }}</div>
        <div class="nama">{{ $name }}</div>
        <div class="tanggal">Cirebon, {{ \Carbon\Carbon::parse($date)->isoFormat('D MMMM Y') }}</div>
        
        <div class="qrcode">
            <img src="data:image/svg+xml;base64,{!! $qrcode !!}" style="width: 100%;" alt="">
        </div>
    </div>

    <div class="page">
        <img class="bg-template" src="{{ $backTemplate }}" alt="">
    </div>
</body>
</html>