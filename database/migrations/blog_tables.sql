-- ============================================================
--  Blog & Advertisement Platform — Database Migration
--  Run once against: ecommerce_db
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. Add premium flag to existing users table
-- ------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS is_premium TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = paid advertiser who can attach product ads to posts';

-- ------------------------------------------------------------
-- 2. Blog Posts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS posts (
    id          INT(11)          NOT NULL AUTO_INCREMENT,
    user_id     INT(11)          NOT NULL,
    title       VARCHAR(255)     NOT NULL,
    slug        VARCHAR(275)     NOT NULL UNIQUE,
    excerpt     VARCHAR(500)     NOT NULL DEFAULT '',
    content     LONGTEXT         NOT NULL,
    image       VARCHAR(300)     NOT NULL DEFAULT '',
    status      ENUM('draft','published') NOT NULL DEFAULT 'draft',
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_posts_user   (user_id),
    KEY idx_posts_status (status),
    KEY idx_posts_created(created_at),
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Comments (supports 1-level thread replies via parent_id)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
    id          INT(11)          NOT NULL AUTO_INCREMENT,
    post_id     INT(11)          NOT NULL,
    user_id     INT(11)          NOT NULL,
    parent_id   INT(11)                  DEFAULT NULL,
    body        TEXT             NOT NULL,
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_post   (post_id),
    KEY idx_comments_user   (user_id),
    KEY idx_comments_parent (parent_id),
    CONSTRAINT fk_comments_post   FOREIGN KEY (post_id)   REFERENCES posts(id)    ON DELETE CASCADE,
    CONSTRAINT fk_comments_user   FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_comments_parent FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Likes  (many-to-many: users ↔ posts)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS likes (
    user_id     INT(11) NOT NULL,
    post_id     INT(11) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    KEY idx_likes_post (post_id),
    CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Post–Product Advertisement Mapping
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS post_products (
    post_id    INT(11) NOT NULL,
    product_id INT(11) NOT NULL,
    sort_order TINYINT NOT NULL DEFAULT 0,
    PRIMARY KEY (post_id, product_id),
    CONSTRAINT fk_pp_post    FOREIGN KEY (post_id)    REFERENCES posts(id)    ON DELETE CASCADE,
    CONSTRAINT fk_pp_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Quick sanity-check: should return 4 rows
-- ============================================================
-- SELECT table_name FROM information_schema.tables
-- WHERE table_schema = 'ecommerce_db'
--   AND table_name IN ('posts','comments','likes','post_products');
