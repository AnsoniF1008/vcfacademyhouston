-- RBAC: roles and activity log
-- Roles: super_admin (full), editor_coach (scores, MOTM, roster edit no delete), staff_campo (Star of the Month, live score only)

ALTER TABLE admin_users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'super_admin' AFTER password_hash;
ALTER TABLE admin_users ADD COLUMN email VARCHAR(255) NULL AFTER role;
UPDATE admin_users SET role = 'super_admin' WHERE role = '' OR role IS NULL;

CREATE TABLE IF NOT EXISTS admin_activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    username VARCHAR(50) NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at),
    INDEX idx_user (user_id)
);
