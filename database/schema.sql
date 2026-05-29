CREATE TABLE wp_algq_deals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 deal_id VARCHAR(50),
 seller_name VARCHAR(255),
 address TEXT,
 phone VARCHAR(50),
 email VARCHAR(255),
 status VARCHAR(50),
 asking_price DECIMAL(15,2),
 arv DECIMAL(15,2),
 repair_cost DECIMAL(15,2),
 mao DECIMAL(15,2),
 created_at DATETIME,
 updated_at DATETIME
);

CREATE TABLE wp_algq_buyers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(255),
 email VARCHAR(255),
 phone VARCHAR(50),
 market VARCHAR(255),
 strategy VARCHAR(255),
 cash_available DECIMAL(15,2)
);
