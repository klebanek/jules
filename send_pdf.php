<?php
/**
 * ELMAR PWA - Skrypt wysyłki pliku PDF mailem
 * 
 * Skrypt odbiera plik PDF z PWA i wysyła go mailem na wskazany adres
 */

// Konfiguracja
$OFFICE_EMAIL = 'kontakt@inovit.com.pl'; // ZMIEŃ NA WŁAŚCIWY ADRES EMAIL BIURA
$FROM_EMAIL = 'PWA@elmar.pl';      // ZMIEŃ NA WŁAŚCIWY ADRES NADAWCY
$FROM_NAME = 'ELMAR PWA System';

// Nagłówki CORS (jeśli PWA jest na innej domenie)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Obsługa preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Funkcja wysyłki odpowiedzi JSON
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Sprawdzenie metody żądania
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Nieprawidłowa metoda żądania');
}

// Sprawdzenie czy plik został wysłany
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    sendJsonResponse(false, 'Nie przesłano pliku lub wystąpił błąd podczas przesyłania');
}

// Pobranie danych z formularza
$worker = isset($_POST['worker']) ? htmlspecialchars($_POST['worker']) : 'Nieznany';
$date = isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d');
$totalDocuments = isset($_POST['totalDocuments']) ? intval($_POST['totalDocuments']) : 0;

// Informacje o pliku
$fileTmpPath = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$fileSize = $_FILES['file']['size'];
$fileType = $_FILES['file']['type'];

// Walidacja typu pliku
$allowedTypes = [
    'application/pdf'
];

if (!in_array($fileType, $allowedTypes)) {
    sendJsonResponse(false, 'Nieprawidłowy typ pliku - oczekiwano PDF');
}

// Walidacja rozmiaru pliku (maksymalnie 10MB)
$maxFileSize = 10 * 1024 * 1024; // 10MB
if ($fileSize > $maxFileSize) {
    sendJsonResponse(false, 'Plik jest zbyt duży (maksymalnie 10MB)');
}

// Odczytanie zawartości pliku
$fileContent = file_get_contents($fileTmpPath);
if ($fileContent === false) {
    sendJsonResponse(false, 'Nie można odczytać pliku');
}

// Przygotowanie załącznika
$attachment = chunk_split(base64_encode($fileContent));

// Przygotowanie treści maila
$boundary = md5(time());

$subject = "ELMAR - Dokumenty wydania PDF z dnia " . date('d.m.Y', strtotime($date));

$emailBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #046276; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f8f9fa; padding: 20px; border: 1px solid #ddd; }
        .info-row { margin: 10px 0; padding: 10px; background: white; border-left: 3px solid #046276; }
        .footer { background: #046276; color: white; padding: 10px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px; }
        strong { color: #046276; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h2>📦 ELMAR - Dokumenty Wydania PDF</h2>
        </div>
        <div class=\"content\">
            <p>Witaj,</p>
            <p>W załączniku znajduje się plik PDF z dokumentami wydania produktów z magazynu.</p>
            
            <div class=\"info-row\">
                <strong>👤 Magazynier:</strong> {$worker}
            </div>
            <div class=\"info-row\">
                <strong>📅 Data:</strong> " . date('d.m.Y', strtotime($date)) . "
            </div>
            <div class=\"info-row\">
                <strong>📄 Liczba dokumentów:</strong> {$totalDocuments}
            </div>
            <div class=\"info-row\">
                <strong>📎 Plik:</strong> {$fileName} (" . round($fileSize / 1024, 2) . " KB)
            </div>
            
            <p style=\"margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 3px solid #ffc107;\">
                <strong>ℹ️ Uwaga:</strong> Dokument został wygenerowany automatycznie przez system ELMAR PWA.
            </p>
        </div>
        <div class=\"footer\">
            <p>ELMAR - System Śledzenia Produktów Rybnych</p>
            <p>Wiadomość wygenerowana automatycznie - nie odpowiadaj na ten email</p>
        </div>
    </div>
</body>
</html>
";

// Przygotowanie nagłówków maila
$headers = "From: {$FROM_NAME} <{$FROM_EMAIL}>\r\n";
$headers .= "Reply-To: {$FROM_EMAIL}\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

// Treść maila
$message = "--{$boundary}\r\n";
$message .= "Content-Type: text/html; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= $emailBody . "\r\n";

// Załącznik
$message .= "--{$boundary}\r\n";
$message .= "Content-Type: {$fileType}; name=\"{$fileName}\"\r\n";
$message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
$message .= "Content-Transfer-Encoding: base64\r\n\r\n";
$message .= $attachment . "\r\n";
$message .= "--{$boundary}--";

// Wysyłka maila
$mailSent = mail($OFFICE_EMAIL, $subject, $message, $headers);

if ($mailSent) {
    // Opcjonalnie: Zapisz log wysyłki
    $logFile = __DIR__ . '/logs/email_log.txt';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logEntry = sprintf(
        "[%s] Email wysłany do: %s | Pracownik: %s | Data: %s | Plik: %s\n",
        date('Y-m-d H:i:s'),
        $OFFICE_EMAIL,
        $worker,
        $date,
        $fileName
    );
    
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    sendJsonResponse(true, 'Email z PDF został wysłany pomyślnie', [
        'to' => $OFFICE_EMAIL,
        'filename' => $fileName,
        'size' => $fileSize
    ]);
} else {
    sendJsonResponse(false, 'Błąd podczas wysyłania emaila. Spróbuj ponownie później.');
}
?>
