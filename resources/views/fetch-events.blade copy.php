<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Calendar Events</title>
</head>

<body>
    <h1>Fetch Google Calendar Events</h1>
    <form action="{{ route('dashboard') }}" method="get">
        <button type="submit">Proceed</button>
    </form>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif
</body>

</html>
