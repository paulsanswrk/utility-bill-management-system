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
            background-color: #f4f4f4;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .content {
            font-size: 16px;
            line-height: 1.5;
            color: #333333;
        }

        .header {
            text-align: center;
            background-color: #007bff;
            padding: 20px;
            color: white;
            font-size: 20px;
            border-radius: 8px 8px 0 0;
        }

        .bill {
            background-color: #f9f9f9;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .bill-item {
            margin-bottom: 5px;
        }

        .button {
            display: block;
            width: 100%;
            text-align: center;
            padding: 10px;
            margin: 20px 0;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            margin-top: 20px;
        }

        @media screen and (max-width: 600px) {
            .container {
                padding: 10px;
            }

            .header, .button {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        Podsjetnik: Nepodmireni Računi
    </div>

    <div class="content">
        <p>Poštovani {{ $user->name }},</p>
        <p>Nadamo se da vas ova poruka zatiče dobro. Naši podaci pokazuju da imate nepodmirene račune s sljedećim detaljima:</p>

        @foreach ($bills as $bill)
            <div class="bill">
                <div class="bill-item"><strong>Komunalno Poduzeće:</strong> {{ $bill->utility_company }}</div>
                <div class="bill-item"><strong>Datum Izdavanja Računa:</strong>
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $bill->issue_date)->locale($locale)->translatedFormat('F Y') }}
                </div>
                <div class="bill-item"><strong>Iznos:</strong> {{ Number::currency($bill->amount) }} </div>
            </div>
        @endforeach

        <p>Molimo vas da podmirite račune do navedenih rokova kako biste izbjegli eventualne prekide usluge ili naknade za kašnjenje.</p>

        <p>Ako ste već izvršili uplatu, zanemarite ovu poruku. Ukoliko vam je potrebna pomoć ili imate pitanja, slobodno se obratite našoj službi za korisnike.</p>

        <a href="{{ $login_link }}" class="button">Prijavite se kako biste platili račune</a>

        <p>Zahvaljujemo na vašoj pažnji i pravovremenom podmirivanju računa.</p>
    </div>

    <div class="footer">
        S poštovanjem,<br>
        {{ $company_name }}<br>
        {{ $customer_service_contact }}<br>
        <a href="{{ $website_url }}">{{ $website_url }}</a>
    </div>
</div>
</body>
</html>
