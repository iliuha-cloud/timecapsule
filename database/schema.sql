-- TimeCapsule database schema (PostgreSQL 16).
-- Safe to run many times: it only creates what is missing.

CREATE TABLE IF NOT EXISTS users (
    id            SERIAL PRIMARY KEY,
    email         VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP NOT NULL DEFAULT (NOW() AT TIME ZONE 'UTC')
);

CREATE TABLE IF NOT EXISTS capsules (
    id              SERIAL PRIMARY KEY,
    user_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title           VARCHAR(120) NOT NULL,
    message         TEXT NOT NULL,
    open_at         TIMESTAMP NOT NULL,
    is_public       BOOLEAN NOT NULL DEFAULT FALSE,
    recipient_email VARCHAR(255),
    file_path       VARCHAR(255),
    file_name       VARCHAR(255),
    file_mime       VARCHAR(100),
    opened_at       TIMESTAMP,
    created_at      TIMESTAMP NOT NULL DEFAULT (NOW() AT TIME ZONE 'UTC')
);

CREATE INDEX IF NOT EXISTS capsules_user_id_idx ON capsules (user_id);
CREATE INDEX IF NOT EXISTS capsules_open_at_idx ON capsules (open_at);

CREATE TABLE IF NOT EXISTS notifications (
    id         SERIAL PRIMARY KEY,
    capsule_id INTEGER NOT NULL REFERENCES capsules(id) ON DELETE CASCADE,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    recipient  VARCHAR(255) NOT NULL,
    message    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT (NOW() AT TIME ZONE 'UTC')
);

CREATE INDEX IF NOT EXISTS notifications_user_id_idx ON notifications (user_id);

-- Built-in "Guest" user, used when AUTH_ENABLED=false.
-- The password hash is not a valid bcrypt hash, so nobody can log in as guest.
INSERT INTO users (id, email, password_hash)
VALUES (1, 'guest@timecapsule.local', '!')
ON CONFLICT (id) DO NOTHING;

-- Make sure new users get ids after the guest user.
SELECT setval('users_id_seq', GREATEST((SELECT MAX(id) FROM users), 1));
