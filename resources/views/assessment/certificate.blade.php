<html>
<head>
    <style>
        body, html {
            margin: 0;
            padding: 0;
        }

        body {
            color: black;
            display: table;
            font-family: Georgia, serif;
            font-size: 24px;
            text-align: center;
            -webkit-print-color-adjust: exact !important;
        }

        .container {
            margin-top: 45px;
            margin-left: 10px;
            width: 1100px;
            height: 700px;
            vertical-align: middle;
            background-image: url('certificate/img//background.jpg');
            background-size: 100% 100%;
        }

        .logo {
            padding-top: 80px;
            color: #3c8dbc;
        }

        .logo div {
            float: left;
            font-weight: bolder;
        }

        .logo img {
            width: 100px;
            height: 60px;
        }

        .marquee {
            color: #3c8dbc;
            font-size: 48px;
            margin: 20px;
        }

        .assignment {
            margin: 20px;
        }

        .person {
            border-bottom: 2px solid black;
            font-size: 32px;
            font-style: oblique;
            margin: 20px auto;
            width: 400px;
        }

        .certified {
            margin-top: 100px;
        }

        .certified img {
            width: 100px;
            height: 50px;
        }

        .verify-table {
            border-collapse: collapse;
            width: 100%;
            height: 120px;
        }

        .verify-table td {
            height: 103px;
            text-align: center;
        }

        .reason {
            margin: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo" style="width: 100%">
        <div style="width: 20%; text-align: right"><img src="certificate/img/logo.png"></div>
        <div style="width: 60%;">JUSC Math Olympiad 2021</div>
        <div style="width: 20%; text-align: left"><img src="certificate/img/tafuri_logo.png"></div>
    </div>

    <div class="marquee">
        Certificate of Completion
    </div>

    <div class="assignment">
        This certificate is presented to
    </div>

    <div class="person">
        {{ $name }}
    </div>

    <div class="reason">
        For his exemplary performance in the <br>Math Olympiad 2021
    </div>
    <div class="certified">
        <table class="verify-table" border="0">
            <tbody>
            <tr>
                <td style="width: 10%;"></td>
                <td style="width: 30%;">
                    <img src="certificate/img/ts.png">
                    <hr/>
                    <b>Shakir Uz Zaman Shuvo</b><br>
                    Organizer
                </td>
                <td style="width: 20%;"></td>
                <td style="width: 30%;">
                    <img src="certificate/img/ts2.png">
                    <hr/>
                    <b>Hemayet Ullah Nirjhoy</b><br>
                    Certified
                </td>
                <td style="width: 10%;"></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
