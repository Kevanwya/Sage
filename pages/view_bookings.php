<?php
require_once '../logic/db.php';

$result = $conn->query("SELECT * FROM bookings ORDER BY date, time");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sage - View Bookings</title>
</head>
<body>
    
<h2>All Bookings</h2>

<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Tutor</th>
            <th>Date</th>
            <th>Time</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['tutor']) ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['time']) ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
