<!DOCTYPE html>
<html>
<head>
    <title>Migration Completed</title>
</head>
<body>
    <h1>Migration Completed</h1>
    <p>The migration process has been completed.</p>

    <h2>Successful Migrations</h2>
    <ul>
        @foreach ($successfulMigrations as $migration)
            <li>{{ $migration }}</li>
        @endforeach
    </ul>

    <h2>Failed Migrations</h2>
    <ul>
        @foreach ($failedMigrations as $migration)
            <li>{{ $migration }}</li>
        @endforeach
    </ul>
</body>
</html>
