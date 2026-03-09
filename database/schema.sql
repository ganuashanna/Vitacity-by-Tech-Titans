-- VITACITY DATABASE SCHEMA
-- Team Tech Titan - SAMVED 2026
-- PostgreSQL Schema for Supabase

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Users table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(50) DEFAULT 'citizen',
    reward_points INTEGER DEFAULT 0,
    tier VARCHAR(50) DEFAULT 'Bronze',
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Zones table
CREATE TABLE zones (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    total_slots INTEGER NOT NULL,
    base_price_per_hour DECIMAL(10, 2) NOT NULL,
    dynamic_price_per_hour DECIMAL(10, 2),
    congestion_level VARCHAR(50) DEFAULT 'Low',
    rating DECIMAL(3, 2) DEFAULT 4.0,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Parking slots table
CREATE TABLE parking_slots (
    id VARCHAR(50) PRIMARY KEY,
    zone_id VARCHAR(10) REFERENCES zones(id),
    slot_number INTEGER NOT NULL,
    slot_type VARCHAR(50) DEFAULT 'Standard',
    state VARCHAR(50) DEFAULT 'available',
    sensor_status BOOLEAN DEFAULT TRUE,
    qr_code VARCHAR(100) UNIQUE,
    last_occupied_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Vehicles table
CREATE TABLE vehicles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id),
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type VARCHAR(50) NOT NULL,
    model VARCHAR(100),
    color VARCHAR(50),
    qr_code VARCHAR(100) UNIQUE,
    total_trips INTEGER DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    registered_at TIMESTAMP DEFAULT NOW()
);

-- Bookings table
CREATE TABLE bookings (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    booking_code VARCHAR(20) UNIQUE NOT NULL,
    user_id UUID REFERENCES users(id),
    vehicle_id UUID REFERENCES vehicles(id),
    zone_id VARCHAR(10) REFERENCES zones(id),
    slot_id VARCHAR(50) REFERENCES parking_slots(id),
    booked_at TIMESTAMP DEFAULT NOW(),
    scheduled_start TIMESTAMP,
    actual_start TIMESTAMP,
    scheduled_end TIMESTAMP,
    actual_end TIMESTAMP,
    duration_minutes INTEGER,
    hourly_rate DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2),
    payment_status VARCHAR(50) DEFAULT 'pending',
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Complaints table
CREATE TABLE complaints (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    complaint_code VARCHAR(20) UNIQUE NOT NULL,
    user_id UUID REFERENCES users(id),
    zone_id VARCHAR(10) REFERENCES zones(id),
    slot_id VARCHAR(50) REFERENCES parking_slots(id),
    type VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority VARCHAR(50) DEFAULT 'Medium',
    status VARCHAR(50) DEFAULT 'open',
    ai_category VARCHAR(100),
    ai_priority VARCHAR(50),
    requires_maintenance BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- Complaint patterns (for AI Memory agent)
CREATE TABLE complaint_patterns (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    zone_id VARCHAR(10),
    slot_id VARCHAR(50),
    complaint_type VARCHAR(100),
    occurrence_count INTEGER DEFAULT 1,
    last_occurrence TIMESTAMP,
    pattern_detected BOOLEAN DEFAULT FALSE,
    maintenance_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

-- AI Predictions table (for AI Decision agent)
CREATE TABLE ai_predictions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    prediction_type VARCHAR(100) NOT NULL,
    zone_id VARCHAR(10) REFERENCES zones(id),
    predicted_at TIMESTAMP DEFAULT NOW(),
    prediction_for TIMESTAMP NOT NULL,
    confidence_score DECIMAL(5, 4),
    predicted_occupancy_percent INTEGER,
    predicted_congestion_level VARCHAR(50),
    created_at TIMESTAMP DEFAULT NOW()
);

-- AI Actions table (for AI Action agent)
CREATE TABLE ai_actions (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    action_type VARCHAR(100) NOT NULL,
    zone_id VARCHAR(10) REFERENCES zones(id),
    action_description TEXT NOT NULL,
    triggered_by VARCHAR(100),
    status VARCHAR(50) DEFAULT 'pending',
    executed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);

-- AI Learning data (for AI Memory agent)
CREATE TABLE ai_learning_data (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    zone_id VARCHAR(10),
    day_of_week INTEGER,
    hour_of_day INTEGER,
    is_weekend BOOLEAN,
    avg_occupancy DECIMAL(5, 2),
    avg_duration_minutes INTEGER,
    total_bookings INTEGER,
    total_complaints INTEGER,
    pattern_strength DECIMAL(5, 4),
    aggregated_for DATE NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Alerts table
CREATE TABLE alerts (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    alert_type VARCHAR(100) NOT NULL,
    severity VARCHAR(50) NOT NULL,
    zone_id VARCHAR(10) REFERENCES zones(id),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX idx_bookings_user ON bookings(user_id);
CREATE INDEX idx_bookings_zone ON bookings(zone_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_complaints_zone ON complaints(zone_id);
CREATE INDEX idx_complaints_status ON complaints(status);
CREATE INDEX idx_slots_zone ON parking_slots(zone_id);
CREATE INDEX idx_slots_state ON parking_slots(state);
CREATE INDEX idx_predictions_zone ON ai_predictions(zone_id);
CREATE INDEX idx_learning_zone ON ai_learning_data(zone_id);