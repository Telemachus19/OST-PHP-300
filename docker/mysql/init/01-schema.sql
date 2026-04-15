CREATE TABLE IF NOT EXISTS countries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS skills (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO countries (name) VALUES
    ('Egypt'),
    ('Saudi Arabia'),
    ('United Arab Emirates'),
    ('Jordan'),
    ('Kuwait'),
    ('Qatar'),
    ('Bahrain'),
    ('Oman'),
    ('Lebanon'),
    ('Syria'),
    ('Iraq'),
    ('Palestine'),
    ('Sudan'),
    ('Libya'),
    ('Morocco'),
    ('Algeria'),
    ('Tunisia'),
    ('United States'),
    ('United Kingdom'),
    ('Canada'),
    ('Australia'),
    ('India'),
    ('Pakistan'),
    ('Bangladesh'),
    ('China'),
    ('Japan'),
    ('Germany'),
    ('France');

INSERT IGNORE INTO skills (name) VALUES
    ('PHP'),
    ('J2SE'),
    ('MySQL'),
    ('PostgreSQL');

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(32) NOT NULL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    country_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    profile_picture_path VARCHAR(255) NULL,
    address TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_users_country_id (country_id),
    INDEX idx_users_department_id (department_id),
    CONSTRAINT fk_users_country FOREIGN KEY (country_id) REFERENCES countries(id),
    CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_skills (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uk_user_skill (user_id, skill_id),
    INDEX idx_user_skills_skill_id (skill_id),
    CONSTRAINT fk_user_skills_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_skills_skill FOREIGN KEY (skill_id) REFERENCES skills(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
