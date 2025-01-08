<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Description Form</title>
</head>
<body>

    <h2>Process Description</h2>

    <form action="{{ route('process.description.claude') }}" method="POST">
        <!-- Add CSRF Token for Security in Laravel -->
        @csrf
        <label for="description">Description ClaudeAI:</label><br>
        <textarea id="description" name="description" rows="6" cols="50" placeholder="Enter your description here..."></textarea><br><br>

        <button type="submit">Submit</button>
    </form>

</body>
</html>
