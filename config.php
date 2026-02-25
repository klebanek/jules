<?php
/**
 * ELMAR PWA - Wspólna konfiguracja
 */

// Adres email biura (miejsce docelowe dla dokumentów)
$OFFICE_EMAIL = 'kontakt@inovit.com.pl';

// Adres nadawcy wiadomości
$FROM_EMAIL = 'PWA@elmar.pl';

// Nazwa nadawcy
$FROM_NAME = 'ELMAR PWA System';

// Dozwolone domeny dla CORS (bez kończącego slasha)
$ALLOWED_ORIGINS = [
    'https://klebanek.github.io',
    'http://localhost',
    'http://localhost:8000',
    'http://127.0.0.1',
    'http://127.0.0.1:8000'
];
