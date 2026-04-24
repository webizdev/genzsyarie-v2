CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    whatsapp_number VARCHAR(50) NOT NULL,
    corporate_address TEXT NOT NULL,
    business_activity TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    payment_proof VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (whatsapp_number)
);
