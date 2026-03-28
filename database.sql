-- ============================================================
--  VOTING APPLICATION — Database Schema
-- ============================================================
CREATE DATABASE IF NOT EXISTS voting_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE voting_app;

CREATE TABLE IF NOT EXISTS voters (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120)  NOT NULL,
    national_id   VARCHAR(50)   NOT NULL UNIQUE,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    has_voted     TINYINT(1)    NOT NULL DEFAULT 0,
    voted_at      DATETIME      NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS candidates (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120)  NOT NULL,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    seat          VARCHAR(120)  NOT NULL,
    party         VARCHAR(120)  NOT NULL,
    manifesto     TEXT          NULL,
    photo         VARCHAR(255)  NULL,
    status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    voter_id      INT           NOT NULL,
    candidate_id  INT           NOT NULL,
    seat          VARCHAR(120)  NOT NULL,
    voted_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_voter_seat (voter_id, seat),
    FOREIGN KEY (voter_id)     REFERENCES voters(id)     ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(60)   NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: username=admin  password=Admin@1234
INSERT IGNORE INTO admins (username, password_hash)
VALUES ('admin', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
