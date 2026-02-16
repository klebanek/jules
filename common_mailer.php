<?php
/**
 * ELMAR PWA - Wspólne funkcje dla skryptów wysyłki
 */

require_once __DIR__ . '/config.php';

/**
 * Nagłówki CORS i obsługa preflight request
 */
function handleCors() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        die();
    }
}

/**
 * Funkcja wysyłki odpowiedzi JSON
 */
function sendJsonResponse($success, $message, $data = []) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    die();
}

/**
 * Podstawowa walidacja żądania POST i pliku
 */
function validateUpload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Nieprawidłowa metoda żądania');
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, 'Nie przesłano pliku lub wystąpił błąd podczas przesyłania');
    }

    // Walidacja rozmiaru pliku (maksymalnie 10MB)
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($_FILES['file']['size'] > $maxFileSize) {
        sendJsonResponse(false, 'Plik jest zbyt duży (maksymalnie 10MB)');
    }
}

/**
 * Pobranie i sanityzacja danych z formularza
 */
function getFormData() {
    return [
        'worker' => isset($_POST['worker']) ? htmlspecialchars($_POST['worker']) : 'Nieznany',
        'date' => isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d'),
        'totalDocuments' => isset($_POST['totalDocuments']) ? intval($_POST['totalDocuments']) : 0
    ];
}

/**
 * Sanityzacja nazwy pliku
 */
function sanitizeFileName($fileNameRaw, $defaultName, $allowedExtensions) {
    $fileName = basename($fileNameRaw);
    $fileName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $fileName);
    $fileName = preg_replace('/\.+/', '.', $fileName);

    if (empty($fileName) || $fileName === '.') {
        $fileName = $defaultName;
    }

    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExtensions)) {
        $fileName = pathinfo($fileName, PATHINFO_FILENAME) . '.' . $allowedExtensions[0];
    }

    return $fileName;
}

/**
 * Generowanie treści HTML emaila
 */
function generateEmailContent($title, $description, $data, $fileName, $fileSize) {
    $worker = $data['worker'];
    $date = date('d.m.Y', strtotime($data['date']));
    $totalDocuments = $data['totalDocuments'];
    $fileNameSafeHtml = htmlspecialchars($fileName);
    $fileSizeKB = round($fileSize / 1024, 2);

    return "
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
            <h2>📦 ELMAR - {$title}</h2>
        </div>
        <div class=\"content\">
            <p>Witaj,</p>
            <p>{$description}</p>

            <div class=\"info-row\">
                <strong>👤 Magazynier:</strong> {$worker}
            </div>
            <div class=\"info-row\">
                <strong>📅 Data:</strong> {$date}
            </div>
            <div class=\"info-row\">
                <strong>📄 Liczba dokumentów:</strong> {$totalDocuments}
            </div>
            <div class=\"info-row\">
                <strong>📎 Plik:</strong> {$fileNameSafeHtml} ({$fileSizeKB} KB)
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
}

/**
 * Wysyłka maila z załącznikiem
 */
function sendEmailWithAttachment($subject, $emailBody, $attachmentInfo) {
    global $OFFICE_EMAIL, $FROM_EMAIL, $FROM_NAME;

    $boundary = md5(time());
    $fileName = $attachmentInfo['name'];
    $fileType = $attachmentInfo['type'];
    $fileContent = $attachmentInfo['content'];

    $headers = "From: {$FROM_NAME} <{$FROM_EMAIL}>\r\n";
    $headers .= "Reply-To: {$FROM_EMAIL}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    $message = "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $emailBody . "\r\n";

    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: {$fileType}; name=\"{$fileName}\"\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $message .= chunk_split(base64_encode($fileContent)) . "\r\n";
    $message .= "--{$boundary}--";

    return mail($OFFICE_EMAIL, $subject, $message, $headers);
}

/**
 * Logowanie wysyłki
 */
function logMail($worker, $date, $fileName) {
    global $OFFICE_EMAIL;
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
}
?>
