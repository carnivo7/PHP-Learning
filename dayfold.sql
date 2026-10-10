CREATE DATABASE dayfold
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- users table
CREATE TABLE dayfold.users (
  id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  uuid                  CHAR(36)        NOT NULL,
  first_name            VARCHAR(80)     NOT NULL,
  last_name             VARCHAR(80)     NOT NULL,
  email                 VARCHAR(254)    NOT NULL,
  phone                 VARCHAR(20)     NULL,
  password              VARCHAR(255)    NOT NULL,
  signup_ip             VARBINARY(16)   NULL,
  email_verified_at     DATETIME        NULL,
  phone_verified_at     DATETIME        NULL,
  remember_token        CHAR(64)        NULL,
  password_updated_at   DATETIME        NULL,
  failed_attempts       INT UNSIGNED    NOT NULL DEFAULT 0,
  locked_until          DATETIME        NULL,
  last_login_at         DATETIME        NULL,
  last_login_ip         VARBINARY(16)   NULL,
  status                ENUM('active','disabled','banned') NOT NULL DEFAULT 'active',
  created_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at            TIMESTAMP       NULL,

  email_active VARCHAR(254)
    AS (IF(deleted_at IS NULL, email, NULL)) STORED,
  phone_active VARCHAR(20)
    AS (IF(deleted_at IS NULL AND phone IS NOT NULL, phone, NULL)) STORED,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_uuid (uuid),
  UNIQUE KEY uq_users_email_active (email_active),
  UNIQUE KEY uq_users_phone_active (phone_active),
  KEY idx_users_status (status),
  KEY idx_users_locked (locked_until, status),

  CONSTRAINT chk_users_failed_attempts CHECK (failed_attempts >= 0)
) ENGINE=InnoDB;