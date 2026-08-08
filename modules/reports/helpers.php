<?php
// Courier Management System - Reports Helper Functions
// Created: 2016-08-08

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}

/**
 * Export data to CSV
 * @param array $data Data to export
 * @param array $headers CSV headers
 * @param string $filename Filename for download
 */
function exportToCSV($data, $headers, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Format date for CSV export
 * @param string $date Date string
 * @return string Formatted date (Y-m-d)
 */
function formatDateForCSV($date) {
    if (empty($date)) {
        return '';
    }
    return date('Y-m-d', strtotime($date));
}

/**
 * Format datetime for CSV export
 * @param string $datetime Datetime string
 * @return string Formatted datetime (Y-m-d H:i:s)
 */
function formatDateTimeForCSV($datetime) {
    if (empty($datetime)) {
        return '';
    }
    return date('Y-m-d H:i:s', strtotime($datetime));
}
