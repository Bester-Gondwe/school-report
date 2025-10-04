<?php
require_once __DIR__ . '/../config/database.php';

try {
    $updateRankingsQuery = "
        UPDATE users u
        JOIN (
            SELECT student_id, RANK() OVER (ORDER BY SUM(marks) DESC) AS rank
            FROM marks
            GROUP BY student_id
        ) ranking ON u.id = ranking.student_id
        SET u.rank = ranking.rank;
    ";
    $conn->exec($updateRankingsQuery);
    echo "Rankings updated successfully!";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
