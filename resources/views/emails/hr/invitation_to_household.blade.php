<!DOCTYPE html>
<html lang="hr">
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

        .household-list {
            margin: 15px 0;
            padding: 0;
            list-style: none;
        }

        .household-list li {
            margin: 5px 0;
            padding: 10px;
            background-color: #f1f1f1;
            border-radius: 5px;
            color: #333;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            margin: 10px 5px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
        }

        .button.decline {
            background-color: #dc3545;
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
        <h1>Poziv za pridruživanje kućanstvima!</h1>
    </div>
    <div class="content">

        @if ($inviteeName)
            <p>Poštovani {{ $inviteeName }},</p>
        @else
            <p>Pozdrav!</p>
        @endif

        <p>
            Pozvani ste od strane <strong>{{ $inviterName }}</strong> da se pridružite sljedećim kućanstvima na platformi <strong>{{ $billingSystemName }}</strong>:
        </p>
        <ul class="household-list">
            @foreach ($households as $household)
                <li>{{ $household }}</li>
            @endforeach
        </ul>
        <p>Kao član kućanstva, moći ćete:</p>
        <ul>
            <li>Pregledavati i upravljati računima za kućanstvo.</li>
            <li>Pratiti uplate i rokove dospijeća.</li>
            <li>Surađivati s drugim članovima kako biste organizirali kućanstvo.</li>
        </ul>
        <p>
            Kako biste odgovorili na ovaj poziv, kliknite jednu od opcija u nastavku:
        </p>
        <p>
            <a href="{{ $acceptLink }}" class="button">Prihvati poziv</a>
            <a href="{{ $declineLink }}" class="button decline">Odbij poziv</a>
        </p>
        <p>Ako imate pitanja ili trebate pomoć, slobodno nas kontaktirajte na <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>.</p>
        <p>Ako ne prepoznajete ovaj poziv ili smatrate da je poslan greškom, možete ga sigurno zanemariti.</p>
    </div>
    <div class="footer">
        <p>Hvala,<br>Tim {{ $billingSystemName }}</p>
        <p><a href="{{ $websiteURL }}">{{ $websiteURL }}</a></p>
    </div>
</div>
</body>
</html>
