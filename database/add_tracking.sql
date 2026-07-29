-- ============================================================
-- KGF Mens Wear — Delivery Tracking Migration
-- Run this ONCE in phpMyAdmin or MySQL CLI
-- ============================================================
USE demoproject_db;

-- Add tracking columns to orders table
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(60) DEFAULT NULL AFTER razorpay_payment_id,
    ADD COLUMN IF NOT EXISTS estimated_delivery DATE DEFAULT NULL AFTER tracking_number;

-- Create order_tracking_logs table for event history timeline
CREATE TABLE IF NOT EXISTS order_tracking_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL,
    location VARCHAR(150) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tracking_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
