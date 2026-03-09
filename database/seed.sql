-- VITACITY SAMPLE DATA
-- Team Tech Titan - SAMVED 2026
-- Sample data for Solapur zones

-- Insert Solapur zones
INSERT INTO zones (id, name, latitude, longitude, total_slots, base_price_per_hour, dynamic_price_per_hour, congestion_level) VALUES
('Z1', 'Siddheshwar Peth Market', 17.6599, 75.9064, 80, 20.00, 30.00, 'Medium'),
('Z2', 'Civil Hospital Area', 17.6714, 75.9101, 60, 25.00, 37.50, 'High'),
('Z3', 'Railway Station Parking', 17.6714, 75.9104, 70, 30.00, 45.00, 'High'),
('Z4', 'Navi Peth Market', 17.6734, 75.9123, 50, 20.00, 24.00, 'Low'),
('Z5', 'Ashwini Hospital', 17.6654, 75.8989, 45, 25.00, 30.00, 'Medium'),
('Z6', 'Bus Stand Area', 17.6799, 75.9134, 55, 22.00, 26.40, 'Low');

-- Insert parking slots for each zone
INSERT INTO parking_slots (id, zone_id, slot_number, slot_type, state, sensor_status, qr_code)
SELECT 
    CONCAT(z.id, '-S', s.slot_num),
    z.id,
    s.slot_num,
    CASE WHEN s.slot_num % 10 = 0 THEN 'Disabled' ELSE 'Standard' END,
    CASE 
        WHEN s.slot_num <= (z.total_slots * 0.3) THEN 'occupied'
        WHEN s.slot_num <= (z.total_slots * 0.4) THEN 'reserved'
        ELSE 'available'
    END,
    TRUE,
    CONCAT('QR-', z.id, '-', LPAD(s.slot_num::TEXT, 3, '0'))
FROM zones z
CROSS JOIN LATERAL generate_series(1, z.total_slots) AS s(slot_num);

-- Insert sample users
INSERT INTO users (id, email, full_name, phone, role, reward_points, tier) VALUES
(uuid_generate_v4(), 'ganesh@techitan.com', 'Ganesh Ashanna', '7038170075', 'admin', 500, 'Gold'),
(uuid_generate_v4(), 'mohit@techitan.com', 'Mohit Kadam', '7020764688', 'admin', 450, 'Gold'),
(uuid_generate_v4(), 'pratik@techitan.com', 'Pratik Hiwale', '7756899203', 'admin', 400, 'Silver'),
(uuid_generate_v4(), 'aditya@techitan.com', 'Aditya Sewlikar', '7823865650', 'admin', 350, 'Silver'),
(uuid_generate_v4(), 'srushti@techitan.com', 'Srushti Murkute', '8500022489', 'admin', 300, 'Bronze'),
(uuid_generate_v4(), 'citizen1@vitacity.com', 'Ramesh Patil', '9876543210', 'citizen', 150, 'Bronze'),
(uuid_generate_v4(), 'citizen2@vitacity.com', 'Priya Sharma', '9876543211', 'citizen', 200, 'Silver');

-- Insert sample vehicles
INSERT INTO vehicles (id, user_id, license_plate, vehicle_type, model, color, qr_code, total_trips)
SELECT 
    uuid_generate_v4(),
    u.id,
    CONCAT('MH', FLOOR(RANDOM() * 50 + 1)::INT, '-', CHR(65 + FLOOR(RANDOM() * 26)::INT), CHR(65 + FLOOR(RANDOM() * 26)::INT), FLOOR(RANDOM() * 9000 + 1000)::INT),
    CASE WHEN RANDOM() > 0.5 THEN 'Two-Wheeler' ELSE 'Four-Wheeler' END,
    CASE WHEN RANDOM() > 0.5 THEN 'Honda Activa' ELSE 'Maruti Swift' END,
    CASE FLOOR(RANDOM() * 5)::INT 
        WHEN 0 THEN 'Black'
        WHEN 1 THEN 'White'
        WHEN 2 THEN 'Red'
        WHEN 3 THEN 'Blue'
        ELSE 'Silver'
    END,
    CONCAT('VH-', LPAD(FLOOR(RANDOM() * 10000)::TEXT, 4, '0')),
    FLOOR(RANDOM() * 50)::INT
FROM users u;

-- Insert AI learning data (historical patterns)
INSERT INTO ai_learning_data (zone_id, day_of_week, hour_of_day, is_weekend, avg_occupancy, avg_duration_minutes, total_bookings, total_complaints, pattern_strength, aggregated_for)
SELECT 
    z.id,
    dow,
    hour,
    CASE WHEN dow IN (5, 6) THEN TRUE ELSE FALSE END,
    CASE 
        WHEN dow IN (5, 6) THEN 65 + RANDOM() * 20
        WHEN hour BETWEEN 9 AND 11 THEN 75 + RANDOM() * 15
        WHEN hour BETWEEN 17 AND 19 THEN 80 + RANDOM() * 15
        ELSE 40 + RANDOM() * 20
    END,
    60 + FLOOR(RANDOM() * 120)::INT,
    FLOOR(RANDOM() * 30)::INT + 10,
    FLOOR(RANDOM() * 5)::INT,
    0.6 + RANDOM() * 0.3,
    CURRENT_DATE - INTERVAL '7 days'
