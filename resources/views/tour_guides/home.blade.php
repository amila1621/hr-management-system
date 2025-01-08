<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TD - Gatepass</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
        integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* General Styles */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #0f1520;
            margin: 0;
        }

        .button-container {
            display: flex;
            gap: 50px;
        }

        .button {
            background-color: #12a6ec;
            color: white;
            padding: 20px 40px;
            border: none;
            font-size: 20px;
            cursor: pointer;
            border-radius: 15px;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: blue;
        }

        /* Logout Button Style */
        .logout-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: #ff4c4c;
            color: white;
            padding: 10px 20px;
            border: none;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
        }

        .logout-button:hover {
            background-color: #e60000;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .button-container {
                flex-direction: column;
                gap: 20px;
            }

            .button {
                width: 100%;
                max-width: 300px;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .button {
                padding: 15px 30px;
                font-size: 18px;
            }
        }
    </style>
</head>

<body>
    <!-- Logout Button -->
    <form action="/logout" method="POST">
        <!-- CSRF Token for security (required in Laravel) -->
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        <button class="logout-button" type="submit">
            <i class="fas fa-sign-out-alt"></i> Logout
        </button>
    </form>


    <div class="button-container">
        <button class="button" onclick="location.href='/guide/work-report'"> Work Report </button>
        <button class="button" onclick="location.href='/guide/report-hours'"> Record Work Hours </button>
    </div>

    <script>
        // JavaScript code for button click actions
        // The location.href changes the current URL to the desired page
    </script>
</body>

</html>
