-- Advanced Search Database Schema
-- Tables for search history, saved searches, and search analytics

-- Search History Table
CREATE TABLE IF NOT EXISTS mfg_search_history (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    search_query VARCHAR(500) NOT NULL,
    search_filters JSON,
    results_count INT DEFAULT 0,
    search_type ENUM('instant', 'advanced', 'autocomplete') DEFAULT 'instant',
    clicked_products JSON,
    search_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    session_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_user_timestamp (user_id, search_timestamp),
    INDEX idx_search_query (search_query(100)),
    INDEX idx_session (session_id),
    FULLTEXT(search_query)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved Searches Table
CREATE TABLE IF NOT EXISTS mfg_saved_searches (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL,
    search_name VARCHAR(200) NOT NULL,
    search_query VARCHAR(500),
    search_filters JSON,
    is_alert_enabled TINYINT(1) DEFAULT 0,
    alert_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
    last_alert_sent TIMESTAMP NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    modified_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_public TINYINT(1) DEFAULT 0,
    usage_count INT DEFAULT 0,
    last_used TIMESTAMP NULL,
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_user_searches (user_id, deleted),
    INDEX idx_public_searches (is_public, deleted),
    INDEX idx_alert_enabled (is_alert_enabled, alert_frequency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search Analytics Table
CREATE TABLE IF NOT EXISTS mfg_search_analytics (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    search_term VARCHAR(500) NOT NULL,
    search_count INT DEFAULT 1,
    no_results_count INT DEFAULT 0,
    avg_results_count DECIMAL(10,2) DEFAULT 0,
    click_through_rate DECIMAL(5,2) DEFAULT 0,
    most_clicked_products JSON,
    first_searched DATE,
    last_searched DATE,
    trending_score DECIMAL(10,2) DEFAULT 0,
    search_category VARCHAR(100),
    deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_search_term (search_term(255)),
    INDEX idx_trending (trending_score DESC),
    INDEX idx_category (search_category),
    FULLTEXT(search_term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Search Index Enhancement
CREATE TABLE IF NOT EXISTS mfg_product_search_index (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    product_id VARCHAR(36) NOT NULL,
    search_text TEXT NOT NULL,
    search_tokens TEXT,
    category_tokens VARCHAR(500),
    material_tokens VARCHAR(500),
    specification_tokens TEXT,
    tag_tokens VARCHAR(1000),
    popularity_score DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_product (product_id),
    FULLTEXT(search_text),
    FULLTEXT(search_tokens),
    FULLTEXT(specification_tokens),
    INDEX idx_category_tokens (category_tokens(100)),
    INDEX idx_material_tokens (material_tokens(100)),
    INDEX idx_popularity (popularity_score DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Popular Search Terms (for autocomplete)
CREATE TABLE IF NOT EXISTS mfg_popular_searches (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    search_term VARCHAR(200) NOT NULL,
    search_type ENUM('product', 'category', 'material', 'sku', 'manufacturer') DEFAULT 'product',
    search_count INT DEFAULT 1,
    success_rate DECIMAL(5,2) DEFAULT 0,
    last_searched TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_trending TINYINT(1) DEFAULT 0,
    popularity_rank INT DEFAULT 0,
    deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_term_type (search_term, search_type),
    INDEX idx_popularity (popularity_rank, search_count DESC),
    INDEX idx_trending (is_trending, last_searched),
    INDEX idx_type (search_type, success_rate DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Search Suggestions Table
CREATE TABLE IF NOT EXISTS mfg_search_suggestions (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    search_term VARCHAR(200) NOT NULL,
    suggested_term VARCHAR(200) NOT NULL,
    suggestion_type ENUM('correction', 'completion', 'related', 'synonym') DEFAULT 'completion',
    confidence_score DECIMAL(5,2) DEFAULT 0,
    usage_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_search_term (search_term(100)),
    INDEX idx_suggestion_type (suggestion_type, confidence_score DESC),
    INDEX idx_active (is_active, usage_count DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample popular searches for manufacturing
INSERT INTO mfg_popular_searches (search_term, search_type, search_count, success_rate, is_trending, popularity_rank) VALUES
('steel', 'material', 150, 95.5, 1, 1),
('brackets', 'product', 120, 88.2, 1, 2),
('fasteners', 'category', 110, 92.1, 1, 3),
('aluminum', 'material', 98, 90.8, 1, 4),
('bolts', 'product', 89, 87.3, 0, 5),
('screws', 'product', 85, 91.2, 0, 6),
('washers', 'product', 78, 89.7, 0, 7),
('stainless', 'material', 72, 93.4, 0, 8),
('pipes', 'product', 68, 86.9, 0, 9),
('valves', 'product', 65, 88.5, 0, 10),
('fittings', 'category', 62, 90.1, 0, 11),
('gaskets', 'product', 58, 85.7, 0, 12),
('bearings', 'product', 55, 91.8, 0, 13),
('springs', 'product', 52, 87.6, 0, 14),
('clamps', 'product', 48, 89.3, 0, 15);

-- Insert sample search suggestions
INSERT INTO mfg_search_suggestions (search_term, suggested_term, suggestion_type, confidence_score, usage_count) VALUES
('steel', 'stainless steel', 'completion', 95.0, 45),
('steel', 'steel brackets', 'completion', 88.5, 32),
('steel', 'carbon steel', 'completion', 82.3, 28),
('bracket', 'brackets', 'correction', 98.0, 15),
('bolt', 'bolts', 'correction', 97.5, 22),
('scru', 'screws', 'completion', 92.0, 38),
('aluminum', 'aluminium', 'synonym', 85.0, 12),
('fastener', 'fasteners', 'correction', 96.5, 18),
('pipe', 'pipes', 'correction', 95.8, 20),
('valve', 'valves', 'correction', 94.2, 16);

-- Create indexes for optimal search performance
CREATE INDEX idx_products_fulltext ON mfg_products(id, name, sku, description, tags);
CREATE INDEX idx_products_category_material ON mfg_products(category, material, deleted, status);
CREATE INDEX idx_products_price_range ON mfg_products(list_price, deleted, status);
CREATE INDEX idx_products_weight_range ON mfg_products(weight_lbs, deleted, status);
CREATE INDEX idx_products_lead_time ON mfg_products(lead_time_days, deleted, status);
CREATE INDEX idx_products_manufacturer ON mfg_products(manufacturer, deleted, status);

-- Trigger to update product search index when products are modified
DELIMITER //
CREATE TRIGGER update_product_search_index 
AFTER UPDATE ON mfg_products
FOR EACH ROW
BEGIN
    IF NEW.name != OLD.name OR NEW.description != OLD.description OR 
       NEW.sku != OLD.sku OR NEW.tags != OLD.tags OR NEW.specifications != OLD.specifications THEN
        
        INSERT INTO mfg_product_search_index (
            product_id, search_text, search_tokens, category_tokens, 
            material_tokens, specification_tokens, tag_tokens, popularity_score
        ) VALUES (
            NEW.id,
            CONCAT(NEW.name, ' ', COALESCE(NEW.description, ''), ' ', NEW.sku),
            CONCAT(NEW.name, ' ', COALESCE(NEW.description, ''), ' ', NEW.sku, ' ', COALESCE(NEW.tags, '')),
            NEW.category,
            NEW.material,
            COALESCE(NEW.specifications, ''),
            COALESCE(NEW.tags, ''),
            0
        ) ON DUPLICATE KEY UPDATE
            search_text = CONCAT(NEW.name, ' ', COALESCE(NEW.description, ''), ' ', NEW.sku),
            search_tokens = CONCAT(NEW.name, ' ', COALESCE(NEW.description, ''), ' ', NEW.sku, ' ', COALESCE(NEW.tags, '')),
            category_tokens = NEW.category,
            material_tokens = NEW.material,
            specification_tokens = COALESCE(NEW.specifications, ''),
            tag_tokens = COALESCE(NEW.tags, ''),
            last_updated = CURRENT_TIMESTAMP;
    END IF;
END//
DELIMITER ;

-- Stored procedure to rebuild search index
DELIMITER //
CREATE PROCEDURE RebuildProductSearchIndex()
BEGIN
    -- Clear existing index
    DELETE FROM mfg_product_search_index;
    
    -- Rebuild index for all active products
    INSERT INTO mfg_product_search_index (
        product_id, search_text, search_tokens, category_tokens, 
        material_tokens, specification_tokens, tag_tokens, popularity_score
    )
    SELECT 
        id,
        CONCAT(name, ' ', COALESCE(description, ''), ' ', sku),
        CONCAT(name, ' ', COALESCE(description, ''), ' ', sku, ' ', COALESCE(tags, '')),
        category,
        material,
        COALESCE(specifications, ''),
        COALESCE(tags, ''),
        0
    FROM mfg_products 
    WHERE deleted = 0 AND status = 'active';
    
    SELECT 'Product search index rebuilt successfully' as result;
END//
DELIMITER ;

-- View for search analytics dashboard
CREATE VIEW search_analytics_dashboard AS
SELECT 
    ps.search_term,
    ps.search_type,
    ps.search_count,
    ps.success_rate,
    ps.is_trending,
    ps.last_searched,
    COUNT(DISTINCT sh.user_id) as unique_users,
    AVG(sh.results_count) as avg_results,
    COUNT(sh.id) as total_searches_7d
FROM mfg_popular_searches ps
LEFT JOIN mfg_search_history sh ON ps.search_term = sh.search_query 
    AND sh.search_timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND sh.deleted = 0
WHERE ps.deleted = 0
GROUP BY ps.id, ps.search_term, ps.search_type, ps.search_count, ps.success_rate, ps.is_trending, ps.last_searched
ORDER BY ps.popularity_rank ASC, ps.search_count DESC;
