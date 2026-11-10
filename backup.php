<?php
/**
 * CuanLog - 1-Click Database Backup & SQL Exporter
 */
require_once __DIR__ . '/../config/app.php';
require_admin();

$db = getDB();
if (!$db) {
    die("Koneksi database gagal.");
}

$tables = ['users', 'categories', 'products', 'customers', 'transactions', 'transaction_items', 'settings', 'activity_logs'];
$backup_file = "Backup_CuanLog_" . date('Y-m-d_His') . ".sql";

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $backup_file . '"');

echo "-- ========================================================\n";
echo "-- CUANLOG POS - DATABASE BACKUP DUMP\n";
echo "-- Sub-tema: Accelerating Digital Transformation for Business and SMEs\n";
echo "-- Generated at: " . date('Y-m-d H:i:s') . "\n";
echo "-- ========================================================\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    try {
        // Table create statement
        $stmt_create = $db->query("SHOW CREATE TABLE `$table`");
        $create_row = $stmt_create->fetch(PDO::FETCH_NUM);
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo $create_row[1] . ";\n\n";

        // Table data
        $stmt_data = $db->query("SELECT * FROM `$table`");
        $rows = $stmt_data->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            echo "INSERT INTO `$table` VALUES \n";
            $val_rows = [];
            foreach ($rows as $r) {
                $values = array_map(function($val) use ($db) {
                    if ($val === null) return "NULL";
                    return $db->quote($val);
                }, array_values($r));
                $val_rows[] = "(" . implode(", ", $values) . ")";
            }
            echo implode(",\n", $val_rows) . ";\n\n";
        }
    } catch (Exception $e) {
        echo "-- Error dumping table $table: " . $e->getMessage() . "\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
log_activity('Backup Database', "Pengguna mengunduh cadangan database $backup_file");
exit;

