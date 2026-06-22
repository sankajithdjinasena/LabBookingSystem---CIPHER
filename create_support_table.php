<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
$pdo = get_db_connection();
$sql = "
CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NULL, -- NULL means sent to 'Admin/Faculty general queue'
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_support_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_support_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE
);
";
$pdo->exec($sql);
echo "Table created successfully\n";
