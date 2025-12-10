<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Treatwell Scraper</title>
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .container {
                text-align: center;
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="container">
            <h1>Treatwell Scraper API</h1>
            <p><a href="/admin">Go to Admin</a></p>
            <p><a href="/api/documentation">API Documentation</a></p>
        </div>
    </body>
</html>
