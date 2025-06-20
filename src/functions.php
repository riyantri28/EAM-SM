<?php
function logAktivitas($pdo, $aktivitas, $status = 'success') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO log_aktivitas (aktivitas, status, user_id) 
            VALUES (:aktivitas, :status, :user_id)
        ");
        
        $stmt->execute([
            ':aktivitas' => $aktivitas,
            ':status' => $status,
            ':user_id' => $_SESSION['user']['id'] ?? null
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error logging activity: " . $e->getMessage());
        return false;
    }
}