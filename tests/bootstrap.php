<?php

// Les tests utilisent exclusivement SQLite en mémoire : on neutralise
// toute configuration MySQL pour qu'aucune connexion réelle ne soit possible.
putenv('DB_HOST=invalid.invalid');
putenv('DB_PASS=');

require_once __DIR__ . '/../app/TicketRepository.php';
