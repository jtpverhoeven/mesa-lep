<!DOCTYPE html>
<html lang="nl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit trail {{ $trail['barcode'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="audit-print-page">
    <div id="app">
        <audit-trail-print v-bind="{{ Illuminate\Support\Js::from([
            'trail' => $trail,
            'links' => App\Support\AuditTrailLinks::for(auth()->user()),
        ]) }}"></audit-trail-print>
    </div>
</body>
</html>