FROM zones z
CROSS JOIN generate_series(0, 6) AS dow
CROSS JOIN generate_series(6, 22) AS hour;

-- Insert sample complaint patterns
INSERT INTO complaint_patterns (zone_id, slot_id, complaint_type, occurrence_count, last_occurrence, pattern_detected, maintenance_required)
VALUES
('Z1', 'Z1-S015', 'Sensor Fault', 3, NOW() - INTERVAL '2 days', TRUE, TRUE),
('Z2', 'Z2-S023', 'QR Unreadable', 2, NOW() - INTERVAL '1 day', TRUE, TRUE),
('Z3', 'Z3-S045', 'Wrong Parking', 5, NOW() - INTERVAL '3 hours', TRUE, FALSE),
('Z4', 'Z4-S010', 'Sensor Fault', 2, NOW() - INTERVAL '5 hours', TRUE, TRUE);

-- Insert sample alerts
INSERT INTO alerts (alert_type, severity, zone_id, title, message, status)
VALUES
('overflow', 'critical', 'Z2', 'Civil Hospital Overflow Alert', 'Zone at 92% capacity - immediate action needed', 'active'),
('maintenance', 'warning', 'Z1', 'Sensor Maintenance Required', 'Multiple sensor faults detected in slots 15-18', 'active'),
('pricing', 'info', 'Z3', 'Dynamic Pricing Active', 'Price increased to ₹45/hour due to high demand', 'resolved');

-- Insert sample bookings (recent)
INSERT INTO bookings (booking_code, user_id, vehicle_id, zone_id, slot_id, scheduled_start, scheduled_end, hourly_rate, status)
SELECT 
    CONCAT('BK-', LPAD(gs::TEXT, 6, '0')),
    u.id,
    v.id,
    z.id,
    ps.id,
    NOW() - (INTERVAL '1 hour' * (10 - gs)),
    NOW() + (INTERVAL '1 hour' * gs),
    z.base_price_per_hour,
    CASE WHEN gs % 3 = 0 THEN 'active' WHEN gs % 3 = 1 THEN 'completed' ELSE 'pending' END
FROM generate_series(1, 20) AS gs
CROSS JOIN LATERAL (SELECT id FROM zones ORDER BY RANDOM() LIMIT 1) AS z
CROSS JOIN LATERAL (SELECT id FROM parking_slots WHERE zone_id = z.id AND state = 'occupied' ORDER BY RANDOM() LIMIT 1) AS ps
CROSS JOIN LATERAL (SELECT id FROM users ORDER BY RANDOM() LIMIT 1) AS u
CROSS JOIN LATERAL (SELECT id FROM vehicles WHERE user_id = u.id ORDER BY RANDOM() LIMIT 1) AS v;

-- Insert sample complaints
INSERT INTO complaints (complaint_code, user_id, zone_id, slot_id, type, title, description, priority, status, ai_category, requires_maintenance)
SELECT 
    CONCAT('CMP-', LPAD(gs::TEXT, 6, '0')),
    u.id,
    z.id,
    ps.id,
    CASE gs % 4
        WHEN 0 THEN 'Sensor Fault'
        WHEN 1 THEN 'Wrong Parking'
        WHEN 2 THEN 'QR Unreadable'
        ELSE 'Billing Issue'
    END,
    CASE gs % 4
        WHEN 0 THEN 'Sensor not detecting vehicle'
        WHEN 1 THEN 'Vehicle parked in wrong slot'
        WHEN 2 THEN 'QR code damaged'
        ELSE 'Incorrect billing amount'
    END,
    'Detailed description of the issue...',
    CASE WHEN gs % 2 = 0 THEN 'High' ELSE 'Medium' END,
    CASE WHEN gs % 3 = 0 THEN 'open' WHEN gs % 3 = 1 THEN 'in_progress' ELSE 'resolved' END,
    CASE gs % 4 WHEN 0 THEN 'Technical Issue' ELSE 'Service Issue' END,
    CASE gs % 4 WHEN 0 THEN TRUE ELSE FALSE END
FROM generate_series(1, 15) AS gs
CROSS JOIN LATERAL (SELECT id FROM zones ORDER BY RANDOM() LIMIT 1) AS z
CROSS JOIN LATERAL (SELECT id FROM parking_slots WHERE zone_id = z.id ORDER BY RANDOM() LIMIT 1) AS ps
CROSS JOIN LATERAL (SELECT id FROM users ORDER BY RANDOM() LIMIT 1) AS u;