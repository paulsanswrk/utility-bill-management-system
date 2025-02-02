<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }

        .email-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
        }

        .content {
            font-size: 16px;
            color: #555;
            line-height: 1.5;
        }

        .status-approved {
            color: #28a745;
            font-weight: bold;
        }

        .status-declined {
            color: #dc3545;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #aaa;
        }
    </style>
</head>
<body>
<div class="email-container">
    <div class="header">
        <h1>Ažuriranje statusa pozivnice</h1>
    </div>
    <div class="content">
        @if ($ownerName)
            <p>Poštovani {{ $ownerName }},</p>
        @else
            <p>Pozdrav!</p>
        @endif
        <p>
            Želimo vas obavijestiti da je vaš zahtjev za članstvo u kućanstvu
            <strong>{{ $householdName }}</strong>
            @if ($status === 'approved')
                <span class="status-approved">odobren</span>.
            @else
                <span class="status-declined">odbijen</span>.
            @endif
        </p>
        @if ($status === 'approved')
            <p>
                Vaš zahtjev je uspješno obrađen i član kućanstva sada ima pristup podacima vašeg kućanstva.
            </p>
        @else
            <p>
                Nažalost, vaš zahtjev nije mogao biti obrađen u ovom trenutku. Slobodno nas kontaktirajte za dodatne
                informacije ili pomoć.
            </p>
        @endif
        <p>Ako imate bilo kakvih pitanja, kontaktirajte nas na <a
                href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
    </div>
    <div class="footer">
        <p>Hvala,<br>Tim {{ $billingSystemName }}</p>
        <p><a href="{{ $websiteURL }}">{{ $websiteURL }}</a></p>
    </div>
</div>
</body>
</html>
