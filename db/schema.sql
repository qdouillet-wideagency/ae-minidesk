-- MiniDesk - Schéma de base de données
-- Ne pas modifier ce fichier (il est initialisé automatiquement par Docker)

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS minidesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE minidesk;

CREATE TABLE IF NOT EXISTS tickets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    description TEXT,
    priority    ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    status      ENUM('open', 'in_progress', 'closed') NOT NULL DEFAULT 'open',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Données de démo
INSERT INTO tickets (title, description, priority, status) VALUES
('Serveur SMTP hors ligne', 'Les emails transactionnels ne partent plus depuis 14h. Impact : relances clients bloquées.', 'critical', 'open'),
('Mise à jour certificat SSL', 'Le certificat de api.internal.local expire dans 12 jours.', 'high', 'in_progress'),
('Erreur 500 sur /admin/users', 'Reproductible uniquement avec le rôle "manager". Stack trace en pièce jointe.', 'high', 'open'),
('Nettoyage logs Apache', 'Les logs de /var/log/apache2 dépassent 40 Go sur prod-03.', 'low', 'open'),
('Accès VPN refusé pour Julie M.', 'Depuis la migration AD du 02/06, son compte ne passe plus l''authentification.', 'medium', 'closed');
