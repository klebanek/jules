<?php
/**
 * ELMAR PWA - Skrypt wysyłki pliku PDF mailem
 */

require_once __DIR__ . '/common_mailer.php';

// Obsługa CORS
handleCors();

// Walidacja przesłanego pliku
validateUpload();

// Pobranie danych z formularza
$formData = getFormData();

// Informacje o pliku
$fileTmpPath = $_FILES['file']['tmp_name'];
$fileNameRaw = $_FILES['file']['name'];
$fileSize = $_FILES['file']['size'];
$fileType = $_FILES['file']['type'];

// Sanityzacja nazwy pliku i walidacja typu
$fileName = sanitizeFileName($fileNameRaw, 'document.pdf', ['pdf']);

$allowedTypes = [
    'application/pdf'
];

if (!in_array($fileType, $allowedTypes)) {
    sendJsonResponse(false, 'Nieprawidłowy typ pliku - oczekiwano PDF');
}

// Odczytanie zawartości pliku
$fileContent = file_get_contents($fileTmpPath);
if ($fileContent === false) {
    sendJsonResponse(false, 'Nie można odczytać pliku');
}

// Przygotowanie treści maila
$subject = "ELMAR - Dokumenty wydania PDF z dnia " . date('d.m.Y', strtotime($formData['date']));
$emailBody = generateEmailContent(
    'Dokumenty Wydania PDF',
    'W załączniku znajduje się plik PDF z dokumentami wydania produktów z magazynu.',
    $formData,
    $fileName,
    $fileSize
);

// Wysyłka maila
$attachmentInfo = [
    'name' => $fileName,
    'type' => $fileType,
    'content' => $fileContent
];

$mailSent = sendEmailWithAttachment($subject, $emailBody, $attachmentInfo);

if ($mailSent) {
    logMail($formData['worker'], $formData['date'], $fileName);
    
    sendJsonResponse(true, 'Email z PDF został wysłany pomyślnie', [
        'to' => $OFFICE_EMAIL,
        'filename' => $fileName,
        'size' => $fileSize
    ]);
} else {
    sendJsonResponse(false, 'Błąd podczas wysyłania emaila. Spróbuj ponownie później.');
}
