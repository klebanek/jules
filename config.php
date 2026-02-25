<?php
/**
 * ELMAR PWA - Wspólna konfiguracja
 */

// Adres email biura (miejsce docelowe dla dokumentów)
$OFFICE_EMAIL = getenv('ELMAR_OFFICE_EMAIL') ?: 'kontakt@inovit.com.pl';

// Adres nadawcy wiadomości
$FROM_EMAIL = getenv('ELMAR_FROM_EMAIL') ?: 'PWA@elmar.pl';

// Nazwa nadawcy
$FROM_NAME = getenv('ELMAR_FROM_NAME') ?: 'ELMAR PWA System';

/**
 * Opcjonalne ładowanie konfiguracji lokalnej (nadpisuje powyższe)
 * Plik config.local.php powinien być wykluczony z systemu kontroli wersji
 */
if (file_exists(__DIR__ . '/config.local.php')) {
    include __DIR__ . '/config.local.php';
}
