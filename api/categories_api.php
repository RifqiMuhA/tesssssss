<?php
header('Content-Type: application/json');

// Baca isi file JSON
$jsonFile = 'question_categories.json';
$jsonData = file_get_contents($jsonFile);
$data = json_decode($jsonData, true); // decode jadi array

// Kalau ada parameter ?type=Literasi atau ?type=TPS
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    foreach ($data as $group) {
        if (strtolower($group['question_type']) === strtolower($type)) {
            echo json_encode($group);
            exit;
        }
    }
    // Tidak ketemu
    echo json_encode(["error" => "Tipe soal '$type' tidak ditemukan"]);
} else {
    // Tanpa parameter, tampilkan semua
    echo json_encode($data);
}
?>